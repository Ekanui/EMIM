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

$decoded = get_decoded_token();
if (!$decoded || $decoded->data->role !== 'owner') {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Owner permissions required."]);
    exit;
}

try {
    $stats = [];
    
    // Total Revenue
    $revStmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total_revenue FROM orders WHERE status != 'pending_payment'");
    $stats['total_revenue'] = $revStmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

    // Total Orders
    $ordersStmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $stats['total_orders'] = $ordersStmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

    // Users Count
    $usersStmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
    $stats['total_users'] = $usersStmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Employees Count
    $empStmt = $pdo->query("SELECT COUNT(*) as total_employees FROM users WHERE role = 'employee'");
    $stats['total_employees'] = $empStmt->fetch(PDO::FETCH_ASSOC)['total_employees'];

    // Total Products
    $prodStmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $stats['total_products'] = $prodStmt->fetch(PDO::FETCH_ASSOC)['total_products'];

    // Total Import Cost (Historical record)
    $importRevStmt = $pdo->query("SELECT COALESCE(SUM(import_price * quantity), 0) as total_import_revenue FROM product_imports");
    $stats['total_import_revenue'] = $importRevStmt->fetch(PDO::FETCH_ASSOC)['total_import_revenue'];

    // Low Stock Alert
    $stockStmt = $pdo->query("SELECT id, name, stock FROM products WHERE stock < 10 ORDER BY stock ASC");
    $stats['low_stock'] = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent 5 orders
    $recentStmt = $pdo->query("SELECT o.id, o.status, o.total_price, o.created_at, u.name as customer_name, u.email as customer_email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
    $stats['recent_orders'] = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Orders by status
    $statusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $stats['orders_by_status'] = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    // Popular/Top Selling Products
    $popularStmt = $pdo->query("
        SELECT 
            p.id, 
            p.name, 
            p.price,
            COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'pending_payment'
        GROUP BY p.id
        ORDER BY total_sold DESC, p.created_at DESC
        LIMIT 5
    ");
    $stats['popular_products'] = $popularStmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(["data" => $stats]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>
