<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE role = 'employee'");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode(["data" => $employees]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new employee
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
        try {
            $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'employee')";
            $stmt = $pdo->prepare($query);
            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
            
            if ($stmt->execute([htmlspecialchars(strip_tags($data->name)), htmlspecialchars(strip_tags($data->email)), $password_hash])) {
                http_response_code(201);
                echo json_encode(["message" => "Employee added successfully."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to add employee."]);
            }
        } catch (PDOException $e) {
             http_response_code(500);
             echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
            if ($stmt->execute([htmlspecialchars(strip_tags($data->id))])) {
                http_response_code(200);
                echo json_encode(["message" => "Employee removed."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to remove employee."]);
            }
        } catch (PDOException $e) {
             http_response_code(500);
             echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    } else {
         http_response_code(400);
         echo json_encode(["message" => "ID is required."]);
    }
}
?>
