<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, title, slug, post_type, published, featured, created_at FROM posts ORDER BY created_at DESC";
    $stmt = dbQuery($sql);
    $rows = dbFetchAll($stmt);
    echo json_encode(['posts' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'upload') {
    $_REQUEST['id'] = (int)($_GET['id'] ?? 0);
    require_once __DIR__ . '/handlers/posts_media_upload.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/handlers/posts_create.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $_REQUEST['id'] = (int)($_GET['id'] ?? 0);
    require_once __DIR__ . '/handlers/posts_update.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['action']) && $_GET['action'] === 'delete-media') {
    $_REQUEST['post_id'] = (int)($_GET['id'] ?? 0);
    $_REQUEST['media_id'] = (int)($_GET['media_id'] ?? 0);
    require_once __DIR__ . '/handlers/posts_media_delete.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $_REQUEST['id'] = (int)($_GET['id'] ?? 0);
    require_once __DIR__ . '/handlers/posts_delete.php';
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Método não permitido.']);