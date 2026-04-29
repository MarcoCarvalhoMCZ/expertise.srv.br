<?php
$_SERVER['REQUEST_URI'] = '/api/posts';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $_SERVER['REQUEST_METHOD'] = 'POST';
} else {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
require_once __DIR__ . '/../index.php';
