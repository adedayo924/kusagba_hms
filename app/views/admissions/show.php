<?php
$dischargeBtn = $x['status'] === 'admitted'
    ? '<button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#dischargeModal"><i class="bi bi-box-arrow-right"></i> Discharge patient</button>'
    : '';
page_header('Admission ' . $x['admission_no'], 'Inpatient record', $dischargeBtn);
?>
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <h6>Patient</h6>
      <a class="h5 text-decoration-none" href="<?= base_url('patients/show/' . $patient['id']) ?>"><?= e(patient_full_name($patient)) ?></a>
      <div class="text-muted small"><?= e($patient['patient_no']) ?></div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <h6>Ward</h6>
      <div class="h5 mb-0"><?= e($ward['name']) ?> <?= $x['bed_label'] ? '<span class="badge text-bg-primary">Bed ' . e($x['bed_label']) . '</span>' : '' ?></div>
      <div class="text-muted small mt-1"><?= e($ward['department'] ?: '') ?></div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <h6>Status</h6>
      <div class="h5 mb-0"><?= status_badge($x['status']) ?></div>
      <?php if ($x['status'] === 'admitted'): ?>
        <div class="text-muted small">Admitted <?= fmt_dt($x['admitted_at']) ?></div>
      <?php else: ?>
        <div class="text-muted small">Discharged <?= fmt_dt($x['discharged_at']) ?></div>
      <?php endif; ?>
    </div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header">Details</div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Consultant</dt><dd class="col-sm-8"><?= e($consultant['full_name'] ?? '—') ?></dd>
          <dt class="col-sm-4">Diagnosis on admission</dt><dd class="col-sm-8"><?= e($x['diagnosis_on_admit'] ?: '—') ?></dd>
          <dt class="col-sm-4">Daily rate</dt><dd class="col-sm-8"><?= money($x['amount_per_day']) ?> / day</dd>
          <dt class="col-sm-4">Admitted by</dt><dd class="col-sm-8"><?= e(fetch_val('SELECT full_name FROM users WHERE id=?', [$x['admitted_by']]) ?: '—') ?></dd>
          <dt class="col-sm-4">Discharge summary</dt><dd class="col-sm-8"><?= nl2br(e($x['discharge_summary'] ?: '—')) ?></dd>
        </dl>
      </div>
    </div>
    <div class="card">
      <div class="card-header">Related invoices</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Invoice</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php if (!$invoices): ?>
            <tr><td colspan="5" class="text-muted text-center py-3">Ward charges appear here after discharge.</td></tr>
          <?php else: foreach ($invoices as $i): ?>
            <tr>
              <td class="small"><?= e($i['invoice_no']) ?></td>
              <td class="small"><?= fmt_date($i['invoice_date']) ?></td>
              <td><?= money($i['total']) ?></td>
              <td><?= status_badge($i['status']) ?></td>
              <td><a class="btn btn-sm btn-light" href="<?= base_url('billing/show/' . $i['id']) ?>">Open</a></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php if ($x['status'] === 'admitted'): ?>
<div class="modal fade" id="dischargeModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= base_url('admissions/discharge/' . $x['id']) ?>">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Discharge patient</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="alert alert-info py-2 small">Ward charges will be computed for the admission period and added to the patient's open invoice.</div>
        <label class="form-label">Discharge summary / notes</label>
        <textarea class="form-control" name="discharge_summary" rows="3"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger">Confirm discharge</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>