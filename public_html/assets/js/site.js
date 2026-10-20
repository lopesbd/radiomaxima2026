/* Rádio Máxima FM — site público
 * - Player ao vivo que continua tocando ao trocar de página (a navegação troca só o <main>).
 * - Tenta tocar sozinho ao abrir o site; se o navegador bloquear, toca no primeiro toque/clique.
 * - "No ar agora" calculado pelo relógio do servidor e atualizado sozinho.
 */
(() => {
  'use strict';

  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const corpo = document.body;
  const guardar = {
    ler(k, area = localStorage) { try { return area.getItem(k); } catch (e) { return null; } },
    gravar(k, v, area = localStorage) { try { v === null ? area.removeItem(k) : area.setItem(k, v); } catch (e) { /* sem storage */ } },
  };

  /* ======================= RELÓGIO DO SERVIDOR ======================= */
  // segundos desde domingo 00:00 no fuso da rádio
  const relogio = {
    base: null, t0: 0,
    definir(dia, minuto, segundo) { this.base = dia * 86400 + minuto * 60 + (segundo || 0); this.t0 = performance.now(); },
    agora() {
      if (this.base === null) { const d = new Date(); return d.getDay() * 86400 + d.getHours() * 3600 + d.getMinutes() * 60 + d.getSeconds(); }
      return (this.base + Math.floor((performance.now() - this.t0) / 1000)) % 604800;
    },
    dia() { return Math.floor(this.agora() / 86400); },
    minutoDoDia() { return (this.agora() % 86400) / 60; },
  };

  /* ======================= PLAYER ======================= */
  const player = $('#player');
  const audio = $('#radio');
  const STREAM = player.dataset.stream;
  const aviso = $('#playerAviso');
  const vol = $('#playerVolume');
  let querTocando = false;
  let tentativas = 0;
  let religar = null;

  const estado = (cls, on) => corpo.classList.toggle(cls, on);

  function tocar() {
    querTocando = true;
    guardar.gravar('maxima_pausado', null, sessionStorage);
    esconderAviso();
    estado('carregando-audio', true);
    // sempre reconecta no ponto ao vivo
    audio.src = STREAM + (STREAM.includes('?') ? '&' : '?') + '_=' + Date.now();
    const p = audio.play();
    if (p && p.catch) {
      p.catch(err => {
        estado('carregando-audio', false);
        if (err && err.name === 'NotAllowedError') { querTocando = false; mostrarAviso(); esperarInteracao(); }
      });
    }
  }

  function pausar(manual = true) {
    querTocando = false;
    clearTimeout(religar);
    if (manual) guardar.gravar('maxima_pausado', '1', sessionStorage);
    audio.pause();
    audio.removeAttribute('src');
    audio.load(); // encerra o download do stream
    estado('tocando', false);
    estado('carregando-audio', false);
    atualizarBotoes();
  }

  function alternar() { (corpo.classList.contains('tocando') || querTocando) ? pausar() : tocar(); }

  audio.addEventListener('playing', () => { tentativas = 0; estado('tocando', true); estado('carregando-audio', false); atualizarBotoes(); });
  audio.addEventListener('waiting', () => { if (querTocando) estado('carregando-audio', true); });
  audio.addEventListener('pause', () => { if (!querTocando) { estado('tocando', false); atualizarBotoes(); } });
  const reconectar = () => {
    if (!querTocando) return;
    estado('carregando-audio', true);
    clearTimeout(religar);
    const espera = Math.min(15000, 1000 * Math.pow(2, tentativas++));
    religar = setTimeout(() => { if (querTocando) tocar(); }, espera);
  };
  audio.addEventListener('error', reconectar);
  audio.addEventListener('ended', reconectar);
  audio.addEventListener('stalled', () => { if (querTocando && audio.readyState < 3) reconectar(); });

  function atualizarBotoes() {
    const on = corpo.classList.contains('tocando');
    $('#playerPlay').setAttribute('aria-label', on ? 'Pausar a rádio' : 'Tocar a rádio ao vivo');
    $$('[data-play]').forEach(b => b.setAttribute('aria-pressed', on ? 'true' : 'false'));
  }

  function mostrarAviso() { if (aviso) aviso.hidden = false; }
  function esconderAviso() { if (aviso) aviso.hidden = true; }

  // navegadores bloqueiam som automático sem interação: toca no primeiro gesto do visitante
  let esperando = false;
  function esperarInteracao() {
    if (esperando) return;
    esperando = true;
    const ini = (ev) => {
      if (ev.target.closest && ev.target.closest('#playerPlay, [data-play], #avisoFechar, #playerMudo, #playerVolume')) return;
      remover();
      if (!querTocando && !guardar.ler('maxima_pausado', sessionStorage)) tocar();
    };
    const remover = () => { esperando = false; ['pointerdown', 'keydown', 'touchend'].forEach(t => document.removeEventListener(t, ini, true)); };
    ['pointerdown', 'keydown', 'touchend'].forEach(t => document.addEventListener(t, ini, true));
  }

  $('#playerPlay').addEventListener('click', alternar);
  document.addEventListener('click', ev => {
    const b = ev.target.closest('[data-play]');
    if (!b) return;
    ev.preventDefault();
    // botões "Ouvir" das páginas só ligam; o do topo alterna
    if (b.classList.contains('btn-ao-vivo')) alternar(); else if (!corpo.classList.contains('tocando')) tocar();
  });
  $('#avisoFechar').addEventListener('click', () => { esconderAviso(); guardar.gravar('maxima_pausado', '1', sessionStorage); });

  // volume
  const volSalvo = parseFloat(guardar.ler('maxima_volume'));
  if (!isNaN(volSalvo)) { audio.volume = volSalvo; vol.value = volSalvo; }
  vol.addEventListener('input', () => { audio.volume = +vol.value; audio.muted = false; estado('mudo', audio.volume === 0); guardar.gravar('maxima_volume', vol.value); });
  $('#playerMudo').addEventListener('click', () => { audio.muted = !audio.muted; estado('mudo', audio.muted); });

  // teclado: espaço fora de campos alterna o player
  document.addEventListener('keydown', ev => {
    if (ev.code === 'Space' && !ev.target.closest('input, textarea, select, button, a, [contenteditable]')) { ev.preventDefault(); alternar(); }
  });

  /* ======================= NO AR AGORA ======================= */
  let infoAtual = null;
  let proximaBusca = null;

  function hhmm(min) { min = Math.round(min); return String(Math.floor(min / 60) % 24).padStart(2, '0') + ':' + String(min % 60).padStart(2, '0'); }

  async function buscarAgora() {
    clearTimeout(proximaBusca);
    try {
      const r = await fetch(player.dataset.agora, { headers: { Accept: 'application/json' }, cache: 'no-store' });
      const d = await r.json();
      relogio.definir(d.dia, d.minuto, d.segundo);
      infoAtual = d;
      aplicarAgora();
    } catch (e) { /* sem rede: tenta depois */ }
    // reconsulta na virada do programa (ou a cada 5 min)
    let espera = 300;
    if (infoAtual && infoAtual.atual) espera = Math.min(espera, Math.max(5, (infoAtual.atual.fim - relogio.minutoDoDia()) * 60 + 3));
    proximaBusca = setTimeout(buscarAgora, espera * 1000);
  }

  function aplicarAgora() {
    const d = infoAtual;
    if (!d) return;
    const a = d.atual;
    const nome = a ? a.nome : d.radio;
    const sub = a ? [a.apresentador, a.horario].filter(Boolean).join(' · ') : d.slogan;
    $('#playerPrograma').textContent = nome;
    $('#playerApresentador').textContent = sub;
    $('#playerFoto').src = (a && a.foto) || player.dataset.logo;
    $('#playerCapa').style.setProperty('--cor', (a && a.cor) || '#e3161b');
    if ('mediaSession' in navigator) {
      navigator.mediaSession.metadata = new MediaMetadata({
        title: nome, artist: (a && a.apresentador) || d.radio, album: d.radio + ' — ao vivo',
        artwork: [{ src: new URL((a && a.foto) || player.dataset.logo, location.href).href, sizes: '512x512' }],
      });
    }
    // card da home
    const card = $('[data-no-ar]');
    if (card && a) {
      const n = $('[data-campo="nome"]', card);
      if (n && n.textContent !== a.nome) { pagina.home(); return; } // mudou o programa: atualiza a seção inteira
    }
  }

  if ('mediaSession' in navigator) {
    navigator.mediaSession.setActionHandler('play', tocar);
    navigator.mediaSession.setActionHandler('pause', () => pausar());
    navigator.mediaSession.setActionHandler('stop', () => pausar());
  }

  // barras de progresso e relógio (1x por segundo)
  setInterval(tique, 1000);
  function tique() {
    const min = relogio.minutoDoDia();
    $$('.progresso[data-inicio]').forEach(p => {
      const i = +p.dataset.inicio, f = +p.dataset.fim;
      p.firstElementChild.style.width = Math.max(0, Math.min(100, (min - i) / (f - i) * 100)) + '%';
    });
    const rel = $('[data-relogio]');
    if (rel) { const s = relogio.agora() % 86400; rel.textContent = hhmm(s / 60 - (s % 60) / 60) + ':' + String(s % 60).padStart(2, '0'); }
    marcarProgramacao();
  }

  function marcarProgramacao() {
    const raiz = $('[data-programacao]');
    if (!raiz) return;
    const hoje = relogio.dia();
    const min = relogio.minutoDoDia();
    $$('.dia-grade', raiz).forEach(painel => {
      const dia = +painel.id.replace('dia-', '');
      $$('.prog', painel).forEach(li => {
        const i = +li.dataset.inicio, f = +li.dataset.fim;
        const agora = dia === hoje && i <= min && min < f;
        li.classList.toggle('agora', agora);
        li.classList.toggle('passado', dia === hoje && f <= min);
        const barra = $('.progresso span', li);
        if (agora && barra) barra.style.width = ((min - i) / (f - i) * 100) + '%';
      });
    });
  }

  /* ======================= NAVEGAÇÃO SEM RECARREGAR ======================= */
  const barra = document.createElement('div');
  barra.className = 'barra-carga';
  corpo.appendChild(barra);
  let navAtual = null;

  function linkInterno(a) {
    if (!a || a.target && a.target !== '_self' || a.hasAttribute('download') || a.dataset.recarregar !== undefined) return false;
    const u = new URL(a.href, location.href);
    if (u.origin !== location.origin) return false;
    if (/\/(admin|api|uploads|assets)(\/|$)/.test(u.pathname)) return false;
    if (/\.(jpe?g|png|webp|gif|pdf|mp3|zip)$/i.test(u.pathname)) return false;
    if (u.pathname === location.pathname && u.search === location.search && u.hash) return false;
    return true;
  }

  async function navegar(url, empurrar = true) {
    if (navAtual) navAtual.abort();
    const ctl = new AbortController();
    navAtual = ctl;
    const main = $('#app');
    main.classList.add('saindo');
    barra.style.opacity = 1; barra.style.width = '30%';
    try {
      const r = await fetch(url, { headers: { 'X-Pjax': '1' }, signal: ctl.signal, credentials: 'same-origin' });
      barra.style.width = '75%';
      const html = await r.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const novo = doc.querySelector('#app');
      if (!novo) throw new Error('sem conteúdo');
      if (empurrar) history.pushState({ pjax: true }, '', r.url || url);
      document.title = doc.title;
      const desc = doc.querySelector('meta[name="description"]');
      if (desc) $('meta[name="description"]').setAttribute('content', desc.content);
      main.replaceWith(novo);
      novo.classList.add('entrando');
      fecharMenu();
      const u = new URL(r.url || url, location.href);
      if (u.hash && document.getElementById(u.hash.slice(1))) document.getElementById(u.hash.slice(1)).scrollIntoView();
      else if (empurrar) window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
      novo.focus({ preventScroll: true });
      iniciarPagina();
    } catch (e) {
      if (e.name === 'AbortError') return;
      location.href = url; // falhou: carrega do jeito normal
    } finally {
      if (navAtual === ctl) navAtual = null;
      barra.style.width = '100%';
      setTimeout(() => { barra.style.opacity = 0; barra.style.width = '0'; }, 250);
    }
  }

  document.addEventListener('click', ev => {
    if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
    const a = ev.target.closest('a[href]');
    if (!linkInterno(a)) return;
    ev.preventDefault();
    navegar(a.href);
  });

  document.addEventListener('submit', ev => {
    const f = ev.target;
    if (!f.hasAttribute('action') || f.method.toLowerCase() !== 'get' || !linkInterno({ href: f.action, hasAttribute: () => false, dataset: {} })) return;
    ev.preventDefault();
    const u = new URL(f.action, location.href);
    u.search = new URLSearchParams(new FormData(f)).toString();
    navegar(u.href);
  });

  history.replaceState({ pjax: true }, '', location.href);
  window.addEventListener('popstate', ev => { if (ev.state && ev.state.pjax) navegar(location.href, false); });

  /* ======================= MENU ======================= */
  const btnMenu = $('#btnMenu');
  function fecharMenu() { corpo.classList.remove('menu-aberto'); btnMenu.setAttribute('aria-expanded', 'false'); }
  btnMenu.addEventListener('click', () => {
    const aberto = corpo.classList.toggle('menu-aberto');
    btnMenu.setAttribute('aria-expanded', aberto ? 'true' : 'false');
  });
  document.addEventListener('keydown', ev => { if (ev.key === 'Escape') fecharMenu(); });

  // recolhe o menu quando os itens não cabem (mede de verdade, não só por breakpoint)
  function medirMenu() {
    corpo.classList.remove('menu-compacto');
    const topo = $('.topo-in');
    const cabe = topo.scrollWidth <= topo.clientWidth + 1 && window.innerWidth > 640;
    corpo.classList.toggle('menu-compacto', !cabe);
    if (cabe) fecharMenu();
  }
  window.addEventListener('resize', medirMenu);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(medirMenu);
  medirMenu();
  requestAnimationFrame(() => requestAnimationFrame(() => corpo.classList.add('menu-pronto')));

  function marcarMenu() {
    const ativo = $('#app').dataset.menu;
    $$('#menu > a').forEach(a => {
      const sim = a.dataset.menu === ativo;
      a.classList.toggle('ativo', sim);
      sim ? a.setAttribute('aria-current', 'page') : a.removeAttribute('aria-current');
    });
  }

  /* ======================= PÁGINAS ======================= */
  const pagina = {
    home() {
      // re-renderiza a seção "no ar" quando o programa muda
      const card = $('[data-no-ar]');
      if (!card || !infoAtual) return;
      fetch(location.href, { headers: { 'X-Pjax': '1' } }).then(r => r.text()).then(html => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const novo = doc.querySelector('.no-ar-home');
        const atual = $('.no-ar-home');
        if (novo && atual) atual.replaceWith(novo);
      }).catch(() => {});
    },
  };

  function slider(raiz) {
    const slides = $$('.hero-slide', raiz);
    const pontos = $$('.hero-pontos button', raiz);
    if (slides.length < 2) return;
    let i = 0, timer;
    const ir = n => {
      slides[i].classList.remove('ativo'); pontos[i] && pontos[i].classList.remove('ativo');
      i = (n + slides.length) % slides.length;
      slides[i].classList.add('ativo'); pontos[i] && pontos[i].classList.add('ativo');
      reiniciar();
    };
    const reiniciar = () => { clearTimeout(timer); timer = setTimeout(() => { if (document.body.contains(raiz)) ir(i + 1); }, 6500); };
    $('.hero-nav.ant', raiz).addEventListener('click', () => ir(i - 1));
    $('.hero-nav.prox', raiz).addEventListener('click', () => ir(i + 1));
    pontos.forEach((p, n) => p.addEventListener('click', () => ir(n)));
    let x0 = null;
    raiz.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
    raiz.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) ir(i + (dx < 0 ? 1 : -1)); x0 = null; });
    raiz.addEventListener('mouseenter', () => clearTimeout(timer));
    raiz.addEventListener('mouseleave', reiniciar);
    reiniciar();
  }

  function abasProgramacao(raiz) {
    const abas = $$('[role="tab"]', raiz);
    const mostrar = aba => {
      abas.forEach(a => { const s = a === aba; a.classList.toggle('ativo', s); a.setAttribute('aria-selected', s); $('#' + a.getAttribute('aria-controls')).hidden = !s; });
      aba.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
    };
    abas.forEach((a, n) => {
      a.addEventListener('click', () => mostrar(a));
      a.addEventListener('keydown', e => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') { const b = abas[(n + (e.key === 'ArrowRight' ? 1 : -1) + abas.length) % abas.length]; b.focus(); mostrar(b); }
      });
    });
    relogio.definir(+raiz.dataset.dia, +raiz.dataset.minuto, +raiz.dataset.segundo);
    marcarProgramacao();
    const atual = $('.dia-grade:not([hidden]) .prog.agora', raiz);
    if (atual) setTimeout(() => atual.scrollIntoView({ block: 'center', behavior: 'smooth' }), 350);
  }

  // lightbox da galeria
  const lb = $('#lightbox');
  let lbItens = [], lbI = 0;
  function abrirLb(itens, n) { lbItens = itens; lbI = n; mostrarLb(); lb.hidden = false; corpo.style.overflow = 'hidden'; $('.lb-fechar', lb).focus(); }
  function mostrarLb() { const it = lbItens[lbI]; $('img', lb).src = it.href; $('img', lb).alt = it.dataset.legenda || ''; $('figcaption', lb).textContent = it.dataset.legenda || ''; }
  function fecharLb() { lb.hidden = true; corpo.style.overflow = ''; }
  $('.lb-fechar', lb).addEventListener('click', fecharLb);
  $('.lb-ant', lb).addEventListener('click', () => { lbI = (lbI - 1 + lbItens.length) % lbItens.length; mostrarLb(); });
  $('.lb-prox', lb).addEventListener('click', () => { lbI = (lbI + 1) % lbItens.length; mostrarLb(); });
  lb.addEventListener('click', e => { if (e.target === lb) fecharLb(); });
  document.addEventListener('keydown', e => {
    if (lb.hidden) return;
    if (e.key === 'Escape') fecharLb();
    if (e.key === 'ArrowLeft') $('.lb-ant', lb).click();
    if (e.key === 'ArrowRight') $('.lb-prox', lb).click();
  });
  let lbX = null;
  lb.addEventListener('touchstart', e => { lbX = e.touches[0].clientX; }, { passive: true });
  lb.addEventListener('touchend', e => { if (lbX === null) return; const dx = e.changedTouches[0].clientX - lbX; if (Math.abs(dx) > 50) $(dx < 0 ? '.lb-prox' : '.lb-ant', lb).click(); lbX = null; });

  function galeria(raiz) {
    const itens = $$('.galeria-item', raiz);
    itens.forEach((a, n) => a.addEventListener('click', e => { e.preventDefault(); abrirLb(itens, n); }));
  }

  function formContato(f) {
    f.addEventListener('submit', async e => {
      e.preventDefault();
      const ret = $('.form-retorno', f);
      ret.className = 'form-retorno';
      if (!f.nome.value.trim() || !f.mensagem.value.trim()) { ret.textContent = 'Preencha seu nome e a mensagem.'; ret.classList.add('erro'); return; }
      const btn = $('button[type="submit"]', f);
      btn.disabled = true;
      try {
        const r = await fetch(f.action, { method: 'POST', body: new FormData(f), credentials: 'same-origin' });
        const d = await r.json();
        if (!r.ok) throw new Error(d.erro || 'Não foi possível enviar.');
        ret.textContent = d.mensagem || 'Mensagem enviada!';
        ret.classList.add('ok');
        f.mensagem.value = '';
      } catch (err) {
        ret.textContent = err.message; ret.classList.add('erro');
      } finally { btn.disabled = false; }
    });
  }

  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-copiar]');
    if (!b) return;
    try { await navigator.clipboard.writeText(b.dataset.copiar); b.classList.add('copiado'); b.setAttribute('aria-label', 'Link copiado!'); } catch (err) { prompt('Copie o link:', b.dataset.copiar); }
  });

  function iniciarPagina() {
    const main = $('#app');
    marcarMenu();
    const s = $('[data-slider]', main); if (s) slider(s);
    const p = $('[data-programacao]', main); if (p) abasProgramacao(p);
    const g = $('[data-galeria]', main); if (g) galeria(g);
    const f = $('[data-contato]', main); if (f) formContato(f);
    tique();
  }

  /* ======================= INÍCIO ======================= */
  iniciarPagina();
  buscarAgora();
  atualizarBotoes();
  // autoplay ao entrar no site (a menos que o visitante tenha pausado nesta visita)
  if (!guardar.ler('maxima_pausado', sessionStorage)) tocar();
  else esperarInteracao();
  document.addEventListener('visibilitychange', () => { if (!document.hidden) buscarAgora(); });
})();
