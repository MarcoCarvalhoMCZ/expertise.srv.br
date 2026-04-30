<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { echo json_encode(['error' => 'slug missing']); exit; }

$stmt = dbQuery(
    "SELECT p.id, p.title, p.slug, p.content, p.post_type, p.thumbnail_url, p.excerpt, p.published, p.featured, p.created_at, p.updated_at, u.display_name AS author_name
     FROM posts p
     LEFT JOIN users u ON p.author_id = u.id
     WHERE p.slug = ? AND p.published = 1",
    [$slug]
);
$rows = dbFetchAll($stmt);
if (empty($rows)) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
$post = $rows[0];
$post['published'] = (bool)$post['published'];
$post['featured'] = (bool)$post['featured'];
$post['media'] = [];
echo json_encode(['post' => $post], JSON_UNESCAPED_UNICODE);