<?php
// Students save or remove a favorite listing. Returns JSON.
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

function out(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
$user = current_user();
if (!$user) {
    out(['ok' => false, 'error' => 'Please log in first.'], 401);
}
if ($user['role'] !== 'student') {
    out(['ok' => false, 'error' => 'Only students can save favorites.'], 403);
}
if (!verify_csrf()) {
    out(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
}

$listingId = (int) ($_POST['listing_id'] ?? 0);

try {
    $pdo = getDB();
    $check = $pdo->prepare("SELECT 1 FROM listings WHERE id = ? AND status = 'approved'");
    $check->execute([$listingId]);
    if (!$check->fetchColumn()) {
        out(['ok' => false, 'error' => 'This listing is not available.'], 404);
    }

    $exists = $pdo->prepare('SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?');
    $exists->execute([$user['id'], $listingId]);
    $row = $exists->fetch();

    if ($row) {
        $pdo->prepare('DELETE FROM favorites WHERE id = ?')->execute([$row['id']]);
        out(['ok' => true, 'favorited' => false]);
    }
    $pdo->prepare('INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)')->execute([$user['id'], $listingId]);
    out(['ok' => true, 'favorited' => true]);
} catch (PDOException $ex) {
    error_log('Favorite failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Something went wrong. Please try again.'], 500);
}
