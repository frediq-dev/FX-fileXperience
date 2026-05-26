<?php
// ============================================================
//  FX - fileXperience – Base configuration
// ============================================================

define('CONFIG_INC_FILE', __DIR__ . '/config.inc.php');
define('UPLOAD_DIR',      __DIR__ . '/uploads/');
define('TOKENS_FILE',     __DIR__ . '/tokens.json');

// config.inc.php is created by the setup wizard and contains all setup values:
// BASE_URL, TOKEN_TTL, CRON_SECRET, APP_LANGUAGE, $AUTH_PASSWORDS, $AUTH_WHITELIST.
if (file_exists(CONFIG_INC_FILE)) {
    require CONFIG_INC_FILE;
}

if (!defined('BASE_URL'))        define('BASE_URL', '');
if (!defined('TOKEN_TTL'))       define('TOKEN_TTL', 300);
if (!defined('MAX_FILESIZE'))    define('MAX_FILESIZE', 10485760); // 10 MB
if (!defined('POLL_TIMEOUT'))    define('POLL_TIMEOUT', 25);
if (!defined('CRON_SECRET'))     define('CRON_SECRET', '');
if (!defined('SESSION_MAX_TTL')) define('SESSION_MAX_TTL', 0);
if (!defined('APP_LANGUAGE'))    define('APP_LANGUAGE', 'en');

if (!isset($AUTH_PASSWORDS) || !is_array($AUTH_PASSWORDS)) {
    $AUTH_PASSWORDS = [];
}
if (!isset($AUTH_WHITELIST) || !is_array($AUTH_WHITELIST)) {
    $AUTH_WHITELIST = [];
}

$APP_LANG = require __DIR__ . '/lang.php';

function app_language(): string {
    global $APP_LANG;
    $lang = defined('APP_LANGUAGE') ? (string)APP_LANGUAGE : 'en';
    return isset($APP_LANG[$lang]) ? $lang : 'en';
}

function app_available_languages(): array {
    global $APP_LANG;
    return array_keys($APP_LANG);
}

function app_text(string $key, array $replace = []): string {
    global $APP_LANG;

    $parts = explode('.', $key);
    $lang = app_language();

    if (isset($parts[0], $APP_LANG[$parts[0]])) {
        $lang = array_shift($parts);
    }

    $value = $APP_LANG[$lang] ?? $APP_LANG['en'] ?? [];
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            $value = $APP_LANG['en'] ?? [];
            foreach (explode('.', preg_replace('/^[a-z]{2}\./', '', $key)) as $fallbackPart) {
                if (!is_array($value) || !array_key_exists($fallbackPart, $value)) {
                    return $key;
                }
                $value = $value[$fallbackPart];
            }
            break;
        }
        $value = $value[$part];
    }

    $text = is_scalar($value) ? (string)$value : $key;
    foreach ($replace as $name => $replacement) {
        $text = str_replace('{' . $name . '}', (string)$replacement, $text);
    }
    return $text;
}

function app_language_pack(string $lang): array {
    global $APP_LANG;
    return $APP_LANG[$lang] ?? $APP_LANG['en'];
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function app_base_url(): string {
    $url = rtrim((string)BASE_URL, '/');
    if ($url === '') {
        throw new RuntimeException(app_text('config.base_url_missing'));
    }
    if (!preg_match('#^https?://#i', $url)) {
        throw new RuntimeException(app_text('config.base_url_scheme'));
    }
    return $url;
}

// ── Blocked file extensions (blacklist) ─────────────────────
define('BLOCKED_EXTENSIONS', [
    'exe', 'msi', 'bat', 'cmd', 'com', 'ps1', 'vbs', 'vbe', 'scr', 'pif', 'hta', 'reg',
    'sh', 'bash', 'zsh', 'bin', 'run', 'app', 'deb', 'rpm', 'dmg', 'pkg', 'elf',
    'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
    'asp', 'aspx', 'ashx', 'asmx',
    'jsp', 'jspx', 'jws',
    'cgi', 'pl', 'py', 'rb', 'lua',
    'xlsm', 'xltm', 'xlam', 'docm', 'dotm',
    'pptm', 'potm', 'ppam', 'ppsm',
    'jar', 'class', 'dll', 'sys', 'drv', 'ocx',
    'lnk', 'url', 'iso', 'img', 'swf',
]);
