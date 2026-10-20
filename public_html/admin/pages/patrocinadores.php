<?php
// Patrocinadores: logos na faixa do rodapé e na página /patrocinadores. Ordem por arrastar.
$editar = $id ? q('SELECT * FROM patrocinadores WHERE id = ?', [(int)$id])->fetch() : null;
if ($id && !$editar) redirecionar('admin/patrocinadores');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post_csrf();
    $nome = mb_substr(trim((string)$_POST['nome']), 0, 160);
    if ($nome === '') throw new RuntimeException('Informe o nome do patrocinador.');
    $link = mb_substr(trim((string)$_POST['link']), 0, 255);
    $ativo = !empty($_POST['ativo']) ? 1 : 0;
    $logo = processar_imagem('logo', $editar['logo'] ?? null, 800);
    if ($editar) {
        q('UPDATE patrocinadores SET nome=?, link=?, ativo=?, logo=? WHERE id=?', [$nome, $link, $ativo, $logo, $editar['id']]);
        flash('Patrocinador atualizado.');
    } else {
        $ordem = (int)q('SELECT COALESCE(MAX(ordem), 0) + 1 FROM patrocinadores')->fetchColumn();
        q('INSERT INTO patrocinadores (nome, link, ativo, logo, ordem) VALUES (?,?,?,?,?)', [$nome, $link, $ativo, $logo, $ordem]);
        flash('Patrocinador adicionado.');
    }
    redirecionar('admin/patrocinadores');
}

$lista = q('SELECT * FROM patrocinadores ORDER BY ordem, id')->fetchAll();
$p = $editar ?: ['nome' => '', 'link' => '', 'ativo' => 1, 'logo' => null];
admin_inicio('Patrocinadores', 'patrocinadores');
?>
<p class="dica"><?= icone('grip') ?> Arraste os cartões para definir a ordem na faixa de patrocínio. Use logos com fundo transparente ou branco (PNG).</p>
<div class="grid-patro-admin" data-ordenar="patrocinadores">
  <?php foreach ($lista as $l): ?>
    <article class="patro-admin<?= $l['ativo'] ? '' : ' inativo' ?>" data-id="<?= (int)$l['id'] ?>">
      <span class="alca" title="Arraste para ordenar"><?= icone('grip') ?></span>
      <div class="patro-logo"><?php if ($l['logo']): ?><img src="<?= e(midia($l['logo'], true)) ?>" alt=""><?php else: ?><span><?= e($l['nome']) ?></span><?php endif; ?></div>
      <b><?= e($l['nome']) ?></b>
      <div class="banner-acoes">
        <label class="interruptor"><input type="checkbox" data-alternar="patrocinadores" data-id="<?= (int)$l['id'] ?>"<?= $l['ativo'] ? ' checked' : '' ?>><span></span></label>
        <a class="btn-ic" href="<?= e(url('admin/patrocinadores/' . $l['id'])) ?>#form" title="Editar"><?= icone('edit') ?></a>
        <button class="btn-ic perigo" type="button" data-excluir="patrocinadores" data-id="<?= (int)$l['id'] ?>" title="Excluir"><?= icone('trash') ?></button>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<section class="caixa" id="form">
  <div class="caixa-cab"><h2><?= $editar ? 'Editar patrocinador' : 'Novo patrocinador' ?></h2><?php if ($editar): ?><a href="<?= e(url('admin/patrocinadores')) ?>">Cancelar</a><?php endif; ?></div>
  <form method="post" enctype="multipart/form-data" class="form-grid" action="<?= e(url('admin/patrocinadores' . ($editar ? '/' . $editar['id'] : ''))) ?>">
    <?= campo_csrf() ?>
    <div class="col">
      <label class="campo">Nome<input type="text" name="nome" required maxlength="160" value="<?= e($p['nome']) ?>"></label>
      <label class="campo">Site ou Instagram (opcional)<input type="text" name="link" maxlength="255" value="<?= e($p['link']) ?>" placeholder="https://..."></label>
      <label class="check"><input type="checkbox" name="ativo" value="1"<?= $p['ativo'] ? ' checked' : '' ?>> Mostrar no site</label>
    </div>
    <div class="col"><?= campo_imagem('logo', $p['logo'], 'Logo') ?></div>
    <div class="form-rodape"><button class="btn btn-primario" type="submit"><?= icone('check') ?> Salvar</button></div>
  </form>
</section>
<?php admin_fim();
