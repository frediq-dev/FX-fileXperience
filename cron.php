<?php
/**
 * FX - fileXperience – Cron cleanup
 *
 * URL cron:
 *   curl -s "https://example.com/fx-filexperience/cron.php?secret=YOUR_SECRET" > /dev/null
 * CLI cron:
 *   * /5 * * * * php /path/to/fx-filexperience/cron.php cli
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tokens.php';

$isCli = php_sapi_name() === 'cli' || in_array($_SERVER['argv'][1] ?? '', ['cli', '--cli'], true);
if (!$isCli) {
    $secret = $_GET['secret'] ?? '';
    if ($secret !== CRON_SECRET || CRON_SECRET === '') {
        http_response_code(403);
        die(app_text('cron.forbidden'));
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$log = [];
$cutoff = time() - TOKEN_TTL;
$deletedJson = 0;
$deletedFiles = 0;

$tokens = tokens_locked(function(array &$tokens) use ($cutoff, &$deletedJson, &$log): array {
    foreach ($tokens as $tok => $data) {
        if (($data['created'] ?? 0) < $cutoff) {
            unset($tokens[$tok]);
            $deletedJson++;
            $log[] = app_text('cron.token_removed', [
                'token' => $tok,
                'time' => date('H:i:s', (int)($data['created'] ?? 0)),
            ]);
        }
    }
    return $tokens;
});

$activeTokens = array_keys($tokens);
$allFiles = glob(UPLOAD_DIR . '*');
if (is_array($allFiles)) {
    foreach ($allFiles as $filepath) {
        $basename = basename($filepath);
        if ($basename === '.htaccess') continue;

        if (!preg_match('/^([a-f0-9]{32})_/', $basename, $m)) {
            if (filemtime($filepath) < $cutoff && @unlink($filepath)) {
                $deletedFiles++;
                $log[] = app_text('cron.orphan_deleted', ['file' => $basename]);
            }
            continue;
        }

        $fileTok = $m[1];
        if (!in_array($fileTok, $activeTokens, true) && @unlink($filepath)) {
            $deletedFiles++;
            $log[] = app_text('cron.expired_file_deleted', ['file' => $basename]);
        }
    }
}

$summary = app_text('cron.summary', [
    'time' => date('Y-m-d H:i:s'),
    'tokens' => $deletedJson,
    'files' => $deletedFiles,
]);
echo $summary . PHP_EOL;
foreach ($log as $line) {
    echo '  - ' . $line . PHP_EOL;
}
