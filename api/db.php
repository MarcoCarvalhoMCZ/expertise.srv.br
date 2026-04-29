<?php
$DB_CONFIG = [
    'host' => 'sosdados.com.br',
    'port' => '11433',
    'user' => 'expertise_sa_sql',
    'password' => 'E@mf5644',
    'database' => 'bd_expertise_srv'
];

function getDbConnection() {
    global $DB_CONFIG;
    $server = $DB_CONFIG['host'] . ',' . $DB_CONFIG['port'];
    $connInfo = [
        'Database' => $DB_CONFIG['database'],
        'UID' => $DB_CONFIG['user'],
        'PWD' => $DB_CONFIG['password'],
        'CharacterSet' => 'UTF-8',
        'Encrypt' => false,
        'TrustServerCertificate' => true,
        'ReturnDatesAsStrings' => true
    ];

    // Try sqlsrv first
    if (function_exists('sqlsrv_connect')) {
        $conn = @sqlsrv_connect($server, $connInfo);
        if ($conn) return ['type' => 'sqlsrv', 'conn' => $conn];
    }

    // Try PDO sqlsrv
    if (extension_loaded('pdo_sqlsrv')) {
        try {
            $dsn = "sqlsrv:Server=$server;Database={$DB_CONFIG['database']}";
            $pdo = new PDO($dsn, $DB_CONFIG['user'], $DB_CONFIG['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
            ]);
            return ['type' => 'pdo', 'conn' => $pdo];
        } catch (Exception $e) {}
    }

    // Try ODBC
    try {
        $dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=$server;Database={$DB_CONFIG['database']}";
        $pdo = new PDO($dsn, $DB_CONFIG['user'], $DB_CONFIG['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return ['type' => 'pdo', 'conn' => $pdo];
    } catch (Exception $e) {}

    http_response_code(500);
    die(json_encode(['error' => 'Falha na conexao com o banco de dados.']));
}

function dbQuery($sql, $params = []) {
    $db = getDbConnection();
    if ($db['type'] === 'pdo') {
        $stmt = $db['conn']->prepare($sql);
        $stmt->execute($params);
        return ['type' => 'pdo', 'stmt' => $stmt];
    } else {
        $stmt = sqlsrv_query($db['conn'], $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            http_response_code(500);
            die(json_encode(['error' => 'Erro SQL: ' . print_r($errors, true)]));
        }
        return ['type' => 'sqlsrv', 'stmt' => $stmt, 'conn' => $db['conn']];
    }
}

function dbFetchAll($result) {
    if ($result['type'] === 'pdo') {
        return $result['stmt']->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $rows = [];
        while ($row = sqlsrv_fetch_array($result['stmt'], SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
}

function dbRowCount($result) {
    if ($result['type'] === 'pdo') {
        return $result['stmt']->rowCount();
    } else {
        return sqlsrv_rows_affected($result['stmt']) ?: 0;
    }
}