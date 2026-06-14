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

$data = json_decode(file_get_contents("php://input"));
$user_id = $decoded->data->id;
$total_price = 0;

if (!empty($data->items) && is_array($data->items)) {
    try {
        $pdo->beginTransaction();

        foreach ($data->items as $item) {
            // Check stock before proceeding
            $sizeCheckStmt = $pdo->prepare("SELECT id, stock FROM product_sizes WHERE product_id = ? AND size = ? FOR UPDATE");
            $sizeCheckStmt->execute([$item->id, $item->selectedSize ?? '']);
            $sizeProduct = $sizeCheckStmt->fetch(PDO::FETCH_ASSOC);

            if ($sizeProduct) {
                if ($sizeProduct['stock'] < $item->quantity) {
                    throw new Exception("Insufficient stock for size " . ($item->selectedSize ?? 'Standard') . " of product.");
                }
            } else {
                $stockStmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
                $stockStmt->execute([$item->id]);
                $product = $stockStmt->fetch(PDO::FETCH_ASSOC);

                if (!$product || $product['stock'] < $item->quantity) {
                    throw new Exception("Insufficient stock for product: " . ($product['name'] ?? 'Unknown'));
                }
            }

            $total_price += ($item->price * $item->quantity);
        }

        // Apply coupon if exists (mocking simple logic for now)
        if (!empty($data->coupon)) {
            // Check coupon table, apply discount...
        }

        // Calculate 10% VAT
        $vat = $total_price * 0.10;
        $total_price += $vat;

        $shipping_cost = isset($data->shipping_cost) ? floatval($data->shipping_cost) : 0.00;
        $total_price += $shipping_cost;

        $query = "INSERT INTO orders (user_id, status, total_price, shipping_name, shipping_phone, shipping_address, province, shipping_cost, payment_screenshot) VALUES (?, 'pending_payment', ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $s_name = htmlspecialchars(strip_tags($data->shipping_name ?? ''));
        $s_phone = htmlspecialchars(strip_tags($data->shipping_phone ?? ''));
        $s_address = htmlspecialchars(strip_tags($data->shipping_address ?? ''));
        $province = htmlspecialchars(strip_tags($data->province ?? ''));
        $screenshot = htmlspecialchars(strip_tags($data->payment_screenshot ?? ''));
        
        $stmt->execute([$user_id, $total_price, $s_name, $s_phone, $s_address, $province, $shipping_cost, $screenshot]);
        $order_id = $pdo->lastInsertId();


        $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price, size) VALUES (?, ?, ?, ?, ?)";
        $itemStmt = $pdo->prepare($itemQuery);

        $updateStockQuery = "UPDATE products SET stock = stock - ? WHERE id = ?";
        $updateStockStmt = $pdo->prepare($updateStockQuery);

        $updateSizeStockQuery = "UPDATE product_sizes SET stock = stock - ? WHERE product_id = ? AND size = ?";
        $updateSizeStockStmt = $pdo->prepare($updateSizeStockQuery);

        foreach ($data->items as $item) {
            $itemStmt->execute([$order_id, $item->id, $item->quantity, $item->price, $item->selectedSize ?? NULL]);
            
            // Check if sizes exist to deduct stock from product_sizes
            $sizeCheckStmt = $pdo->prepare("SELECT id FROM product_sizes WHERE product_id = ? AND size = ?");
            $sizeCheckStmt->execute([$item->id, $item->selectedSize ?? '']);
            if ($sizeCheckStmt->fetch()) {
                $updateSizeStockStmt->execute([$item->quantity, $item->id, $item->selectedSize ?? '']);
                
                // Keep the products table in sync with the first size's stock/details
                $firstSzStmt = $pdo->prepare("SELECT size, price, stock FROM product_sizes WHERE product_id = ? ORDER BY id ASC LIMIT 1");
                $firstSzStmt->execute([$item->id]);
                $firstSz = $firstSzStmt->fetch(PDO::FETCH_ASSOC);
                if ($firstSz) {
                    $mirrorStmt = $pdo->prepare("UPDATE products SET size = ?, price = ?, stock = ? WHERE id = ?");
                    $mirrorStmt->execute([$firstSz['size'], $firstSz['price'], $firstSz['stock'], $item->id]);
                }
            } else {
                $updateStockStmt->execute([$item->quantity, $item->id]);
            }
        }

        $pdo->commit();

        http_response_code(201);
        echo json_encode(["message" => "Order created successfully.", "order_id" => $order_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(400); // 400 Bad Request for business logic errors like stock
        echo json_encode(["message" => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create order. Cart is empty."]);
}
?>
