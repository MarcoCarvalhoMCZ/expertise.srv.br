<?php
$authUser = getAuthUser();
$filter = $authUser ? '' : 'WHERE p.published = 1';
// Se usuário autenticado, mostrar todos (incluindo rascunhos)
// Para isso, removemos o filtro se tiver token válido
$isAdmin = $authUser !== null;

$sql = "SELECT p.id, p.title, p.slug, p.content, p.post_type, p.media_url,
               p.thumbnail_url, p.excerpt, p.published, p.featured,
               CONVERT(VARCHAR, p.created_at, 120) AS created_at,
               CONVERT(VARCHAR, p.updated_at, 120) AS updated_at,
               u.display_name AS author_name
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id";
if (!$isAdmin) {
    $sql .= " WHERE p.published = 1";
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = dbQuery($sql);
$posts = dbFetchAll($stmt);

// Buscar mídias associadas para cada post
foreach ($posts as &$post) {
    $mStmt = dbQuery("SELECT id, media_type, file_url, file_name, file_size, sort_order FROM post_media WHERE post_id = ? ORDER BY sort_order", [$post['id']]);
    $post['media'] = dbFetchAll($mStmt);
    // Converter published para boolean
    $post['published'] = (bool)$post['published'];
    $post['featured'] = (bool)$post['featured'];
}

echo json_encode(['posts' => $posts]);