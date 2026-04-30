<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$sql = "SELECT id, title, slug FROM posts ORDER BY created_at DESC";
$stmt = dbQuery($sql);
$rows = dbFetchAll($stmt);
echo json_encode(['posts' => $rows], JSON_UNESCAPED_UNICODE);