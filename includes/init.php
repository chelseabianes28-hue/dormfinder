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

// Regex-based amenity engine: turns a typed amenity ("Free Wi-Fi", "Aircon room") into a Bootstrap Icon.
// \b = word boundary, so "ac" matches "AC" or "A/C" but not the "ac" inside "space".
if (!function_exists('get_amenity_icon')) {
    function get_amenity_icon(string $amenity): string
    {
        $rules = [
            'bi-wifi'         => '/\b(wi-?fi|internet|wireless)\b/i',
            'bi-snow'         => '/\b(air[\s-]?con(ditioning|ditioned)?|aircon|a\/?c|ac)\b/i',
            'bi-droplet-half' => '/\b(laundry|washing|washer|dryer|clothes)\b/i',
            'bi-car-front'    => '/\b(parking|garage|car|vehicle|motor(cycle)?)\b/i',
            'bi-cup-hot'      => '/\b(kitchen|cook(ing)?|stove|pantry)\b/i',
            'bi-shield-check' => '/\b(security|cctv|guard|gated)\b/i',
            'bi-water'        => '/\b(water|heater|shower|bath(room)?|toilet|cr)\b/i',
            'bi-fan'          => '/\b(electric fan|fan)\b/i',
            'bi-lightning'    => '/\b(electric(ity)?|power|outlet|generator)\b/i',
            'bi-book'         => '/\b(study|desk|library)\b/i',
            'bi-tv'           => '/\b(tv|television|cable)\b/i',
        ];
        foreach ($rules as $icon => $pattern) {
            if (preg_match($pattern, $amenity)) {
                return $icon;
            }
        }
        return 'bi-check-circle';   // default icon for any other amenity
    }
}
