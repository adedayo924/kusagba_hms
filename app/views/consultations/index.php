<?php
$actions = '<a class="btn btn-primary" href="' . base_url('consultations/create') . '"><i class="bi bi-clipboard2-plus"></i> New Consultation</a>';
page_header('Consultations', 'Clinical consultations and visits', $actions);
?>
<form method="get" action="<?= base_url('consultations') ?>" class="row g-2 mb-3">
  <div class="col-auto">
    <input class="form-control" type="date" name="date" value="<?= e($date) ?>">
  </div>
  <?php if (Auth::role() !== 'doctor'): ?>
    <div class="col-auto">
      <select class="form-select" name="doctor">
        <option value="0">All doctors</option>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= $doc['id'] ?>" <?= $doctorId == $doc['id'] ? 'selected' : '' ?>><?= e($doc['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>
  <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th>Date</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Complaint</th><th>Diagnosis</th><th>Vitals</th><th></th>
      </tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No consultations found.</td></tr>
      <?php else: foreach ($list as $c): ?>
        <tr>
          <td><?= fmt_date($c['visit_date']) ?></td>
          <td class="fw-semibold"><a class="text-decoration-none" href="<?= base_url('patients/show/' . $c['patient_id']) ?>"><?= e($c['patient_name']) ?></a>
            <br><span class="text-muted small"><?= e($c['patient_no']) ?></span></td>
          <td><?= e($c['doctor_name'] ?: '—') ?></td>
          <td><span class="badge text-bg-<?= $c['visit_type'] === 'inpatient' ? 'info' : 'secondary' ?>"><?= ucfirst($c['visit_type']) ?></span></td>
          <td class="small text-muted"><?= e(mb_strimwidth($c['chief_complaint'] ?: '—', 0, 50, '…')) ?></td>
          <td class="small"><?= e(mb_strimwidth($c['diagnosis'] ?: '—', 0, 60, '…')) ?></td>
          <td class="small text-muted"><?= $c['blood_pressure'] ? 'BP ' . e($c['blood_pressure']) : '' ?> <?= $c['temperature'] ? '· ' . e($c['temperature']) . '°C' : '' ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= base_url('consultations/show/' . $c['id']) ?>">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>