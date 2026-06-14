<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';
require_once './jwt_helper.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([htmlspecialchars(strip_tags($data->email))]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $row['id'];
            $name = $row['name'];
            $email = $row['email'];
            $hashed_password = $row['password'];
            $role = $row['role'];

            if (password_verify($data->password, $hashed_password)) {
                
                // Set expiry to 24 hours
                $payload = [
                    "iss" => "lao_natural_essentials_api",
                    "iat" => time(),
                    "exp" => time() + (60 * 60 * 24),
                    "data" => [
                        "id" => $id,
                        "name" => $name,
                        "email" => $email,
                        "role" => $role
                    ]
                ];

                $jwt = generate_jwt($payload);

                http_response_code(200);
                echo json_encode([
                    "message" => "Successful login.",
                    "jwt" => $jwt,
                    "user" => [
                        "id" => $id,
                        "name" => $name,
                        "email" => $email,
                        "role" => $role
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Login failed. Password is incorrect."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Login failed. User not found."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data."]);
}
?>
