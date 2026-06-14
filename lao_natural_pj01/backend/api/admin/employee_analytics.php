<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';
require_once '../auth/jwt_helper.php';

$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied."]);
    exit;
}

$decoded = verify_jwt(str_replace('Bearer ', '', $headers['Authorization']));
if (!$decoded || ($decoded->data->role !== 'owner' && $decoded->data->role !== 'employee')) {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Staff permissions required."]);
    exit;
}

try {
    $stats = [];

    // Pending orders count
    $pendingStmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status IN ('pending_payment', 'prepare')");
    $stats['pending_orders'] = $pendingStmt->fetch(PDO::FETCH_ASSOC)['pending_orders'] ?? 0;

    // Total products count
    $productsStmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $stats['total_products'] = $productsStmt->fetch(PDO::FETCH_ASSOC)['total_products'] ?? 0;

    // Shipped today
    $shippedStmt = $pdo->query("SELECT COUNT(*) as shipped_today FROM orders WHERE status = 'sending' AND DATE(created_at) = CURDATE()");
    $stats['shipped_today'] = $shippedStmt->fetch(PDO::FETCH_ASSOC)['shipped_today'] ?? 0;

    // Orders by status
    $statusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $stats['orders_by_status'] = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent 5 orders
    $recentStmt = $pdo->query("SELECT o.id, o.status, o.total_price, o.created_at, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
    $stats['recent_orders'] = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(["data" => $stats]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>
