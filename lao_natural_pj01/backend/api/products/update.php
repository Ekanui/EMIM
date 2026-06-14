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

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        $fields = [];
        $params = [];

        // Dynamic fields mapping
        $updatable_fields = [
            'name' => 's',
            'description' => 's',
            'category' => 's',
            'size' => 's',
            'price' => 'd',
            'import_price' => 'd',
            'stock' => 'i',
            'ingredients' => 's',
            'image_url' => 's'
        ];

        foreach ($updatable_fields as $field => $type) {
            if (isset($data->$field)) {
                $fields[] = "$field = ?";
                $params[] = htmlspecialchars(strip_tags($data->$field));
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["message" => "No fields provided for update."]);
            exit;
        }

        $params[] = $data->id;
        $query = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);

        if ($stmt->execute($params)) {
            
            // If sizes list is provided, update product_sizes table
            if (isset($data->sizes) && is_array($data->sizes)) {
                // Delete existing sizes
                $delStmt = $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
                $delStmt->execute([$data->id]);

                // Fetch base prices to calculate proportional import prices if needed
                $baseStmt = $pdo->prepare("SELECT price, import_price FROM products WHERE id = ?");
                $baseStmt->execute([$data->id]);
                $baseProd = $baseStmt->fetch(PDO::FETCH_ASSOC);
                $basePrice = $baseProd ? floatval($baseProd['price']) : 1.0;
                $baseImportPrice = $baseProd ? floatval($baseProd['import_price']) : 0.0;
                $importProportion = $baseImportPrice / ($basePrice ?: 1.0);

                if (!empty($data->sizes)) {
                    $insertSzStmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size, price, import_price, stock) VALUES (?, ?, ?, ?, ?)");
                    foreach ($data->sizes as $sz) {
                        $sz_name = htmlspecialchars(strip_tags($sz->size));
                        $sz_price = floatval($sz->price);
                        $sz_stock = intval($sz->stock);
                        $sz_import = isset($sz->import_price) ? floatval($sz->import_price) : ($sz_price * $importProportion);
                        
                        $insertSzStmt->execute([$data->id, $sz_name, $sz_price, $sz_import, $sz_stock]);
                    }

                    // Mirror the first size to the main products table!
                    $firstSz = $data->sizes[0];
                    $mirrorStmt = $pdo->prepare("UPDATE products SET size = ?, price = ?, stock = ? WHERE id = ?");
                    $mirrorStmt->execute([
                        htmlspecialchars(strip_tags($firstSz->size)),
                        floatval($firstSz->price),
                        intval($firstSz->stock),
                        $data->id
                    ]);
                }
            }

            if (!empty($data->import_date) && !empty($data->supplier_name)) {
                $import_date = htmlspecialchars(strip_tags($data->import_date));
                $supplier_name = htmlspecialchars(strip_tags($data->supplier_name));
                $import_price = isset($data->import_price) ? htmlspecialchars(strip_tags($data->import_price)) : 0;
                $stock = isset($data->stock) ? htmlspecialchars(strip_tags($data->stock)) : 0;
                
                $importStmt = $pdo->prepare("INSERT INTO product_imports (product_id, import_date, supplier_name, quantity, import_price) VALUES (?, ?, ?, ?, ?)");
                $importStmt->execute([$data->id, $import_date, $supplier_name, $stock, $import_price]);
            }

            http_response_code(200);
            echo json_encode(["message" => "Product was updated."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to update product."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update product. ID is required."]);
}
?>
