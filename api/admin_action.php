<?php
// Admin actions: approve or reject a listing, enable or disable a user. Returns JSON.
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
$admin = current_user();
if (!$admin) {
    out(['ok' => false, 'error' => 'Please log in first.'], 401);
}
if ($admin['role'] !== 'admin') {
    out(['ok' => false, 'error' => 'Only administrators can do this.'], 403);
}
if (!verify_csrf()) {
    out(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
}

$action = $_POST['action'] ?? '';
$id     = (int) ($_POST['id'] ?? 0);
if (!in_array($action, ['approve_listing', 'reject_listing', 'enable_user', 'disable_user'], true)) {
    out(['ok' => false, 'error' => 'Invalid action.'], 422);
}

try {
    $pdo = getDB();

    if ($action === 'approve_listing' || $action === 'reject_listing') {
        $stmt = $pdo->prepare('SELECT owner_id, title FROM listings WHERE id = ?');
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
        if (!$listing) {
            out(['ok' => false, 'error' => 'Listing not found.'], 404);
        }
        $status = $action === 'approve_listing' ? 'approved' : 'rejected';

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE listings SET status = ? WHERE id = ?')->execute([$status, $id]);
        $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)')->execute([
            $listing['owner_id'],
            mb_substr('Your listing "' . $listing['title'] . '" was ' . $status . ' by the administrator.', 0, 255),
        ]);
        $pdo->commit();
        out(['ok' => true, 'status' => $status]);
    }

    // enable_user / disable_user
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $target = $stmt->fetch();
    if (!$target) {
        out(['ok' => false, 'error' => 'User not found.'], 404);
    }
    if ($target['role'] === 'admin' || $id === (int) $admin['id']) {
        out(['ok' => false, 'error' => 'Administrator accounts cannot be changed here.'], 403);
    }
    $active = $action === 'enable_user' ? 1 : 0;
    $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$active, $id]);
    out(['ok' => true, 'active' => (bool) $active]);
} catch (PDOException $ex) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Admin action failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Something went wrong. Please try again.'], 500);
}
