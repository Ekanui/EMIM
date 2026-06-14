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

$is_owner = false;
$headers = apache_request_headers();
if (isset($headers['Authorization'])) {
    $token = str_replace(['Bearer ', 'bearer '], '', $headers['Authorization']);
    $decoded = verify_jwt($token);
    if ($decoded && isset($decoded->data->role) && $decoded->data->role === 'owner') {
        $is_owner = true;
    }
}

$select_columns = $is_owner 
    ? "p.*, u.name as importer_name" 
    : "p.id, p.name, p.description, p.category, p.size, p.price, p.stock, p.image_url, p.ingredients, p.created_at, p.imported_by, u.name as importer_name";

$category = isset($_GET['category']) ? $_GET['category'] : '';

try {
    if ($category) {
        $stmt = $pdo->prepare("SELECT $select_columns FROM products p LEFT JOIN users u ON p.imported_by = u.id WHERE p.category = ? AND p.is_deleted = 0 ORDER BY p.created_at DESC");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT $select_columns FROM products p LEFT JOIN users u ON p.imported_by = u.id WHERE p.is_deleted = 0 ORDER BY p.created_at DESC");
    }

    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Fetch sizes for this product
        $sizesStmt = $pdo->prepare("SELECT * FROM product_sizes WHERE product_id = ? ORDER BY price ASC");
        $sizesStmt->execute([$row['id']]);
        $row['sizes'] = $sizesStmt->fetchAll(PDO::FETCH_ASSOC);
        $products[] = $row;
    }

    http_response_code(200);
    echo json_encode(["data" => $products]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>
