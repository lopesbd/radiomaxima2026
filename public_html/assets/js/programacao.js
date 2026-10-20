/* Editor da grade de programação (estilo agenda semanal).
 * - Arraste um programa da biblioteca para um dia/horário: cria o horário (1h, ou até o próximo programa).
 * - Arraste um bloco para mudar de dia/horário (Ctrl/Alt = copiar). Puxe as bordas para mudar a duração.
 * - Clique num bloco para editar horários; clique num programa da biblioteca para editar nome, foto, cor.
 */
(() => {
  'use strict';
  const { api, toast, $, $$ } = window.Painel;
  const raiz = $('#progEditor');
  if (!raiz) return;

  const programas = new Map(JSON.parse(raiz.dataset.programas).map(p => [p.id, p]));
  let grade = JSON.parse(raiz.dataset.grade);
  const corpo = $('#agendaCorpo');
  const agenda = $('#agenda');
  const cols = $$('.agenda-col', corpo);
  const colDoDia = d => cols.find(c => +c.dataset.dia === d);
  const DIAS = { 0: 'Domingo', 1: 'Segunda', 2: 'Terça', 3: 'Quarta', 4: 'Quinta', 5: 'Sexta', 6: 'Sábado' };
  const CORES = ['#e3161b', '#f97316', '#f59e0b', '#eab308', '#16a34a', '#0ea5e9', '#2563eb', '#6d28d9', '#8b5cf6', '#db2777', '#64748b', '#111111'];

  const ppm = () => parseFloat(getComputedStyle(corpo).getPropertyValue('--ppm')) || 0.8; // pixels por minuto
  const passo = () => +$('#encaixe').value;
  const encaixar = m => Math.round(m / passo()) * passo();
  const limitar = (v, a, b) => Math.max(a, Math.min(b, v));
  const hh = m => String(Math.floor(m / 60) % 24).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
  const fimTxt = m => (m === 1440 ? '24:00' : hh(m));
  const paraMin = (v, ehFim) => { const [h, m] = v.split(':').map(Number); const t = h * 60 + m; return ehFim && t === 0 ? 1440 : t; };
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function conflita(dia, ini, fim, ignorar) {
    return grade.find(g => g.dia === dia && g.id !== ignorar && g.inicio < fim && g.fim > ini);
  }

  /* ---------------- desenho ---------------- */
  function desenhar() {
    cols.forEach(c => $$('.bloco', c).forEach(b => b.remove()));
    for (const g of grade) {
      const p = programas.get(g.programa_id);
      if (!p) continue;
      colDoDia(g.dia).appendChild(criarBloco(g, p));
    }
    desenharBiblioteca();
    linhaAgora();
  }

  function criarBloco(g, p) {
    const b = document.createElement('div');
    b.className = 'bloco';
    b.dataset.id = g.id;
    b.style.setProperty('--cor', p.cor);
    posicionar(b, g.inicio, g.fim);
    b.innerHTML = '<span class="puxador topo" data-puxar="topo"></span>' +
      '<div class="bloco-txt"><b>' + esc(p.nome) + '</b><small>' + hh(g.inicio) + ' – ' + fimTxt(g.fim) + '</small>' +
      (p.apresentador ? '<em>' + esc(p.apresentador) + '</em>' : '') + '</div>' +
      '<span class="puxador base" data-puxar="base"></span>';
    b.title = p.nome + ' · ' + hh(g.inicio) + ' – ' + fimTxt(g.fim) + '\nArraste para mover · Ctrl+arrastar copia · clique para editar';
    return b;
  }

  function posicionar(b, ini, fim) {
    b.style.top = ini * ppm() + 'px';
    b.style.height = Math.max(14, (fim - ini) * ppm()) + 'px';
    b.classList.toggle('curto', (fim - ini) < 45);
    b.classList.toggle('mini', (fim - ini) < 25);
    const s = $('small', b);
    if (s) s.textContent = hh(ini) + ' – ' + fimTxt(fim);
  }

  function desenharBiblioteca() {
    const lista = $('#bibLista');
    const ordem = [...programas.values()].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));
    lista.innerHTML = ordem.map(p => {
      const usos = grade.filter(g => g.programa_id === p.id).length;
      return '<div class="bib-item" data-programa="' + p.id + '" style="--cor:' + p.cor + '" tabindex="0" role="button" aria-label="' + esc(p.nome) + ' — arraste para a grade ou clique para editar">' +
        '<span class="bib-foto">' + (p.foto ? '<img src="' + esc(p.foto) + '" alt="" draggable="false">' : esc(p.nome.charAt(0))) + '</span>' +
        '<span class="bib-txt"><b>' + esc(p.nome) + '</b><small>' + esc(p.apresentador || 'sem apresentador') + '</small></span>' +
        '<span class="bib-usos" title="Horários na grade">' + usos + '×</span></div>';
    }).join('') || '<p class="vazio">Nenhum programa. Clique em “Novo”.</p>';
  }

  function linhaAgora() {
    $$('.linha-agora', corpo).forEach(l => l.remove());
    const d = new Date();
    const col = colDoDia(d.getDay());
    if (!col) return;
    const l = document.createElement('div');
    l.className = 'linha-agora';
    l.style.top = (d.getHours() * 60 + d.getMinutes()) * ppm() + 'px';
    col.appendChild(l);
  }
  setInterval(linhaAgora, 60000);

  /* ---------------- arrastar ---------------- */
  let op = null; // operação em andamento

  function colunaEm(x, y) {
    for (const c of cols) { const r = c.getBoundingClientRect(); if (x >= r.left && x < r.right) return c; }
    return null;
  }
  function minutoEm(col, y) { return (y - col.getBoundingClientRect().top) / ppm(); }

  corpo.addEventListener('pointerdown', ev => {
    if (ev.button !== 0) return;
    const bloco = ev.target.closest('.bloco');
    if (!bloco) return;
    const g = grade.find(x => x.id === +bloco.dataset.id);
    const col = bloco.parentElement;
    op = {
      tipo: ev.target.dataset.puxar || 'mover', bloco, g, x0: ev.clientX, y0: ev.clientY, iniciou: false,
      pega: minutoEm(col, ev.clientY) - g.inicio, dia: g.dia, ini: g.inicio, fim: g.fim,
    };
    bloco.setPointerCapture(ev.pointerId);
    ev.preventDefault();
  });

  $('#bibLista').addEventListener('pointerdown', ev => {
    if (ev.button !== 0) return;
    const it = ev.target.closest('.bib-item');
    if (!it) return;
    op = { tipo: 'novo', item: it, programa: programas.get(+it.dataset.programa), x0: ev.clientX, y0: ev.clientY, iniciou: false };
    it.setPointerCapture(ev.pointerId);
    ev.preventDefault();
  });

  document.addEventListener('pointermove', ev => {
    if (!op) return;
    if (!op.iniciou) {
      if (Math.hypot(ev.clientX - op.x0, ev.clientY - op.y0) < 5) return;
      op.iniciou = true;
      document.body.classList.add('arrastando');
      if (op.tipo === 'novo') {
        op.fantasma = op.item.cloneNode(true);
        op.fantasma.classList.add('arrastar-fantasma');
        document.body.appendChild(op.fantasma);
        op.previa = criarBloco({ id: 0, inicio: 0, fim: 60 }, op.programa);
        op.previa.classList.add('previa');
      } else {
        op.bloco.classList.add('movendo');
        op.copiar = ev.ctrlKey || ev.altKey || ev.metaKey;
        if (op.copiar) { op.original = op.bloco; op.bloco = op.bloco.cloneNode(true); op.bloco.classList.add('copia'); op.original.parentElement.appendChild(op.bloco); }
      }
      op.rolar = setInterval(rolarBorda, 16);
    }
    op.px = ev.clientX; op.py = ev.clientY;
    atualizarArraste();
  });

  function atualizarArraste() {
    const { px: x, py: y } = op;
    if (op.tipo === 'novo') {
      Object.assign(op.fantasma.style, { left: (x + 12) + 'px', top: (y + 12) + 'px' });
      const col = colunaEm(x, y);
      const r = agenda.getBoundingClientRect();
      const dentro = col && y > r.top && y < r.bottom;
      if (!dentro) { op.previa.remove(); op.alvo = null; return; }
      const ini = limitar(encaixar(minutoEm(col, y) - 15), 0, 1440 - passo());
      let fim = Math.min(1440, ini + 60);
      const dia = +col.dataset.dia;
      const prox = grade.filter(g => g.dia === dia && g.inicio >= ini).sort((a, b) => a.inicio - b.inicio)[0];
      if (prox && prox.inicio < fim) fim = prox.inicio;
      if (op.previa.parentElement !== col) col.appendChild(op.previa);
      posicionar(op.previa, ini, Math.max(fim, ini + 5));
      op.previa.classList.toggle('invalido', fim - ini < 5 || !!conflita(dia, ini, fim, -1));
      op.alvo = { dia, ini, fim };
      return;
    }
    const g = op.g, dur = g.fim - g.inicio;
    if (op.tipo === 'mover') {
      const col = colunaEm(x, y) || op.bloco.parentElement;
      op.dia = +col.dataset.dia;
      op.ini = limitar(encaixar(minutoEm(col, y) - op.pega), 0, 1440 - dur);
      op.fim = op.ini + dur;
      if (op.bloco.parentElement !== col) col.appendChild(op.bloco);
    } else if (op.tipo === 'base') {
      op.fim = limitar(encaixar(minutoEm(op.bloco.parentElement, y)), g.inicio + passo(), 1440);
    } else if (op.tipo === 'topo') {
      op.ini = limitar(encaixar(minutoEm(op.bloco.parentElement, y)), 0, g.fim - passo());
    }
    posicionar(op.bloco, op.ini, op.fim);
    op.bloco.classList.toggle('invalido', !!conflita(op.dia, op.ini, op.fim, op.copiar ? -1 : g.id));
  }

  function rolarBorda() {
    if (!op || op.py == null) return;
    const r = agenda.getBoundingClientRect(), m = 50;
    let d = 0;
    if (op.py < r.top + m) d = -12; else if (op.py > r.bottom - m) d = 12;
    if (d) { agenda.scrollTop += d; atualizarArraste(); }
  }

  document.addEventListener('pointerup', async ev => {
    if (!op) return;
    const o = op; op = null;
    clearInterval(o.rolar);
    document.body.classList.remove('arrastando');
    if (!o.iniciou) {
      // clique simples
      if (o.tipo === 'novo') abrirPrograma(o.programa); else abrirHorario(o.g);
      return;
    }
    if (o.tipo === 'novo') {
      o.fantasma.remove(); o.previa.remove();
      if (!o.alvo) return;
      if (o.alvo.fim - o.alvo.ini < 5) { toast('Sem espaço livre nesse horário.', 'erro'); return; }
      await salvarHorario({ programa_id: o.programa.id, dia: o.alvo.dia, inicio: o.alvo.ini, fim: o.alvo.fim, encaixar: 1 });
      return;
    }
    if (o.copiar) o.bloco.remove();
    o.bloco.classList.remove('movendo');
    const g = o.g;
    if (!o.copiar && o.dia === g.dia && o.ini === g.inicio && o.fim === g.fim) { desenhar(); return; }
    await salvarHorario({ id: o.copiar ? 0 : g.id, programa_id: g.programa_id, dia: o.dia, inicio: o.ini, fim: o.fim });
  });

  async function salvarHorario(dados) {
    try {
      const d = await api('grade_salvar', dados);
      const i = grade.findIndex(g => g.id === d.item.id);
      if (i >= 0) grade[i] = d.item; else grade.push(d.item);
      toast((dados.id ? 'Horário atualizado: ' : 'Adicionado: ') + programas.get(d.item.programa_id).nome + ' · ' + DIAS[d.item.dia] + ' ' + hh(d.item.inicio) + '–' + fimTxt(d.item.fim));
      desenhar();
      const novo = $('.bloco[data-id="' + d.item.id + '"]', corpo);
      if (novo) novo.classList.add('piscar');
      return true;
    } catch (e) {
      toast(e.message, 'erro');
      desenhar();
      return false;
    }
  }

  // teclado na biblioteca: Enter edita
  $('#bibLista').addEventListener('keydown', e => {
    const it = e.target.closest('.bib-item');
    if (it && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); abrirPrograma(programas.get(+it.dataset.programa)); }
  });

  /* ---------------- modais ---------------- */
  $$('dialog [data-fechar]').forEach(b => b.addEventListener('click', () => b.closest('dialog').close()));

  // programa
  const mProg = $('#modalPrograma'), fProg = $('#formPrograma');
  $('#coresRapidas').innerHTML = CORES.map(c => '<button type="button" style="background:' + c + '" data-cor="' + c + '" aria-label="Cor ' + c + '"></button>').join('');
  $('#coresRapidas').addEventListener('click', e => { const b = e.target.closest('[data-cor]'); if (b) fProg.cor.value = b.dataset.cor; });
  const areaFoto = $('[data-soltar-imagem]', fProg);

  function abrirPrograma(p) {
    fProg.reset();
    fProg.elements.namedItem('id').value = p ? p.id : '';
    fProg.nome.value = p ? p.nome : '';
    fProg.apresentador.value = p ? p.apresentador : '';
    fProg.descricao.value = p ? p.descricao : '';
    fProg.cor.value = p ? p.cor : CORES[Math.floor(Math.random() * CORES.length)];
    $('.prev', areaFoto).innerHTML = p && p.foto ? '<img src="' + esc(p.foto) + '" alt="">' : '';
    areaFoto.classList.toggle('tem-imagem', !!(p && p.foto));
    $('#tituloModalPrograma').textContent = p ? 'Editar programa' : 'Novo programa';
    $('#excluirPrograma').hidden = !p;
    mProg.showModal();
    fProg.nome.focus();
  }
  $('#btnNovoPrograma').addEventListener('click', () => abrirPrograma(null));

  fProg.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(fProg);
    if (!fd.get('foto') || !fd.get('foto').size) fd.delete('foto');
    try {
      const d = await api('programa_salvar', fd);
      programas.set(d.programa.id, d.programa);
      mProg.close();
      desenhar();
      toast('Programa salvo');
    } catch (err) { toast(err.message, 'erro'); }
  });

  $('#excluirPrograma').addEventListener('click', async () => {
    const id = +fProg.elements.namedItem('id').value;
    const usos = grade.filter(g => g.programa_id === id).length;
    if (!confirm('Excluir o programa "' + programas.get(id).nome + '"?' + (usos ? '\nEle será removido de ' + usos + ' horário(s) da grade.' : ''))) return;
    try {
      await api('excluir', { tabela: 'programas', id });
      programas.delete(id);
      grade = grade.filter(g => g.programa_id !== id);
      mProg.close(); desenhar(); toast('Programa excluído');
    } catch (err) { toast(err.message, 'erro'); }
  });

  // horário
  const mHor = $('#modalHorario'), fHor = $('#formHorario');
  function abrirHorario(g) {
    $('#selPrograma').innerHTML = [...programas.values()].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR')).map(p => '<option value="' + p.id + '">' + esc(p.nome) + '</option>').join('');
    fHor.elements.namedItem('id').value = g.id;
    fHor.programa_id.value = g.programa_id;
    fHor.dia.value = g.dia;
    fHor.inicio.value = hh(g.inicio);
    fHor.fim.value = hh(g.fim);
    mHor.showModal();
  }
  fHor.addEventListener('submit', async e => {
    e.preventDefault();
    const ok = await salvarHorario({ id: +fHor.elements.namedItem('id').value, programa_id: +fHor.programa_id.value, dia: +fHor.dia.value, inicio: paraMin(fHor.inicio.value, false), fim: paraMin(fHor.fim.value, true) });
    if (ok) mHor.close();
  });
  $('#excluirHorario').addEventListener('click', async () => {
    const id = +fHor.elements.namedItem('id').value;
    try {
      await api('grade_excluir', { id });
      grade = grade.filter(g => g.id !== id);
      mHor.close(); desenhar(); toast('Removido da grade');
    } catch (err) { toast(err.message, 'erro'); }
  });

  // copiar dia
  const mCop = $('#modalCopiar'), fCop = $('#formCopiar');
  $('#btnCopiarDia').addEventListener('click', () => { fCop.reset(); mCop.showModal(); });
  fCop.addEventListener('submit', async e => {
    e.preventDefault();
    const para = $$('input[name="para"]:checked', fCop).map(c => +c.value);
    const de = +fCop.de.value;
    if (!para.length) { toast('Escolha os dias de destino', 'erro'); return; }
    if (!confirm('Substituir a programação de ' + para.map(d => DIAS[d]).join(', ') + ' pela de ' + DIAS[de] + '?')) return;
    try {
      const d = await api('grade_copiar_dia', { de, para });
      grade = d.grade;
      mCop.close(); desenhar(); toast('Programação copiada');
    } catch (err) { toast(err.message, 'erro'); }
  });

  /* ---------------- início ---------------- */
  desenhar();
  // começa mostrando o horário atual
  const agora = new Date();
  agenda.scrollTop = Math.max(0, (agora.getHours() * 60 + agora.getMinutes() - 90) * ppm());
})();
