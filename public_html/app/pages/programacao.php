<?php
$meta['titulo'] = 'Programação';
$meta['descricao'] = 'Programação completa da ' . cfg('nome') . ', dia a dia e hora a hora. Veja o que está no ar agora.';
$agora = no_ar();
$hoje = $agora['dia'];
$todas = q('SELECT g.id, g.dia, g.inicio, g.fim, p.nome, p.apresentador, p.descricao, p.foto, p.cor
            FROM grade g JOIN programas p ON p.id = g.programa_id ORDER BY g.dia, g.inicio')->fetchAll();
$porDia = array_fill(0, 7, []);
foreach ($todas as $g) $porDia[(int)$g['dia']][] = $g;
$ordemDias = [1, 2, 3, 4, 5, 6, 0]; // semana começando na segunda
?>
<section class="cab-pagina">
  <div class="container">
    <span class="sobretitulo"><?= icone('radio') ?> <?= e(cfg('frequencia')) ?> FM</span>
    <h1>Programação</h1>
    <p>Hoje é <strong><?= DIAS[$hoje] ?></strong>. O programa destacado é o que está no ar agora.</p>
  </div>
</section>

<section class="secao pt0">
  <div class="container" data-programacao data-dia="<?= $hoje ?>" data-minuto="<?= $agora['minuto'] ?>" data-segundo="<?= $agora['segundo'] ?>">
    <div class="abas-dias" role="tablist" aria-label="Dias da semana">
      <?php foreach ($ordemDias as $d): ?>
        <button type="button" role="tab" id="aba-<?= $d ?>" aria-controls="dia-<?= $d ?>" aria-selected="<?= $d === $hoje ? 'true' : 'false' ?>" data-dia="<?= $d ?>"<?= $d === $hoje ? ' class="ativo"' : '' ?>>
          <span class="dia-longo"><?= DIAS[$d] ?></span><span class="dia-curto"><?= DIAS_CURTOS[$d] ?></span><?php if ($d === $hoje): ?><small>hoje</small><?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>

    <?php foreach ($ordemDias as $d): ?>
      <div class="dia-grade" role="tabpanel" id="dia-<?= $d ?>" aria-labelledby="aba-<?= $d ?>"<?= $d === $hoje ? '' : ' hidden' ?>>
        <?php if (!$porDia[$d]): ?>
          <p class="vazio">Programação deste dia ainda não cadastrada.</p>
        <?php endif; ?>
        <ol class="linha-tempo">
          <?php foreach ($porDia[$d] as $g): ?>
            <li class="prog" data-inicio="<?= (int)$g['inicio'] ?>" data-fim="<?= (int)$g['fim'] ?>" style="--cor: <?= e($g['cor']) ?>">
              <div class="prog-hora"><b><?= hora((int)$g['inicio']) ?></b><span><?= hora((int)$g['fim']) ?></span></div>
              <div class="prog-card">
                <div class="prog-foto"><?php if ($g['foto']): ?><img src="<?= e(midia($g['foto'], true)) ?>" alt="" loading="lazy"><?php else: ?><?= icone('mic') ?><?php endif; ?></div>
                <div class="prog-txt">
                  <span class="selo-ao-vivo"><i></i>No ar agora</span>
                  <h3><?= e($g['nome']) ?></h3>
                  <?php if ($g['apresentador']): ?><p class="apresentador"><?= e($g['apresentador']) ?></p><?php endif; ?>
                  <?php if ($g['descricao']): ?><p class="desc"><?= e($g['descricao']) ?></p><?php endif; ?>
                  <div class="progresso"><span></span></div>
                </div>
                <button class="btn btn-primario btn-peq ouvir-agora" type="button" data-play><?= icone('play') ?> Ouvir</button>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    <?php endforeach; ?>
  </div>
</section>
