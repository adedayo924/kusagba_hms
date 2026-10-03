<?php $appname = app_setting('hospital_name', defined('APP_NAME') ? APP_NAME : 'Hospital'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Page not found</title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card text-center">
    <div class="card shadow-lg border-0">
      <div class="card-body p-5">
        <i class="bi bi-patch-question display-4 text-warning"></i>
        <h1 class="h3 mt-3">404 &mdash; Page not found</h1>
        <p class="text-muted">The page you are looking for does not exist.</p>
        <a class="btn btn-primary" href="<?= base_url('dashboard') ?>">Back to Dashboard</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>