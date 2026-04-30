<?php
try {
    $user = requireAuth();
    $input = json_decode(file_get_contents('php://input'), true);
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';

    if (!$currentPassword || !$newPassword) {
        http_response_code(400);
        echo json_encode(['error' => 'Senha atual e nova senha são obrigatórias.']);
        exit;
    }
    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'A nova senha deve ter pelo menos 6 caracteres.']);
        exit;
    }

    $stmt = dbQuery("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
    $rows = dbFetchAll($stmt);
    if (empty($rows)) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado.']);
        exit;
    }

    $storedHash = trim($rows[0]['password_hash']);

    // password_verify do PHP é compatível tanto com hashes gerados pelo PHP ($2y$)
    // quanto pelo Node.js/bcryptjs ($2a$), ambos são suportados nativamente
    if (!password_verify($currentPassword, $storedHash)) {
        http_response_code(401);
        echo json_encode(['error' => 'Senha atual incorreta.']);
        exit;
    }

    // Sempre gera o hash com password_hash do PHP (prefixo $2y$) para consistência
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateResult = dbQuery(
        "UPDATE users SET password_hash = ?, updated_at = GETDATE() WHERE id = ?",
        [$hash, $user['id']]
    );

    echo json_encode(['message' => 'Senha alterada com sucesso.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao alterar senha: ' . $e->getMessage()]);
}
