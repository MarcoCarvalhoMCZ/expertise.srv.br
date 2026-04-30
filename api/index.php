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

$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api';
$path = parse_url($requestUri, PHP_URL_PATH);

// Remover query string da análise de rota
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Remover base path
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Remove .php extension if present (admin panel adds it for IIS compatibility)
$path = preg_replace('/\.php$/', '', $path);

$path = '/' . trim($path, '/');
// Method override: IIS/WebDAV blocks PUT/DELETE; client sends POST with ?_method=PUT|DELETE
$method = strtoupper($_GET['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_SERVER['REQUEST_METHOD']);

// === ROTAS DE AUTENTICAÇÃO ===
if ($path === '/auth/login' && $method === 'POST') {
    require_once __DIR__ . '/handlers/auth_login.php';
    exit;
}
if ($path === '/auth/me' && $method === 'GET') {
    require_once __DIR__ . '/handlers/auth_me.php';
    exit;
}
if ($path === '/auth/logout' && $method === 'POST') {
    require_once __DIR__ . '/handlers/auth_logout.php';
    exit;
}
if ($path === '/auth/password' && $method === 'PUT') {
    require_once __DIR__ . '/handlers/auth_password.php';
    exit;
}

// === ROTAS DE POSTS ===
if ($path === '/posts' && $method === 'GET') {
    require_once __DIR__ . '/handlers/posts_list.php';
    exit;
}
if ($path === '/posts' && $method === 'POST') {
    require_once __DIR__ . '/handlers/posts_create.php';
    exit;
}

// /posts/{id} ou /posts/{slug}
if (preg_match('#^/posts/([a-zA-Z0-9\-]+)$#', $path, $matches) && $method === 'GET') {
    $_REQUEST['param'] = $matches[1];
    require_once __DIR__ . '/handlers/posts_get.php';
    exit;
}
if (preg_match('#^/posts/(\d+)$#', $path, $matches) && $method === 'PUT') {
    $_REQUEST['id'] = $matches[1];
    require_once __DIR__ . '/handlers/posts_update.php';
    exit;
}
if (preg_match('#^/posts/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    $_REQUEST['id'] = $matches[1];
    require_once __DIR__ . '/handlers/posts_delete.php';
    exit;
}

// /posts/{id}/media
if (preg_match('#^/posts/(\d+)/media$#', $path, $matches) && $method === 'POST') {
    $_REQUEST['id'] = $matches[1];
    require_once __DIR__ . '/handlers/posts_media_upload.php';
    exit;
}
if (preg_match('#^/posts/(\d+)/media/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    $_REQUEST['post_id'] = $matches[1];
    $_REQUEST['media_id'] = $matches[2];
    require_once __DIR__ . '/handlers/posts_media_delete.php';
    exit;
}

// Servir arquivos de upload
if (preg_match('#^/uploads/(.+)$#', $path, $matches)) {
    $filePath = __DIR__ . '/uploads/' . $matches[1];
    if (file_exists($filePath)) {
        $mime = mime_content_type($filePath);
        header('Content-Type: ' . $mime);
        readfile($filePath);
        exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'Arquivo não encontrado.']);
    exit;
}

// Rota não encontrada
http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada.']);