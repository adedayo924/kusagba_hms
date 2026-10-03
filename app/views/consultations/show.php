<?php
$editBtn = in_array(Auth::role(), ['admin', 'doctor'])
    ? '<a class="btn btn-outline-primary" href="' . base_url('consultations/edit/' . $c['id']) . '"><i class="bi bi-pencil"></i> Edit</a>'
    : '';
page_header('Consultation #' . $c['id'], fmt_date($c['visit_date']) . ' · ' . ucfirst($c['visit_type']), $editBtn);

$addRx = in_array(Auth::role(), ['doctor', 'admin'])
    ? '<a class="btn btn-sm btn-outline-success" href="' . base_url('prescriptions/create?consultation=' . $c['id']) . '"><i class="bi bi-prescription2"></i> Add prescription</a>'
    : '';
$addLab = in_array(Auth::role(), ['doctor', 'admin'])
    ? '<a class="btn btn-sm btn-outline-success" href="' . base_url('lab/create?consultation=' . $c['id']) . '"><i class="bi bi-eyedropper"></i> Order lab tests</a>'
    : '';
$admitBtn = in_array(Auth::role(), ['admin', 'doctor', 'nurse'])
    ? '<a class="btn btn-sm btn-outline-success" href="' . base_url('admissions/create?patient=' . $patient['id']) . '"><i class="bi bi-hospital"></i> Admit</a>'
    : '';
?>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">Patient</div>
      <div class="card-body">
        <h6 class="mb-0"><a href="<?= base_url('patients/show/' . $patient['id']) ?>" class="text-decoration-none"><?= e(patient_full_name($patient)) ?></a></h6>
        <div class="text-muted small"><?= e($patient['patient_no']) ?></div>
        <div class="small mt-2">
          <?php if ($patient['dob']): ?>Age <?= age_from_dob($patient['dob']) ?>&nbsp;&middot;&nbsp;<?php endif; ?><?= e($patient['gender']) ?>
          <?php if ($patient['blood_group']): ?>&nbsp;&middot;&nbsp;<span class="badge text-bg-danger"><?= e($patient['blood_group']) ?></span><?php endif; ?>
        </div>
        <?php if ($patient['allergies']): ?><div class="small mt-2 text-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($patient['allergies']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">Doctor</div>
      <div class="card-body">
        <?php if ($doctor): ?>
          <h6 class="mb-0"><?= e($doctor['full_name']) ?></h6>
          <div class="small text-muted"><?= e($doctor['specialty'] ?: 'General practice') ?><?= $doctor['license_no'] ? ' &middot; ' . e($doctor['license_no']) : '' ?></div>
        <?php else: ?>
          <h6 class="mb-0 text-muted">Unassigned</h6>
          <div class="small text-muted">No clinician attached to this visit</div>
        <?php endif; ?>
        <div class="small text-muted mt-2">Recorded <?= fmt_dt($c['created_at']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">Status</div>
      <div class="card-body">
        <?php if ($admission): ?>
          <span class="badge text-bg-success mb-2">Admitted</span>
          <div class="small"><?= e($admission['ward_name']) ?> &middot; Bed <?= e($admission['bed_label'] ?: '—') ?></div>
        <?php else: ?>
          <span class="badge text-bg-secondary">Outpatient</span>
          <div class="small text-muted mt-2">Not currently admitted.</div>
        <?php endif; ?>
        <div class="small text-muted mt-2">
          <?= $addRx ?: '' ?> <?= $addLab ?: '' ?> <?= $admitBtn ?: '' ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header">Clinical findings</div>
      <div class="card-body">
        <dl class="row mb-2">
          <dt class="col-sm-3">Complaint</dt>
          <dd class="col-sm-9"><?= e($c['chief_complaint'] ?: '—') ?></dd>
          <dt class="col-sm-3">History</dt>
          <dd class="col-sm-9"><?= nl2br(e($c['history'] ?: '—')) ?></dd>
          <dt class="col-sm-3">Examination</dt>
          <dd class="col-sm-9"><?= nl2br(e($c['examination'] ?: '—')) ?></dd>
          <dt class="col-sm-3">Diagnosis</dt>
          <dd class="col-sm-9 fw-semibold"><?= nl2br(e($c['diagnosis'] ?: '—')) ?></dd>
          <dt class="col-sm-3">Plan</dt>
          <dd class="col-sm-9"><?= nl2br(e($c['treatment_plan'] ?: '—')) ?></dd>
          <dt class="col-sm-3">Notes</dt>
          <dd class="col-sm-9 text-muted"><?= nl2br(e($c['notes'] ?: '—')) ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header">Vitals</div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tbody>
            <tr><td>Blood pressure</td><td class="fw-semibold"><?= e($c['blood_pressure'] ?: '—') ?> <?= $c['blood_pressure'] ? 'mmHg' : '' ?></td></tr>
            <tr><td>Temperature</td><td class="fw-semibold"><?= $c['temperature'] ? e($c['temperature']) . ' °C' : '—' ?></td></tr>
            <tr><td>Pulse</td><td class="fw-semibold"><?= $c['pulse'] ? e($c['pulse']) . ' bpm' : '—' ?></td></tr>
            <tr><td>Respiratory rate</td><td class="fw-semibold"><?= $c['respiratory_rate'] ? e($c['respiratory_rate']) . ' /min' : '—' ?></td></tr>
            <tr><td>Weight</td><td class="fw-semibold"><?= $c['weight'] ? e($c['weight']) . ' kg' : '—' ?></td></tr>
            <tr><td>Height</td><td class="fw-semibold"><?= $c['height'] ? e($c['height']) . ' cm' : '—' ?></td></tr>
            <tr><td>SpO₂</td><td class="fw-semibold"><?= $c['spo2'] ? e($c['spo2']) . ' %' : '—' ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Prescriptions</span><?= $addRx ?>
  </div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Rx No</th><th>Drug</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$prescriptions): ?>
        <tr><td colspan="6" class="text-muted text-center py-3">No prescriptions for this consultation.</td></tr>
      <?php else: foreach ($prescriptions as $rx): ?>
        <tr>
          <td class="small"><?= e($rx['prescription_no']) ?></td>
          <td class="fw-semibold"><?= e($rx['drug_name'] ?? 'Deleted drug') ?></td>
          <td><?= e($rx['dosage'] ?: '') ?></td>
          <td class="small"><?= e($rx['frequency'] ?: '') ?></td>
          <td class="small"><?= e($rx['duration'] ?: '') ?></td>
          <td><?= status_badge($rx['status']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>