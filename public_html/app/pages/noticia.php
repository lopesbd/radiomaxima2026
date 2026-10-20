<?php
$n = q('SELECT * FROM noticias WHERE slug = ? AND publicado = 1 AND publicado_em <= NOW()', [$param])->fetch();
if (!$n) nao_encontrado();
q('UPDATE noticias SET visualizacoes = visualizacoes + 1 WHERE id = ?', [$n['id']]);
$meta['titulo'] = $n['titulo'];
$meta['descricao'] = $n['resumo'] ?: mb_substr(trim(strip_tags($n['conteudo'])), 0, 160);
$meta['imagem'] = $n['imagem'] ? midia($n['imagem']) : null;
$meta['menu'] = 'noticias';
$outras = q('SELECT titulo, slug, imagem, categoria, publicado_em FROM noticias WHERE publicado = 1 AND publicado_em <= NOW() AND id <> ? ORDER BY publicado_em DESC LIMIT 3', [$n['id']])->fetchAll();
$urlAbs = url_absoluta('/noticias/' . $n['slug']);
?>
<article class="materia">
  <header class="materia-cab container estreito">
    <a class="voltar" href="<?= e(url('/noticias')) ?>"><?= icone('back') ?> Notícias</a>
    <span class="tag"><?= e($n['categoria']) ?></span>
    <h1><?= e($n['titulo']) ?></h1>
    <?php if ($n['resumo']): ?><p class="linha-fina"><?= e($n['resumo']) ?></p><?php endif; ?>
    <div class="materia-meta">
      <time datetime="<?= e(date('c', strtotime($n['publicado_em']))) ?>"><?= icone('calendar') ?> <?= e(data_extenso($n['publicado_em'])) ?> às <?= date('H:i', strtotime($n['publicado_em'])) ?></time>
      <div class="compartilhar">
        <span>Compartilhe</span>
        <a href="https://wa.me/?text=<?= rawurlencode($n['titulo'] . ' ' . $urlAbs) ?>" target="_blank" rel="noopener" aria-label="Compartilhar no WhatsApp"><?= icone('whatsapp') ?></a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($urlAbs) ?>" target="_blank" rel="noopener" aria-label="Compartilhar no Facebook"><?= icone('facebook') ?></a>
        <button type="button" data-copiar="<?= e($urlAbs) ?>" aria-label="Copiar link"><?= icone('copy') ?></button>
      </div>
    </div>
  </header>
  <?php if ($n['imagem']): ?>
    <figure class="materia-capa container"><img src="<?= e(midia($n['imagem'])) ?>" alt=""></figure>
  <?php endif; ?>
  <div class="materia-texto texto-rico container estreito"><?= $n['conteudo'] ?></div>
</article>

<?php if ($outras): ?>
<section class="secao">
  <div class="container">
    <h2 class="titulo-secao"><span>Leia</span> também</h2>
    <div class="grid-lista">
      <?php foreach ($outras as $o): ?>
        <a class="card-noticia" href="<?= e(url('/noticias/' . $o['slug'])) ?>">
          <div class="card-img"><?php if ($o['imagem']): ?><img src="<?= e(midia($o['imagem'], true)) ?>" alt="" loading="lazy"><?php else: ?><div class="sem-img"><?= icone('news') ?></div><?php endif; ?><span class="tag"><?= e($o['categoria']) ?></span></div>
          <div class="card-corpo"><time><?= e(data_extenso($o['publicado_em'])) ?></time><h3><?= e($o['titulo']) ?></h3></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
