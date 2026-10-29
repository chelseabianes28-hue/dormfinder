<?php
// Owners accept or decline an inquiry sent to one of their listings. Returns JSON.
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
if ($user['role'] !== 'owner') {
    out(['ok' => false, 'error' => 'Only owners can reply to inquiries.'], 403);
}
if (!verify_csrf()) {
    out(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
}

$inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
$action    = $_POST['action'] ?? '';
if (!in_array($action, ['accept', 'decline'], true)) {
    out(['ok' => false, 'error' => 'Invalid action.'], 422);
}
$newStatus = $action === 'accept' ? 'accepted' : 'declined';

try {
    $pdo = getDB();

    // The inquiry must belong to a listing owned by this user, and still be pending.
    $stmt = $pdo->prepare(
        "SELECT i.student_id, l.title FROM inquiries i JOIN listings l ON l.id = i.listing_id
          WHERE i.id = ? AND l.owner_id = ? AND i.status = 'pending'"
    );
    $stmt->execute([$inquiryId, $user['id']]);
    $inquiry = $stmt->fetch();
    if (!$inquiry) {
        out(['ok' => false, 'error' => 'This inquiry was not found or was already answered.'], 404);
    }

    $pdo->beginTransaction();
    $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute([$newStatus, $inquiryId]);
    $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)')->execute([
        $inquiry['student_id'],
        mb_substr('Your inquiry on "' . $inquiry['title'] . '" was ' . $newStatus . '.', 0, 255),
    ]);
    $pdo->commit();

    out(['ok' => true, 'status' => $newStatus]);
} catch (PDOException $ex) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Inquiry update failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Something went wrong. Please try again.'], 500);
}
