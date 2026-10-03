<?php
page_header('Billing', 'Invoice per patient — ' . money($todayRevenue) . ' received today', '<a class="btn btn-primary" href="' . base_url('billing/create') . '"><i class="bi bi-plus-lg"></i> New Bill</a>');
?>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body py-3">
    <div class="text-muted small">Today received</div>
    <div class="h5 mb-0"><?= money($todayRevenue) ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body py-3">
    <div class="text-muted small">Transactions</div>
    <div class="h5 mb-0"><?= $todayReceived ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body py-3">
    <div class="text-muted small">Outstanding total</div>
    <div class="h5 mb-0 text-danger"><?= money(fetch_val('SELECT IFNULL(SUM(total - paid_amount),0) FROM invoices WHERE status != "void" AND status != "paid"')) ?></div>
  </div></div></div>
</div>

<form method="get" action="<?= base_url('billing') ?>" class="row g-2 mb-3">
  <div class="col-auto">
    <select class="form-select" name="status">
      <option value="" <?= $status === '' ? 'selected' : '' ?>>All statuses</option>
      <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
      <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partial</option>
      <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
      <option value="void" <?= $status === 'void' ? 'selected' : '' ?>>Void</option>
    </select>
  </div>
  <div class="col-sm-4"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search invoice no / patient..."></div>
  <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Invoice</th><th>Patient</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No invoices found.</td></tr>
      <?php else: foreach ($list as $i): ?>
        <tr>
          <td class="small fw-semibold"><?= e($i['invoice_no']) ?></td>
          <td><?= $i['patient_id'] ? e($i['patient_name']) : '<span class="text-muted">—</span>' ?><br>
            <span class="text-muted small"><?= e($i['patient_no'] ?: '') ?></span></td>
          <td class="small"><?= fmt_date($i['invoice_date']) ?></td>
          <td class="text-end"><?= money($i['total']) ?></td>
          <td class="text-end text-success"><?= money($i['paid_amount']) ?></td>
          <td class="text-end fw-semibold <?= $i['status'] === 'paid' ? 'text-muted' : 'text-danger' ?>"><?= money($i['total'] - $i['paid_amount']) ?></td>
          <td><?= status_badge($i['status']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= base_url('billing/show/' . $i['id']) ?>">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>