<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$log = [];

try {
    $log[] = ['step' => 1, 'msg' => 'starting'];
    
    require_once __DIR__ . '/api/db.php';
    $log[] = ['step' => 2, 'msg' => 'db loaded'];
    
    require_once __DIR__ . '/api/jwt.php';
    $log[] = ['step' => 3, 'msg' => 'jwt loaded'];
    
    $sql = "SELECT id, title FROM posts";
    $stmt = dbQuery($sql);
    $log[] = ['step' => 4, 'msg' => 'query ok'];
    
    $rows = dbFetchAll($stmt);
    $log[] = ['step' => 5, 'msg' => 'fetch ok', 'count' => count($rows)];
    
    echo json_encode(['ok' => true, 'log' => $log, 'rows' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'log' => $log, 'error' => $e->getMessage(), 'type' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()], JSON_UNESCAPED_UNICODE);
}