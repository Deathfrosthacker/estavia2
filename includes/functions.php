<?php
/**
 * Shared security helpers: escaping, CSRF, sessions, rate limiting.
 */

/** Start a hardened session (HttpOnly + SameSite cookies, no JS access). */
function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,          // blocks session theft via XSS
        'samesite' => 'Lax',         // blocks cross-site request forgery
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    ]);
    session_start();
}

/** Escape output (primary XSS defense — use on EVERY echoed value). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Multibyte-safe string length with a fallback for hosts without mbstring. */
function str_len(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

/* ------------------------------ CSRF ------------------------------ */

function csrf_token(): string
{
    secure_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Embed as a hidden form field. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    secure_session_start();
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);   // timing-safe compare
}

/* --------------------------- Rate limiting --------------------------- */

/**
 * Returns true when the action should be blocked (too many attempts).
 * For login: 5 failures within 15 minutes per IP + username.
 */
function rate_limited(string $action, int $maxAttempts = 5, int $windowMinutes = 15): bool
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS c FROM login_attempts
         WHERE ip_address = :ip AND username = :u AND success = 0
           AND attempted_at > (NOW() - INTERVAL :w MINUTE)'
    );
    // bindValue with explicit PARAM_INT so LIMIT-style values are safe everywhere
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $stmt->bindValue(':u', $action);
    $stmt->bindValue(':w', $windowMinutes, PDO::PARAM_INT);
    $stmt->execute();
    return ((int) $stmt->fetch()['c']) >= $maxAttempts;
}

function record_attempt(string $username, bool $success): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (username, ip_address, success) VALUES (:u, :ip, :s)'
    );
    $stmt->execute([
        ':u'  => $username,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ':s'  => $success ? 1 : 0,
    ]);
}

/* ------------------------------ Input ------------------------------ */

/** Trim + strip control characters from user input. */
function clean(string $value): string
{
    $value = trim($value);
    // remove NUL bytes and other control chars except newlines/tabs
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}
