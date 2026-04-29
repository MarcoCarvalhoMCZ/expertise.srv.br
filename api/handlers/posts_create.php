<?php
$user = requireAuth();
$input = json_decode(file_get_contents('php://input'), true);

$title = $input['title'] ?? '';
if (!$title) {
    http_response_code(400);
    echo json_encode(['error' => 'Título é obrigatório.']);
    exit;
}

// Gerar slug
$slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $title)));
$slug = trim($slug, '-') . '-' . time();

$content = $input['content'] ?? '';
$postType = $input['post_type'] ?? 'text';
$excerpt = $input['excerpt'] ?? null;
$published = !empty($input['published']) ? 1 : 0;
$featured = !empty($input['featured']) ? 1 : 0;
$thumbnailUrl = $input['thumbnail_url'] ?? null;
$authorId = $user['id'];

$sql = "INSERT INTO posts (title, slug, content, post_type, excerpt, published, featured, thumbnail_url, author_id)
        OUTPUT INSERTED.id
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = dbQuery($sql, [$title, $slug, $content, $postType, $excerpt, $published, $featured, $thumbnailUrl, $authorId]);
$newId = dbLastInsertId($stmt);

http_response_code(201);
echo json_encode(['message' => 'Post criado com sucesso.', 'id' => $newId, 'slug' => $slug]);