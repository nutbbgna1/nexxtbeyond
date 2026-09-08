<?php

$localConfig = __DIR__ . '/db.local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}

if (!defined('ADMIN_DB_HOST')) {
    define('ADMIN_DB_HOST', getenv('ADMIN_DB_HOST') ?: 'localhost');
    define('ADMIN_DB_NAME', getenv('ADMIN_DB_NAME') ?: 'nextbeyond');
    define('ADMIN_DB_USER', getenv('ADMIN_DB_USER') ?: 'root');
    define('ADMIN_DB_PASS', getenv('ADMIN_DB_PASS') ?: '');
    define('ADMIN_DB_CHARSET', getenv('ADMIN_DB_CHARSET') ?: 'utf8mb4');
}

try {
    $dsn = 'mysql:host=' . ADMIN_DB_HOST . ';dbname=' . ADMIN_DB_NAME . ';charset=' . ADMIN_DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, ADMIN_DB_USER, ADMIN_DB_PASS, $options);
} catch (PDOException $e) {
    die('Connection failed. Please check the database configuration.');
}
