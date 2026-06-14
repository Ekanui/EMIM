<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

try {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    
    $addresses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $addresses[] = $row;
    }

    http_response_code(200);
    echo json_encode(["data" => $addresses]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>
