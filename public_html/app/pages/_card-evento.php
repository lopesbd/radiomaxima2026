<?php /** @var array $ev  (precisa de: titulo, slug, local, data_evento, capa, total_fotos) */ $t = strtotime($ev['data_evento']); ?>
<a class="card-evento" href="<?= e(url('/eventos/' . $ev['slug'])) ?>">
  <div class="card-img">
    <?php if ($ev['capa']): ?><img src="<?= e(midia($ev['capa'], true)) ?>" alt="" loading="lazy"><?php else: ?><div class="sem-img"><?= icone('camera') ?></div><?php endif; ?>
    <span class="data-bloco"><b><?= date('d', $t) ?></b><?= MESES_CURTOS[(int)date('n', $t)] ?><small><?= date('Y', $t) ?></small></span>
    <span class="qtd-fotos"><?= icone('camera') ?> <?= (int)$ev['total_fotos'] ?></span>
  </div>
  <div class="card-corpo">
    <h3><?= e($ev['titulo']) ?></h3>
    <?php if ($ev['local']): ?><p class="local"><?= icone('pin') ?> <?= e($ev['local']) ?></p><?php endif; ?>
  </div>
</a>
