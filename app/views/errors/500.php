<?php
$appname = function_exists('app_setting')
    ? app_setting('hospital_name', defined('APP_NAME') ? APP_NAME : 'Hospital')
    : (defined('APP_NAME') ? APP_NAME : 'Hospital');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Something went wrong</title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card text-center">
    <div class="card shadow-lg border-0">
      <div class="card-body p-5">
        <i class="bi bi-exclamation-triangle display-4 text-warning"></i>
        <h1 class="h3 mt-3">Something went wrong</h1>
        <p class="text-muted">
          The request could not be completed. The error has been recorded in the
          server error log.
        </p>
        <p class="text-muted small">
          If this keeps happening, contact the administrator and quote the time of
          the error.
        </p>
        <a class="btn btn-primary" href="<?= base_url('dashboard') ?>">Back to Dashboard</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>