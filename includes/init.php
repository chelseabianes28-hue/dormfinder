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

// The administrator's contact email, set in includes/config.php as 'admin_email'.
function admin_email(): string
{
    $file = __DIR__ . '/config.php';
    if (!is_file($file)) {
        return '';
    }
    $config = require $file;
    $email  = is_array($config) ? trim((string) ($config['admin_email'] ?? '')) : '';
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

// Map amenity keywords to Bootstrap Icons
if (!function_exists('get_amenity_icon')) {
    function get_amenity_icon(string $amenity): string
    {
        $a = strtolower(trim($amenity));
        
        // Wi-Fi / Internet
        if (str_contains($a, 'wifi') || str_contains($a, 'wi-fi') || str_contains($a, 'internet')) {
            return 'bi-wifi';
        }
        // Aircon / AC
        if (str_contains($a, 'air') || str_contains($a, 'ac')) {
            return 'bi-snow';
        }
        // Laundry / Wash / Dryer / Space
        if (str_contains($a, 'laundry') || str_contains($a, 'wash') || str_contains($a, 'dryer') || str_contains($a, 'clothes') || str_contains($a, 'space')) {
            return 'bi-droplet-half';
        }
        // Parking / Garage / Lot / Vehicle / Car
        if (str_contains($a, 'park') || str_contains($a, 'garage') || str_contains($a, 'vehicle') || str_contains($a, 'car') || str_contains($a, 'lot')) {
            return 'bi-car-front';
        }
        // Kitchen / Cooking
        if (str_contains($a, 'kitchen') || str_contains($a, 'cook') || str_contains($a, 'stove')) {
            return 'bi-cup-hot';
        }
        // Security / CCTV / Guard
        if (str_contains($a, 'security') || str_contains($a, 'cctv') || str_contains($a, 'guard')) {
            return 'bi-shield-check';
        }
        // Water / Heater / Shower
        if (str_contains($a, 'water') || str_contains($a, 'heater') || str_contains($a, 'shower')) {
            return 'bi-water';
        }
        // Default fallback icon for any new amenity
        return 'bi-check-circle';
    }
}
