<?php
// Handles database connection with getDB() wrapper and API JSON handling

function getDB() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';

    $host    = $config['db_host'] ?? 'localhost';
    $dbname  = $config['db_name'] ?? 'dormfinder';
    $user    = $config['db_user'] ?? 'root';
    $pass    = $config['db_pass'] ?? '';
    $charset = $config['db_charset'] ?? 'utf8mb4';

    try {
        $dsn = "mysql:host={$host};port=3306;dbname={$dbname};charset={$charset}";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        $isApi = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
        
        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
            exit;
        }

        if (!empty($config['debug'])) {
            die("Database error: " . $e->getMessage());
        }

        die("Unable to connect to the database. Please try again later.");
    }
}

// Global $pdo variable for pages expecting $pdo directly
$pdo = getDB();