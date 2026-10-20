/* Painel da Máxima — comportamentos comuns */
(() => {
  'use strict';
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const CSRF = $('meta[name="csrf"]').content;
  const BASE = $('meta[name="base"]').content;

  // chamada à API do painel
  async function api(acao, dados = {}, arquivos = null) {
    let corpo, cab = { 'X-CSRF': CSRF, Accept: 'application/json' };
    if (arquivos || dados instanceof FormData) {
      corpo = dados instanceof FormData ? dados : new FormData();
      if (!(dados instanceof FormData)) Object.entries(dados).forEach(([k, v]) => corpo.append(k, v));
      if (arquivos) Object.entries(arquivos).forEach(([k, v]) => corpo.append(k, v));
      corpo.append('acao', acao);
    } else {
      corpo = JSON.stringify({ ...dados, acao });
      cab['Content-Type'] = 'application/json';
    }
    const r = await fetch(BASE + '/admin/api', { method: 'POST', headers: cab, body: corpo, credentials: 'same-origin' });
    let d = {};
    try { d = await r.json(); } catch (e) { d = { erro: 'Resposta inválida do servidor (' + r.status + ').' }; }
    if (!r.ok || d.erro) throw new Error(d.erro || 'Erro ' + r.status);
    return d;
  }

  // envio com progresso (fotos)
  function enviarComProgresso(acao, dados, aoProgresso) {
    return new Promise((ok, falha) => {
      const fd = new FormData();
      Object.entries(dados).forEach(([k, v]) => fd.append(k, v));
      fd.append('acao', acao);
      const x = new XMLHttpRequest();
      x.open('POST', BASE + '/admin/api');
      x.setRequestHeader('X-CSRF', CSRF);
      x.setRequestHeader('Accept', 'application/json');
      x.upload.onprogress = e => e.lengthComputable && aoProgresso(e.loaded / e.total);
      x.onload = () => { let d = {}; try { d = JSON.parse(x.responseText); } catch (e) { d = { erro: 'Falha no envio (' + x.status + ').' }; } (x.status < 300 && !d.erro) ? ok(d) : falha(new Error(d.erro || 'Erro ' + x.status)); };
      x.onerror = () => falha(new Error('Sem conexão.'));
      x.send(fd);
    });
  }

  let tToast;
  function toast(msg, tipo = 'ok') {
    const t = $('#toast');
    t.textContent = msg;
    t.className = 'toast visivel ' + tipo;
    clearTimeout(tToast);
    tToast = setTimeout(() => { t.className = 'toast'; }, tipo === 'erro' ? 5000 : 2400);
  }

  window.Painel = { api, toast, enviarComProgresso, $, $$ };

  // menu lateral no celular
  const btnLat = $('#btnLateral');
  if (btnLat) btnLat.addEventListener('click', () => document.body.classList.toggle('lateral-aberta'));
  document.addEventListener('click', e => { if (document.body.classList.contains('lateral-aberta') && !e.target.closest('#lateral, #btnLateral')) document.body.classList.remove('lateral-aberta'); });

  // avisos somem sozinhos
  $$('[data-some]').forEach(a => setTimeout(() => a.classList.add('sumindo'), 4000));

  // ordenar por arrastar
  $$('[data-ordenar]').forEach(c => {
    Arrastar.ordenar(c, {
      itens: '[data-id]',
      alca: c.dataset.ordenar === 'evento_fotos' ? '.alca, img' : '.alca',
      aoSoltar: async ids => {
        try { await api('ordenar', { tabela: c.dataset.ordenar, ids }); toast('Ordem salva'); }
        catch (e) { toast(e.message, 'erro'); }
      },
    });
  });

  // interruptores (ativo, publicado, destaque, lida)
  document.addEventListener('change', async e => {
    const c = e.target.closest('[data-alternar]');
    if (!c) return;
    try {
      await api('alternar', { tabela: c.dataset.alternar, campo: c.dataset.campo || '', id: c.dataset.id, valor: c.checked ? 1 : 0 });
      const card = c.closest('.banner-item, .patro-admin');
      if (card) card.classList.toggle('inativo', !c.checked);
      const msg = c.closest('.mensagem');
      if (msg) msg.classList.toggle('nao-lida', !c.checked);
      toast('Salvo');
    } catch (err) { c.checked = !c.checked; toast(err.message, 'erro'); }
  });

  // excluir
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-excluir]');
    if (!b) return;
    e.preventDefault();
    if (!confirm(b.dataset.confirmar || 'Excluir este item? Esta ação não pode ser desfeita.')) return;
    try {
      await api('excluir', { tabela: b.dataset.excluir, id: b.dataset.id });
      if (b.dataset.voltar) { location.href = b.dataset.voltar; return; }
      const item = b.closest('[data-id]:not(button), .item-linha, .mensagem');
      if (item) { item.classList.add('removendo'); setTimeout(() => item.remove(), 250); }
      atualizarTotalFotos(-1, b);
      toast('Excluído');
    } catch (err) { toast(err.message, 'erro'); }
  });

  // formulários com confirmação
  document.addEventListener('submit', e => {
    const f = e.target.closest('form[data-confirmar]');
    if (f && !confirm(f.dataset.confirmar)) e.preventDefault();
  });

  // campo de imagem única: arrastar e prévia
  function prepararSoltarImagem(area) {
    const input = $('input[type="file"]', area);
    const prev = $('.prev', area);
    const mostrar = arq => {
      if (!arq || !arq.type.startsWith('image/')) return;
      const url = URL.createObjectURL(arq);
      prev.innerHTML = '';
      const img = new Image(); img.src = url; img.alt = '';
      prev.appendChild(img);
      area.classList.add('tem-imagem');
    };
    input.addEventListener('change', () => mostrar(input.files[0]));
    ['dragenter', 'dragover'].forEach(t => area.addEventListener(t, e => { e.preventDefault(); area.classList.add('sobre'); }));
    ['dragleave', 'drop'].forEach(t => area.addEventListener(t, () => area.classList.remove('sobre')));
    area.addEventListener('drop', e => {
      e.preventDefault();
      const arq = e.dataTransfer.files[0];
      if (!arq) return;
      const dt = new DataTransfer(); dt.items.add(arq); input.files = dt.files;
      mostrar(arq);
    });
  }
  $$('[data-soltar-imagem]').forEach(prepararSoltarImagem);
  window.Painel.prepararSoltarImagem = prepararSoltarImagem;

  // várias fotos no formulário de novo evento
  $$('[data-soltar-varias]').forEach(area => {
    const input = $('input[type="file"]', area);
    const cont = $('.contagem', area);
    const mostrar = () => { cont.textContent = input.files.length ? input.files.length + ' foto(s) selecionada(s)' : ''; area.classList.toggle('tem-imagem', input.files.length > 0); };
    input.addEventListener('change', mostrar);
    ['dragenter', 'dragover'].forEach(t => area.addEventListener(t, e => { e.preventDefault(); area.classList.add('sobre'); }));
    ['dragleave', 'drop'].forEach(t => area.addEventListener(t, () => area.classList.remove('sobre')));
    area.addEventListener('drop', e => {
      e.preventDefault();
      const dt = new DataTransfer();
      Array.from(input.files).concat(Array.from(e.dataTransfer.files)).filter(f => f.type.startsWith('image/')).forEach(f => dt.items.add(f));
      input.files = dt.files; mostrar();
    });
  });

  // ---------- fotos de um evento existente ----------
  function atualizarTotalFotos(delta, origem) {
    const t = $('#totalFotos');
    if (!t || (origem && !origem.closest('#gridFotos'))) return;
    t.textContent = '(' + $$('#gridFotos .foto-admin').length + ')';
  }

  const up = $('[data-upload-fotos]');
  if (up) {
    const eventoId = up.dataset.evento;
    const input = $('#inputFotos');
    const fila = $('.fila-envio', up);
    const grid = $('#gridFotos');
    const tpl = $('#tplFoto');
    const zona = $('.soltar-varias', up);
    let emAndamento = 0;

    async function enviarArquivos(lista) {
      const arquivos = Array.from(lista).filter(f => f.type.startsWith('image/'));
      if (!arquivos.length) return;
      // 3 envios simultâneos
      const tarefas = arquivos.map(arq => () => enviar(arq));
      const rodar = async () => { while (tarefas.length) await tarefas.shift()(); };
      await Promise.all([rodar(), rodar(), rodar()]);
      toast(arquivos.length + ' foto(s) processada(s)');
    }

    async function enviar(arq) {
      emAndamento++;
      const linha = document.createElement('div');
      linha.className = 'envio';
      linha.innerHTML = '<span class="nome"></span><span class="barra"><i></i></span><span class="pct">0%</span>';
      $('.nome', linha).textContent = arq.name;
      fila.appendChild(linha);
      try {
        const d = await enviarComProgresso('foto_enviar', { evento_id: eventoId, foto: arq }, p => {
          $('.barra i', linha).style.width = Math.round(p * 100) + '%';
          $('.pct', linha).textContent = p >= 1 ? 'processando…' : Math.round(p * 100) + '%';
        });
        const el = tpl.content.firstElementChild.cloneNode(true);
        el.dataset.id = d.id;
        $('img', el).src = d.miniatura;
        $('[data-capa]', el).dataset.capa = d.id;
        $('[data-excluir]', el).dataset.id = d.id;
        $('[data-legenda]', el).dataset.legenda = d.id;
        el.classList.add('nova');
        grid.appendChild(el);
        atualizarTotalFotos(1);
        linha.classList.add('ok');
        $('.pct', linha).textContent = 'ok';
        setTimeout(() => linha.remove(), 1500);
      } catch (e) {
        linha.classList.add('erro');
        $('.pct', linha).textContent = e.message;
      } finally { emAndamento--; }
    }

    input.addEventListener('change', () => { enviarArquivos(input.files); input.value = ''; });
    ['dragenter', 'dragover'].forEach(t => zona.addEventListener(t, e => { e.preventDefault(); zona.classList.add('sobre'); }));
    ['dragleave', 'drop'].forEach(t => zona.addEventListener(t, () => zona.classList.remove('sobre')));
    zona.addEventListener('drop', e => { e.preventDefault(); enviarArquivos(e.dataTransfer.files); });
    // também aceita soltar fotos em qualquer lugar da grade
    grid.addEventListener('dragover', e => { if (e.dataTransfer.types.includes('Files')) { e.preventDefault(); grid.classList.add('sobre'); } });
    grid.addEventListener('dragleave', () => grid.classList.remove('sobre'));
    grid.addEventListener('drop', e => { if (e.dataTransfer.files.length) { e.preventDefault(); grid.classList.remove('sobre'); enviarArquivos(e.dataTransfer.files); } });
    window.addEventListener('beforeunload', e => { if (emAndamento) { e.preventDefault(); e.returnValue = ''; } });

    grid.addEventListener('click', async e => {
      const b = e.target.closest('[data-capa]');
      if (!b) return;
      try {
        await api('foto_capa', { id: b.dataset.capa });
        $$('.foto-admin', grid).forEach(f => f.classList.toggle('capa', f.dataset.id === b.dataset.capa));
        toast('Capa definida');
      } catch (err) { toast(err.message, 'erro'); }
    });
    grid.addEventListener('change', async e => {
      const i = e.target.closest('[data-legenda]');
      if (!i) return;
      try { await api('foto_legenda', { id: i.dataset.legenda, legenda: i.value }); toast('Legenda salva'); }
      catch (err) { toast(err.message, 'erro'); }
    });
    grid.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.matches('[data-legenda]')) { e.preventDefault(); e.target.blur(); } });
  }
})();
