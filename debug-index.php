<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$log = [];

try {
    $log[] = '1:' . __DIR__;
    
    require_once __DIR__ . '/api/db.php';
    $log[] = 'db loaded';
    
    require_once __DIR__ . '/api/jwt.php';
    $log[] = 'jwt loaded';
    
    // Simular o que index.php faz quando /api/posts é acessado
    $path = '/posts';
    $method = 'GET';
    
    $log[] = "routing: $path $method";
    
    if ($path === '/posts' && $method === 'GET') {
        $log[] = 'loading posts_list handler';
        
        // Simular posts_list.php
        $sql = "SELECT posts.id, posts.title FROM posts LEFT JOIN users ON posts.author_id = users.id ORDER BY posts.created_at DESC";
        $stmt = dbQuery($sql);
        $rows = dbFetchAll($stmt);
        
        $log[] = 'query executed, rows: ' . count($rows);
        echo json_encode(['posts' => $rows], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['error' => 'no route matched', 'path' => $path, 'method' => $method]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'log' => $log
    ], JSON_UNESCAPED_UNICODE);
}