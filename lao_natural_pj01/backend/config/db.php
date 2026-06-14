<?php
$host    = getenv('DB_HOST') ?: 'sql207.infinityfree.com';
$db      = getenv('DB_NAME') ?: 'if0_42179849_mim';
$user    = getenv('DB_USER') ?: 'if0_42179849';
$pass    = getenv('DB_PASS') ?: 'hbdmimphaphin69';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>
