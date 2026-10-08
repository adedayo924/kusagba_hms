<?php
$actions = '<a class="btn btn-outline-primary" href="' . base_url('billing/receipt/' . $inv['id']) . '" target="_blank"><i class="bi bi-printer"></i> Receipt</a>'
    . ($inv['status'] === 'void' ? '' : '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#payModal" ' . ($balance <= 0 ? 'disabled' : '') . '><i class="bi bi-cash-coin"></i> Record payment</button>')
    . (Auth::role() === 'admin' && $inv['status'] !== 'void' && $balance > 0
        ? '<form method="post" action="' . base_url('billing/void/' . $inv['id']) . '" class="d-inline" onsubmit="return confirm(\'Void this invoice?\')">' . csrf_field() . '<button class="btn btn-outline-danger"><i class="bi bi-x-circle"></i></button></form>'
        : '');
page_header('Invoice ' . $inv['invoice_no'], '', $actions);
?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Invoice items</div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit</th><th class="text-end">Amount</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= e($it['description']) ?>
                <?php if ($it['item_type']): ?><span class="badge text-bg-light text-muted ms-1"><?= e($it['item_type']) ?></span><?php endif; ?></td>
              <td class="text-end"><?= (int)$it['qty'] ?></td>
              <td class="text-end"><?= money($it['unit_price']) ?></td>
              <td class="text-end"><?= money($it['amount']) ?></td>
              <td class="text-end">
                <?php if ($inv['status'] !== 'void' && in_array($it['item_type'], ['', 'service', 'caregiving'], true)): ?>
                  <form method="post" action="<?= base_url('billing/remove_line/' . $inv['id'] . '/' . $it['id']) ?>" class="d-inline"
                        onsubmit="return confirm('Remove this line?')"><?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button></form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th colspan="3" class="text-end">Subtotal</th><th class="text-end"><?= money($inv['subtotal']) ?></th><th></th></tr>
            <tr><th colspan="3" class="text-end">Discount</th><th class="text-end"><?= money($inv['discount']) ?></th><th></th></tr>
            <tr><th colspan="3" class="text-end">Tax</th><th class="text-end"><?= money($inv['tax_amount']) ?></th><th></th></tr>
            <tr class="table-light"><th colspan="3" class="text-end">Total</th><th class="text-end"><?= money($inv['total']) ?></th><th></th></tr>
            <tr><th colspan="3" class="text-end">Paid</th><th class="text-end text-success"><?= money($inv['paid_amount']) ?></th><th></th></tr>
            <tr class="table-light"><th colspan="3" class="text-end">Balance</th>
              <th class="text-end fw-semibold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>"><?= money($balance) ?></th><th></th></tr>
          </tfoot>
        </table>
      </div>
      <?php if ($inv['status'] !== 'void'): ?>
      <div class="card-footer">
        <form method="post" action="<?= base_url('billing/add_line/' . $inv['id']) ?>" class="row g-2 align-items-end">
          <?= csrf_field() ?>
          <div class="col-sm-5"><label class="form-label small mb-1">Add manual line</label>
            <input class="form-control" name="description" placeholder="Description"></div>
          <div class="col-sm-3"><label class="form-label small mb-1">Amount (NGN)</label>
            <input class="form-control" type="number" step="0.01" min="0.01" name="amount"></div>
          <div class="col-sm-auto"><button class="btn btn-outline-primary">Add</button></div>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($payments): ?>
    <div class="card">
      <div class="card-header">Payment history</div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th>Date</th><th>Payment no.</th><th class="text-end">Amount</th><th>Method</th><th>Reference</th><th>Received by</th></tr></thead>
          <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td class="small"><?= fmt_dt($p['paid_at']) ?></td>
              <td class="small text-nowrap"><?= e($p['payment_no']) ?></td>
              <td class="text-end fw-semibold"><?= money($p['amount']) ?></td>
              <td><?= e(ucfirst($p['method'])) ?></td>
              <td class="small"><?= e($p['reference_no'] ?: '—') ?></td>
              <td class="small"><?= e($p['received_by_name'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-body">
      <div class="text-muted small">Patient</div>
      <?php if ($patient): ?>
        <div class="h5 mb-0"><a class="text-decoration-none" href="<?= base_url('patients/show/' . $patient['id']) ?>"><?= e($patient['last_name']) ?>, <?= e($patient['first_name']) ?></a></div>
        <div class="text-muted small"><?= e($patient['patient_no']) ?></div>
      <?php else: ?>
        <div class="text-muted">Walk-in / no patient</div>
      <?php endif; ?>
      <div class="mt-3 text-muted small">Invoice date</div>
      <div><?= fmt_date($inv['invoice_date']) ?></div>
      <div class="mt-3 text-muted small">Notes</div>
      <div><?= e($inv['notes'] ?: '—') ?></div>
    </div></div>
    <div class="card"><div class="card-body text-center">
      <div class="text-muted small">Outstanding balance</div>
      <div class="display-6 fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>"><?= money($balance) ?></div>
      <?php if ($balance > 0 && $inv['status'] !== 'void'): ?>
        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash-coin"></i> Record payment</button>
      <?php endif; ?>
    </div></div>
  </div>
</div>

<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= base_url('billing/pay/' . $inv['id']) ?>">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Record payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Outstanding balance</label>
          <div class="form-control bg-light"><?= money($balance) ?></div>
        </div>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label required">Amount</label>
            <input class="form-control" type="number" step="0.01" min="0.01" max="<?= e($balance) ?>" name="amount" required></div>
          <div class="col-md-6"><label class="form-label">Method</label>
            <select class="form-select" name="method">
              <option value="cash">Cash</option>
              <option value="transfer">Transfer</option>
              <option value="card">Card</option>
              <option value="bank">Bank deposit</option>
              <option value="other">Other</option>
            </select></div>
          <div class="col-12"><label class="form-label">Reference no.</label>
            <input class="form-control" name="reference_no"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save payment</button>
      </div>
    </form>
  </div></div>
</div>