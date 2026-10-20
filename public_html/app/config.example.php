<?php
// Copie este arquivo para ../config.php (fora do public_html, recomendado) ou app/config.php.
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'nome' => 'NOME_DO_BANCO',
        'usuario' => 'USUARIO',
        'senha' => 'SENHA',
    ],
    'base_path' => '',            // '' se o site estiver na raiz do domínio; ex.: '/site' se estiver numa subpasta
    'timezone' => 'America/Sao_Paulo',
    'debug' => false,
];
