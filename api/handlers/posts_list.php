<?php
$authUser = getAuthUser();
$isAdmin = $authUser !== null;

$sql = "SELECT posts.id, posts.title, posts.slug, posts.content, posts.post_type, posts.media_url,
               posts.thumbnail_url, posts.excerpt, posts.published, posts.featured,
               posts.created_at, posts.updated_at,
               users.display_name AS author_name
        FROM posts
        LEFT JOIN users ON posts.author_id = users.id";
if (!$isAdmin) {
    $sql .= " WHERE posts.published = 1";
}
$sql .= " ORDER BY posts.created_at DESC";

$stmt = dbQuery($sql);
$rows = dbFetchAll($stmt);

$result = [];
foreach ($rows as $i => $row) {
    $postId = $row['id'];
    $mStmt = dbQuery("SELECT id, media_type, file_url, file_name, file_size, sort_order FROM post_media WHERE post_id = ? ORDER BY sort_order", [$postId]);
    $media = dbFetchAll($mStmt);
    
    $result[] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'slug' => $row['slug'],
        'content' => $row['content'] ?? '',
        'post_type' => $row['post_type'] ?? 'text',
        'media_url' => $row['media_url'] ?? null,
        'thumbnail_url' => $row['thumbnail_url'] ?? null,
        'excerpt' => $row['excerpt'] ?? null,
        'published' => (bool)$row['published'],
        'featured' => (bool)$row['featured'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'author_name' => $row['author_name'] ?? '',
        'media' => $media
    ];
}

echo json_encode(['posts' => $result]);