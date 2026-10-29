<?php
require_once __DIR__ . '/includes/auth.php';
require_role('student', 'owner');

$user    = current_user();
$isOwner = $user['role'] === 'owner';
$notifications = [];
$inquiries     = [];

try {
    $pdo = getDB();

    // Show the latest notifications, then mark them as read.
    $notifStmt = $pdo->prepare('SELECT message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 8');
    $notifStmt->execute([$user['id']]);
    $notifications = $notifStmt->fetchAll();
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$user['id']]);

    if ($isOwner) {
        $stmt = $pdo->prepare(
            "SELECT i.id, i.message, i.move_in_date, i.status, i.created_at, l.id AS listing_id, l.title,
                    s.full_name AS other_name, s.email AS other_email, s.phone AS other_phone
               FROM inquiries i JOIN listings l ON l.id = i.listing_id JOIN users s ON s.id = i.student_id
              WHERE l.owner_id = ? ORDER BY (i.status = 'pending') DESC, i.created_at DESC"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT i.id, i.message, i.move_in_date, i.status, i.created_at, l.id AS listing_id, l.title,
                    o.full_name AS other_name, o.email AS other_email, o.phone AS other_phone
               FROM inquiries i JOIN listings l ON l.id = i.listing_id JOIN users o ON o.id = l.owner_id
              WHERE i.student_id = ? ORDER BY i.created_at DESC"
        );
    }
    $stmt->execute([$user['id']]);
    $inquiries = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('Inquiries failed: ' . $ex->getMessage());
}

$statusInfo  = ['pending' => ['Pending', 'warning'], 'accepted' => ['Accepted', 'success'], 'declined' => ['Declined', 'secondary']];
$pageTitle   = 'Inquiries';
$pageScripts = ['assets/js/inquiries.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" id="inquiriesPage" data-csrf="<?= e(csrf_token()) ?>">
  <h1 class="h2"><i class="bi bi-envelope" aria-hidden="true"></i> Inquiries</h1>
  <p class="text-body-secondary">
    You are logged in as <?= $isOwner ? 'an owner. These are the inquiries students sent you.' : 'a student. These are the inquiries you sent to owners.' ?>
  </p>
  <div id="pageAlert" aria-live="polite"></div>

  <?php if ($notifications): ?>
    <div class="card shadow-sm mb-4"><div class="card-body">
      <h2 class="h6 mb-3"><i class="bi bi-bell" aria-hidden="true"></i> Recent notifications</h2>
      <ul class="list-unstyled mb-0">
        <?php foreach ($notifications as $n): ?>
          <li class="d-flex justify-content-between gap-3 py-1 <?= $n['is_read'] ? 'text-body-secondary' : 'fw-semibold' ?>">
            <span><?= e($n['message']) ?></span>
            <small class="text-nowrap"><?= e(date('M j, g:i a', strtotime($n['created_at']))) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    </div></div>
  <?php endif; ?>

  <?php if (!$inquiries): ?>
    <div class="card shadow-sm"><div class="card-body text-center py-5">
      <i class="bi bi-envelope-open fs-1 text-body-secondary" aria-hidden="true"></i>
      <p class="mt-3 text-body-secondary mb-0"><?= $isOwner ? 'No inquiries yet.' : 'You have not sent any inquiries yet.' ?></p>
      <?php if (!$isOwner): ?><a class="btn btn-primary mt-3" href="<?= url('listings.php') ?>">Find a dorm</a><?php endif; ?>
    </div></div>
  <?php else: ?>
    <div class="vstack gap-3">
      <?php foreach ($inquiries as $inq): ?>
        <?php [$statusText, $statusColor] = $statusInfo[$inq['status']] ?? [$inq['status'], 'secondary']; ?>
        <article class="card shadow-sm" data-inquiry="<?= (int) $inq['id'] ?>">
          <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
              <h2 class="h5 mb-0"><a class="text-decoration-none" href="<?= url('listing.php?id=' . (int) $inq['listing_id']) ?>"><?= e($inq['title']) ?></a></h2>
              <span class="badge text-bg-<?= e($statusColor) ?> inq-status"><?= e($statusText) ?></span>
            </div>
            <p class="small text-body-secondary mb-2">
              <?= $isOwner ? 'From' : 'To' ?> <strong><?= e($inq['other_name']) ?></strong>
              &middot; sent <?= e(date('M j, Y', strtotime($inq['created_at']))) ?>
              <?php if ($inq['move_in_date']): ?>&middot; wants to move in <?= e(date('M j, Y', strtotime($inq['move_in_date']))) ?><?php endif; ?>
            </p>
            <p class="mb-3"><?= nl2br(e($inq['message'])) ?></p>
            <p class="small mb-0">
              <i class="bi bi-telephone" aria-hidden="true"></i>
              <?php if ($inq['other_phone']): ?>
                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $inq['other_phone'])) ?>"><?= e($inq['other_phone']) ?></a>
              <?php else: ?>no phone number<?php endif; ?>
              &middot; <i class="bi bi-envelope" aria-hidden="true"></i> <a href="mailto:<?= e($inq['other_email']) ?>"><?= e($inq['other_email']) ?></a>
            </p>
            <?php if ($isOwner && $inq['status'] === 'pending'): ?>
              <div class="d-flex gap-2 mt-3 inq-actions">
                <button type="button" class="btn btn-primary btn-sm inq-action" data-id="<?= (int) $inq['id'] ?>" data-action="accept">Accept</button>
                <button type="button" class="btn btn-outline-secondary btn-sm inq-action" data-id="<?= (int) $inq['id'] ?>" data-action="decline">Decline</button>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
