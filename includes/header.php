<?php
require_once __DIR__ . '/init.php';

$pageTitle   = $pageTitle ?? 'DormFinder';
$user        = current_user();
$flash       = get_flash();
$currentPage = basename($_SERVER['PHP_SELF']);

// Number of unread notifications (shown as a red badge on the envelope icon).
$unreadCount = 0;
if ($user && function_exists('getDB')) {
    try {
        $unreadStmt = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $unreadStmt->execute([$user['id']]);
        $unreadCount = (int) $unreadStmt->fetchColumn();
    } catch (Throwable $ex) {
        error_log('Unread count failed: ' . $ex->getMessage());
    }
}

function nav_active(string $page): string
{
    global $currentPage;
    return $currentPage === $page ? ' active' : '';
}

// Local paths go through url(); full https:// links are used as they are.
function asset_href(string $path): string
{
    return preg_match('#^https?://#', $path) ? $path : url($path);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | DormFinder</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <?php foreach (($pageStyles ?? []) as $style): ?>
  <link rel="stylesheet" href="<?= e(asset_href($style)) ?>">
  <?php endforeach; ?>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100">
  <a class="visually-hidden-focusable" href="#main">Skip to main content</a>

  <header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom" aria-label="Main navigation">
      <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= url('index.php') ?>">
          <i class="bi bi-house-heart-fill text-primary" aria-hidden="true"></i> DormFinder
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false"
                aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link<?= nav_active('index.php') ?>" href="<?= url('index.php') ?>">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= nav_active('listings.php') ?>" href="<?= url('listings.php') ?>">Find a dorm</a>
            </li>
          </ul>

          <ul class="navbar-nav align-items-lg-center gap-lg-1">
            <?php if ($user): ?>
              <?php if ($user['role'] === 'student'): ?>
                <li class="nav-item">
                  <a class="nav-link<?= nav_active('favorites.php') ?>" href="<?= url('favorites.php') ?>" aria-label="Favorites">
                    <i class="bi bi-heart fs-5" aria-hidden="true"></i><span class="d-lg-none ms-2">Favorites</span>
                  </a>
                </li>
              <?php endif; ?>
              <?php if (in_array($user['role'], ['student', 'owner'], true)): ?>
                <li class="nav-item">
                  <a class="nav-link position-relative<?= nav_active('inquiries.php') ?>" href="<?= url('inquiries.php') ?>"
                     aria-label="Inquiries<?= $unreadCount ? ', ' . $unreadCount . ' new' : '' ?>">
                    <i class="bi bi-envelope fs-5" aria-hidden="true"></i><span class="d-lg-none ms-2">Inquiries</span>
                    <?php if ($unreadCount > 0): ?>
                      <span class="badge rounded-pill text-bg-danger ms-1 ms-lg-0 position-lg-absolute"><?= $unreadCount ?></span>
                    <?php endif; ?>
                  </a>
                </li>
              <?php endif; ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                   aria-label="Account menu for <?= e($user['name']) ?>">
                  <i class="bi bi-person-circle fs-5" aria-hidden="true"></i><span class="d-lg-none ms-2"><?= e($user['name']) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><span class="dropdown-item-text small text-body-secondary"><?= e($user['name']) ?></span></li>
                  <li><a class="dropdown-item" href="<?= url('profile.php') ?>">Profile</a></li>
                  <li><a class="dropdown-item" href="<?= url('dashboard.php') ?>">Dashboard</a></li>
                  <?php if ($user['role'] === 'owner'): ?>
                    <li><a class="dropdown-item" href="<?= url('owner/my-listings.php') ?>">My listings</a></li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="<?= url('logout.php') ?>">Log out</a></li>
                </ul>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a class="nav-link<?= nav_active('login.php') ?>" href="<?= url('login.php') ?>">Log in</a>
              </li>
              <li class="nav-item">
                <a class="btn btn-primary" href="<?= url('register.php') ?>">Sign up</a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <main id="main" class="flex-grow-1">
    <?php if ($flash): ?>
      <div class="container mt-3">
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show flash-message" role="alert">
          <?= e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>
