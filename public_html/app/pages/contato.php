<?php
$meta['titulo'] = 'Contato';
$meta['descricao'] = 'Fale com a ' . cfg('nome') . ': pedidos musicais, comercial, sugestões e recados.';
$assuntos = ['Pedido de música', 'Recado / alô', 'Anunciar', 'Sugestão', 'Outro'];
$assuntoSel = in_array($_GET['assunto'] ?? '', $assuntos, true) ? $_GET['assunto'] : 'Pedido de música';
?>
<section class="cab-pagina">
  <div class="container">
    <span class="sobretitulo"><?= icone('mail') ?> Fale com a gente</span>
    <h1>Contato</h1>
    <p>Peça sua música, mande um alô, sugira uma pauta ou fale com o comercial.</p>
  </div>
</section>
<section class="secao pt0">
  <div class="container contato-grid">
    <form class="form-contato" data-contato action="<?= e(url('api/contato.php')) ?>" method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="campo-hp" aria-hidden="true"><label>Não preencha <input type="text" name="site" tabindex="-1" autocomplete="off"></label></div>
      <fieldset class="assuntos">
        <legend>Assunto</legend>
        <?php foreach ($assuntos as $a): ?>
          <label class="chip-radio"><input type="radio" name="assunto" value="<?= e($a) ?>"<?= $a === $assuntoSel ? ' checked' : '' ?>><span><?= e($a) ?></span></label>
        <?php endforeach; ?>
      </fieldset>
      <div class="linha2">
        <label>Seu nome<input type="text" name="nome" required maxlength="120" autocomplete="name"></label>
        <label>Cidade<input type="text" name="cidade" maxlength="120" autocomplete="address-level2"></label>
      </div>
      <div class="linha2">
        <label>E-mail<input type="email" name="email" maxlength="190" autocomplete="email"></label>
        <label>Telefone / WhatsApp<input type="tel" name="telefone" maxlength="40" autocomplete="tel"></label>
      </div>
      <label>Mensagem<textarea name="mensagem" rows="5" required maxlength="3000" placeholder="Escreva aqui..."></textarea></label>
      <button class="btn btn-primario" type="submit"><?= icone('send') ?> Enviar mensagem</button>
      <p class="form-retorno" role="status" aria-live="polite"></p>
    </form>
    <aside class="contato-lado">
      <?php if ($w = link_whatsapp('Olá, Máxima!')): ?>
        <a class="contato-card whats" href="<?= e($w) ?>" target="_blank" rel="noopener"><?= icone('whatsapp') ?><div><b>WhatsApp</b><span>Fale direto com o estúdio</span></div></a>
      <?php endif; ?>
      <?php if (cfg('telefone')): ?>
        <a class="contato-card" href="tel:<?= e(preg_replace('/\D/', '', cfg('telefone'))) ?>"><?= icone('phone') ?><div><b><?= e(cfg('telefone')) ?></b><span>Telefone</span></div></a>
      <?php endif; ?>
      <?php if (cfg('email')): ?>
        <a class="contato-card" href="mailto:<?= e(cfg('email')) ?>"><?= icone('mail') ?><div><b><?= e(cfg('email')) ?></b><span>E-mail</span></div></a>
      <?php endif; ?>
      <?php if (cfg('endereco')): ?>
        <div class="contato-card"><?= icone('pin') ?><div><b><?= e(cfg('endereco')) ?></b><span>Endereço</span></div></div>
      <?php endif; ?>
      <div class="redes grande"><?= redes_sociais() ?></div>
    </aside>
  </div>
</section>
