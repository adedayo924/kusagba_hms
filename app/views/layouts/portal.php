<?php
/** Patient portal layout. Expects: $content (view file path), $page_title, $active, $user */
$user = $user ?? Auth::user();
$flash = get_flash();
$appname = app_setting('hospital_name', defined('APP_NAME') ? APP_NAME : 'Hospital');
$role = Auth::role();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<title><?= e($page_title) ?> &mdash; <?= e($appname) ?></title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  .portal-nav { background: #0d3b66; }
  .portal-nav .navbar-brand, .portal-nav .btn { color: #fff; }
</style>
</head>
<body>
<nav class="navbar navbar-expand portal-nav sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-semibold" href="<?= base_url('portal') ?>"><i class="bi bi-hospital"></i> <?= e($appname) ?> <span class="badge text-bg-light ms-1">Patient portal</span></a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="text-white-50 small d-none d-md-inline"><?= e(($user['full_name'] ?? '') ?: 'Account') ?></span>
      <a class="btn btn-sm btn-outline-light" href="<?= base_url('profile') ?>"><i class="bi bi-person-circle me-1"></i>My account</a>
      <?php /* POST logout: a GET link can be triggered cross-site by any page. */ ?>
      <form method="post" action="<?= base_url('auth/logout') ?>" class="d-inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-light">
          <i class="bi bi-box-arrow-right me-1"></i>Log out
        </button>
      </form>
    </div>
  </div>
</nav>

<main class="container-fluid py-4 px-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash[0] === 'error' ? 'danger' : $flash[0]) ?> alert-dismissible fade show" role="alert">
      <?= e($flash[1]) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php require $content; ?>
</main>

<footer class="text-center text-muted small py-3">
  &copy; <?= date('Y') ?> <?= e($appname) ?> &mdash; Hospital Management System
</footer>

<script src="<?= base_url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= isset($scripts) ? $scripts : '' ?>
</body>
</html>