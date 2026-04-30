<?php
/**
 * Script de diagnóstico do banco de dados.
 * 
 * ATENÇÃO: Este script NÃO altera senhas.
 * Use apenas para testes de conectividade e verificação.
 * 
 * Para executar, acesse com o parâmetro: ?run=true&confirm=yes
 * Exemplo: /api/fix-db.php?run=true&confirm=yes
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

// Proteção: só executa diagnóstico se explicitamente autorizado
$allowed = isset($_GET['run']) && $_GET['run'] === 'true' && isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$allowed) {
    echo json_encode([
        'success' => false,
        'error' => 'Script protegido. Para executar diagnóstico, acesse com ?run=true&confirm=yes',
        'info' => 'Este script é apenas para diagnóstico e NÃO altera senhas.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$log = [];

try {
    // Test posts query
    $stmt = dbQuery("SELECT COUNT(*) AS cnt FROM posts");
    $rows = dbFetchAll($stmt);
    $log['posts_count'] = $rows[0]['cnt'] ?? 0;
    
    // Test users query
    $stmt = dbQuery("SELECT id, username, display_name, created_at FROM users");
    $users = dbFetchAll($stmt);
    $log['users'] = $users;
    
    // Test password verification (apenas verifica, não altera)
    $stmt = dbQuery("SELECT password_hash FROM users WHERE username = ?", ['admin']);
    $adminRows = dbFetchAll($stmt);
    if (!empty($adminRows)) {
        $log['admin_password_hash_format'] = substr($adminRows[0]['password_hash'], 0, 4);
        $log['admin_password_hash_valid'] = !empty($adminRows[0]['password_hash']);
    }
    
    echo json_encode(['success' => true, 'log' => $log], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()]);
}
