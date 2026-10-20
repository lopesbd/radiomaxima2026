/* Chat da Máxima — atendimento guiado no canto da tela.
 * Fluxos: pedir música, anunciar e falar com a rádio. Pede os dados passo a passo,
 * mostra um resumo para confirmar e grava em "mensagens" (painel → Mensagens).
 * Fica fora do <main>, então continua aberto ao navegar pelo site; o histórico dura a visita.
 */
(() => {
  'use strict';
  const $ = s => document.querySelector(s);
  const raiz = $('#chat');
  if (!raiz) return;

  const janela = $('#chatJanela'), botao = $('#chatBotao'), msgs = $('#chatMsgs'), opcoes = $('#chatOpcoes');
  const form = $('#chatForm'), input = $('#chatInput'), enviar = $('#chatEnviar'), balao = $('#chatBalao');
  const D = raiz.dataset;
  const FORMATOS = (() => { try { return JSON.parse(D.formatos || '[]'); } catch (e) { return []; } })();
  const armaz = {
    ler(k, a = sessionStorage) { try { return JSON.parse(a.getItem(k)); } catch (e) { return null; } },
    gravar(k, v, a = sessionStorage) { try { a.setItem(k, JSON.stringify(v)); } catch (e) { /* sem storage */ } },
  };

  let historico = armaz.ler('maxima_chat') || [];
  let atual = null;          // { fluxo, passo, dados }
  let aguardando = null;     // passo à espera de resposta digitada
  let fila = Promise.resolve();

  /* ---------------- abrir / fechar ---------------- */
  function abrir(fluxo) {
    raiz.classList.add('aberto');
    janela.setAttribute('aria-hidden', 'false');
    botao.setAttribute('aria-expanded', 'true');
    botao.setAttribute('aria-label', 'Fechar chat');
    esconderBalao(true);
    if (fluxo) { iniciarFluxo(fluxo, !historico.length); return; }
    if (!historico.length) boasVindas();
    setTimeout(() => (input.disabled ? (opcoes.querySelector('button') || botao) : input).focus({ preventScroll: true }), 250);
  }
  function fechar() {
    raiz.classList.remove('aberto');
    janela.setAttribute('aria-hidden', 'true');
    botao.setAttribute('aria-expanded', 'false');
    botao.setAttribute('aria-label', 'Abrir chat com a Rádio Máxima');
  }
  botao.addEventListener('click', () => (raiz.classList.contains('aberto') ? fechar() : abrir()));
  $('#chatFechar').addEventListener('click', () => { fechar(); botao.focus(); });
  $('#chatReiniciar').addEventListener('click', () => { historico = []; salvar(); msgs.innerHTML = ''; atual = null; aguardando = null; boasVindas(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && raiz.classList.contains('aberto')) { fechar(); botao.focus(); } });
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-abrir-chat]');
    if (!b) return;
    e.preventDefault();
    abrir(b.dataset.abrirChat || null);
  });

  // balão de convite (uma vez por visita)
  function esconderBalao(definitivo) {
    balao.hidden = true;
    raiz.classList.remove('com-convite');
    if (definitivo) armaz.gravar('maxima_chat_convite', 1);
  }
  $('#chatBalaoX').addEventListener('click', () => esconderBalao(true));
  if (!armaz.ler('maxima_chat_convite')) {
    setTimeout(() => {
      if (raiz.classList.contains('aberto') || armaz.ler('maxima_chat_convite')) return;
      balao.hidden = false;
      raiz.classList.add('com-convite');
    }, 7000);
  }

  /* ---------------- mensagens ---------------- */
  function salvar() { armaz.gravar('maxima_chat', historico.slice(-60)); }

  function desenhar(m, animar = true) {
    const el = document.createElement('div');
    el.className = 'chat-msg ' + (m.de === 'eu' ? 'eu' : 'bot') + (m.tipo ? ' ' + m.tipo : '') + (animar ? ' nova' : '');
    const bal = document.createElement('div');
    bal.className = 'chat-bal';
    bal.textContent = m.texto;
    el.appendChild(bal);
    msgs.appendChild(el);
    rolar();
    return el;
  }
  const rolar = () => { msgs.scrollTop = msgs.scrollHeight; };
  // teclado do celular abrindo/fechando muda a altura: mantém a última mensagem visível
  if ('ResizeObserver' in window) new ResizeObserver(rolar).observe(msgs);

  function eu(texto) {
    const m = { de: 'eu', texto };
    historico.push(m); salvar(); desenhar(m);
  }

  // mensagens do robô entram em sequência com "digitando…"
  function bot(textos, tipo) {
    (Array.isArray(textos) ? textos : [textos]).forEach(texto => {
      fila = fila.then(() => new Promise(ok => {
        const dig = document.createElement('div');
        dig.className = 'chat-msg bot digitando';
        dig.innerHTML = '<div class="chat-bal"><i></i><i></i><i></i></div>';
        msgs.appendChild(dig); rolar();
        setTimeout(() => {
          dig.remove();
          const m = { de: 'bot', texto, tipo };
          historico.push(m); salvar(); desenhar(m);
          ok();
        }, Math.min(1100, 350 + texto.length * 9));
      }));
    });
    return fila;
  }

  function mostrarOpcoes(lista) {
    opcoes.innerHTML = '';
    fila.then(() => {
      opcoes.innerHTML = '';
      lista.forEach(o => {
        const b = document.createElement(o.link ? 'a' : 'button');
        b.className = 'chat-opcao' + (o.destaque ? ' destaque' : '');
        b.textContent = o.rotulo;
        if (o.link) { b.href = o.link; b.target = '_blank'; b.rel = 'noopener'; }
        else b.type = 'button';
        b.addEventListener('click', ev => {
          if (o.link) return;
          ev.preventDefault();
          opcoes.innerHTML = '';
          if (!o.silencioso) eu(o.rotulo);
          o.acao();
        });
        opcoes.appendChild(b);
      });
      rolar();
    });
  }

  function habilitarEntrada(passo) {
    fila.then(() => {
      aguardando = passo;
      input.disabled = false; enviar.disabled = false;
      input.value = '';
      input.type = passo.tipo === 'email' ? 'email' : (passo.tipo === 'tel' ? 'tel' : 'text');
      input.inputMode = passo.tipo === 'tel' ? 'tel' : (passo.tipo === 'email' ? 'email' : 'text');
      input.autocomplete = { nome: 'name', cidade: 'address-level2', telefone: 'tel', email: 'email' }[passo.campo] || 'off';
      input.placeholder = passo.placeholder || 'Digite aqui…';
      if (raiz.classList.contains('aberto')) input.focus({ preventScroll: true });
    });
  }
  function desabilitarEntrada() {
    aguardando = null;
    input.disabled = true; enviar.disabled = true;
    input.value = ''; input.placeholder = 'Escolha uma opção acima';
  }

  /* ---------------- fluxos ---------------- */
  const nomeSalvo = () => armaz.ler('maxima_chat_nome', localStorage);
  const soDigitos = v => v.replace(/\D/g, '');
  const telOk = v => soDigitos(v).length >= 10 && soDigitos(v).length <= 13;
  const emailOk = v => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);

  const FLUXOS = {
    musica: {
      assunto: 'Pedido de música',
      abertura: ['Bora! 🎶 Vamos fazer seu pedido.'],
      passos: [
        { campo: 'musica', pergunta: 'Qual música você quer ouvir? Manda o nome e, se souber, o cantor.', placeholder: 'Ex.: Evidências — Chitãozinho & Xororó' },
        { campo: 'nome', pergunta: 'Show! E qual é o seu nome?', placeholder: 'Seu nome' },
        { campo: 'cidade', pergunta: 'De qual cidade você está ouvindo a Máxima?', placeholder: 'Ex.: Bom Despacho', opcional: true },
        { campo: 'recado', pergunta: 'Quer mandar um alô para alguém junto com a música? Escreva o recado ou toque em “Pular”.', placeholder: 'Ex.: Um beijo para minha mãe, Dona Maria!', opcional: true },
        { campo: 'telefone', pergunta: 'Para finalizar, deixe seu WhatsApp caso a gente queira falar com você no ar (opcional).', placeholder: '(37) 99999-9999', tipo: 'tel', opcional: true },
      ],
      resumo: d => ['🎵 Música: ' + d.musica, '🙋 Nome: ' + d.nome, d.cidade && '📍 Cidade: ' + d.cidade, d.recado && '💬 Recado: ' + d.recado, d.telefone && '📱 WhatsApp: ' + d.telefone],
      mensagem: d => 'Música: ' + d.musica + (d.recado ? '\nRecado: ' + d.recado : ''),
      sucesso: d => ['Pedido enviado! 🎉', 'Obrigado, ' + d.nome.split(' ')[0] + '! Fica ligado na Máxima que sua música pode tocar a qualquer momento. 📻'],
      denovo: 'Pedir outra música',
    },
    anunciar: {
      assunto: 'Anunciar',
      passos: [
        { campo: 'nome', pergunta: 'Qual é o seu nome?', placeholder: 'Seu nome' },
        { campo: 'empresa', pergunta: 'E o nome da empresa ou negócio?', placeholder: 'Nome da empresa', opcional: true },
        { campo: 'telefone', pergunta: 'Qual o melhor telefone ou WhatsApp para o nosso comercial falar com você?', placeholder: '(37) 99999-9999', tipo: 'tel' },
        { campo: 'email', pergunta: 'Tem um e-mail para enviarmos a proposta? (opcional)', placeholder: 'voce@empresa.com.br', tipo: 'email', opcional: true },
        { campo: 'mensagem', pergunta: 'Conta pra gente rapidinho o que você quer divulgar. (opcional)', placeholder: 'Ex.: inauguração da loja em novembro', opcional: true },
      ],
      resumo: d => ['🙋 Nome: ' + d.nome, d.empresa && '🏢 Empresa: ' + d.empresa, '📱 Telefone: ' + d.telefone, d.email && '✉️ E-mail: ' + d.email, d.mensagem && '📝 ' + d.mensagem],
      mensagem: d => (d.empresa ? 'Empresa: ' + d.empresa + '\n' : '') + (d.mensagem || 'Quer receber uma proposta para anunciar.'),
      sucesso: d => ['Recebido! ✅', 'Nosso comercial vai entrar em contato com você, ' + d.nome.split(' ')[0] + ', o quanto antes. Obrigado por pensar na Máxima!'],
    },
    contato: {
      assunto: 'Contato',
      passos: [
        { campo: 'nome', pergunta: 'Qual é o seu nome?', placeholder: 'Seu nome' },
        { campo: 'contato', pergunta: 'Como a gente te responde? Deixe seu WhatsApp ou e-mail.', placeholder: 'WhatsApp ou e-mail', tipo: 'contato' },
        { campo: 'mensagem', pergunta: 'Pode escrever sua mensagem. 😊', placeholder: 'Sua mensagem' },
      ],
      resumo: d => ['🙋 Nome: ' + d.nome, '📱 Contato: ' + d.contato, '💬 ' + d.mensagem],
      mensagem: d => d.mensagem,
      sucesso: d => ['Mensagem enviada! ✅', 'Obrigado, ' + d.nome.split(' ')[0] + '. A equipe da Máxima vai te responder em breve.'],
      denovo: 'Mandar outra mensagem',
    },
  };

  function boasVindas() {
    bot(['Olá! 👋 Aqui é a Máxima ' + '97,1 FM.', 'Como podemos te ajudar?']);
    menu();
  }

  function menu() {
    desabilitarEntrada();
    const lista = [
      { rotulo: '🎵 Pedir uma música', acao: () => iniciarFluxo('musica'), destaque: true },
      { rotulo: '📢 Anunciar na Máxima', acao: () => iniciarFluxo('anunciar') },
      { rotulo: '💬 Falar com a rádio', acao: () => iniciarFluxo('contato') },
    ];
    if (D.whats) lista.push({ rotulo: '🟢 Prefiro WhatsApp', link: D.whats });
    mostrarOpcoes(lista);
  }

  function iniciarFluxo(nome, comSaudacao) {
    if (comSaudacao) bot('Olá! 👋 Aqui é a Máxima 97,1 FM.');
    atual = { fluxo: nome, passo: 0, dados: {} };
    const f = FLUXOS[nome];
    if (nome === 'anunciar') return introAnunciar();
    if (nome === 'contato') {
      bot('Claro! Sobre o que você quer falar?');
      mostrarOpcoes(['Recado / alô', 'Sugestão', 'Reclamação', 'Outro assunto'].map(a => ({ rotulo: a, acao: () => { atual.assunto = a === 'Outro assunto' ? 'Contato' : a; perguntar(); } })));
      return;
    }
    if (f.abertura) bot(f.abertura);
    perguntar();
  }

  function introAnunciar() {
    const txt = ['Que ótimo! 📢 Na Máxima sua marca fala com ' + (D.alcance || 'muita gente') + ' de pessoas' + (D.localidades ? ' em mais de ' + D.localidades + ' localidades' : '') + ' do Centro-Oeste mineiro.'];
    if (FORMATOS.length) txt.push('Trabalhamos com formatos como:\n• ' + FORMATOS.join('\n• '));
    txt.push('Quer receber uma proposta do nosso comercial?');
    bot(txt);
    const lista = [
      { rotulo: '✅ Sim, quero uma proposta', acao: () => perguntar(), destaque: true },
      { rotulo: '📄 Ver formatos no site', acao: () => { navegarInterno(D.anuncie); bot('Abri a página de anunciantes para você. Quando quiser, é só pedir a proposta aqui. 😉'); mostrarOpcoes([{ rotulo: '✅ Quero uma proposta', acao: () => perguntar(), destaque: true }, { rotulo: '↩ Voltar ao início', acao: () => voltarInicio() }]); } },
    ];
    if (D.whats) lista.push({ rotulo: '🟢 Falar no WhatsApp', link: D.whats + '?text=' + encodeURIComponent('Olá! Quero saber como anunciar na Máxima.') });
    lista.push({ rotulo: '↩ Voltar', acao: () => voltarInicio() });
    mostrarOpcoes(lista);
  }

  function navegarInterno(href) {
    const a = document.createElement('a');
    a.href = href; a.hidden = true;
    document.body.appendChild(a); a.click(); a.remove();
  }

  function perguntar() {
    const f = FLUXOS[atual.fluxo];
    const p = f.passos[atual.passo];
    if (!p) return resumo();
    bot(p.pergunta);
    const atalhos = [];
    if (p.campo === 'nome' && nomeSalvo()) atalhos.push({ rotulo: 'Sou ' + nomeSalvo(), acao: () => responder(nomeSalvo(), true), silencioso: true });
    if (p.opcional) atalhos.push({ rotulo: 'Pular ⏭', acao: () => responder('', true) });
    mostrarOpcoes(atalhos);
    habilitarEntrada(p);
  }

  function responder(valor, jaMostrado) {
    const p = aguardando;
    if (!p || !atual) return;
    valor = valor.trim();
    if (!valor && !p.opcional) { bot('Preciso dessa informação para continuar. 🙂'); habilitarEntrada(p); return; }
    if (valor) {
      let erro = '';
      if (p.tipo === 'tel' && !telOk(valor)) erro = 'Esse número parece incompleto. Pode mandar com DDD? Ex.: (37) 99999-9999';
      if (p.tipo === 'email' && !emailOk(valor)) erro = 'Hmm, esse e-mail não parece válido. Pode conferir?';
      if (p.tipo === 'contato' && !telOk(valor) && !emailOk(valor)) erro = 'Mande um WhatsApp com DDD ou um e-mail válido, por favor.';
      if (!jaMostrado || p.campo === 'nome') eu(valor);
      if (erro) {
        bot(erro);
        if (p.opcional) mostrarOpcoes([{ rotulo: 'Pular ⏭', acao: () => responder('', true) }]);
        habilitarEntrada(p);
        return;
      }
    }
    if (p.campo === 'nome' && valor) armaz.gravar('maxima_chat_nome', valor.slice(0, 60), localStorage);
    atual.dados[p.campo] = valor;
    desabilitarEntrada();
    opcoes.innerHTML = '';
    atual.passo++;
    perguntar();
  }

  form.addEventListener('submit', e => {
    e.preventDefault();
    if (!aguardando) return;
    const v = input.value;
    opcoes.innerHTML = '';
    responder(v, false);
  });

  function resumo() {
    const f = FLUXOS[atual.fluxo];
    bot('Confere se está tudo certo:');
    bot(f.resumo(atual.dados).filter(Boolean).join('\n'), 'resumo');
    mostrarOpcoes([
      { rotulo: '✅ Enviar', acao: enviarDados, destaque: true },
      { rotulo: '✏️ Corrigir', acao: () => { atual.passo = 0; atual.dados = {}; bot('Sem problema, vamos de novo.'); perguntar(); } },
      { rotulo: 'Cancelar', acao: () => voltarInicio() },
    ]);
  }

  async function enviarDados() {
    const f = FLUXOS[atual.fluxo];
    const d = atual.dados;
    const fd = new FormData();
    fd.append('csrf', D.csrf);
    fd.append('assunto', atual.assunto || f.assunto);
    fd.append('nome', d.nome || '');
    fd.append('cidade', d.cidade || '');
    const contato = d.contato || '';
    fd.append('telefone', d.telefone || (contato && !emailOk(contato) ? contato : ''));
    fd.append('email', d.email || (emailOk(contato) ? contato : ''));
    fd.append('mensagem', f.mensagem(d));
    const envio = document.createElement('div');
    envio.className = 'chat-msg bot digitando';
    envio.innerHTML = '<div class="chat-bal"><i></i><i></i><i></i></div>';
    await fila;
    msgs.appendChild(envio); rolar();
    try {
      const r = await fetch(D.api, { method: 'POST', body: fd, credentials: 'same-origin' });
      const j = await r.json().catch(() => ({}));
      envio.remove();
      if (!r.ok) throw new Error(j.erro || 'Não consegui enviar agora.');
      bot(f.sucesso(d), 'sucesso');
      const fim = [];
      if (f.denovo) fim.push({ rotulo: '🔁 ' + f.denovo, acao: () => iniciarFluxo(atual.fluxo), destaque: true });
      fim.push({ rotulo: '↩ Voltar ao início', acao: () => voltarInicio() });
      mostrarOpcoes(fim);
    } catch (err) {
      envio.remove();
      bot('Ops! ' + err.message);
      const lista = [{ rotulo: '🔁 Tentar de novo', acao: enviarDados, destaque: true }];
      if (D.whats) lista.push({ rotulo: '🟢 Mandar pelo WhatsApp', link: D.whats + '?text=' + encodeURIComponent(f.mensagem(d)) });
      lista.push({ rotulo: '↩ Voltar ao início', acao: () => voltarInicio() });
      mostrarOpcoes(lista);
    }
  }

  function voltarInicio() {
    atual = null;
    bot('Posso ajudar em mais alguma coisa?');
    menu();
  }

  /* ---------------- início ---------------- */
  if (historico.length) {
    // retoma a conversa desta visita (sem repetir mensagens)
    historico.forEach(m => desenhar(m, false));
    menu();
  }
})();
