<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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
$role = $decoded->data->role;

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->payment_screenshot)) {
    try {
        // 1. Fetch current order to check status and permissions
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$data->id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(["message" => "Order not found."]);
            exit;
        }

        // 2. Validate permissions: only order owner can re-upload payment
        if ((string)$order['user_id'] !== (string)$user_id) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. You can only update your own orders."]);
            exit;
        }

        // 3. Validate status: only allowed if status is pending_payment or payment_rejected
        if ($order['status'] !== 'pending_payment' && $order['status'] !== 'payment_rejected') {
            http_response_code(400);
            echo json_encode(["message" => "Order payment cannot be updated. It is already prepared, shipped, or received."]);
            exit;
        }

        // 4. Update the order
        $updateQuery = "UPDATE orders SET payment_screenshot = :screenshot, status = 'pending_payment', rejection_reason = NULL WHERE id = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':screenshot', htmlspecialchars(strip_tags($data->payment_screenshot)));
        $updateStmt->bindParam(':id', $data->id);

        if ($updateStmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Payment screenshot updated successfully.", "status" => "pending_payment"]);

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
            $action = "Re-uploaded payment screenshot for order #{$data->id}";
            $logStmt->execute([$user_id, $action]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to update payment screenshot."]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update order. ID and Payment Screenshot are required."]);
}
?>
