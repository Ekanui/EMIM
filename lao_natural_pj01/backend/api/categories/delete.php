<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';
require_once '../auth/jwt_helper.php';

$decoded = get_decoded_token();

if (!$decoded || ($decoded->data->role !== 'owner' && $decoded->data->role !== 'employee')) {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$data->id])) {
            http_response_code(200);
            echo json_encode(["message" => "Category deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete category."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. ID is required."]);
}
?>
