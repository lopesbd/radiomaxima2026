<?php
$meta['titulo'] = 'Eventos';
$meta['descricao'] = 'Fotos e registros dos eventos da ' . cfg('nome') . '.';
$ano = (int)($_GET['ano'] ?? 0);
$anos = q('SELECT DISTINCT YEAR(data_evento) a FROM eventos WHERE publicado = 1 ORDER BY a DESC')->fetchAll(PDO::FETCH_COLUMN);
$eventos = q('SELECT e.*, (SELECT COUNT(*) FROM evento_fotos f WHERE f.evento_id = e.id) AS total_fotos,
                     COALESCE((SELECT arquivo FROM evento_fotos f WHERE f.id = e.capa_id), (SELECT arquivo FROM evento_fotos f WHERE f.evento_id = e.id ORDER BY ordem, id LIMIT 1)) AS capa
              FROM eventos e WHERE publicado = 1' . ($ano ? ' AND YEAR(data_evento) = ?' : '') . ' ORDER BY data_evento DESC LIMIT 60', $ano ? [$ano] : [])->fetchAll();
?>
<section class="cab-pagina">
  <div class="container">
    <span class="sobretitulo"><?= icone('camera') ?> Registros</span>
    <h1>Eventos</h1>
    <p>A Máxima está onde a festa acontece. Confira as fotos dos nossos eventos.</p>
  </div>
</section>
<section class="secao pt0">
  <div class="container">
    <?php if (count($anos) > 1): ?>
      <div class="chips">
        <a class="chip<?= !$ano ? ' ativo' : '' ?>" href="<?= e(url('/eventos')) ?>">Todos</a>
        <?php foreach ($anos as $a): ?><a class="chip<?= $ano === (int)$a ? ' ativo' : '' ?>" href="<?= e(url('/eventos?ano=' . $a)) ?>"><?= (int)$a ?></a><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!$eventos): ?>
      <p class="vazio">Nenhum evento publicado ainda.</p>
    <?php else: ?>
      <div class="grid-eventos"><?php foreach ($eventos as $ev): require __DIR__ . '/_card-evento.php'; endforeach; ?></div>
    <?php endif; ?>
  </div>
</section>
