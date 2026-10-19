<?php
// Database connection using PDO with prepared statements (prevents SQL injection).

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile)) {
        http_response_code(500);
        exit('Configuration file missing. Copy includes/config.example.php to includes/config.php.');
    }
    $config = require $configFile;

    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Unable to connect to the database. Please try again later.');
    }

    return $pdo;
}
