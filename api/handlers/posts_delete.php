<?php
$user = requireAuth();
$id = (int)($_REQUEST['id'] ?? 0);

dbQuery("DELETE FROM post_media WHERE post_id = ?", [$id]);
$stmt = dbQuery("DELETE FROM posts WHERE id = ?", [$id]);
$affected = dbRowCount($stmt);

if ($affected === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Post não encontrado.']);
    exit;
}

echo json_encode(['message' => 'Post excluído com sucesso.']);