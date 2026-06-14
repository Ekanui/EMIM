<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';
require_once '../auth/jwt_helper.php';

// Verify JWT
$decoded = get_decoded_token();
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. Invalid or missing token."]);
    exit;
}

if ($decoded->data->role !== 'owner' && $decoded->data->role !== 'employee') {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Insufficient permissions."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->key) || !isset($data->value)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid inputs. Missing key or value."]);
    exit;
}

$key = trim($data->key);
$value = trim($data->value);

if ($key === '') {
    http_response_code(400);
    echo json_encode(["message" => "Setting key cannot be empty."]);
    exit;
}

try {
    // Insert or update setting
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([$key, $value, $value]);

    http_response_code(200);
    echo json_encode(["message" => "Setting updated successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>
