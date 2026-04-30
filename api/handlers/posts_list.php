<?php
$authUser = getAuthUser();
$isAdmin = $authUser !== null;

$sql = "SELECT p.id, p.title, p.slug, p.content, p.post_type, p.media_url,
               p.thumbnail_url, p.excerpt, p.published, p.featured,
               p.created_at, p.updated_at,
               u.display_name AS author_name
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id";
if (!$isAdmin) {
    $sql .= " WHERE p.published = 1";
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = dbQuery($sql);
$posts = dbFetchAll($stmt);

$result = [];
foreach ($posts as $post) {
    $mStmt = dbQuery("SELECT id, media_type, file_url, file_name, file_size, sort_order FROM post_media WHERE post_id = ? ORDER BY sort_order", [$post['id']]);
    $media = dbFetchAll($mStmt);
    
    $result[] = [
        'id' => (int)$post['id'],
        'title' => $post['title'],
        'slug' => $post['slug'],
        'content' => $post['content'] ?? '',
        'post_type' => $post['post_type'] ?? 'text',
        'media_url' => $post['media_url'] ?? null,
        'thumbnail_url' => $post['thumbnail_url'] ?? null,
        'excerpt' => $post['excerpt'] ?? null,
        'published' => (bool)$post['published'],
        'featured' => (bool)$post['featured'],
        'created_at' => $post['created_at'],
        'updated_at' => $post['updated_at'],
        'author_name' => $post['author_name'] ?? '',
        'media' => $media
    ];
}

echo json_encode(['posts' => $result]);