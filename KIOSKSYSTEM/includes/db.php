<?php
// includes/db.php
declare(strict_types=1);

function get_pdo(): PDO {
    static $pdo = null;
    
    $DB_HOST = '127.0.0.1';
    $DB_NAME = 'kiosksystem';
    $DB_USER = 'root';
    $DB_PASS = ''; // Your MySQL password (empty for XAMPP default)
    
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>