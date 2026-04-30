<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/api/db.php';

try {
    $stmt = dbQuery("SELECT id, username, password_hash FROM users WHERE username = ?", ['admin']);
    $rows = dbFetchAll($stmt);
    $user = $rows[0] ?? null;
    
    $testLogin = false;
    if ($user) {
        $testLogin = password_verify('admin123', $user['password_hash']);
    }
    
    echo json_encode([
        'user_found' => $user !== null,
        'hash_start' => $user ? substr($user['password_hash'], 0, 15) : 'N/A',
        'hash_len' => $user ? strlen($user['password_hash']) : 0,
        'verify_admin123' => $testLogin,
        'php_version' => PHP_VERSION
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage(), 'line' => $e->getLine()]);
}