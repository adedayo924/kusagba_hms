<?php
$actions = '<a class="btn btn-primary" href="' . base_url('admissions/create') . '"><i class="bi bi-plus-lg"></i> Admit Patient</a>';
page_header('Admissions', 'Inpatient admissions and discharges', $actions);
?>
<form method="get" action="<?= base_url('admissions') ?>" class="row g-2 mb-3">
  <div class="col-auto">
    <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name / admission no...">
  </div>
  <div class="col-auto">
    <select class="form-select" name="status">
      <option value="admitted" <?= $status === 'admitted' ? 'selected' : '' ?>>Currently admitted</option>
      <option value="discharged" <?= $status === 'discharged' ? 'selected' : '' ?>>Discharged</option>
      <option value="" <?= $status === '' ? 'selected' : '' ?>>All</option>
    </select>
  </div>
  <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th>Admission No</th><th>Patient</th><th>Ward</th><th>Bed</th><th>Admitted</th><th>Days</th><th>Daily Rate</th><th>Status</th><th></th>
      </tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No admissions found.</td></tr>
      <?php else: foreach ($list as $x):
        $days = $x['status'] === 'discharged' && $x['discharged_at']
            ? max(0, (int)((strtotime($x['discharged_at']) - strtotime($x['admitted_at'])) / 86400))
            : (int)((time() - strtotime($x['admitted_at'])) / 86400); ?>
        <tr>
          <td class="small text-muted"><?= e($x['admission_no']) ?></td>
          <td class="fw-semibold"><a class="text-decoration-none" href="<?= base_url('patients/show/' . $x['patient_id']) ?>"><?= e($x['patient_name']) ?></a></td>
          <td><?= e($x['ward_name']) ?></td>
          <td><?= e($x['bed_label'] ?: '—') ?></td>
          <td class="small"><?= fmt_dt($x['admitted_at']) ?></td>
          <td><?= $days ?></td>
          <td><?= money($x['amount_per_day']) ?></td>
          <td><?= status_badge($x['status']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= base_url('admissions/show/' . $x['id']) ?>">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>