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
    // Single post by slug or id
    if (isset($_GET['slug']) || isset($_GET['id'])) {
        $where = isset($_GET['slug']) ? 'p.slug = ?' : 'p.id = ?';
        $param = isset($_GET['slug']) ? $_GET['slug'] : (int)$_GET['id'];
        
        // Fetch post data
        $stmt = dbQuery(
            "SELECT p.*, u.display_name AS author_name FROM posts p LEFT JOIN users u ON p.author_id = u.id WHERE $where",
            [$param]
        );
        $rows = dbFetchAll($stmt);
        if (empty($rows)) {
            http_response_code(404);
            echo json_encode(['error' => 'Post não encontrado.']);
            exit;
        }
        $post = $rows[0];
        $post['published'] = (bool)$post['published'];
        $post['featured'] = (bool)$post['featured'];
        
        // Fetch media (ODBC requires separate connection to avoid cursor issues)
        // Use dbQuery which reuses the connection - but fetch results immediately
        // Since we already fetched all rows above, we can safely run another query
        $mStmt = dbQuery(
            "SELECT id, media_type, file_url, file_name, file_size, sort_order FROM post_media WHERE post_id = ? ORDER BY sort_order",
            [$post['id']]
        );
        $media = dbFetchAll($mStmt);
        $post['media'] = $media ?: [];
        
        echo json_encode(['post' => $post], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // List all posts
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