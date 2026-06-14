<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';
require_once '../auth/jwt_helper.php';

$decoded = get_decoded_token();
if (!$decoded || ($decoded->data->role !== 'owner' && $decoded->data->role !== 'employee')) {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Insufficient permissions."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->status)) {
    try {
        $rejection_reason = ($data->status === 'payment_rejected' && !empty($data->rejection_reason)) ? htmlspecialchars(strip_tags($data->rejection_reason)) : null;

        $query = "UPDATE orders SET status = :status, express_tracking = :tracking, express_company = :company, rejection_reason = :rejection_reason WHERE id = :id";
        $stmt = $pdo->prepare($query);

        $stmt->bindParam(':status', htmlspecialchars(strip_tags($data->status)));
        $stmt->bindParam(':tracking', htmlspecialchars(strip_tags($data->tracking ?? '')));
        $stmt->bindParam(':company', htmlspecialchars(strip_tags($data->company ?? '')));
        $stmt->bindParam(':rejection_reason', $rejection_reason);
        $stmt->bindParam(':id', htmlspecialchars(strip_tags($data->id)));

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Order updated successfully."]);
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
            $action = "Updated order #{$data->id} status to {$data->status}";
            $logStmt->execute([$decoded->data->id, $action]);

            // Real application would also insert notification for user here and trigger email.

        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to update order."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update order. ID and Status are required."]);
}
?>
