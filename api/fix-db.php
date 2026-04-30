<?php
// One-time run: fix password hash and test connectivity
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

$log = [];

try {
    // Generate new PHP-compatible bcrypt hash
    $newHash = password_hash('admin123', PASSWORD_BCRYPT);
    $log['new_hash_prefix'] = substr($newHash, 0, 15);
    
    // Update the admin user's password
    dbQuery("UPDATE users SET password_hash = ?, updated_at = GETDATE() WHERE username = ?", 
            [$newHash, 'admin']);
    $log['password_updated'] = true;
    
    // Verify it works
    $stmt = dbQuery("SELECT password_hash FROM users WHERE username = ?", ['admin']);
    $rows = dbFetchAll($stmt);
    $verify = password_verify('admin123', $rows[0]['password_hash']);
    $log['verify_works'] = $verify;
    
    // Test posts query
    $stmt = dbQuery("SELECT COUNT(*) AS cnt FROM posts");
    $rows = dbFetchAll($stmt);
    $log['posts_count'] = $rows[0]['cnt'] ?? 0;
    
    echo json_encode(['success' => true, 'log' => $log], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()]);
}