<?php
$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuário e senha são obrigatórios.']);
    exit;
}

$stmt = dbQuery("SELECT id, username, password_hash, display_name FROM users WHERE username = ?", [$username]);
$users = dbFetchAll($stmt);

if (empty($users)) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário ou senha inválidos.']);
    exit;
}

$user = $users[0];
if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário ou senha inválidos.']);
    exit;
}

$token = jwt_encode(['id' => $user['id'], 'username' => $user['username']]);

echo json_encode([
    'message' => 'Login realizado com sucesso.',
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name']
    ],
    'token' => $token
]);