<?php
/**
 * FX - fileXperience – Token-Verwaltung
 * Alle Operationen auf tokens.json laufen hier durch.
 *
 * Wichtig: Lesen und Schreiben passieren unter demselben exklusiven flock(),
 * damit parallele Requests keine Token-Daten überschreiben.
 */
require_once __DIR__ . '/config.php';

function tokens_decode(string $raw): array {
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Führt eine Token-Operation atomar mit Dateisperre aus.
 *
 * Callback-Signatur:
 *   function(array &$tokens): mixed
 */
function tokens_locked(callable $callback): mixed {
    $dir = dirname(TOKENS_FILE);
    if (!is_dir($dir)) {
        throw new RuntimeException(app_text('config.tokens_dir_missing'));
    }

    $fh = fopen(TOKENS_FILE, 'c+');
    if ($fh === false) {
        throw new RuntimeException(app_text('config.tokens_open_error'));
    }

    try {
        if (!flock($fh, LOCK_EX)) {
            throw new RuntimeException(app_text('config.tokens_lock_error'));
        }

        rewind($fh);
        $raw = stream_get_contents($fh);
        $tokens = tokens_decode($raw === false ? '' : $raw);

        $result = $callback($tokens);

        rewind($fh);
        ftruncate($fh, 0);
        fwrite($fh, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($fh);
        flock($fh, LOCK_UN);

        return $result;
    } finally {
        fclose($fh);
    }
}

// ── Lesen ────────────────────────────────────────────────────
function tokens_load(): array {
    return tokens_locked(function(array &$tokens): array {
        return $tokens;
    });
}

// ── Schreiben ────────────────────────────────────────────────
function tokens_save(array $tokens): void {
    tokens_locked(function(array &$current) use ($tokens): void {
        $current = $tokens;
    });
}

// ── Token anlegen ────────────────────────────────────────────
function token_create(): string {
    return tokens_locked(function(array &$tokens): string {
        do {
            $token = bin2hex(random_bytes(16));
        } while (isset($tokens[$token]));

        $tokens[$token] = [
            'created'  => time(),
            'has_file' => false,
            'filename' => null,
        ];

        return $token;
    });
}

// ── Token validieren (existiert + nicht abgelaufen) ──────────
function token_valid(string $token): bool {
    return tokens_locked(function(array &$tokens) use ($token): bool {
        if (!isset($tokens[$token])) return false;

        if (time() - (int)$tokens[$token]['created'] > TOKEN_TTL) {
            unset($tokens[$token]);
            return false;
        }

        return true;
    });
}

// ── Token-Daten abrufen ──────────────────────────────────────
function token_get(string $token): ?array {
    return tokens_locked(function(array &$tokens) use ($token): ?array {
        return $tokens[$token] ?? null;
    });
}

// ── Datei-Status setzen ──────────────────────────────────────
function token_set_file(string $token, string $filename): void {
    tokens_locked(function(array &$tokens) use ($token, $filename): void {
        if (!isset($tokens[$token])) return;
        $tokens[$token]['has_file'] = true;
        $tokens[$token]['filename'] = $filename;
        $tokens[$token]['uploaded'] = time();
    });
}

// ── Token löschen ────────────────────────────────────────────
function token_delete(string $token): void {
    tokens_locked(function(array &$tokens) use ($token): void {
        unset($tokens[$token]);
    });
}

// ── Alle aktiven Token-Strings zurückgeben ───────────────────
function tokens_active(): array {
    return tokens_locked(function(array &$tokens): array {
        return array_keys($tokens);
    });
}
