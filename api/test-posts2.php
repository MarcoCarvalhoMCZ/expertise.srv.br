<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/db.php';
    echo json_encode(['step' => 'db_loaded']);
    
    require_once __DIR__ . '/jwt.php';
    echo json_encode(['step' => 'jwt_loaded']);
    
    require_once __DIR__ . '/handlers/posts_list.php';
    echo json_encode(['step' => 'handler_done']);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}