<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tokens.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$token = preg_replace('/[^a-f0-9]/', '', (string)($_GET['token'] ?? ''));
if (strlen($token) !== 32) {
    echo json_encode(['status' => 'error', 'message' => app_text('poll.invalid_token')]);
    exit;
}

set_time_limit(POLL_TIMEOUT + 5);
ignore_user_abort(true);
$deadline = time() + POLL_TIMEOUT;

while (time() < $deadline) {
    if (!token_valid($token)) {
        echo json_encode(['status' => 'expired']);
        exit;
    }

    $data = token_get($token);
    if ($data && !empty($data['has_file'])) {
        echo json_encode(['status' => 'ready', 'filename' => $data['filename']]);
        exit;
    }

    sleep(1);
    if (connection_aborted()) exit;
}

echo json_encode(['status' => 'waiting']);
