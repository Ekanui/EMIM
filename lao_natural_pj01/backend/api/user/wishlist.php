<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. Invalid or expired token."]);
    exit;
}

$user_id = $decoded->data->id;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?");
        $stmt->execute([$user_id]);
        
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = $row;
        }

        http_response_code(200);
        echo json_encode(["data" => $products]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->product_id)) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
            if ($stmt->execute([$user_id, htmlspecialchars(strip_tags($data->product_id))])) {
                http_response_code(201);
                echo json_encode(["message" => "Product added to wishlist."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to add to wishlist."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
             echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Product ID is required."]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->product_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            if ($stmt->execute([$user_id, htmlspecialchars(strip_tags($data->product_id))])) {
                http_response_code(200);
                echo json_encode(["message" => "Product removed from wishlist."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to remove from wishlist."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
             echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Product ID is required."]);
    }
}
?>
