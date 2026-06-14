<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT");
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role, address, phone FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["data" => $row]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "User not found."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        $query = "UPDATE users SET name = :name, address = :address, phone = :phone WHERE id = :id";
        $stmt = $pdo->prepare($query);
        
        $stmt->bindParam(':name', htmlspecialchars(strip_tags($data->name ?? '')));
        $stmt->bindParam(':address', htmlspecialchars(strip_tags($data->address ?? '')));
        $stmt->bindParam(':phone', htmlspecialchars(strip_tags($data->phone ?? '')));
        $stmt->bindParam(':id', $user_id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Profile updated successfully."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to update profile."]);
        }
    } catch (PDOException $e) {
         http_response_code(500);
         echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}
?>
