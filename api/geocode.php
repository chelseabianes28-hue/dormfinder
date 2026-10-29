<?php
// Turns a typed address into map coordinates using OpenStreetMap's Nominatim service.
// Follows the Nominatim usage policy: identifying User-Agent, at most 1 request per second
// for the whole app, cached results, one lookup per click (no search-as-you-type).
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

const GEO_COUNTRY    = 'ph';   // search only inside the Philippines
const GEO_USER_AGENT = 'DormFinder/1.0 (Web Systems Technology school project)';

function out(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function http_get(string $url): string
{
    if (!function_exists('curl_init')) {
        $context = stream_context_create(['http' => ['header' => 'User-Agent: ' . GEO_USER_AGENT . "\r\n", 'timeout' => 8]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new RuntimeException('Request failed.');
        }
        return $body;
    }

    $ch = curl_init($url);
    $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => GEO_USER_AGENT];
    // XAMPP on Windows often needs the CA bundle path for HTTPS to verify.
    foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile'), 'C:/xampp/apache/bin/curl-ca-bundle.crt', 'C:/xampp/php/extras/ssl/cacert.pem'] as $ca) {
        if ($ca && is_file($ca)) {
            $options[CURLOPT_CAINFO] = $ca;
            break;
        }
    }
    curl_setopt_array($ch, $options);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($body === false || $code !== 200) {
        $reason = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Geocoder request failed (' . $code . ') ' . $reason);
    }
    curl_close($ch);
    return $body;
}

// One cached, rate-limited lookup. Returns the first match or null.
function lookup(string $query): ?array
{
    $cacheDir = sys_get_temp_dir() . '/dormfinder_geocache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . '/' . md5(mb_strtolower($query)) . '.json';
    if (is_file($cacheFile) && filemtime($cacheFile) > time() - 30 * 86400) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        return $cached['result'] ?? null;
    }

    // Shared lock file so all users together stay under 1 request per second.
    $lock = fopen(sys_get_temp_dir() . '/dormfinder_geocode.lock', 'c+');
    flock($lock, LOCK_EX);
    try {
        $last = (float) stream_get_contents($lock);
        $wait = 1.2 - (microtime(true) - $last);
        if ($wait > 0) {
            usleep((int) ($wait * 1000000));
        }
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $query, 'format' => 'jsonv2', 'limit' => 1, 'countrycodes' => GEO_COUNTRY,
        ]);
        $rows = json_decode(http_get($url), true);
    } finally {
        ftruncate($lock, 0);
        rewind($lock);
        fwrite($lock, (string) microtime(true));
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    $result = is_array($rows) && isset($rows[0]['lat'], $rows[0]['lon'])
        ? ['lat' => (float) $rows[0]['lat'], 'lng' => (float) $rows[0]['lon'], 'name' => (string) ($rows[0]['display_name'] ?? '')]
        : null;
    @file_put_contents($cacheFile, json_encode(['result' => $result]));
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
$user = current_user();
if (!$user) {
    out(['ok' => false, 'error' => 'Please log in first.'], 401);
}
if (!in_array($user['role'], ['owner', 'admin'], true)) {
    out(['ok' => false, 'error' => 'Only owners can look up addresses.'], 403);
}

$address = trim((string) ($_GET['address'] ?? ''));
$city    = trim((string) ($_GET['city'] ?? ''));
if (mb_strlen($address) < 3 || mb_strlen($address) > 255 || mb_strlen($city) < 2 || mb_strlen($city) > 100) {
    out(['ok' => false, 'error' => 'Please type the street address and the city first.'], 422);
}

// One lookup every 2 seconds per person.
if (($_SESSION['geo_last'] ?? 0) > microtime(true) - 2) {
    out(['ok' => false, 'error' => 'Please wait a moment before searching again.'], 429);
}
$_SESSION['geo_last'] = microtime(true);

try {
    $match = lookup($address . ', ' . $city);
    if ($match) {
        out(['ok' => true, 'precision' => 'address', 'lat' => $match['lat'], 'lng' => $match['lng'], 'display_name' => $match['name']]);
    }
    $cityMatch = lookup($city);          // fall back to the city so the map at least moves there
    if ($cityMatch) {
        out(['ok' => true, 'precision' => 'city', 'lat' => $cityMatch['lat'], 'lng' => $cityMatch['lng'], 'display_name' => $cityMatch['name']]);
    }
    out(['ok' => false, 'error' => 'We could not find that address. Please click the map to place the pin.'], 404);
} catch (Throwable $ex) {
    error_log('Geocode failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'The address search is not available right now. Please click the map to place the pin.'], 502);
}
