<?php
/**
 * FX - fileXperience – Authentication and setup handling
 */
require_once __DIR__ . '/config.php';

function needs_setup(): bool {
    return !file_exists(CONFIG_INC_FILE);
}

function run_setup(array $settings, array $passwords, array $whitelist): string|true {
    $lang = $settings['language'] ?? 'en';
    if (!in_array($lang, app_available_languages(), true)) {
        return app_text($lang . '.setup.validation_language');
    }

    $baseUrl = rtrim(trim((string)($settings['base_url'] ?? '')), '/');
    if ($baseUrl === '') {
        return app_text($lang . '.setup.validation_base_url_required');
    }
    if (!preg_match('#^https?://#i', $baseUrl)) {
        return app_text($lang . '.setup.validation_base_url_scheme');
    }

    $tokenTtl = filter_var($settings['token_ttl'] ?? 300, FILTER_VALIDATE_INT);
    if ($tokenTtl === false || $tokenTtl < 30 || $tokenTtl > 86400) {
        return app_text($lang . '.setup.validation_token_ttl');
    }

    $cronSecret = trim((string)($settings['cron_secret'] ?? ''));
    if (strlen($cronSecret) < 12) {
        return app_text($lang . '.setup.validation_cron_secret');
    }

    foreach ($passwords as $i => $entry) {
        $label = trim((string)($entry['label'] ?? ''));
        $pw    = (string)($entry['password'] ?? '');
        $pw2   = (string)($entry['confirm'] ?? '');
        if ($label === '') {
            return app_text($lang . '.setup.validation_password_label', ['number' => $i + 1]);
        }
        if (strlen($pw) < 8) {
            return app_text($lang . '.setup.validation_password_length', ['label' => $label]);
        }
        if ($pw !== $pw2) {
            return app_text($lang . '.setup.validation_password_match', ['label' => $label]);
        }
    }

    $pwArray = [];
    foreach ($passwords as $entry) {
        $label = trim((string)$entry['label']);
        $pwArray[$label] = password_hash((string)$entry['password'], PASSWORD_BCRYPT);
    }

    $cleanWhitelist = [];
    foreach ($whitelist as $entry) {
        $e = trim((string)$entry);
        if ($e !== '') $cleanWhitelist[] = $e;
    }

    $generated = date('Y-m-d H:i:s');
    $content  = "<?php\n";
    $content .= "// FX - fileXperience – setup configuration\n";
    $content .= "// Automatically generated on " . $generated . "\n";
    $content .= "// To reset setup: delete this file and open index.php again.\n\n";
    $content .= "define('BASE_URL', " . var_export($baseUrl, true) . ");\n";
    $content .= "define('TOKEN_TTL', " . (int)$tokenTtl . ");\n";
    $content .= "define('CRON_SECRET', " . var_export($cronSecret, true) . ");\n";
    $content .= "define('APP_LANGUAGE', " . var_export($lang, true) . ");\n\n";
    $content .= "// Passwords: label => bcrypt hash\n";
    $content .= "\$AUTH_PASSWORDS = " . var_export($pwArray, true) . ";\n\n";
    $content .= "// IP/host whitelist (no password required)\n";
    $content .= "\$AUTH_WHITELIST = " . var_export($cleanWhitelist, true) . ";\n";

    if (file_put_contents(CONFIG_INC_FILE, $content, LOCK_EX) === false) {
        return app_text($lang . '.setup.write_error');
    }

    auth_session_start();
    session_regenerate_id(true);
    $_SESSION['authenticated'] = true;
    $_SESSION['auth_time'] = time();
    return true;
}

function client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
        if (!empty($_SERVER[$h])) return trim(explode(',', (string)$_SERVER[$h])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_trusted_client(): bool {
    global $AUTH_WHITELIST;
    $clientIp = client_ip();
    foreach ($AUTH_WHITELIST as $entry) {
        if ($entry === $clientIp) return true;
        if (!filter_var($entry, FILTER_VALIDATE_IP)) {
            $resolved = gethostbyname($entry);
            if ($resolved !== $entry && $resolved === $clientIp) return true;
        }
    }
    return false;
}

function auth_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
    if (SESSION_MAX_TTL > 0 && isset($_SESSION['auth_time'])) {
        if (time() - (int)$_SESSION['auth_time'] > SESSION_MAX_TTL) {
            session_unset();
            session_destroy();
            session_start();
        }
    }
}

function access_restricted_to_whitelist(): bool {
    global $AUTH_PASSWORDS, $AUTH_WHITELIST;
    return empty($AUTH_PASSWORDS) && !empty($AUTH_WHITELIST);
}

function is_authenticated(): bool {
    global $AUTH_PASSWORDS, $AUTH_WHITELIST;

    if (is_trusted_client()) return true;

    // No passwords and no whitelist means intentionally public access.
    if (empty($AUTH_PASSWORDS) && empty($AUTH_WHITELIST)) return true;

    // No passwords but a whitelist means only trusted clients may access index.php.
    if (empty($AUTH_PASSWORDS) && !empty($AUTH_WHITELIST)) return false;

    auth_session_start();
    return !empty($_SESSION['authenticated']);
}

function try_authenticate(string $password): bool {
    global $AUTH_PASSWORDS;
    foreach ($AUTH_PASSWORDS as $hash) {
        if (password_verify($password, $hash)) {
            auth_session_start();
            session_regenerate_id(true);
            $_SESSION['authenticated'] = true;
            $_SESSION['auth_time'] = time();
            return true;
        }
    }
    return false;
}

function require_auth(): string {
    if (needs_setup()) return 'setup';
    if (is_authenticated()) return 'authenticated';
    if (access_restricted_to_whitelist()) return 'denied';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['password'])) {
        if (try_authenticate((string)$_POST['password'])) return 'authenticated';
        define('AUTH_ERROR', true);
    }
    return 'login';
}
