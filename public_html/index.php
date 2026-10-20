<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/icones.php';
require __DIR__ . '/app/layout.php';

cabecalhos_seguranca();

$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (base_path() && str_starts_with($caminho, base_path())) $caminho = substr($caminho, strlen(base_path()));
$caminho = trim(rawurldecode($caminho), '/');
$partes = $caminho === '' ? [] : explode('/', $caminho);

$rotas = [
    '' => 'home',
    'a-radio' => 'sobre',
    'programacao' => 'programacao',
    'noticias' => 'noticias',
    'eventos' => 'eventos',
    'patrocinadores' => 'patrocinadores',
    'contato' => 'contato',
];

$param = null;
if (count($partes) <= 1 && isset($rotas[$partes[0] ?? ''])) {
    $pagina = $rotas[$partes[0] ?? ''];
} elseif (count($partes) === 2 && $partes[0] === 'noticias') {
    $pagina = 'noticia'; $param = $partes[1];
} elseif (count($partes) === 2 && $partes[0] === 'eventos') {
    $pagina = 'evento'; $param = $partes[1];
} else {
    $pagina = '404';
}

renderizar($pagina, $param);
