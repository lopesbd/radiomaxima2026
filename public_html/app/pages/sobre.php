<?php
$meta['titulo'] = 'A Rádio';
$meta['descricao'] = 'Conheça a ' . cfg('nome') . ' ' . cfg('frequencia') . ', em ' . cfg('cidade') . ': cobertura, público e formatos para anunciantes.';
$cidades = cfg_lista('cidades');
?>
<section class="cab-pagina cab-sobre">
  <div class="container sobre-topo">
    <div>
      <span class="sobretitulo"><?= icone('radio') ?> <?= e(cfg('nome')) ?> <?= e(cfg('frequencia')) ?></span>
      <h1><?= e(cfg('sobre_titulo', 'A Rádio')) ?></h1>
      <p class="linha-fina"><?= e(cfg('slogan')) ?></p>
    </div>
    <div class="disco" aria-hidden="true"><img src="<?= e(url('assets/img/logo.png')) ?>" alt=""></div>
  </div>
</section>
<section class="secao pt0">
  <div class="container sobre-grid">
    <div class="texto-rico"><?= cfg('sobre_texto') ?></div>
    <aside class="sobre-lado">
      <div class="numeros vertical">
        <div><b><?= e(cfg('alcance')) ?></b><span>de pessoas na área de cobertura</span></div>
        <div><b>+<?= e(cfg('localidades')) ?></b><span>localidades alcançadas</span></div>
        <div><b>Popular Hits</b><span>sertanejo, MPB, axé, flash back e os sucessos do momento</span></div>
      </div>
    </aside>
  </div>
</section>
<?php if ($cidades): ?>
<section class="secao">
  <div class="container">
    <h2 class="titulo-secao"><span>Onde</span> a Máxima chega</h2>
    <ul class="cidades">
      <?php foreach ($cidades as $c): ?><li><?= icone('pin') ?> <?= e($c) ?></li><?php endforeach; ?>
      <li class="mais">e muitas outras</li>
    </ul>
  </div>
</section>
<?php endif; ?>
<section class="secao">
  <div class="container cta-duplo">
    <div class="cta cta-anuncie">
      <?= icone('star', 'ic cta-ic') ?>
      <h3>Quer anunciar?</h3>
      <p>Fale com nosso comercial e monte o plano ideal para sua marca.</p>
      <a class="btn btn-escuro" href="<?= e(url('/contato?assunto=Anunciar')) ?>">Falar com o comercial <?= icone('arrow') ?></a>
    </div>
    <div class="cta cta-musica">
      <?= icone('radio', 'ic cta-ic') ?>
      <h3>Ouça agora</h3>
      <p>A Máxima toca ao vivo aqui no site, 24 horas por dia.</p>
      <button class="btn btn-claro" type="button" data-play><?= icone('play') ?> Ouvir ao vivo</button>
    </div>
  </div>
</section>
