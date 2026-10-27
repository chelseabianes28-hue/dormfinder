<?php
// Authentication helpers. Include this at the top of any page that needs the database or a login.
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/db.php';

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

// CSRF protection: every form carries a secret token that must match the session.
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token)
        && isset($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

// Access control: send visitors away from pages they should not see.
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        set_flash('danger', 'You do not have permission to view that page.');
        redirect('dashboard.php');
    }
}
