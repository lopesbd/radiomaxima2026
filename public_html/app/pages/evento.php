<?php
$ev = q('SELECT * FROM eventos WHERE slug = ? AND publicado = 1', [$param])->fetch();
if (!$ev) nao_encontrado();
$fotos = q('SELECT arquivo, legenda FROM evento_fotos WHERE evento_id = ? ORDER BY ordem, id', [$ev['id']])->fetchAll();
$meta['titulo'] = $ev['titulo'];
$meta['descricao'] = $ev['descricao'] ? mb_substr($ev['descricao'], 0, 160) : 'Fotos do evento ' . $ev['titulo'];
$meta['menu'] = 'eventos';
if ($fotos) $meta['imagem'] = midia($fotos[0]['arquivo']);
?>
<section class="cab-pagina cab-evento">
  <div class="container">
    <a class="voltar" href="<?= e(url('/eventos')) ?>"><?= icone('back') ?> Eventos</a>
    <h1><?= e($ev['titulo']) ?></h1>
    <div class="evento-meta">
      <span><?= icone('calendar') ?> <?= e(data_extenso($ev['data_evento'])) ?></span>
      <?php if ($ev['local']): ?><span><?= icone('pin') ?> <?= e($ev['local']) ?></span><?php endif; ?>
      <span><?= icone('camera') ?> <?= count($fotos) ?> foto<?= count($fotos) === 1 ? '' : 's' ?></span>
    </div>
    <?php if ($ev['descricao']): ?><p class="evento-desc"><?= nl2br(e($ev['descricao'])) ?></p><?php endif; ?>
  </div>
</section>
<section class="secao pt0">
  <div class="container">
    <?php if (!$fotos): ?>
      <p class="vazio">As fotos deste evento serão publicadas em breve.</p>
    <?php else: ?>
      <div class="galeria" data-galeria>
        <?php foreach ($fotos as $i => $f): ?>
          <a class="galeria-item" href="<?= e(midia($f['arquivo'])) ?>" data-legenda="<?= e($f['legenda']) ?>" data-indice="<?= $i ?>">
            <img src="<?= e(midia($f['arquivo'], true)) ?>" alt="<?= e($f['legenda'] ?: $ev['titulo']) ?>" loading="lazy">
            <?php if ($f['legenda']): ?><span><?= e($f['legenda']) ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
