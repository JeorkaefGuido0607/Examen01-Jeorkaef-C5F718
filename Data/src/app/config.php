<?php
$container->set('config_bd', function () {
    $host = $_ENV['DB_HOST'] ?? 'jggdb';
    $port = $_ENV['DB_PORT'] ?? '3306';

    return (object) [
        'host' => $host,
        'port' => $port,
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASSW'] ?? '',
        'database' => $_ENV['DB_NAME'] ?? 'db_salon',
        'charset' => 'utf8mb4',
    ];
});
