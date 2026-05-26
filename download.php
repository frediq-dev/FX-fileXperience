<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tokens.php';

$token = preg_replace('/[^a-f0-9]/', '', (string)($_GET['token'] ?? ''));
if (strlen($token) !== 32 || !token_valid($token)) {
    http_response_code(403);
    die(app_text('download.invalid_token'));
}

$data = token_get($token);
if (!$data || empty($data['has_file']) || empty($data['filename'])) {
    http_response_code(404);
    die(app_text('download.no_file'));
}

$filename = (string)$data['filename'];
$filepath = UPLOAD_DIR . $token . '_' . $filename;

if (!file_exists($filepath)) {
    token_delete($token);
    http_response_code(404);
    die(app_text('download.file_missing'));
}

$mime = mime_content_type($filepath) ?: 'application/octet-stream';
$asciiName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

while (ob_get_level()) ob_end_clean();
readfile($filepath);
@unlink($filepath);
token_delete($token);
