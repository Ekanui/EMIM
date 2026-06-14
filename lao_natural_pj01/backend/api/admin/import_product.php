<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';
require_once '../auth/jwt_helper.php';

// Verify JWT and owner role
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied."]);
    exit;
}

$decoded = verify_jwt(str_replace('Bearer ', '', $headers['Authorization']));
if (!$decoded || $decoded->data->role !== 'owner') {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Owner permissions required."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->price)) {
    try {
        $query = "INSERT INTO products (name, size, import_price, price, stock, image_url, imported_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);

        $name = htmlspecialchars(strip_tags($data->name));
        $size = htmlspecialchars(strip_tags($data->size ?? ''));
        $import_price = htmlspecialchars(strip_tags($data->import_price ?? 0));
        $price = htmlspecialchars(strip_tags($data->price));
        $stock = htmlspecialchars(strip_tags($data->stock ?? 0));
        $image_url = htmlspecialchars(strip_tags($data->image_url ?? ''));
        $imported_by = $decoded->data->id;
        
        $import_date = htmlspecialchars(strip_tags($data->import_date ?? date('Y-m-d H:i:s')));
        $supplier_name = htmlspecialchars(strip_tags($data->supplier_name ?? 'Unknown'));

        $pdo->beginTransaction();

        if ($stmt->execute([$name, $size, $import_price, $price, $stock, $image_url, $imported_by])) {
            $product_id = $pdo->lastInsertId();
            
            $importStmt = $pdo->prepare("INSERT INTO product_imports (product_id, import_date, supplier_name, quantity, import_price) VALUES (?, ?, ?, ?, ?)");
            $importStmt->execute([$product_id, $import_date, $supplier_name, $stock, $import_price]);
            $import_id = $pdo->lastInsertId();
            
            $pdo->commit();

            http_response_code(201);
            echo json_encode(["message" => "Product imported successfully.", "id" => $product_id, "import_id" => $import_id]);
        } else {
            $pdo->rollBack();
            http_response_code(503);
            echo json_encode(["message" => "Unable to import product."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Product name and price are required."]);
}
?>
