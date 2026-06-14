<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';
require_once '../auth/jwt_helper.php';

// Get headers to verify JWT
$decoded = get_decoded_token();
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. Invalid or missing token."]);
    exit;
}

// Only 'owner' or 'employee' can create products
if ($decoded->data->role !== 'owner' && $decoded->data->role !== 'employee') {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Insufficient permissions."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->name) &&
    !empty($data->price)
) {
    try {
        $query = "INSERT INTO products (name, description, category, size, price, stock, image_url, ingredients) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);

        $name = htmlspecialchars(strip_tags($data->name));
        $desc = htmlspecialchars(strip_tags($data->description ?? ''));
        $category = htmlspecialchars(strip_tags($data->category ?? ''));
        $size = htmlspecialchars(strip_tags($data->size ?? ''));
        $price = htmlspecialchars(strip_tags($data->price));
        $stock = htmlspecialchars(strip_tags($data->stock ?? 0));
        $image_url = htmlspecialchars(strip_tags($data->image_url ?? ''));
        $ingredients = htmlspecialchars(strip_tags($data->ingredients ?? ''));

        if ($stmt->execute([$name, $desc, $category, $size, $price, $stock, $image_url, $ingredients])) {
            http_response_code(201);
            echo json_encode(["message" => "Product created successfully."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to create product."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create product. Name and Price are required."]);
}
?>
