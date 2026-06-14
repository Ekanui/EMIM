<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/db.php';
require_once '../auth/jwt_helper.php';

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

try {
    $stmt = $pdo->query("SELECT al.*, u.name as user_name, u.role as user_role FROM activity_log al JOIN users u ON al.user_id = u.id ORDER BY al.timestamp DESC LIMIT 100");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(["data" => $logs]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>
