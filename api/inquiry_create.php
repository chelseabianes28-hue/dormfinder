<?php
// Students send an inquiry to the owner of a listing. Returns JSON.
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
    out(['ok' => false, 'error' => 'Only students can send inquiries.'], 403);
}
if (!verify_csrf()) {
    out(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
}

$listingId = (int) ($_POST['listing_id'] ?? 0);
$message   = trim((string) ($_POST['message'] ?? ''));
$moveIn    = trim((string) ($_POST['move_in_date'] ?? ''));

$length = mb_strlen($message);
if ($length < 10 || $length > 1000) {
    out(['ok' => false, 'error' => 'Please write a message of 10 to 1000 characters.'], 422);
}
if ($moveIn !== '') {
    $date = DateTime::createFromFormat('Y-m-d', $moveIn);
    $valid = $date && $date->format('Y-m-d') === $moveIn
        && $moveIn >= date('Y-m-d') && $moveIn <= date('Y-m-d', strtotime('+2 years'));
    if (!$valid) {
        out(['ok' => false, 'error' => 'Please choose a move-in date from today up to two years ahead.'], 422);
    }
} else {
    $moveIn = null;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT owner_id, title FROM listings WHERE id = ? AND status = 'approved'");
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();
    if (!$listing) {
        out(['ok' => false, 'error' => 'This listing is not available.'], 404);
    }

    $dup = $pdo->prepare("SELECT 1 FROM inquiries WHERE listing_id = ? AND student_id = ? AND status = 'pending'");
    $dup->execute([$listingId, $user['id']]);
    if ($dup->fetchColumn()) {
        out(['ok' => false, 'error' => 'You already sent an inquiry for this listing. Please wait for the owner to reply.'], 409);
    }

    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO inquiries (listing_id, student_id, message, move_in_date) VALUES (?, ?, ?, ?)')
        ->execute([$listingId, $user['id'], $message, $moveIn]);
    $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)')
        ->execute([$listing['owner_id'], mb_substr('New inquiry on "' . $listing['title'] . '" from ' . $user['name'], 0, 255)]);
    $pdo->commit();

    out(['ok' => true, 'message' => 'Inquiry sent! The owner will be notified.']);
} catch (PDOException $ex) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Inquiry failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Something went wrong. Please try again.'], 500);
}
