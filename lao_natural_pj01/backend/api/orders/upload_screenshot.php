<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../auth/jwt_helper.php';

// Verify JWT
$decoded = get_decoded_token();
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. Invalid or missing token."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["message" => "No screenshot file provided or upload error."]);
    exit;
}

$file = $_FILES['screenshot'];
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
$filename = uniqid('screenshot_', true) . '.' . strtolower($ext);

$upload_dir = __DIR__ . '/../../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$destination = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    // Return the URL relative to the backend
    $screenshot_url = 'http://localhost/lao_natural_pj01/backend/uploads/' . $filename;
    
    http_response_code(200);
    echo json_encode([
        "message" => "Screenshot uploaded successfully.",
        "screenshot_url" => $screenshot_url,
        "filename" => $filename
    ]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Failed to save uploaded file."]);
}
?>
