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

try {
    // Select products joined with sum of quantity sold in completed/paid orders
    $query = "
        SELECT 
            p.id, 
            p.name, 
            p.description, 
            p.category, 
            p.size, 
            p.price, 
            p.stock, 
            p.image_url, 
            p.ingredients, 
            p.created_at,
            COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'pending_payment'
        WHERE p.is_deleted = 0
        GROUP BY p.id
        ORDER BY total_sold DESC, p.created_at DESC
        LIMIT 8
    ";
    
    $stmt = $pdo->query($query);
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
