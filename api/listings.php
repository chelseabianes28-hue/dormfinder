<?php
// JSON endpoint used by the Find a dorm page (Fetch API). Only approved listings are returned.
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function out(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

// ---- Read and validate the filters ----
$q       = trim((string) ($_GET['q'] ?? ''));
$type    = in_array($_GET['type'] ?? '', ['dormitory', 'bedspace', 'boarding_house'], true) ? $_GET['type'] : '';
$gender  = in_array($_GET['gender'] ?? '', ['any', 'male_only', 'female_only'], true) ? $_GET['gender'] : '';
$min     = is_numeric($_GET['min_price'] ?? '') ? max(0, (float) $_GET['min_price']) : null;
$max     = is_numeric($_GET['max_price'] ?? '') ? max(0, (float) $_GET['max_price']) : null;
$onlyAvailable = !empty($_GET['available']);
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;
$hasCoords = is_numeric($lat) && is_numeric($lng) && abs((float) $lat) <= 90 && abs((float) $lng) <= 180;

$sort = in_array($_GET['sort'] ?? '', ['newest', 'price_asc', 'price_desc', 'nearest'], true) ? $_GET['sort'] : 'newest';
if ($sort === 'nearest' && !$hasCoords) {
    $sort = 'newest';
}
if (mb_strlen($q) > 100) {
    out(['ok' => false, 'error' => 'Search text is too long.'], 422);
}

// ---- Build the query with prepared statements ----
$where  = ["l.status = 'approved'"];
$params = [];

if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(l.title LIKE ? OR l.address LIKE ? OR l.city LIKE ? OR l.amenities LIKE ?)';
    array_push($params, $like, $like, $like, $like);
}
if ($type !== '') {
    $where[] = 'l.type = ?';
    $params[] = $type;
}
if ($gender !== '') {
    $where[] = "(l.gender_policy = ? OR l.gender_policy = 'any')";
    $params[] = $gender;
}
if ($min !== null) {
    $where[] = 'l.price_per_month >= ?';
    $params[] = $min;
}
if ($max !== null && $max > 0) {
    $where[] = 'l.price_per_month <= ?';
    $params[] = $max;
}
if ($onlyAvailable) {
    $where[] = 'l.slots_available > 0';
}
$whereSql = implode(' AND ', $where);

$distanceSql    = 'NULL AS distance_km';
$distanceParams = [];
if ($hasCoords) {
    $distanceSql = '(6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(l.latitude)) * COS(RADIANS(l.longitude) - RADIANS(?))
                    + SIN(RADIANS(?)) * SIN(RADIANS(l.latitude))))) AS distance_km';
    $distanceParams = [(float) $lat, (float) $lng, (float) $lat];
}

$orderSql = [
    'newest'     => 'l.created_at DESC, l.id DESC',
    'price_asc'  => 'l.price_per_month ASC, l.id DESC',
    'price_desc' => 'l.price_per_month DESC, l.id DESC',
    'nearest'    => 'distance_km ASC, l.id DESC',
][$sort];

try {
    $pdo = getDB();

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM listings l WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $pages  = max(1, (int) ceil($total / $perPage));
    $page   = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare(
        "SELECT l.id, l.title, l.address, l.city, l.type, l.price_per_month, l.slots_available, l.gender_policy,
                (SELECT p.file_path FROM listing_photos p WHERE p.listing_id = l.id
                  ORDER BY p.is_primary DESC, p.id ASC LIMIT 1) AS photo,
                $distanceSql
           FROM listings l
          WHERE $whereSql
          ORDER BY $orderSql
          LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute(array_merge($distanceParams, $params));

    $listings = array_map(static function (array $row): array {
        return [
            'id'              => (int) $row['id'],
            'title'           => $row['title'],
            'address'         => $row['address'],
            'city'            => $row['city'],
            'type'            => $row['type'],
            'price_per_month' => (float) $row['price_per_month'],
            'slots_available' => (int) $row['slots_available'],
            'gender_policy'   => $row['gender_policy'],
            'photo_url'       => $row['photo'] ? url($row['photo']) : null,
            'link'            => url('listing.php?id=' . (int) $row['id']),
            'distance_km'     => $row['distance_km'] !== null ? round((float) $row['distance_km'], 1) : null,
        ];
    }, $stmt->fetchAll());

    out(['ok' => true, 'total' => $total, 'page' => $page, 'pages' => $pages, 'listings' => $listings]);
} catch (PDOException $ex) {
    error_log('Listings API failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'We could not load listings. Please try again.'], 500);
}
