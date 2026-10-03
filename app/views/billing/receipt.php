<?php
$hname = app_setting('hospital_name', defined('APP_NAME') ? APP_NAME : 'Hospital');
$addr  = app_setting('hospital_address', defined('HOSPITAL_ADDRESS') ? HOSPITAL_ADDRESS : '');
$phone = app_setting('hospital_phone');
$email = app_setting('hospital_email');
$footer = app_setting('receipt_footer');
?>
<div class="text-center mb-3">
  <h2 class="h4 mb-0 fw-bold"><?= e($hname) ?></h2>
  <?php if ($addr): ?><div><?= e($addr) ?></div><?php endif; ?>
  <?php if ($phone || $email): ?><div><?= e($phone ?: '') ?> <?= e($email) ? '· ' . e($email) : '' ?></div><?php endif; ?>
  <div class="text-muted small">OFFICIAL RECEIPT</div>
</div>
<hr>
<table class="table table-sm mb-3">
  <tr><td class="text-muted">Receipt no.</td><td class="text-end fw-bold"><?= e($inv['invoice_no']) ?></td></tr>
  <tr><td class="text-muted">Date</td><td class="text-end"><?= fmt_dt($inv['updated_at']) ?></td></tr>
  <?php if ($patient): ?>
  <tr><td class="text-muted">Patient</td><td class="text-end"><?= e($patient['last_name']) ?>, <?= e($patient['first_name']) ?></td></tr>
  <tr><td class="text-muted">Reg. no.</td><td class="text-end"><?= e($patient['patient_no']) ?></td></tr>
  <?php endif; ?>
  <?php if ($inv['discount'] > 0): ?><tr><td class="text-muted">Discount</td><td class="text-end"><?= money($inv['discount']) ?></td></tr><?php endif; ?>
</table>
<table class="table table-sm border-bottom mb-2">
  <thead><tr><th class="border-0">Description</th><th class="text-end border-0">Qty</th><th class="text-end border-0">Amount</th></tr></thead>
  <tbody>
  <?php foreach ($items as $it): ?>
    <tr><td><?= e($it['description']) ?></td><td class="text-end"><?= (int)$it['qty'] ?></td><td class="text-end"><?= money($it['amount']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<table class="table table-sm mb-4">
  <tr><th>Total</th><th class="text-end"><?= money($inv['total']) ?></th></tr>
  <?php foreach ($payments as $p): ?>
  <tr><td class="text-muted">Paid <?= fmt_dt($p['paid_at'], 'M j, h:i A') ?> (<?= e($p['method']) ?>)<?= $p['payment_no'] ? ' · ' . e($p['payment_no']) : '' ?></td>
      <td class="text-end"><?= money($p['amount']) ?></td></tr>
  <?php endforeach; ?>
  <tr><th>Outstanding balance</th>
      <th class="text-end <?= invoice_balance($inv) > 0 ? 'text-danger' : 'text-success' ?>"><?= money(invoice_balance($inv)) ?></th></tr>
</table>
<div class="d-flex justify-content-between mt-5 pt-3">
  <div class="text-center text-muted small"><?= $footer ? e($footer) : 'Thank you.' ?><br><br>______________________</div>
</div>
<p class="text-center text-muted mt-2 small">Generated <?= fmt_dt(date('Y-m-d H:i:s')) ?> by <?= e(Auth::user()['full_name'] ?? '') ?></p>