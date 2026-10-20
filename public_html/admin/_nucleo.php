<?php
declare(strict_types=1);
// Núcleo do painel: login, proteção e layout.

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/icones.php';

iniciar_sessao();
header('X-Robots-Tag: noindex, nofollow');
cabecalhos_seguranca();

function usuario(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $l = q('SELECT id, nome, email, sessao_versao FROM usuarios WHERE id = ?', [$_SESSION['uid']])->fetch();
        if ($l && (int)$l['sessao_versao'] === (int)($_SESSION['sv'] ?? 0)) $u = $l;
        else unset($_SESSION['uid']);
    }
    return $u;
}

function exigir_login(): array
{
    $u = usuario();
    if (!$u) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || !empty($_SERVER['HTTP_X_CSRF'])) json_resposta(['erro' => 'Sessão expirada. Entre novamente.'], 401);
        header('Location: ' . url('admin/login'));
        exit;
    }
    return $u;
}

function exigir_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? null))) {
        http_response_code(400);
        exit('Requisição inválida (token de segurança). Volte e recarregue a página.');
    }
}

function flash(?string $msg = null, string $tipo = 'ok')
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $tipo]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function menu_admin(): array
{
    $naoLidas = (int)q('SELECT COUNT(*) FROM mensagens WHERE lida = 0')->fetchColumn();
    return [
        '' => ['Painel', 'home'],
        'banners' => ['Banners', 'image'],
        'programacao' => ['Programação', 'radio'],
        'noticias' => ['Notícias', 'news'],
        'eventos' => ['Eventos', 'camera'],
        'patrocinadores' => ['Patrocinadores', 'star'],
        'mensagens' => ['Mensagens' . ($naoLidas ? " <b class=\"badge\">$naoLidas</b>" : ''), 'inbox'],
        'configuracoes' => ['Configurações', 'settings'],
        'usuarios' => ['Usuários', 'users'],
    ];
}

function admin_inicio(string $titulo, string $ativo, string $acoes = ''): void
{
    $u = usuario();
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> · Painel Máxima</title>
<meta name="robots" content="noindex">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<meta name="base" content="<?= e(base_path()) ?>">
<link rel="icon" href="<?= e(url('assets/img/logo.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<aside class="lateral" id="lateral">
  <a class="lat-marca" href="<?= e(url('admin')) ?>"><img src="<?= e(url('assets/img/logo.png')) ?>" alt="" width="44" height="44"><span>Painel<b>Máxima FM</b></span></a>
  <nav>
    <?php foreach (menu_admin() as $chave => [$rot, $ic]): ?>
      <a href="<?= e(url('admin/' . $chave)) ?>"<?= $ativo === $chave ? ' class="ativo"' : '' ?>><?= icone($ic) ?><span><?= $rot ?></span></a>
    <?php endforeach; ?>
  </nav>
  <div class="lat-rodape">
    <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icone('external') ?><span>Ver o site</span></a>
    <form method="post" action="<?= e(url('admin/sair')) ?>"><?= campo_csrf() ?><button type="submit"><?= icone('logout') ?><span>Sair (<?= e(explode(' ', $u['nome'])[0]) ?>)</span></button></form>
  </div>
</aside>
<div class="conteudo">
  <header class="barra">
    <button class="btn-lateral" type="button" id="btnLateral" aria-label="Menu"><?= icone('menu') ?></button>
    <h1><?= e($titulo) ?></h1>
    <div class="barra-acoes"><?= $acoes ?></div>
  </header>
  <?php if ($f = flash()): ?><div class="aviso aviso-<?= e($f[1]) ?>" data-some><?= e($f[0]) ?></div><?php endif; ?>
  <main class="pagina">
<?php
}

function admin_fim(array $scripts = []): void
{
    ?>
  </main>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="<?= e(asset('js/arrastar.js')) ?>"></script>
<script src="<?= e(asset('js/admin.js')) ?>"></script>
<?php foreach ($scripts as $s): ?><script src="<?= e(asset('js/' . $s)) ?>"></script><?php endforeach; ?>
</body>
</html>
<?php
}

/** Área de upload de uma imagem com arrastar-e-soltar e prévia. */
function campo_imagem(string $nome, ?string $atual, string $rotulo = 'Imagem', string $dica = 'Arraste uma imagem aqui ou clique para escolher'): string
{
    $prev = $atual ? '<img src="' . e(midia($atual, true)) . '" alt="">' : '';
    return '<div class="campo"><span class="rotulo">' . e($rotulo) . '</span>
      <label class="soltar-imagem' . ($atual ? ' tem-imagem' : '') . '" data-soltar-imagem>
        <input type="file" name="' . e($nome) . '" accept="image/jpeg,image/png,image/webp,image/gif">
        <span class="prev">' . $prev . '</span>
        <span class="soltar-txt">' . icone('upload') . '<b>' . e($dica) . '</b><small>JPG, PNG ou WebP · até 15 MB</small></span>
      </label>' . ($atual ? '<label class="check"><input type="checkbox" name="' . e($nome) . '_remover" value="1"> Remover imagem atual</label>' : '') . '</div>';
}

/** Processa o campo de imagem do formulário: devolve o novo caminho (ou o atual). */
function processar_imagem(string $nome, ?string $atual, int $largura = 1920): ?string
{
    if (!empty($_FILES[$nome]) && $_FILES[$nome]['error'] !== UPLOAD_ERR_NO_FILE) {
        $novo = salvar_imagem($_FILES[$nome], $largura);
        apagar_imagem($atual);
        return $novo;
    }
    if (!empty($_POST[$nome . '_remover'])) { apagar_imagem($atual); return null; }
    return $atual;
}
