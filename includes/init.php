<?php
// Loaded at the top of every page: starts the session and defines shared helpers.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// BASE_URL is the folder the project lives in ("/dormfinder" locally, "" on most hosts).
$docRoot     = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT']));
$projectRoot = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
define('BASE_URL', rtrim(substr($projectRoot, strlen($docRoot)), '/'));

// Escape text before printing it in HTML (prevents XSS).
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Build a link that works both locally and when deployed.
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// One-time messages shown after a redirect (success, danger, warning, info).
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// The logged-in user is stored as ['id' => ..., 'name' => ..., 'role' => ...].
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}
