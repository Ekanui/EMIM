<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';
require_once '../auth/jwt_helper.php';

$decoded = get_decoded_token();
if (!$decoded || $decoded->data->role !== 'owner') {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Owner permissions required."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET — List users with optional role filter
if ($method === 'GET') {
    try {
        $role_filter = isset($_GET['role']) ? $_GET['role'] : null;
        
        if ($role_filter && in_array($role_filter, ['employee', 'user', 'owner'])) {
            $stmt = $pdo->prepare("SELECT id, name, email, role, phone, address, profile_picture, created_at FROM users WHERE role = ? ORDER BY created_at DESC");
            $stmt->execute([$role_filter]);
        } else {
            $stmt = $pdo->query("SELECT id, name, email, role, phone, address, profile_picture, created_at FROM users ORDER BY created_at DESC");
        }
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode(["data" => $users]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}

// POST — Create new user (employee or customer)
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
        $role = isset($data->role) && in_array($data->role, ['employee', 'user']) ? $data->role : 'user';
        
        try {
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([htmlspecialchars(strip_tags($data->email))]);
            if ($checkStmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Email already exists."]);
                exit;
            }
            
            $query = "INSERT INTO users (name, email, password, role, phone, address, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
            
            $stmt->execute([
                htmlspecialchars(strip_tags($data->name)),
                htmlspecialchars(strip_tags($data->email)),
                $password_hash,
                $role,
                htmlspecialchars(strip_tags($data->phone ?? '')),
                htmlspecialchars(strip_tags($data->address ?? '')),
                htmlspecialchars(strip_tags($data->profile_picture ?? ''))
            ]);
            
            http_response_code(201);
            echo json_encode(["message" => "User created successfully.", "id" => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Name, email, and password are required."]);
    }
}

// PUT — Update user
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id)) {
        try {
            $fields = [];
            $values = [];
            
            if (!empty($data->name)) {
                $fields[] = "name = ?";
                $values[] = htmlspecialchars(strip_tags($data->name));
            }
            if (!empty($data->email)) {
                $fields[] = "email = ?";
                $values[] = htmlspecialchars(strip_tags($data->email));
            }
            if (!empty($data->role) && in_array($data->role, ['employee', 'user'])) {
                $fields[] = "role = ?";
                $values[] = $data->role;
            }
            if (isset($data->phone)) {
                $fields[] = "phone = ?";
                $values[] = htmlspecialchars(strip_tags($data->phone));
            }
            if (isset($data->address)) {
                $fields[] = "address = ?";
                $values[] = htmlspecialchars(strip_tags($data->address));
            }
            if (isset($data->profile_picture)) {
                $fields[] = "profile_picture = ?";
                $values[] = htmlspecialchars(strip_tags($data->profile_picture));
            }
            if (!empty($data->password)) {
                $fields[] = "password = ?";
                $values[] = password_hash($data->password, PASSWORD_BCRYPT);
            }
            
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(["message" => "No fields to update."]);
                exit;
            }
            
            $values[] = $data->id;
            $query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ? AND role != 'owner'";
            $stmt = $pdo->prepare($query);
            $stmt->execute($values);
            
            http_response_code(200);
            echo json_encode(["message" => "User updated successfully."]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "User ID is required."]);
    }
}

// DELETE — Remove user
elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'owner'");
            $stmt->execute([$data->id]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(["message" => "User deleted successfully."]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "User not found or cannot delete owner."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "User ID is required."]);
    }
}
?>
