<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
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

// Support both DELETE and POST for flexibility
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"));
$id = isset($data->id) ? intval($data->id) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid banner ID."]);
    exit;
}

try {
    // Check if banner exists
    $stmt = $pdo->prepare("SELECT * FROM hero_banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$banner) {
        http_response_code(404);
        echo json_encode(["message" => "Banner not found."]);
        exit;
    }

    // Delete record from database
    $deleteStmt = $pdo->prepare("DELETE FROM hero_banners WHERE id = ?");
    $deleteStmt->execute([$id]);

    // Optional: Extract filename and delete file from /uploads directory
    $img_url = $banner['image_url'];
    if (strpos($img_url, '/backend/uploads/') !== false) {
        $parts = explode('/backend/uploads/', $img_url);
        if (count($parts) > 1) {
            $filename = $parts[1];
            $filepath = __DIR__ . '/../../uploads/' . $filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    http_response_code(200);
    echo json_encode(["message" => "Banner deleted successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>
