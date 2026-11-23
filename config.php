<?php
declare(strict_types=1);

/*
 * Configuração local. Não publique este arquivo em repositórios públicos.
 * Altere os dados do banco conforme sua hospedagem.
 */
return [
    'db' => [
    'host' => 'mundocriarte.mysql.dbaas.com.br',
    'port' => '3306',
    'name' => 'mundocriarte',
    'user' => 'mundocriarte',
    'pass' => 'Admim2020@',
    'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'F&J Artes Presentes',
        'base_url' => 'https://mundocriarte.com.br',
        'timezone' => 'America/Sao_Paulo',
        'max_upload_mb' => 12,
    ],
];
