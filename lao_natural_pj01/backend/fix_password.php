<?php
require_once __DIR__ . '/config/db.php';

$newPassword = password_hash('owner123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'owner@laonatural.com'");
$stmt->execute([$newPassword]);

echo json_encode([
    'success' => true,
    'message' => 'Password reset to owner123'
]);
