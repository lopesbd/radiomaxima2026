<?php
// Notícias / matérias: lista com busca e formulário com editor visual.
$nova = $id === 'nova';
$n = (!$nova && $id) ? q('SELECT * FROM noticias WHERE id = ?', [(int)$id])->fetch() : null;
if ($id && !$nova && !$n) redirecionar('admin/noticias');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($nova || $n)) {
    exigir_post_csrf();
    $titulo = mb_substr(trim((string)$_POST['titulo']), 0, 200);
    if ($titulo === '') throw new RuntimeException('Informe o título da notícia.');
    $conteudo = limpar_html((string)($_POST['conteudo'] ?? ''));
    $resumo = mb_substr(trim((string)$_POST['resumo']), 0, 400);
    $categoria = mb_substr(trim((string)$_POST['categoria']), 0, 60) ?: 'Geral';
    $quando = str_replace('T', ' ', (string)($_POST['publicado_em'] ?? '')) ?: date('Y-m-d H:i');
    if (!strtotime($quando)) $quando = date('Y-m-d H:i');
    $imagem = processar_imagem('imagem', $n['imagem'] ?? null);
    $slug = slug_unico('noticias', trim((string)($_POST['slug'] ?? '')) ?: $titulo, (int)($n['id'] ?? 0));
    $vals = [$titulo, $slug, $resumo, $conteudo, $imagem, $categoria, !empty($_POST['destaque']) ? 1 : 0, !empty($_POST['publicado']) ? 1 : 0, date('Y-m-d H:i:s', strtotime($quando))];
    if ($n) {
        q('UPDATE noticias SET titulo=?, slug=?, resumo=?, conteudo=?, imagem=?, categoria=?, destaque=?, publicado=?, publicado_em=? WHERE id=?', [...$vals, $n['id']]);
        $novoId = $n['id'];
    } else {
        q('INSERT INTO noticias (titulo, slug, resumo, conteudo, imagem, categoria, destaque, publicado, publicado_em) VALUES (?,?,?,?,?,?,?,?,?)', $vals);
        $novoId = db()->lastInsertId();
    }
    flash('Notícia salva.');
    redirecionar('admin/noticias/' . $novoId);
}

