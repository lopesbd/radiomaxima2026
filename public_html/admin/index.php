<?php
declare(strict_types=1);
// Roteador do painel (/admin/...).
require __DIR__ . '/_nucleo.php';

$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (base_path() && str_starts_with($caminho, base_path())) $caminho = substr($caminho, strlen(base_path()));
$partes = array_values(array_filter(explode('/', trim($caminho, '/'))));
array_shift($partes); // "admin"
$secao = $partes[0] ?? '';
$id = $partes[1] ?? null;

if ($secao === 'login') { require __DIR__ . '/pages/login.php'; exit; }
if ($secao === 'sair') {
    exigir_post_csrf();
    $_SESSION = [];
    session_regenerate_id(true);
    redirecionar('admin/login');
}

exigir_login();

$paginas = ['' => 'painel', 'banners' => 'banners', 'programacao' => 'programacao', 'noticias' => 'noticias', 'eventos' => 'eventos',
            'patrocinadores' => 'patrocinadores', 'mensagens' => 'mensagens', 'configuracoes' => 'configuracoes', 'usuarios' => 'usuarios', 'api' => 'api'];
if (!isset($paginas[$secao])) { http_response_code(404); admin_inicio('Não encontrado', ''); echo '<p>Página não encontrada.</p>'; admin_fim(); exit; }

try {
    require __DIR__ . '/pages/' . $paginas[$secao] . '.php';
} catch (RuntimeException $e) {
    // erros de validação/upload em formulários comuns
    flash($e->getMessage(), 'erro');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('admin')));
}
