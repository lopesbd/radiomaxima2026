<?php
// Dados da rádio, contatos, redes sociais, stream e textos da página "A Rádio".
$campos = [
    'Rádio' => [
        'nome' => ['Nome da rádio', 'text'],
        'frequencia' => ['Frequência', 'text'],
        'slogan' => ['Slogan', 'text'],
        'cidade' => ['Cidade/UF', 'text'],
        'stream_url' => ['Endereço do stream (áudio ao vivo)', 'url'],
        'alcance' => ['Alcance (ex.: 1,4 milhão)', 'text'],
        'localidades' => ['Nº de localidades', 'text'],
    ],
    'Contato e redes' => [
        'telefone' => ['Telefone', 'text'],
        'whatsapp' => ['WhatsApp (só números, com DDI 55 e DDD)', 'text'],
        'email' => ['E-mail', 'email'],
        'endereco' => ['Endereço', 'text'],
        'instagram' => ['Instagram (link)', 'url'],
        'facebook' => ['Facebook (link)', 'url'],
        'youtube' => ['YouTube (link)', 'url'],
    ],
    'Aplicativo' => [
        'app_android' => ['Link do app na Google Play (Android)', 'url'],
        'app_ios' => ['Link do app na App Store (iPhone)', 'url'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post_csrf();
    $salvar = [];
    foreach ($campos as $grupo) foreach ($grupo as $k => $_) $salvar[$k] = mb_substr(trim((string)($_POST[$k] ?? '')), 0, 500);
    $salvar['sobre_titulo'] = mb_substr(trim((string)($_POST['sobre_titulo'] ?? '')), 0, 160);
    $salvar['sobre_texto'] = limpar_html((string)($_POST['sobre_texto'] ?? ''));
    $salvar['cidades'] = trim((string)($_POST['cidades'] ?? ''));
    $salvar['formatos'] = trim((string)($_POST['formatos'] ?? ''));
    if ($salvar['stream_url'] && !preg_match('#^https?://#', $salvar['stream_url'])) throw new RuntimeException('O endereço do stream deve começar com http:// ou https://');
    $st = db()->prepare('INSERT INTO config (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
    foreach ($salvar as $k => $v) $st->execute([$k, $v]);
    flash('Configurações salvas.');
    redirecionar('admin/configuracoes');
}

admin_inicio('Configurações', 'configuracoes');
?>
<form method="post" data-form-editor>
  <?= campo_csrf() ?>
  <?php foreach ($campos as $grupo => $lista): ?>
    <section class="caixa">
      <div class="caixa-cab"><h2><?= e($grupo) ?></h2></div>
      <div class="grade-campos">
        <?php foreach ($lista as $k => [$rot, $tipo]): ?>
          <label class="campo<?= $k === 'stream_url' ? ' largo' : '' ?>"><?= e($rot) ?><input type="<?= $tipo ?>" name="<?= $k ?>" value="<?= e(cfg($k)) ?>"></label>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
  <section class="caixa">
    <div class="caixa-cab"><h2>Página “A Rádio”</h2><a href="<?= e(url('/a-radio')) ?>" target="_blank" rel="noopener">Ver página</a></div>
    <label class="campo">Título<input type="text" name="sobre_titulo" value="<?= e(cfg('sobre_titulo')) ?>"></label>
    <div class="campo"><span class="rotulo">Texto</span>
      <div class="editor" data-editor>
        <div class="editor-barra" role="toolbar">
          <button type="button" data-cmd="bold"><b>B</b></button>
          <button type="button" data-cmd="italic"><i>I</i></button>
          <button type="button" data-cmd="formatBlock" data-val="h3">H3</button>
          <button type="button" data-cmd="formatBlock" data-val="p">¶</button>
          <button type="button" data-cmd="insertUnorderedList">• Lista</button>
          <button type="button" data-cmd="link"><?= icone('external') ?></button>
          <button type="button" data-cmd="imagem"><?= icone('image') ?></button>
        </div>
        <div class="editor-area texto-rico" contenteditable="true"><?= cfg('sobre_texto') ?></div>
        <textarea name="sobre_texto" hidden></textarea>
      </div>
    </div>
    <div class="linha2">
      <label class="campo">Cidades da área de cobertura (uma por linha)<textarea name="cidades" rows="10"><?= e(cfg('cidades')) ?></textarea></label>
      <label class="campo">Formatos para anunciantes (um por linha)<textarea name="formatos" rows="10"><?= e(cfg('formatos')) ?></textarea></label>
    </div>
  </section>
  <div class="barra-salvar"><button class="btn btn-primario" type="submit"><?= icone('check') ?> Salvar configurações</button></div>
</form>
<?php admin_fim(['editor.js']);
