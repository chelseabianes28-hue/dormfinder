<?php
// Unread notification count for the navbar badge. main.js asks every 30 seconds, so new
// inquiries and replies show up without reloading the page. Returns JSON.
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
$user = current_user();
if (!$user) {
    out(['ok' => false, 'error' => 'Please log in first.'], 401);
}

try {
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user['id']]);
    $unread = (int) $stmt->fetchColumn();

    $latest = null;
    if ($unread > 0) {
        $msg = getDB()->prepare('SELECT message FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC, id DESC LIMIT 1');
        $msg->execute([$user['id']]);
        $latest = $msg->fetchColumn() ?: null;
    }
    out(['ok' => true, 'unread' => $unread, 'latest' => $latest]);
} catch (PDOException $ex) {
    error_log('Notification count failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not check notifications.'], 500);
}
