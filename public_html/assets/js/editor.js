/* Editor visual simples para notícias e textos (o servidor limpa o HTML ao salvar). */
(() => {
  'use strict';
  const { api, toast, $, $$ } = window.Painel;

  $$('[data-editor]').forEach(ed => {
    const area = $('.editor-area', ed);
    const saida = $('textarea', ed);
    document.execCommand('defaultParagraphSeparator', false, 'p');

    const sincronizar = () => { saida.value = area.innerHTML.trim(); };
    area.addEventListener('input', sincronizar);
    const form = ed.closest('form');
    if (form) form.addEventListener('submit', sincronizar);
    sincronizar();

    let selecao = null;
    const guardarSelecao = () => { const s = getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) selecao = s.getRangeAt(0).cloneRange(); };
    const restaurarSelecao = () => { area.focus(); if (selecao) { const s = getSelection(); s.removeAllRanges(); s.addRange(selecao); } };
    area.addEventListener('keyup', guardarSelecao);
    area.addEventListener('mouseup', guardarSelecao);
    area.addEventListener('blur', guardarSelecao);

    function inserirHtml(html) { restaurarSelecao(); document.execCommand('insertHTML', false, html); sincronizar(); }

    async function enviarImagens(arquivos) {
      for (const arq of Array.from(arquivos).filter(f => f.type.startsWith('image/'))) {
        toast('Enviando imagem…');
        try {
          const d = await api('imagem_editor', {}, { imagem: arq });
          inserirHtml('<p><img src="' + d.url + '" alt=""></p>');
          toast('Imagem inserida');
        } catch (e) { toast(e.message, 'erro'); }
      }
    }

    $('.editor-barra', ed).addEventListener('mousedown', e => { if (e.target.closest('button')) e.preventDefault(); });
    $('.editor-barra', ed).addEventListener('click', e => {
      const b = e.target.closest('button[data-cmd]');
      if (!b) return;
      const cmd = b.dataset.cmd;
      area.focus();
      if (cmd === 'link') {
        guardarSelecao();
        const url = prompt('Endereço do link (https://...)');
        if (url) { restaurarSelecao(); document.execCommand('createLink', false, url.trim()); }
      } else if (cmd === 'imagem') {
        guardarSelecao();
        const inp = document.createElement('input');
        inp.type = 'file'; inp.accept = 'image/*'; inp.multiple = true;
        inp.onchange = () => enviarImagens(inp.files);
        inp.click();
      } else if (cmd === 'youtube') {
        guardarSelecao();
        const url = prompt('Cole o link do vídeo do YouTube');
        const m = url && url.match(/(?:youtu\.be\/|v=|embed\/|shorts\/)([\w-]{11})/);
        if (m) inserirHtml('<p><iframe src="https://www.youtube-nocookie.com/embed/' + m[1] + '"></iframe></p><p><br></p>');
        else if (url) toast('Link do YouTube não reconhecido', 'erro');
      } else if (cmd === 'formatBlock') {
        document.execCommand('formatBlock', false, '<' + b.dataset.val + '>');
      } else {
        document.execCommand(cmd, false, null);
      }
      sincronizar();
    });

    // arrastar imagens para dentro do texto / colar
    area.addEventListener('dragover', e => { if (e.dataTransfer.types.includes('Files')) { e.preventDefault(); area.classList.add('sobre'); } });
    area.addEventListener('dragleave', () => area.classList.remove('sobre'));
    area.addEventListener('drop', e => {
      if (!e.dataTransfer.files.length) return;
      e.preventDefault(); area.classList.remove('sobre');
      if (document.caretRangeFromPoint) { const r = document.caretRangeFromPoint(e.clientX, e.clientY); if (r) selecao = r; }
      enviarImagens(e.dataTransfer.files);
    });
    area.addEventListener('paste', e => {
      const arqs = Array.from(e.clipboardData.files || []);
      if (arqs.length) { e.preventDefault(); guardarSelecao(); enviarImagens(arqs); return; }
      // cola como texto simples mantendo parágrafos (evita estilos estranhos de outros sites)
      const html = e.clipboardData.getData('text/html');
      if (html) {
        e.preventDefault();
        const txt = e.clipboardData.getData('text/plain');
        const paras = txt.split(/\n{2,}|\r\n\r\n/).map(p => '<p>' + p.replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c])).replace(/\n/g, '<br>') + '</p>').join('');
        document.execCommand('insertHTML', false, paras);
      }
    });
  });
})();
