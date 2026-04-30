<?php
$DB_CONFIG = [
    'host' => 'sosdados.com.br',
    'port' => '11433',
    'user' => 'expertise_sa_sql',
    'password' => 'E@mf5644',
    'database' => 'bd_expertise_srv'
];

// Reuse a single connection instead of opening a new one every query
$_dbConn = null;

function getDbConnection() {
    global $_dbConn, $DB_CONFIG;
    if ($_dbConn !== null) {
        return $_dbConn;
    }
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
        if ($conn) {
            $_dbConn = ['type' => 'sqlsrv', 'conn' => $conn];
            return $_dbConn;
        }
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
            $_dbConn = ['type' => 'pdo', 'conn' => $pdo];
            return $_dbConn;
        } catch (Exception $e) {}
    }

    // Try ODBC
    try {
        $dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=$server;Database={$DB_CONFIG['database']}";
        $pdo = new PDO($dsn, $DB_CONFIG['user'], $DB_CONFIG['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $_dbConn = ['type' => 'pdo', 'conn' => $pdo];
        return $_dbConn;
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

function dbLastInsertId($result) {
    if ($result['type'] === 'pdo') {
        // For PDO with OUTPUT INSERTED.id, the result set contains the id
        $id = $result['stmt']->fetch(PDO::FETCH_ASSOC);
        if ($id && isset($id['id'])) {
            return (int)$id['id'];
        }
        // Fallback: try lastInsertId()
        return (int)$result['stmt']->fetchColumn();
    } else {
        // For sqlsrv, query @@IDENTITY using the same connection
        $stmt = sqlsrv_query($result['conn'], 'SELECT @@IDENTITY AS id');
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return (int)$row['id'];
        }
        return 0;
    }
}

function dbClose() {
    global $_dbConn;
    if ($_dbConn) {
        if ($_dbConn['type'] === 'pdo') {
            $_dbConn['conn'] = null;
        } else {
            sqlsrv_close($_dbConn['conn']);
        }
        $_dbConn = null;
    }
}