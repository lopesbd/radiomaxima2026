/* Arrastar.ordenar(container, opcoes) — reordenação por arrastar-e-soltar com mouse e toque
 * (Pointer Events; funciona em listas e grades). Sem dependências.
 * opcoes: { itens: '.seletor' (filhos diretos do container), alca: '.seletor' (opcional), aoSoltar(ordemDeIds, elemento) }
 */
window.Arrastar = (() => {
  'use strict';

  function ordenar(container, op = {}) {
    const selItem = op.itens || '*';
    const ehItem = el => el && el.parentElement === container && el.matches(selItem);
    const itemDe = alvo => { let el = alvo; while (el && el !== container && !ehItem(el)) el = el.parentElement; return ehItem(el) ? el : null; };
    let arrastado = null, fantasma = null, inicio = null, desloc = null, ativo = false, rolagem = null, ultimoPonto = null;

    const itens = () => Array.from(container.children).filter(ehItem);

    container.addEventListener('pointerdown', ev => {
      if (ev.button !== 0) return;
      const item = itemDe(ev.target);
      if (!item) return;
      if (op.alca && !ev.target.closest(op.alca)) return;
      if (!op.alca && ev.target.closest('input, textarea, select, button, a, label')) return;
      arrastado = item;
      inicio = { x: ev.clientX, y: ev.clientY };
      const r = item.getBoundingClientRect();
      desloc = { x: ev.clientX - r.left, y: ev.clientY - r.top };
      // em toque só começa após pequeno movimento; alça evita conflito com a rolagem
      document.addEventListener('pointermove', mover, { passive: false });
      document.addEventListener('pointerup', soltar);
      document.addEventListener('pointercancel', soltar);
    });

    function comecar(ev) {
      ativo = true;
      const r = arrastado.getBoundingClientRect();
      fantasma = arrastado.cloneNode(true);
      fantasma.classList.add('arrastar-fantasma');
      Object.assign(fantasma.style, { position: 'fixed', left: r.left + 'px', top: r.top + 'px', width: r.width + 'px', height: r.height + 'px', margin: 0, zIndex: 9999, pointerEvents: 'none' });
      document.body.appendChild(fantasma);
      arrastado.classList.add('arrastar-origem');
      document.body.classList.add('arrastando');
      rolagem = setInterval(rolarBorda, 16);
    }

    function mover(ev) {
      if (!arrastado) return;
      ultimoPonto = { x: ev.clientX, y: ev.clientY };
      if (!ativo) {
        if (Math.hypot(ev.clientX - inicio.x, ev.clientY - inicio.y) < 6) return;
        comecar(ev);
      }
      ev.preventDefault();
      fantasma.style.left = (ev.clientX - desloc.x) + 'px';
      fantasma.style.top = (ev.clientY - desloc.y) + 'px';
      reposicionar(ev.clientX, ev.clientY);
    }

    function reposicionar(x, y) {
      const lista = itens().filter(i => i !== arrastado);
      let alvo = null, antes = true;
      for (const it of lista) {
        const r = it.getBoundingClientRect();
        if (x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) {
          alvo = it;
          // grade: compara pela horizontal quando o item é largo o bastante para ter vizinhos na mesma linha
          const mesmaLinha = r.width < container.clientWidth * 0.8;
          antes = mesmaLinha ? x < r.left + r.width / 2 : y < r.top + r.height / 2;
          break;
        }
      }
      if (alvo) {
        const ref = antes ? alvo : alvo.nextSibling;
        if (ref !== arrastado && arrastado.nextSibling !== ref) alvo.parentNode.insertBefore(arrastado, ref);
      }
    }

    function rolarBorda() {
      if (!ultimoPonto) return;
      const m = 70, v = 14;
      if (ultimoPonto.y < m) window.scrollBy(0, -v);
      else if (ultimoPonto.y > innerHeight - m) window.scrollBy(0, v);
    }

    function soltar() {
      document.removeEventListener('pointermove', mover);
      document.removeEventListener('pointerup', soltar);
      document.removeEventListener('pointercancel', soltar);
      clearInterval(rolagem);
      if (ativo) {
        const el = arrastado;
        const destino = el.getBoundingClientRect();
        fantasma.style.transition = 'left .18s, top .18s';
        fantasma.style.left = destino.left + 'px';
        fantasma.style.top = destino.top + 'px';
        const f = fantasma;
        setTimeout(() => { f.remove(); el.classList.remove('arrastar-origem'); }, 180);
        document.body.classList.remove('arrastando');
        if (op.aoSoltar) op.aoSoltar(itens().map(i => i.dataset.id), el);
      }
      arrastado = null; fantasma = null; ativo = false; ultimoPonto = null;
    }
  }

  return { ordenar };
})();
