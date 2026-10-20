<?php
$meta['titulo'] = 'Notícias';
$meta['descricao'] = 'Notícias de Bom Despacho, do Centro-Oeste mineiro e do Brasil na ' . cfg('nome') . '.';
$porPagina = 9;
$pag = max(1, (int)($_GET['pagina'] ?? 1));
$busca = trim((string)($_GET['q'] ?? ''));
$cat = trim((string)($_GET['categoria'] ?? ''));
$where = 'publicado = 1 AND publicado_em <= NOW()';
$params = [];
if ($busca !== '') { $where .= ' AND (titulo LIKE ? OR resumo LIKE ?)'; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($cat !== '') { $where .= ' AND categoria = ?'; $params[] = $cat; }
$total = (int)q("SELECT COUNT(*) FROM noticias WHERE $where", $params)->fetchColumn();
$paginas = max(1, (int)ceil($total / $porPagina));
$pag = min($pag, $paginas);
$lista = q("SELECT titulo, slug, resumo, imagem, categoria, publicado_em FROM noticias WHERE $where ORDER BY publicado_em DESC LIMIT $porPagina OFFSET " . (($pag - 1) * $porPagina), $params)->fetchAll();
$categorias = q('SELECT categoria, COUNT(*) n FROM noticias WHERE publicado = 1 AND publicado_em <= NOW() GROUP BY categoria ORDER BY n DESC')->fetchAll();
$linkPagina = fn(int $p) => '/noticias' . (($q = http_build_query(array_filter(['q' => $busca, 'categoria' => $cat, 'pagina' => $p > 1 ? $p : null]))) ? '?' . $q : '');
?>
<section class="cab-pagina">
  <div class="container">
    <span class="sobretitulo"><?= icone('news') ?> Portal Máxima</span>
    <h1>Notícias</h1>
    <form class="busca" action="<?= e(url('/noticias')) ?>" method="get" role="search">
      <?= icone('search') ?>
      <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Buscar notícias..." aria-label="Buscar notícias">
      <?php if ($cat): ?><input type="hidden" name="categoria" value="<?= e($cat) ?>"><?php endif; ?>
      <button class="btn btn-primario btn-peq" type="submit">Buscar</button>
    </form>
  </div>
</section>

<section class="secao pt0">
  <div class="container">
    <?php if ($categorias): ?>
      <div class="chips">
        <a href="<?= e(url('/noticias' . ($busca ? '?q=' . rawurlencode($busca) : ''))) ?>" class="chip<?= $cat === '' ? ' ativo' : '' ?>">Todas</a>
        <?php foreach ($categorias as $c): ?>
          <a href="<?= e(url('/noticias?' . http_build_query(array_filter(['q' => $busca, 'categoria' => $c['categoria']])))) ?>" class="chip<?= $cat === $c['categoria'] ? ' ativo' : '' ?>"><?= e($c['categoria']) ?> <small><?= (int)$c['n'] ?></small></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$lista): ?>
      <p class="vazio">Nenhuma notícia encontrada<?= $busca ? ' para “' . e($busca) . '”' : '' ?>.</p>
    <?php else: ?>
      <div class="grid-lista">
        <?php foreach ($lista as $n): ?>
          <a class="card-noticia" href="<?= e(url('/noticias/' . $n['slug'])) ?>">
            <div class="card-img">
              <?php if ($n['imagem']): ?><img src="<?= e(midia($n['imagem'], true)) ?>" alt="" loading="lazy"><?php else: ?><div class="sem-img"><?= icone('news') ?></div><?php endif; ?>
              <span class="tag"><?= e($n['categoria']) ?></span>
            </div>
            <div class="card-corpo">
              <time><?= e(data_extenso($n['publicado_em'])) ?></time>
              <h3><?= e($n['titulo']) ?></h3>
              <?php if ($n['resumo']): ?><p><?= e($n['resumo']) ?></p><?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if ($paginas > 1): ?>
        <nav class="paginacao" aria-label="Páginas">
          <?php for ($i = 1; $i <= $paginas; $i++): ?>
            <a href="<?= e(url($linkPagina($i))) ?>"<?= $i === $pag ? ' class="ativo" aria-current="page"' : '' ?>><?= $i ?></a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
