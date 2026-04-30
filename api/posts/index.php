<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$_SERVER['REQUEST_URI'] = '/api/posts';
// Preserve original HTTP method for PUT/DELETE
require_once __DIR__ . '/../index.php';
