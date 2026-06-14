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

$decoded = get_decoded_token();
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. Invalid or expired token."]);
    exit;
}

$user_id = $decoded->data->id;
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->recipient_name) && !empty($data->phone) && !empty($data->address_text)) {
    try {
        $query = "INSERT INTO user_addresses (user_id, recipient_name, phone, address_text, is_default) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        
        $name = htmlspecialchars(strip_tags($data->recipient_name));
        $phone = htmlspecialchars(strip_tags($data->phone));
        $address = htmlspecialchars(strip_tags($data->address_text));
        $is_default = isset($data->is_default) && $data->is_default ? 1 : 0;
        
        // If this is the new default, unset other defaults
        if ($is_default) {
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }

        if ($stmt->execute([$user_id, $name, $phone, $address, $is_default])) {
            $last_id = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(["message" => "Address created.", "id" => $last_id]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to create address."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data."]);
}
?>
