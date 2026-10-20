<?php
// Registro de eventos com galeria de fotos (envio múltiplo arrastando, ordem por arrastar, capa e legendas).
$novo = $id === 'novo';
$ev = (!$novo && $id) ? q('SELECT * FROM eventos WHERE id = ?', [(int)$id])->fetch() : null;
if ($id && !$novo && !$ev) redirecionar('admin/eventos');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($novo || $ev)) {
    exigir_post_csrf();
    $titulo = mb_substr(trim((string)$_POST['titulo']), 0, 200);
    if ($titulo === '') throw new RuntimeException('Informe o nome do evento.');
    $data = (string)($_POST['data_evento'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) $data = date('Y-m-d');
    $vals = [$titulo, slug_unico('eventos', $titulo, (int)($ev['id'] ?? 0)), trim((string)$_POST['descricao']), mb_substr(trim((string)$_POST['local']), 0, 200), $data, !empty($_POST['publicado']) ? 1 : 0];
    if ($ev) {
        // mantém o endereço antigo se o título não mudou
        if ($ev['titulo'] === $titulo) $vals[1] = $ev['slug'];
        q('UPDATE eventos SET titulo=?, slug=?, descricao=?, local=?, data_evento=?, publicado=? WHERE id=?', [...$vals, $ev['id']]);
        $eid = (int)$ev['id'];
    } else {
        q('INSERT INTO eventos (titulo, slug, descricao, local, data_evento, publicado) VALUES (?,?,?,?,?,?)', $vals);
        $eid = (int)db()->lastInsertId();
    }
    $enviadas = 0; $falhas = 0;
    if (!empty($_FILES['fotos'])) {
        $ordem = (int)q('SELECT COALESCE(MAX(ordem), 0) FROM evento_fotos WHERE evento_id = ?', [$eid])->fetchColumn();
        foreach (arquivos_multiplos($_FILES['fotos']) as $arq) {
            if ($arq['error'] === UPLOAD_ERR_NO_FILE) continue;
            try { q('INSERT INTO evento_fotos (evento_id, arquivo, ordem) VALUES (?,?,?)', [$eid, salvar_imagem($arq), ++$ordem]); $enviadas++; }
            catch (RuntimeException $e) { $falhas++; }
        }
    }
    flash('Evento salvo.' . ($enviadas ? " $enviadas foto(s) enviada(s)." : '') . ($falhas ? " $falhas arquivo(s) não puderam ser lidos." : ''), $falhas ? 'erro' : 'ok');
    redirecionar('admin/eventos/' . $eid);
}

if ($novo || $ev):
    $f = $ev ?: ['titulo' => '', 'descricao' => '', 'local' => '', 'data_evento' => date('Y-m-d'), 'publicado' => 1];
    $fotos = $ev ? q('SELECT * FROM evento_fotos WHERE evento_id = ? ORDER BY ordem, id', [$ev['id']])->fetchAll() : [];
    $acoes = $ev ? '<a class="btn btn-fantasma" href="' . e(url('/eventos/' . $ev['slug'])) . '" target="_blank" rel="noopener">' . icone('eye') . ' Ver no site</a>' : '';
    admin_inicio($ev ? 'Editar evento' : 'Novo evento', 'eventos', $acoes);
?>
<form method="post" enctype="multipart/form-data" class="caixa form-grid">
  <?= campo_csrf() ?>
  <div class="col">
    <label class="campo">Nome do evento<input type="text" name="titulo" required maxlength="200" value="<?= e($f['titulo']) ?>" placeholder="Ex.: Show de aniversário da Máxima"></label>
    <div class="linha2">
      <label class="campo">Data<input type="date" name="data_evento" required value="<?= e($f['data_evento']) ?>"></label>
      <label class="campo">Local<input type="text" name="local" maxlength="200" value="<?= e($f['local']) ?>" placeholder="Cidade / espaço"></label>
    </div>
    <label class="check"><input type="checkbox" name="publicado" value="1"<?= $f['publicado'] ? ' checked' : '' ?>> Publicado no site</label>
  </div>
  <div class="col">
    <label class="campo">Descrição<textarea name="descricao" rows="6"><?= e($f['descricao']) ?></textarea></label>
  </div>
  <?php if (!$ev): ?>
    <div class="col-inteira">
      <label class="soltar-varias" data-soltar-varias>
        <input type="file" name="fotos[]" multiple accept="image/jpeg,image/png,image/webp">
        <?= icone('upload') ?><b>Arraste as fotos do evento aqui</b><small>ou clique para escolher (várias de uma vez). Depois de salvar você pode ordenar, legendar e escolher a capa.</small>
        <span class="contagem"></span>
      </label>
    </div>
  <?php endif; ?>
  <div class="form-rodape">
    <?php if ($ev): ?><button class="btn btn-perigo" type="button" data-excluir="eventos" data-id="<?= (int)$ev['id'] ?>" data-voltar="<?= e(url('admin/eventos')) ?>" data-confirmar="Excluir o evento e TODAS as suas fotos?"><?= icone('trash') ?> Excluir evento</button><?php endif; ?>
    <button class="btn btn-primario" type="submit"><?= icone('check') ?> <?= $ev ? 'Salvar dados' : 'Criar evento' ?></button>
  </div>
</form>

<?php if ($ev): ?>
<section class="caixa">
  <div class="caixa-cab"><h2>Fotos <small id="totalFotos">(<?= count($fotos) ?>)</small></h2></div>
  <div class="upload-fotos" data-upload-fotos data-evento="<?= (int)$ev['id'] ?>">
    <input type="file" multiple accept="image/jpeg,image/png,image/webp" id="inputFotos" hidden>
    <label for="inputFotos" class="soltar-varias"><?= icone('upload') ?><b>Arraste fotos aqui para enviar</b><small>ou clique para escolher · várias de uma vez · JPG/PNG/WebP</small></label>
    <div class="fila-envio"></div>
  </div>
  <p class="dica"><?= icone('grip') ?> Arraste as fotos para reordenar. Clique na estrela para definir a capa e escreva legendas direto na foto.</p>
  <div class="grid-fotos" data-ordenar="evento_fotos" id="gridFotos">
    <?php foreach ($fotos as $ft): ?>
      <figure class="foto-admin<?= (int)$ev['capa_id'] === (int)$ft['id'] ? ' capa' : '' ?>" data-id="<?= (int)$ft['id'] ?>">
        <img src="<?= e(midia($ft['arquivo'], true)) ?>" alt="" draggable="false">
        <span class="alca" title="Arraste para ordenar"><?= icone('grip') ?></span>
        <button type="button" class="foto-capa" data-capa="<?= (int)$ft['id'] ?>" title="Usar como capa"><?= icone('star') ?></button>
        <button type="button" class="foto-excluir" data-excluir="evento_fotos" data-id="<?= (int)$ft['id'] ?>" data-sem-confirmar-lote title="Excluir foto"><?= icone('trash') ?></button>
        <input type="text" class="foto-legenda" data-legenda="<?= (int)$ft['id'] ?>" value="<?= e($ft['legenda']) ?>" placeholder="Legenda..." maxlength="255">
      </figure>
    <?php endforeach; ?>
  </div>
  <template id="tplFoto">
    <figure class="foto-admin" data-id="">
      <img src="" alt="" draggable="false">
      <span class="alca" title="Arraste para ordenar"><?= icone('grip') ?></span>
      <button type="button" class="foto-capa" data-capa="" title="Usar como capa"><?= icone('star') ?></button>
      <button type="button" class="foto-excluir" data-excluir="evento_fotos" data-id="" title="Excluir foto"><?= icone('trash') ?></button>
      <input type="text" class="foto-legenda" data-legenda="" value="" placeholder="Legenda..." maxlength="255">
    </figure>
  </template>
</section>
<?php endif; ?>
<?php
    admin_fim();
    return;
endif;

$lista = q('SELECT e.*, (SELECT COUNT(*) FROM evento_fotos f WHERE f.evento_id = e.id) total,
                   COALESCE((SELECT arquivo FROM evento_fotos f WHERE f.id = e.capa_id), (SELECT arquivo FROM evento_fotos f WHERE f.evento_id = e.id ORDER BY ordem, id LIMIT 1)) capa
            FROM eventos e ORDER BY data_evento DESC')->fetchAll();
admin_inicio('Eventos', 'eventos', '<a class="btn btn-primario" href="' . e(url('admin/eventos/novo')) . '">' . icone('plus') . ' Novo evento</a>');
?>
<?php if (!$lista): ?><p class="vazio">Nenhum evento cadastrado.</p><?php endif; ?>
<div class="grid-eventos-admin">
  <?php foreach ($lista as $l): ?>
    <a class="evento-admin<?= $l['publicado'] ? '' : ' inativo' ?>" href="<?= e(url('admin/eventos/' . $l['id'])) ?>">
      <div class="ev-capa"><?php if ($l['capa']): ?><img src="<?= e(midia($l['capa'], true)) ?>" alt=""><?php else: ?><?= icone('camera') ?><?php endif; ?><span><?= icone('camera') ?> <?= (int)$l['total'] ?></span></div>
      <b><?= e($l['titulo']) ?></b>
      <small><?= date('d/m/Y', strtotime($l['data_evento'])) ?><?= $l['local'] ? ' · ' . e($l['local']) : '' ?><?= $l['publicado'] ? '' : ' · <em>rascunho</em>' ?></small>
    </a>
  <?php endforeach; ?>
</div>
<?php admin_fim();
