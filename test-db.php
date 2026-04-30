<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$info = [
    'php' => PHP_VERSION,
    'sqlsrv' => function_exists('sqlsrv_connect'),
    'pdo_sqlsrv' => extension_loaded('pdo_sqlsrv'),
    'pdo_odbc' => extension_loaded('PDO_ODBC'),
    'pdo_drivers' => PDO::getAvailableDrivers()
];

// Try ODBC connection to SQL Server
try {
    $server = 'sosdados.com.br,11433';
    $db = 'bd_expertise_srv';
    $user = 'expertise_sa_sql';
    $pass = 'E@mf5644';
    
    $dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=$server;Database=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $stmt = $pdo->query("SELECT 1 AS ok");
    $info['odbc_test'] = $stmt->fetch();
    $info['odbc_status'] = 'OK';
} catch (Exception $e) {
    $info['odbc_error'] = $e->getMessage();
    $info['odbc_status'] = 'FAILED';
}

// Now test via api/db.php
try {
    require_once __DIR__ . '/api/db.php';
    $result = dbQuery("SELECT COUNT(*) AS cnt FROM users");
    $rows = dbFetchAll($result);
    $info['db_test'] = $rows;
    $info['db_status'] = 'OK';
} catch (Exception $e) {
    $info['db_error'] = $e->getMessage();
    $info['db_status'] = 'FAILED';
}

echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);