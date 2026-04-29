<?php
$user = requireAuth();
$stmt = dbQuery("SELECT id, username, display_name, created_at FROM users WHERE id = ?", [$user['id']]);
$users = dbFetchAll($stmt);
if (empty($users)) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuário não encontrado.']);
    exit;
}
echo json_encode(['user' => $users[0]]);