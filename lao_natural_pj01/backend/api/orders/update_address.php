<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

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

if (!empty($data->id) && !empty($data->shipping_name) && !empty($data->shipping_phone) && !empty($data->shipping_address)) {
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

        // 2. Validate permissions: only order owner or employee/owner can edit
        if ($role !== 'owner' && $role !== 'employee' && (string)$order['user_id'] !== (string)$user_id) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. You can only edit your own orders."]);
            exit;
        }

        // 3. Validate status: only allowed if status is pending_payment or prepare (not sending or received)
        if ($order['status'] !== 'pending_payment' && $order['status'] !== 'prepare') {
            http_response_code(400);
            echo json_encode(["message" => "Order cannot be edited. It has already been shipped or received."]);
            exit;
        }

        // 4. Update the shipping details
        $updateQuery = "UPDATE orders SET shipping_name = :name, shipping_phone = :phone, shipping_address = :address WHERE id = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':name', htmlspecialchars(strip_tags($data->shipping_name)));
        $updateStmt->bindParam(':phone', htmlspecialchars(strip_tags($data->shipping_phone)));
        $updateStmt->bindParam(':address', htmlspecialchars(strip_tags($data->shipping_address)));
        $updateStmt->bindParam(':id', $data->id);

        if ($updateStmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Shipping details updated successfully."]);

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
            $action = "Updated order #{$data->id} shipping details";
            $logStmt->execute([$user_id, $action]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to update shipping details."]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update order. ID, Name, Phone, and Address are required."]);
}
?>
