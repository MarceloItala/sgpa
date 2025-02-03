<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'name' => $_ENV['DB_NAME'] ?? 'sgpa',
            'user' => $_ENV['DB_USER'] ?? 'sgpa',
            'pass' => $_ENV['DB_PASS'] ?? 'Bit@12120',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'name' => $_ENV['DB_NAME'] ?? 'sgpa',
            'user' => $_ENV['DB_USER'] ?? 'sgpa',
            'pass' => $_ENV['DB_PASS'] ?? 'Bit@12120',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
    'version_order' => 'creation'
];
