<?php
$user = requireAuth();
$id = (int)($_REQUEST['id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido.']);
    exit;
}

$fields = [];
$params = [];

if (isset($input['title'])) { $fields[] = 'title = ?'; $params[] = $input['title']; }
if (array_key_exists('content', $input)) { $fields[] = 'content = ?'; $params[] = $input['content']; }
if (isset($input['post_type'])) { $fields[] = 'post_type = ?'; $params[] = $input['post_type']; }
if (array_key_exists('excerpt', $input)) { $fields[] = 'excerpt = ?'; $params[] = $input['excerpt']; }
if (array_key_exists('published', $input)) { $fields[] = 'published = ?'; $params[] = $input['published'] ? 1 : 0; }
if (array_key_exists('featured', $input)) { $fields[] = 'featured = ?'; $params[] = $input['featured'] ? 1 : 0; }
if (array_key_exists('thumbnail_url', $input)) { $fields[] = 'thumbnail_url = ?'; $params[] = $input['thumbnail_url']; }

if (empty($fields)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nenhum campo para atualizar.']);
    exit;
}

$fields[] = 'updated_at = GETDATE()';
$params[] = $id;

$sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = ?";
$stmt = dbQuery($sql, $params);

$affected = dbRowCount($stmt);
if ($affected === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Post não encontrado.']);
    exit;
}

echo json_encode(['message' => 'Post atualizado com sucesso.']);