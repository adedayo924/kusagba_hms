<?php $appname = e(app_setting('hospital_name', defined('APP_NAME') ? APP_NAME : 'Hospital')); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<title>Log in &mdash; <?= $appname ?></title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="card shadow-lg border-0">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
          <div class="brand-icon mx-auto mb-3" style="width:54px;height:54px;font-size:1.5rem;
               border-radius:14px;background:linear-gradient(135deg,#0d9488,#6366f1);
               color:#fff;display:inline-flex;align-items:center;justify-content:center;">
            <i class="bi bi-hospital"></i>
          </div>
          <h1 class="h4 mb-1"><?= $appname ?></h1>
          <p class="text-muted small mb-0">Sign in to the hospital management system</p>
        </div>

        <?php $flash = get_flash(); if ($flash): ?>
          <div class="alert alert-<?= e($flash[0] === 'error' ? 'danger' : $flash[0]) ?> py-2"><?= e($flash[1]) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('auth/login') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input class="form-control" name="username" autocomplete="username" required autofocus>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input class="form-control" type="password" name="password" autocomplete="current-password" required>
            </div>
          </div>
          <div class="d-grid">
            <button class="btn btn-primary btn-lg">Sign in</button>
          </div>
        </form>
      </div>
    </div>
    <p class="text-center text-white-50 small mt-3 mb-0">Hospital Management System &copy; <?= date('Y') ?></p>
  </div>
</div>
</body>
</html>