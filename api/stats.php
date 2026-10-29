<?php
// Numbers for the dashboard charts (Fetch API). Admins see the whole system, owners see their own listings.
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

function out(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$user = current_user();
if (!$user) {
    out(['ok' => false, 'error' => 'Please log in first.'], 401);
}
if (!in_array($user['role'], ['admin', 'owner'], true)) {
    out(['ok' => false, 'error' => 'No charts for this account.'], 403);
}

// Turn rows of [label, value] into chart items.
function items(array $rows, array $labels = []): array
{
    return array_map(static fn($r) => ['label' => $labels[$r[0]] ?? (string) $r[0], 'value' => (int) $r[1]], $rows);
}

try {
    $pdo = getDB();
    $statusLabels = ['approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected'];

    if ($user['role'] === 'admin') {
        $status = $pdo->query('SELECT status, COUNT(*) FROM listings GROUP BY status')->fetchAll(PDO::FETCH_NUM);
        $roles  = $pdo->query('SELECT role, COUNT(*) FROM users GROUP BY role')->fetchAll(PDO::FETCH_NUM);
        $cities = $pdo->query("SELECT city, COUNT(*) AS c FROM listings WHERE status = 'approved' GROUP BY city ORDER BY c DESC LIMIT 5")->fetchAll(PDO::FETCH_NUM);
        out(['ok' => true, 'role' => 'admin', 'charts' => [
            'status' => items($status, $statusLabels),
            'roles'  => items($roles, ['student' => 'Students', 'owner' => 'Owners', 'admin' => 'Admins']),
            'cities' => items($cities),
        ]]);
    }

    $status = $pdo->prepare('SELECT status, COUNT(*) FROM listings WHERE owner_id = ? GROUP BY status');
    $status->execute([$user['id']]);
    $inq = $pdo->prepare(
        'SELECT l.title, COUNT(i.id) AS c FROM listings l LEFT JOIN inquiries i ON i.listing_id = l.id
          WHERE l.owner_id = ? GROUP BY l.id, l.title ORDER BY c DESC LIMIT 6'
    );
    $inq->execute([$user['id']]);
    out(['ok' => true, 'role' => 'owner', 'charts' => [
        'status'    => items($status->fetchAll(PDO::FETCH_NUM), $statusLabels),
        'inquiries' => items($inq->fetchAll(PDO::FETCH_NUM)),
    ]]);
} catch (PDOException $ex) {
    error_log('Stats failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'We could not load the charts.'], 500);
}
