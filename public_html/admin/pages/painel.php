<?php
$agora = no_ar(3);
$tot = fn(string $sql) => (int)q($sql)->fetchColumn();
$cards = [
    ['Notícias publicadas', $tot('SELECT COUNT(*) FROM noticias WHERE publicado = 1'), 'news', 'noticias'],
    ['Eventos', $tot('SELECT COUNT(*) FROM eventos'), 'camera', 'eventos'],
    ['Fotos de eventos', $tot('SELECT COUNT(*) FROM evento_fotos'), 'image', 'eventos'],
    ['Mensagens não lidas', $tot('SELECT COUNT(*) FROM mensagens WHERE lida = 0'), 'inbox', 'mensagens'],
];
$msgs = q('SELECT id, nome, assunto, mensagem, cidade, criado_em, lida FROM mensagens ORDER BY criado_em DESC LIMIT 6')->fetchAll();
$maisLidas = q('SELECT titulo, slug, visualizacoes FROM noticias WHERE publicado = 1 ORDER BY visualizacoes DESC LIMIT 5')->fetchAll();
admin_inicio('Painel', '', '<a class="btn btn-primario" href="' . e(url('admin/noticias/nova')) . '">' . icone('plus') . ' Nova notícia</a>');
?>
<section class="no-ar-admin" style="--cor: <?= e($agora['atual']['cor'] ?? '#e3161b') ?>">
  <div>
    <span class="selo"><i></i> No ar agora</span>
    <h2><?= e($agora['atual']['nome'] ?? 'Sem programa cadastrado neste horário') ?></h2>
    <?php if ($agora['atual']): ?><p><?= e($agora['atual']['apresentador']) ?> · <?= hora((int)$agora['atual']['inicio']) ?> – <?= hora((int)$agora['atual']['fim']) ?></p><?php endif; ?>
  </div>
  <div class="prox">
    <small>A seguir</small>
    <?php foreach ($agora['proximos'] as $p): ?><span><b><?= hora((int)$p['inicio']) ?></b> <?= e($p['nome']) ?></span><?php endforeach; ?>
  </div>
</section>

<div class="cards-num">
  <?php foreach ($cards as [$rot, $n, $ic, $link]): ?>
    <a class="card-num" href="<?= e(url('admin/' . $link)) ?>"><?= icone($ic) ?><b><?= $n ?></b><span><?= e($rot) ?></span></a>
  <?php endforeach; ?>
</div>

<div class="grid-2">
  <section class="caixa">
    <div class="caixa-cab"><h2>Últimas mensagens</h2><a href="<?= e(url('admin/mensagens')) ?>">Ver todas</a></div>
    <?php if (!$msgs): ?><p class="vazio">Nenhuma mensagem ainda.</p><?php endif; ?>
    <ul class="lista-msgs">
      <?php foreach ($msgs as $m): ?>
        <li class="<?= $m['lida'] ? '' : 'nao-lida' ?>">
          <a href="<?= e(url('admin/mensagens#m' . $m['id'])) ?>">
            <b><?= e($m['nome']) ?></b> <span class="etiqueta"><?= e($m['assunto']) ?></span>
            <p><?= e(mb_strimwidth($m['mensagem'], 0, 110, '…')) ?></p>
            <small><?= date('d/m H:i', strtotime($m['criado_em'])) ?><?= $m['cidade'] ? ' · ' . e($m['cidade']) : '' ?></small>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="caixa">
    <div class="caixa-cab"><h2>Notícias mais lidas</h2><a href="<?= e(url('admin/noticias')) ?>">Gerenciar</a></div>
    <?php if (!$maisLidas): ?><p class="vazio">Nenhuma notícia publicada.</p><?php endif; ?>
    <ol class="mais-lidas">
      <?php foreach ($maisLidas as $n): ?>
        <li><a href="<?= e(url('/noticias/' . $n['slug'])) ?>" target="_blank" rel="noopener"><?= e($n['titulo']) ?></a><span><?= icone('eye') ?> <?= (int)$n['visualizacoes'] ?></span></li>
      <?php endforeach; ?>
    </ol>
    <div class="atalhos">
      <a href="<?= e(url('admin/eventos/novo')) ?>"><?= icone('camera') ?> Novo evento com fotos</a>
      <a href="<?= e(url('admin/banners')) ?>"><?= icone('image') ?> Trocar banners</a>
      <a href="<?= e(url('admin/programacao')) ?>"><?= icone('radio') ?> Editar programação</a>
    </div>
  </section>
</div>
<?php admin_fim();
