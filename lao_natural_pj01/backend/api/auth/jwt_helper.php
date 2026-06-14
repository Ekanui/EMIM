<?php
// backend/api/auth/jwt_helper.php

// A simple symmetric JWT implementation for our API
// In production, use a secure secret and a verified library like firebase/php-jwt
define('JWT_SECRET', 'lao_natural_super_secret_key_123!@#');

function generate_jwt($payload, $secret = JWT_SECRET) {
    // Header
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    // Encode Header
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // Encode Payload
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    // Encode Signature
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Create JWT
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Get header Authorization
 * */
function get_authorization_header(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast PHP
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old versions of Apache
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

/**
 * get access token from header
 * */
function get_bearer_token() {
    $headers = get_authorization_header();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Get decoded token or null
 * */
function get_decoded_token() {
    $token = get_bearer_token();
    if (!$token) return null;
    return verify_jwt($token);
}

function verify_jwt($jwt, $secret = JWT_SECRET) {
    // split the jwt
    $tokenParts = explode('.', $jwt);
    if(count($tokenParts) != 3) return false;
    
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
    $signature_provided = $tokenParts[2];

    // check the expiration time
    $decoded_payload = json_decode($payload);
    if(isset($decoded_payload->exp) && ($decoded_payload->exp - time()) < 0) {
        return false;
    }

    // build a signature based on the header and payload using the secret
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // verify it matches the signature provided in the jwt
    $is_signature_valid = ($base64UrlSignature === $signature_provided);
    
    if($is_signature_valid) {
        return $decoded_payload;
    } else {
        return false;
    }
}
?>
