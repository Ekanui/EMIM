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

$id = isset($_GET['id']) ? $_GET['id'] : die();

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_deleted = 0 LIMIT 1");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch sizes for this product
        $sizesStmt = $pdo->prepare("SELECT * FROM product_sizes WHERE product_id = ? ORDER BY price ASC");
        $sizesStmt->execute([$id]);
        $row['sizes'] = $sizesStmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Product not found."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>
