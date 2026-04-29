<?php
// Configuração do SQL Server
$config = [
    'host' => 'sosdados.com.br',
    'port' => '11433',
    'user' => 'expertise_sa_sql',
    'password' => 'E@mf5644',
    'database' => 'bd_expertise_srv'
];

function getDbConnection() {
    global $config;
    try {
        // Tenta sqlsrv (driver nativo do SQL Server)
        if (extension_loaded('sqlsrv')) {
            $conn = sqlsrv_connect(
                $config['host'] . ',' . $config['port'],
                [
                    'Database' => $config['database'],
                    'UID' => $config['user'],
                    'PWD' => $config['password'],
                    'CharacterSet' => 'UTF-8',
                    'Encrypt' => false,
                    'TrustServerCertificate' => true
                ]
            );
            if ($conn) return $conn;
        }
        
        // Fallback PDO via sqlsrv
        if (extension_loaded('pdo_sqlsrv')) {
            $dsn = sprintf('sqlsrv:Server=%s,%s;Database=%s', $config['host'], $config['port'], $config['database']);
            $pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $pdo;
        }
        
        // Fallback PDO via ODBC
        $dsn = sprintf('odbc:Driver={ODBC Driver 17 for SQL Server};Server=%s,%s;Database=%s', $config['host'], $config['port'], $config['database']);
        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (Exception $e) {
        http_response_code(500);
        die(json_encode(['error' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()]));
    }
}

function dbQuery($sql, $params = []) {
    $db = getDbConnection();
    try {
        if ($db instanceof PDO) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } else {
            // sqlsrv
            $stmt = sqlsrv_query($db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception(print_r($errors, true));
            }
            return $stmt;
        }
    } catch (Exception $e) {
        http_response_code(500);
        die(json_encode(['error' => 'Erro na consulta: ' . $e->getMessage()]));
    }
}

function dbFetchAll($stmt) {
    if ($stmt instanceof PDOStatement) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ? array_merge(...array_map(function($r) use ($stmt) {
            $rows = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
            return $rows;
        }, [])) : (function($stmt) {
            $rows = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
            return $rows;
        })($stmt);
    }
}

function dbLastInsertId($stmt) {
    // Para IDENTITY no SQL Server
    $row = dbFetchAll($stmt);
    return $row[0]['id'] ?? null;
}

function dbRowCount($stmt) {
    if ($stmt instanceof PDOStatement) {
        return $stmt->rowCount();
    } else {
        return sqlsrv_rows_affected($stmt);
    }
}