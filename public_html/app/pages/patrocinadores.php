<?php
$meta['titulo'] = 'Patrocinadores e anunciantes';
$meta['descricao'] = 'Quem faz a Máxima acontecer e como anunciar na ' . cfg('nome') . '.';
$lista = q('SELECT nome, logo, link FROM patrocinadores WHERE ativo = 1 ORDER BY ordem, id')->fetchAll();
$formatos = cfg_lista('formatos');
?>
<section class="cab-pagina">
  <div class="container">
    <span class="sobretitulo"><?= icone('star') ?> Parceiros</span>
    <h1>Patrocinadores</h1>
    <p>Empresas que acreditam na Máxima e falam todos os dias com o Centro-Oeste mineiro.</p>
  </div>
</section>
<?php if ($lista): ?>
<section class="secao pt0">
  <div class="container">
    <div class="grid-patro">
      <?php foreach ($lista as $p): $ext = preg_match('#^https?://#', $p['link']); ?>
        <a class="patro-card" href="<?= e(url($p['link'] ?: '/contato')) ?>"<?= $ext ? ' target="_blank" rel="noopener"' : '' ?>>
          <?php if ($p['logo']): ?><img src="<?= e(midia($p['logo'], true)) ?>" alt="<?= e($p['nome']) ?>" loading="lazy"><?php else: ?><span><?= e($p['nome']) ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="secao">
  <div class="container anuncie-box">
    <div>
      <h2 class="titulo-secao"><span>Anuncie</span> na Máxima</h2>
      <p>Com a Máxima você atinge todo o Centro-Oeste de Minas Gerais — <?= e(cfg('alcance')) ?> de pessoas em mais de <?= e(cfg('localidades')) ?> localidades — com o melhor custo-benefício.</p>
      <a class="btn btn-primario" href="<?= e(url('/contato?assunto=Anunciar')) ?>">Quero anunciar <?= icone('arrow') ?></a>
    </div>
    <?php if ($formatos): ?>
      <ul class="formatos"><?php foreach ($formatos as $f): ?><li><?= icone('check') ?> <?= e($f) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</section>
