<?php
$outstanding = (float)fetch_val('SELECT IFNULL(SUM(total - paid_amount),0) FROM invoices WHERE patient_id = ? AND status NOT IN ("void","paid")', [$patient['id']]);
page_header('Welcome, ' . patient_full_name($patient), 'Patient portal');
?>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body text-center py-3">
    <div class="text-muted small">Patient no.</div><div class="h5 mb-0"><?= e($patient['patient_no']) ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body text-center py-3">
    <div class="text-muted small">Age / Gender</div><div class="h5 mb-0"><?= $patient['dob'] ? age_from_dob($patient['dob']) . ' yrs' : '—' ?> · <?= e($patient['gender']) ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body text-center py-3">
    <div class="text-muted small">Blood group</div><div class="h5 mb-0"><?= e($patient['blood_group'] ?: '—') ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body text-center py-3">
    <div class="text-muted small">Outstanding balance</div>
    <div class="h5 mb-0 <?= $outstanding > 0 ? 'text-danger' : 'text-success' ?>"><?= money($outstanding) ?></div>
  </div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header">Upcoming appointments</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$appointments): ?><tr><td class="text-center text-muted py-3">No appointments yet.</td></tr>
          <?php else: foreach ($appointments as $a): ?>
            <tr>
              <td class="fw-semibold"><?= fmt_date($a['appointment_date']) ?></td>
              <td><?= fmt_time($a['appointment_time']) ?></td>
              <td class="small text-muted"><?= e($a['doctor_name'] ?? '—') ?></td>
              <td><?= status_badge($a['status']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card h-100 mt-3"><div class="card-header">Visit history</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$visits): ?><tr><td class="text-center text-muted py-3">No consultations recorded.</td></tr>
          <?php else: foreach ($visits as $v): ?>
            <tr>
              <td class="fw-semibold"><?= fmt_date($v['visit_date']) ?></td>
              <td><?= e(ucfirst($v['visit_type'])) ?></td>
              <td class="small text-muted"><?= e($v['doctor_name'] ?? '—') ?></td>
              <td class="small text-muted"><?= e($v['diagnosis'] ? mb_strimwidth($v['diagnosis'], 0, 40, '…') : '—') ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header">My prescriptions</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$prescriptions): ?><tr><td class="text-center text-muted py-3">No prescriptions.</td></tr>
          <?php else: foreach ($prescriptions as $rx): ?>
            <tr>
              <td class="fw-semibold"><?= fmt_dt($rx['prescribed_at'], 'M j, Y') ?></td>
              <td class="small text-muted"><?= e($rx['doctor_name'] ?? '—') ?></td>
              <td><?= status_badge($rx['status']) ?></td>
              <td class="text-end text-muted small"><?= (int)fetch_val('SELECT COUNT(*) FROM prescription_items WHERE prescription_id = ?', [$rx['id']]) ?> drug(s)</td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card h-100 mt-3"><div class="card-header">Lab requests</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$lab): ?><tr><td class="text-center text-muted py-3">No lab requests.</td></tr>
          <?php else: foreach ($lab as $l): ?>
            <tr>
              <td class="fw-semibold"><?= fmt_dt($l['requested_at'], 'M j, Y') ?></td>
              <td><?= status_badge($l['status']) ?></td>
              <td class="text-end text-muted small"><?= (int)fetch_val('SELECT COUNT(*) FROM lab_request_tests WHERE lab_request_id = ?', [$l['id']]) ?> test(s)</td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card h-100 mt-3"><div class="card-header">Invoices</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$invoices): ?><tr><td class="text-center text-muted py-3">No invoices yet.</td></tr>
          <?php else: foreach ($invoices as $i): ?>
            <tr>
              <td class="fw-semibold"><?= e($i['invoice_no']) ?></td>
              <td><?= fmt_date($i['invoice_date']) ?></td>
              <td class="text-end"><?= money($i['total']) ?></td>
              <td><?= status_badge($i['status']) ?></td>
              <td class="text-end"><a class="btn btn-sm btn-light" href="<?= base_url('billing/receipt/' . $i['id']) ?>" target="_blank">Receipt</a></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>