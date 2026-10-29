<?php
require_once __DIR__ . '/includes/auth.php';

$user       = current_user();
$adminEmail = admin_email();
$subject    = 'DormFinder support request';
$body       = ($user ? 'Name: ' . $user['name'] . "\nRole: " . $user['role'] . "\n\n" : '') . 'My question or problem:' . "\n";

$gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($adminEmail)
          . '&su=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
$mailtoUrl = 'mailto:' . $adminEmail . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);

$pageTitle = 'Contact';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <h1 class="h2">Contact the administrator</h1>
      <p class="text-body-secondary">
        Need help with your account, a listing, or a report? Send us an email and we will reply as soon as we can.
      </p>

      <div class="card shadow-sm mb-4"><div class="card-body p-4">
        <?php if ($adminEmail): ?>
          <p class="mb-3"><i class="bi bi-envelope" aria-hidden="true"></i>
            <strong><?= e($adminEmail) ?></strong></p>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="<?= e($gmailUrl) ?>" target="_blank" rel="noopener noreferrer">
              <i class="bi bi-google" aria-hidden="true"></i> Write in Gmail</a>
            <a class="btn btn-outline-secondary" href="<?= e($mailtoUrl) ?>">
              <i class="bi bi-envelope-at" aria-hidden="true"></i> Use my email app</a>
          </div>
        <?php else: ?>
          <p class="mb-0 text-body-secondary">The administrator's contact email is not set up yet.</p>
        <?php endif; ?>
      </div></div>

      <h2 class="h5">To help us reply faster, please include</h2>
      <ul class="text-body-secondary">
        <li>The email address you registered with.</li>
        <li>The title of the listing, if your question is about a listing.</li>
        <li>A short description of what happened.</li>
      </ul>
      <p class="small text-body-secondary">
        For questions about a specific dorm, use the Inquire button or the owner's contact details on the listing page instead.
      </p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
