<?php $appname = app_setting('hospital_name', defined('APP_NAME') ? APP_NAME : 'Hospital'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Access denied</title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card text-center">
    <div class="card shadow-lg border-0">
      <div class="card-body p-5">
        <i class="bi bi-shield-exclamation display-4 text-danger"></i>
        <h1 class="h3 mt-3">403 &mdash; Access denied</h1>
        <p class="text-muted">You do not have permission to view this page.</p>
        <a class="btn btn-primary" href="<?= base_url('dashboard') ?>">Back to Dashboard</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>