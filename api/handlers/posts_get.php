<?php
$param = $_REQUEST['param'] ?? '';
$isNumeric = is_numeric($param);

if ($isNumeric) {
    $stmt = dbQuery("SELECT p.*, u.display_name AS author_name FROM posts p LEFT JOIN users u ON p.author_id = u.id WHERE p.id = ?", [$param]);
} else {
    $stmt = dbQuery("SELECT p.*, u.display_name AS author_name FROM posts p LEFT JOIN users u ON p.author_id = u.id WHERE p.slug = ? AND p.published = 1", [$param]);
}
$posts = dbFetchAll($stmt);

if (empty($posts)) {
    http_response_code(404);
    echo json_encode(['error' => 'Post não encontrado.']);
    exit;
}

$post = $posts[0];
$post['published'] = (bool)$post['published'];
$post['featured'] = (bool)$post['featured'];

$mStmt = dbQuery("SELECT id, media_type, file_url, file_name, file_size, sort_order FROM post_media WHERE post_id = ? ORDER BY sort_order", [$post['id']]);
$post['media'] = dbFetchAll($mStmt);

echo json_encode(['post' => $post]);