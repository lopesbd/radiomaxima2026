<?php
// Banners da página inicial: arrastar para ordenar, ativar/desativar, criar e editar.
$editar = $id ? q('SELECT * FROM banners WHERE id = ?', [(int)$id])->fetch() : null;
if ($id && !$editar) redirecionar('admin/banners');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post_csrf();
    $d = [
        'titulo' => mb_substr(trim((string)$_POST['titulo']), 0, 160),
        'subtitulo' => mb_substr(trim((string)$_POST['subtitulo']), 0, 255),
        'link' => mb_substr(trim((string)$_POST['link']), 0, 255),
        'botao' => mb_substr(trim((string)$_POST['botao']), 0, 60),
        'ativo' => !empty($_POST['ativo']) ? 1 : 0,
    ];
    $d['imagem'] = processar_imagem('imagem', $editar['imagem'] ?? null, 2200);
    if ($editar) {
        q('UPDATE banners SET titulo=?, subtitulo=?, link=?, botao=?, ativo=?, imagem=? WHERE id=?', [...array_values($d), $editar['id']]);
        flash('Banner atualizado.');
    } else {
        $ordem = (int)q('SELECT COALESCE(MAX(ordem), 0) + 1 FROM banners')->fetchColumn();
        q('INSERT INTO banners (titulo, subtitulo, link, botao, ativo, imagem, ordem) VALUES (?,?,?,?,?,?,?)', [...array_values($d), $ordem]);
        flash('Banner criado.');
    }
    redirecionar('admin/banners');
}

$lista = q('SELECT * FROM banners ORDER BY ordem, id')->fetchAll();
$b = $editar ?: ['titulo' => '', 'subtitulo' => '', 'link' => '', 'botao' => '', 'ativo' => 1, 'imagem' => null];
admin_inicio('Banners da página inicial', 'banners');
?>
<p class="dica"><?= icone('grip') ?> Arraste os banners para mudar a ordem em que aparecem no destaque da página inicial. Tamanho ideal da imagem: <b>1920 × 800 px</b> (paisagem). Sem imagem, o banner usa a arte da Máxima.</p>

<div class="grid-banners" data-ordenar="banners">
  <?php foreach ($lista as $l): ?>
    <article class="banner-item<?= $l['ativo'] ? '' : ' inativo' ?>" data-id="<?= (int)$l['id'] ?>">
      <div class="banner-prev"<?= $l['imagem'] ? ' style="background-image:url(\'' . e(midia($l['imagem'], true)) . '\')"' : '' ?>>
        <span class="alca" title="Arraste para ordenar"><?= icone('grip') ?></span>
        <div><b><?= e($l['titulo'] ?: '(sem título)') ?></b><small><?= e(mb_strimwidth($l['subtitulo'], 0, 80, '…')) ?></small></div>
      </div>
      <div class="banner-acoes">
        <label class="interruptor" title="Mostrar no site"><input type="checkbox" data-alternar="banners" data-id="<?= (int)$l['id'] ?>"<?= $l['ativo'] ? ' checked' : '' ?>><span></span> Ativo</label>
        <a class="btn-ic" href="<?= e(url('admin/banners/' . $l['id'])) ?>" title="Editar"><?= icone('edit') ?></a>
        <button class="btn-ic perigo" type="button" data-excluir="banners" data-id="<?= (int)$l['id'] ?>" title="Excluir"><?= icone('trash') ?></button>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<section class="caixa" id="form">
  <div class="caixa-cab"><h2><?= $editar ? 'Editar banner' : 'Novo banner' ?></h2><?php if ($editar): ?><a href="<?= e(url('admin/banners')) ?>">Cancelar</a><?php endif; ?></div>
  <form method="post" enctype="multipart/form-data" class="form-grid" action="<?= e(url('admin/banners' . ($editar ? '/' . $editar['id'] : ''))) ?>">
    <?= campo_csrf() ?>
    <div class="col">
      <label class="campo">Título<input type="text" name="titulo" maxlength="160" value="<?= e($b['titulo']) ?>" placeholder="Ex.: Promoção de aniversário"></label>
      <label class="campo">Texto de apoio<textarea name="subtitulo" rows="3" maxlength="255"><?= e($b['subtitulo']) ?></textarea></label>
      <div class="linha2">
        <label class="campo">Texto do botão<input type="text" name="botao" maxlength="60" value="<?= e($b['botao']) ?>" placeholder="Saiba mais"></label>
        <label class="campo">Link do botão<input type="text" name="link" maxlength="255" value="<?= e($b['link']) ?>" placeholder="/noticias/... ou https://..."></label>
      </div>
      <p class="ajuda">Use <code>#ouvir</code> como link para o botão tocar a rádio ao vivo.</p>
      <label class="check"><input type="checkbox" name="ativo" value="1"<?= $b['ativo'] ? ' checked' : '' ?>> Mostrar no site</label>
    </div>
    <div class="col"><?= campo_imagem('imagem', $b['imagem'], 'Imagem do banner') ?></div>
    <div class="form-rodape"><button class="btn btn-primario" type="submit"><?= icone('check') ?> Salvar banner</button></div>
  </form>
</section>
<?php admin_fim();
