<?php
require __DIR__ . '/../../config/db.php';

try {
    $stmt = $pdo->query("SELECT DATABASE()");
    $row = $stmt->fetch();
    echo "Connected to database: " . $row['DATABASE()'];
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>