$categorias = q('SELECT DISTINCT categoria FROM noticias ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);
$categorias = array_values(array_unique(array_merge($categorias, ['Geral', 'Bom Despacho', 'Região', 'Esporte', 'Saúde', 'Cultura', 'Serviço', 'Máxima'])));

if ($nova || $n):
    $f = $n ?: ['titulo' => '', 'slug' => '', 'resumo' => '', 'conteudo' => '', 'imagem' => null, 'categoria' => 'Geral', 'destaque' => 0, 'publicado' => 1, 'publicado_em' => date('Y-m-d H:i')];
    $acoes = $n ? '<a class="btn btn-fantasma" href="' . e(url('/noticias/' . $n['slug'])) . '" target="_blank" rel="noopener">' . icone('eye') . ' Ver no site</a>' : '';
    admin_inicio($n ? 'Editar notícia' : 'Nova notícia', 'noticias', $acoes);
?>
<form method="post" enctype="multipart/form-data" class="form-materia" data-form-editor>
  <?= campo_csrf() ?>
  <div class="materia-principal">
    <input class="campo-titulo" type="text" name="titulo" required maxlength="200" value="<?= e($f['titulo']) ?>" placeholder="Título da notícia">
    <label class="campo">Linha fina / resumo<textarea name="resumo" rows="2" maxlength="400" placeholder="Uma ou duas frases que aparecem na lista e no compartilhamento"><?= e($f['resumo']) ?></textarea></label>
    <div class="campo"><span class="rotulo">Texto</span>
      <div class="editor" data-editor>
        <div class="editor-barra" role="toolbar" aria-label="Formatação">
          <button type="button" data-cmd="bold" title="Negrito"><b>B</b></button>
          <button type="button" data-cmd="italic" title="Itálico"><i>I</i></button>
          <button type="button" data-cmd="underline" title="Sublinhado"><u>S</u></button>
          <span class="sep"></span>
          <button type="button" data-cmd="formatBlock" data-val="h2" title="Título">H2</button>
          <button type="button" data-cmd="formatBlock" data-val="h3" title="Subtítulo">H3</button>
          <button type="button" data-cmd="formatBlock" data-val="p" title="Parágrafo">¶</button>
          <button type="button" data-cmd="formatBlock" data-val="blockquote" title="Citação">❝</button>
          <span class="sep"></span>
          <button type="button" data-cmd="insertUnorderedList" title="Lista">• Lista</button>
          <button type="button" data-cmd="insertOrderedList" title="Lista numerada">1. Lista</button>
          <span class="sep"></span>
          <button type="button" data-cmd="link" title="Link"><?= icone('external') ?></button>
          <button type="button" data-cmd="imagem" title="Inserir imagem"><?= icone('image') ?></button>
          <button type="button" data-cmd="youtube" title="Vídeo do YouTube"><?= icone('youtube') ?></button>
          <button type="button" data-cmd="removeFormat" title="Limpar formatação">✕</button>
        </div>
        <div class="editor-area texto-rico" contenteditable="true" data-placeholder="Escreva a matéria... Você também pode arrastar imagens para dentro do texto."><?= $f['conteudo'] ?></div>
        <textarea name="conteudo" hidden><?= e($f['conteudo']) ?></textarea>
      </div>
    </div>
  </div>
  <aside class="materia-lateral">
    <div class="caixa">
      <label class="check"><input type="checkbox" name="publicado" value="1"<?= $f['publicado'] ? ' checked' : '' ?>> Publicada</label>
      <label class="check"><input type="checkbox" name="destaque" value="1"<?= $f['destaque'] ? ' checked' : '' ?>> Destaque na página inicial</label>
      <label class="campo">Data de publicação<input type="datetime-local" name="publicado_em" value="<?= e(date('Y-m-d\TH:i', strtotime($f['publicado_em']))) ?>"></label>
      <label class="campo">Categoria<input type="text" name="categoria" list="cats" maxlength="60" value="<?= e($f['categoria']) ?>"></label>
      <datalist id="cats"><?php foreach ($categorias as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
      <label class="campo">Endereço (slug)<input type="text" name="slug" maxlength="200" value="<?= e($f['slug']) ?>" placeholder="gerado pelo título"></label>
      <button class="btn btn-primario largo" type="submit"><?= icone('check') ?> Salvar notícia</button>
    </div>
    <div class="caixa"><?= campo_imagem('imagem', $f['imagem'], 'Imagem de capa') ?></div>
    <?php if ($n): ?><button class="btn btn-perigo largo" type="button" data-excluir="noticias" data-id="<?= (int)$n['id'] ?>" data-voltar="<?= e(url('admin/noticias')) ?>"><?= icone('trash') ?> Excluir notícia</button><?php endif; ?>
  </aside>
</form>
<?php
    admin_fim(['editor.js']);
    return;
endif;

// ---------- lista ----------
$busca = trim((string)($_GET['q'] ?? ''));
$pag = max(1, (int)($_GET['pagina'] ?? 1));
$where = $busca !== '' ? 'WHERE titulo LIKE ?' : '';
$params = $busca !== '' ? ["%$busca%"] : [];
$total = (int)q("SELECT COUNT(*) FROM noticias $where", $params)->fetchColumn();
$porPag = 20;
$lista = q("SELECT id, titulo, slug, imagem, categoria, destaque, publicado, publicado_em, visualizacoes FROM noticias $where ORDER BY publicado_em DESC LIMIT $porPag OFFSET " . (($pag - 1) * $porPag), $params)->fetchAll();
admin_inicio('Notícias', 'noticias', '<a class="btn btn-primario" href="' . e(url('admin/noticias/nova')) . '">' . icone('plus') . ' Nova notícia</a>');
?>
<form class="busca-admin" method="get"><?= icone('search') ?><input type="search" name="q" value="<?= e($busca) ?>" placeholder="Buscar pelo título..."><button class="btn btn-fantasma" type="submit">Buscar</button></form>
<?php if (!$lista): ?><p class="vazio">Nenhuma notícia<?= $busca ? ' encontrada' : ' cadastrada' ?>.</p><?php endif; ?>
<div class="lista-itens">
  <?php foreach ($lista as $l): ?>
    <article class="item-linha">
      <div class="item-mini"><?php if ($l['imagem']): ?><img src="<?= e(midia($l['imagem'], true)) ?>" alt=""><?php else: ?><?= icone('news') ?><?php endif; ?></div>
      <div class="item-txt">
        <a href="<?= e(url('admin/noticias/' . $l['id'])) ?>"><b><?= e($l['titulo']) ?></b></a>
        <small><span class="etiqueta"><?= e($l['categoria']) ?></span> <?= date('d/m/Y H:i', strtotime($l['publicado_em'])) ?> · <?= icone('eye') ?> <?= (int)$l['visualizacoes'] ?><?= strtotime($l['publicado_em']) > time() ? ' · <b class="agendada">agendada</b>' : '' ?></small>
      </div>
      <div class="item-acoes">
        <label class="interruptor" title="Destaque"><input type="checkbox" data-alternar="noticias" data-campo="destaque" data-id="<?= (int)$l['id'] ?>"<?= $l['destaque'] ? ' checked' : '' ?>><span></span> <em>Destaque</em></label>
        <label class="interruptor" title="Publicada"><input type="checkbox" data-alternar="noticias" data-campo="publicado" data-id="<?= (int)$l['id'] ?>"<?= $l['publicado'] ? ' checked' : '' ?>><span></span> <em>Publicada</em></label>
        <a class="btn-ic" href="<?= e(url('admin/noticias/' . $l['id'])) ?>" title="Editar"><?= icone('edit') ?></a>
        <button class="btn-ic perigo" type="button" data-excluir="noticias" data-id="<?= (int)$l['id'] ?>" title="Excluir"><?= icone('trash') ?></button>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php if ($total > $porPag): ?>
  <nav class="paginacao-admin"><?php for ($i = 1; $i <= ceil($total / $porPag); $i++): ?><a href="?<?= e(http_build_query(['q' => $busca, 'pagina' => $i])) ?>"<?= $i === $pag ? ' class="ativo"' : '' ?>><?= $i ?></a><?php endfor; ?></nav>
<?php endif; ?>
<?php admin_fim();
