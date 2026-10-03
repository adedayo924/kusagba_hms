<?php
/** Minimal print layout. Expects: $content */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$appname = app_setting('hospital_name', defined('APP_NAME') ? APP_NAME : 'Hospital');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title ?? $appname) ?></title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons.min.css') ?>">
<style>
  body { background:#fff; color:#000; font-size:14px; }
  .print-area { max-width:420px; margin:0 auto; padding:20px 12px; font-family:'Courier New', monospace; }
  .print-area .table { font-size:13px; }
  @media print { .no-print { display:none !important; } body { margin:0; } }
</style>
</head>
<body>
<div class="print-area">
  <?php require $content; ?>
</div>
<div class="text-center mb-4 no-print">
  <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
  <a class="btn btn-outline-secondary" href="javascript:history.back()">Back</a>
</div>
</body>
</html>