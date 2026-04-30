<?php
$_SERVER['REQUEST_URI'] = '/api/auth/password';
// Accept POST with _method=PUT override (IIS/WebDAV blocks PUT directly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['_method'] ?? '') === 'PUT' || ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '') === 'PUT')) {
    $_SERVER['REQUEST_METHOD'] = 'PUT';
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
    $_SERVER['REQUEST_METHOD'] = 'PUT';
}
require_once __DIR__ . '/../index.php';
