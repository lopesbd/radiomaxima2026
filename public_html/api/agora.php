<?php
declare(strict_types=1);
// Programa no ar agora + próximos. Usado pelo player e pelas páginas (atualiza sozinho).
require dirname(__DIR__) . '/app/bootstrap.php';

$a = no_ar(3);
header('Cache-Control: public, max-age=20');
json_resposta([
    'atual' => programa_json($a['atual']),
    'proximos' => array_map('programa_json', $a['proximos']),
    'dia' => $a['dia'],
    'minuto' => $a['minuto'],
    'segundo' => $a['segundo'],
    'radio' => cfg('nome'),
    'slogan' => cfg('slogan'),
]);
