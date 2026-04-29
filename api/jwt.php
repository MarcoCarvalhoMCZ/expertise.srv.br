<?php
define('JWT_SECRET', 'expertise_blog_jwt_s3cr3t_k3y_2026');

function jwt_encode($payload) {
    $header = rtrim(strtr(base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])), '+/', '-_'), '=');
    $payload['iat'] = time();
    $payload['exp'] = time() + 28800; // 8h
    $payload_enc = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload_enc", JWT_SECRET, true)), '+/', '-_'), '=');
    return "$header.$payload_enc.$signature";
}

function jwt_decode($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    list($header, $payload, $signature) = $parts;
    $validSig = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)), '+/', '-_'), '=');

    if (!hash_equals($validSig, $signature)) return null;

    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    if (!$data) return null;
    if (isset($data['exp']) && $data['exp'] < time()) return null;

    return $data;
}

function getAuthUser() {
    $token = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            $token = $m[1];
        }
    }
    if (!$token && isset($_COOKIE['token'])) {
        $token = $_COOKIE['token'];
    }
    if (!$token) return null;
    return jwt_decode($token);
}

function requireAuth() {
    $user = getAuthUser();
    if (!$user) {
        http_response_code(401);
        die(json_encode(['error' => 'Acesso negado. Token não fornecido ou inválido.']));
    }
    return $user;
}