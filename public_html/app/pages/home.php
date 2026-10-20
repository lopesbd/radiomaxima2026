<?php
$banners = q('SELECT * FROM banners WHERE ativo = 1 ORDER BY ordem, id')->fetchAll();
$noticias = q('SELECT id, titulo, slug, resumo, imagem, categoria, publicado_em FROM noticias WHERE publicado = 1 AND publicado_em <= NOW() ORDER BY destaque DESC, publicado_em DESC LIMIT 4')->fetchAll();
$eventos = q('SELECT e.*, (SELECT COUNT(*) FROM evento_fotos f WHERE f.evento_id = e.id) AS total_fotos,
                     COALESCE((SELECT arquivo FROM evento_fotos f WHERE f.id = e.capa_id), (SELECT arquivo FROM evento_fotos f WHERE f.evento_id = e.id ORDER BY ordem, id LIMIT 1)) AS capa
              FROM eventos e WHERE publicado = 1 ORDER BY data_evento DESC LIMIT 3')->fetchAll();
$agora = no_ar(4);
$atual = $agora['atual'];
?>
<?php if ($banners): ?>
<section class="hero" data-slider aria-roledescription="carrossel" aria-label="Destaques">
  <div class="hero-slides">
    <?php foreach ($banners as $i => $b): ?>
      <article class="hero-slide<?= $i === 0 ? ' ativo' : '' ?><?= $b['imagem'] ? ' com-imagem' : '' ?>" aria-roledescription="slide" aria-label="<?= $i + 1 ?> de <?= count($banners) ?>">
        <?php if ($b['imagem']): ?>
          <img class="hero-img" src="<?= e(midia($b['imagem'])) ?>" alt="" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
        <?php else: ?>
          <div class="hero-arte" aria-hidden="true">
            <img src="<?= e(url('assets/img/logo.png')) ?>" alt="">
            <span class="onda o1"></span><span class="onda o2"></span><span class="onda o3"></span>
          </div>
        <?php endif; ?>
        <div class="hero-txt">
          <?php if ($b['titulo']): ?><h2><?= e($b['titulo']) ?></h2><?php endif; ?>
          <?php if ($b['subtitulo']): ?><p><?= e($b['subtitulo']) ?></p><?php endif; ?>
          <?php if ($b['botao'] && $b['link']): ?>
            <?php if ($b['link'] === '#ouvir'): ?>
              <button class="btn btn-primario" type="button" data-play><?= icone('play') ?> <?= e($b['botao']) ?></button>
            <?php else: ?>
              <a class="btn btn-primario" href="<?= e(url($b['link'])) ?>"<?= preg_match('#^https?://#', $b['link']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($b['botao']) ?> <?= icone('arrow') ?></a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php if (count($banners) > 1): ?>
    <button class="hero-nav ant" type="button" aria-label="Anterior"><?= icone('left') ?></button>
    <button class="hero-nav prox" type="button" aria-label="Próximo"><?= icone('right') ?></button>
    <div class="hero-pontos"><?php foreach ($banners as $i => $b): ?><button type="button" aria-label="Ir para o destaque <?= $i + 1 ?>"<?= $i === 0 ? ' class="ativo"' : '' ?>></button><?php endforeach; ?></div>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="secao no-ar-home">
  <div class="container grid-no-ar">
    <div class="card-no-ar" data-no-ar style="--cor: <?= e($atual['cor'] ?? '#e3161b') ?>">
      <div class="card-no-ar-topo"><span class="selo-ao-vivo grande"><i></i>No ar agora</span><span class="relogio" data-relogio></span></div>
      <?php if ($atual): ?>
        <div class="card-no-ar-corpo">
          <div class="foto-prog"><?php if ($atual['foto']): ?><img src="<?= e(midia($atual['foto'], true)) ?>" alt=""><?php else: ?><?= icone('mic') ?><?php endif; ?></div>
          <div>
            <h2 data-campo="nome"><?= e($atual['nome']) ?></h2>
            <p class="apresentador" data-campo="apresentador"><?= e($atual['apresentador']) ?></p>
            <p class="horario"><?= icone('clock') ?> <span data-campo="horario"><?= hora((int)$atual['inicio']) ?> – <?= hora((int)$atual['fim']) ?></span></p>
          </div>
        </div>
        <div class="progresso" data-inicio="<?= (int)$atual['inicio'] ?>" data-fim="<?= (int)$atual['fim'] ?>"><span></span></div>
      <?php else: ?>
        <div class="card-no-ar-corpo"><div class="foto-prog"><?= icone('music') ?></div><div><h2>Programação musical</h2><p class="apresentador"><?= e(cfg('slogan')) ?></p></div></div>
      <?php endif; ?>
      <div class="card-no-ar-acoes">
        <button class="btn btn-primario" type="button" data-play><?= icone('play') ?> Ouvir agora</button>
        <a class="btn btn-fantasma" href="<?= e(url('/programacao')) ?>">Programação completa</a>
      </div>
    </div>
    <div class="a-seguir">
      <h3>A seguir</h3>
      <ol>
        <?php foreach ($agora['proximos'] as $p): ?>
          <li style="--cor: <?= e($p['cor']) ?>">
            <time><?= hora((int)$p['inicio']) ?><?php if ($p['amanha']): ?><small>amanhã</small><?php endif; ?></time>
            <div><strong><?= e($p['nome']) ?></strong><span><?= e($p['apresentador']) ?></span></div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>

<?php if ($noticias): ?>
<section class="secao">
  <div class="container">
    <div class="secao-cab">
      <h2 class="titulo-secao"><span>Últimas</span> notícias</h2>
      <a class="link-mais" href="<?= e(url('/noticias')) ?>">Ver todas <?= icone('arrow') ?></a>
    </div>
    <div class="grid-noticias">
      <?php foreach ($noticias as $i => $n): ?>
        <a class="card-noticia<?= $i === 0 ? ' principal' : '' ?>" href="<?= e(url('/noticias/' . $n['slug'])) ?>">
          <div class="card-img">
            <?php if ($n['imagem']): ?><img src="<?= e(midia($n['imagem'], $i !== 0)) ?>" alt="" loading="lazy"><?php else: ?><div class="sem-img"><?= icone('news') ?></div><?php endif; ?>
            <span class="tag"><?= e($n['categoria']) ?></span>
          </div>
          <div class="card-corpo">
            <time><?= e(data_extenso($n['publicado_em'])) ?></time>
            <h3><?= e($n['titulo']) ?></h3>
            <?php if ($i === 0 && $n['resumo']): ?><p><?= e($n['resumo']) ?></p><?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="secao faixa-numeros">
  <div class="container numeros">
    <div><b><?= e(cfg('frequencia')) ?></b><span>FM em <?= e(explode('/', cfg('cidade'))[0]) ?></span></div>
    <div><b><?= e(cfg('alcance')) ?></b><span>de pessoas na área de cobertura</span></div>
    <div><b>+<?= e(cfg('localidades')) ?></b><span>localidades alcançadas</span></div>
    <div><b>24h</b><span>no ar, todos os dias</span></div>
  </div>
</section>

<?= secao_app($atual) ?>

<?php if ($eventos): ?>
<section class="secao">
  <div class="container">
    <div class="secao-cab">
      <h2 class="titulo-secao"><span>Eventos</span> & fotos</h2>
      <a class="link-mais" href="<?= e(url('/eventos')) ?>">Todos os eventos <?= icone('arrow') ?></a>
    </div>
    <div class="grid-eventos">
      <?php foreach ($eventos as $ev): require APP . '/pages/_card-evento.php'; endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="secao">
  <div class="container cta-duplo">
    <div class="cta cta-musica">
      <?= icone('music', 'ic cta-ic') ?>
      <h3>Peça sua música</h3>
      <p>Mande seu pedido e um alô pelo chat — a gente toca na Máxima!</p>
      <button class="btn btn-claro" type="button" data-abrir-chat="musica"><?= icone('chat') ?> Pedir pelo chat</button>
    </div>
    <div class="cta cta-anuncie">
      <?= icone('star', 'ic cta-ic') ?>
      <h3>Anuncie na Máxima</h3>
      <p>Sua marca falando com <?= e(cfg('alcance')) ?> de pessoas no Centro-Oeste mineiro.</p>
      <a class="btn btn-escuro" href="<?= e(url('/patrocinadores')) ?>">Conheça os formatos <?= icone('arrow') ?></a>
    </div>
  </div>
</section>
