<?php
page_header('Stock movements', 'History of all stock changes');
?>
<form method="get" action="<?= base_url('inventory/movements') ?>" class="row g-2 mb-3">
  <div class="col-auto">
    <select class="form-select" name="item">
      <option value="">All items</option>
      <?php foreach ($items as $it2): ?>
        <option value="<?= $it2['id'] ?>" <?= $itemId !== '' && (int)$itemId === (int)$it2['id'] ? 'selected' : '' ?>><?= e($it2['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-4"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search reference / notes..."></div>
  <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
  <div class="col-auto ms-auto"><a class="btn btn-outline-secondary" href="<?= base_url('inventory') ?>"><i class="bi bi-arrow-left"></i> Back to inventory</a></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Date</th><th>Item</th><th>Type</th><th class="text-end">Qty</th><th>Batch</th><th>Expiry</th><th>Reference</th><th>Notes</th><th>By</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No movements found.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td class="small"><?= fmt_dt($r['created_at']) ?></td>
          <td class="fw-semibold"><?= e($r['item_name']) ?></td>
          <td><?= status_badge($r['movement_type']) ?></td>
          <td class="text-end fw-semibold <?= $r['quantity'] < 0 ? 'text-danger' : 'text-success' ?>"><?= $r['quantity'] > 0 ? '+' : '' ?><?= (int)$r['quantity'] ?></td>
          <td class="small"><?= e($r['batch_no'] ?: '—') ?></td>
          <td class="small"><?= fmt_date($r['expiry_date'], 'M j, Y') ?></td>
          <td class="small"><?= e($r['reference'] ?: '—') ?></td>
          <td class="small text-muted"><?= e($r['notes'] ?: '—') ?></td>
          <td class="small"><?= e($r['user_name'] ?? '—') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>