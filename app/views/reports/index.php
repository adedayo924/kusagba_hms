<?php
page_header('Reports', 'Period summaries and financial breakdown');
?>
<form method="get" action="<?= base_url('reports') ?>" class="row g-2 mb-3 align-items-end bg-light p-3 rounded">
  <div class="col-auto"><label class="form-label small mb-1">From</label>
    <input class="form-control" type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="col-auto"><label class="form-label small mb-1">To</label>
    <input class="form-control" type="date" name="to" value="<?= e($to) ?>"></div>
  <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel"></i> Run report</button></div>
</form>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body py-3">
    <div class="text-muted small">Income</div>
    <div class="h4 mb-0"><?= money($income['total']) ?></div>
    <div class="text-muted small">(<?= (int)$income['count'] ?> payments)</div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body py-3">
    <div class="text-muted small">Outstanding</div>
    <div class="h4 mb-0 text-danger"><?= money($outstanding) ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body py-3">
    <div class="text-muted small">Patients registered</div>
    <div class="h4 mb-0"><?= $registered ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body py-3">
    <div class="text-muted small">Consultations</div>
    <div class="h4 mb-0"><?= $consultations ?></div>
  </div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3 text-center">
    <div class="text-muted small">Appointments</div><div class="h5 mb-0"><?= $appointments ?></div>
  </div></div></div>
  <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3 text-center">
    <div class="text-muted small">Admissions</div><div class="h5 mb-0"><?= $admissions ?></div>
  </div></div></div>
  <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3 text-center">
    <div class="text-muted small">Lab requests</div><div class="h5 mb-0"><?= $labRequests ?></div>
  </div></div></div>
  <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3 text-center">
    <div class="text-muted small">Prescriptions</div><div class="h5 mb-0"><?= $prescriptions ?></div>
  </div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header">Revenue by day</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Date</th><th class="text-end">Payments</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            <?php if (!$byDay): ?><tr><td colspan="3" class="text-center text-muted py-3">No payments in period.</td></tr>
            <?php else: foreach ($byDay as $d): ?>
              <tr><td><?= fmt_date($d['d']) ?></td><td class="text-end"><?= (int)$d['count'] ?></td>
                  <td class="text-end"><?= money($d['total']) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header">Revenue by payment method</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Method</th><th class="text-end">Count</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            <?php if (!$byMethod): ?><tr><td colspan="3" class="text-center text-muted py-3">No payments in period.</td></tr>
            <?php else: foreach ($byMethod as $m): ?>
              <tr><td><?= e(ucfirst($m['method'])) ?></td><td class="text-end"><?= (int)$m['count'] ?></td>
                  <td class="text-end"><?= money($m['total']) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header">Top billed services</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Service</th><th class="text-end">Count</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            <?php if (!$byService): ?><tr><td colspan="3" class="text-center text-muted py-3">No billings in period.</td></tr>
            <?php else: foreach ($byService as $s): ?>
              <tr><td><?= e($s['service']) ?></td><td class="text-end"><?= (int)$s['count'] ?></td>
                  <td class="text-end"><?= money($s['total']) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header">Consultations by doctor</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Doctor</th><th class="text-end">Consultations</th></tr></thead>
            <tbody>
            <?php if (!$byDoctor): ?><tr><td colspan="2" class="text-center text-muted py-3">No consultations in period.</td></tr>
            <?php else: foreach ($byDoctor as $d): ?>
              <tr><td><?= e($d['full_name'] ?? 'Unassigned') ?></td><td class="text-end"><?= (int)$d['count'] ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div></div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">Payments in period</div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Date</th><th>Payment no.</th><th>Patient</th><th>Invoice</th><th>Method</th><th class="text-end">Amount</th><th>Received by</th></tr></thead>
      <tbody>
      <?php if (!$recentPayments): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No payments in period.</td></tr>
      <?php else: foreach ($recentPayments as $p): ?>
        <tr>
          <td class="small"><?= fmt_dt($p['paid_at']) ?></td>
          <td class="small text-nowrap"><?= e($p['payment_no']) ?></td>
          <td><?= e($p['patient_name'] ?? '—') ?></td>
          <td class="small"><a class="text-decoration-none" href="<?= base_url('billing/show/' . $p['invoice_id']) ?>"><?= e($p['invoice_no']) ?></a></td>
          <td><?= e(ucfirst($p['method'])) ?></td>
          <td class="text-end fw-semibold"><?= money($p['amount']) ?></td>
          <td class="small"><?= e($p['receiver'] ?? '—') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>