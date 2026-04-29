<?php
$user = requireAuth();
$postId = (int)($_REQUEST['post_id'] ?? 0);
$mediaId = (int)($_REQUEST['media_id'] ?? 0);

$media = dbFetchAll(dbQuery("SELECT id, file_url FROM post_media WHERE id = ? AND post_id = ?", [$mediaId, $postId]));
if (empty($media)) {
    http_response_code(404);
    echo json_encode(['error' => 'Mídia não encontrada.']);
    exit;
}

// Remover arquivo físico
$filePath = __DIR__ . '/../' . str_replace('/api/', '', $media[0]['file_url']);
if (file_exists($filePath)) {
    unlink($filePath);
}

dbQuery("DELETE FROM post_media WHERE id = ?", [$mediaId]);
echo json_encode(['message' => 'Mídia excluída com sucesso.']);