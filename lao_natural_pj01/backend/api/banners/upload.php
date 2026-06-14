<?php
header("Access-Control-Allow-Origin: *");
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

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["message" => "No image file provided or upload error."]);
    exit;
}

$file = $_FILES['image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 8 * 1024 * 1024; // 8MB

// Validate file type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowed_types)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed."]);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(["message" => "File too large. Maximum 8MB allowed."]);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('banner_', true) . '.' . strtolower($ext);

$upload_dir = __DIR__ . '/../../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$destination = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    // Return the URL relative to the backend
    $image_url = 'http://localhost/lao_natural_pj01/backend/uploads/' . $filename;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO hero_banners (image_url) VALUES (?)");
        $stmt->execute([$image_url]);
        $new_id = $pdo->lastInsertId();

        http_response_code(200);
        echo json_encode([
            "message" => "Banner uploaded and registered successfully.",
            "id" => $new_id,
            "image_url" => $image_url
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Failed to save uploaded file."]);
}
?>
