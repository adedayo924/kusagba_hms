<?php
$actions = in_array(Auth::role(), ['admin', 'doctor'])
    ? '<a class="btn btn-primary" href="' . base_url('prescriptions/create') . '"><i class="bi bi-capsule"></i> New Prescription</a>'
    : '';
page_header('Prescriptions', 'Prescribe and dispense drugs', $actions);
?>
<form method="get" action="<?= base_url('prescriptions') ?>" class="row g-2 mb-3">
  <div class="col-auto">
    <select class="form-select" name="status">
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Pending dispatch</option>
      <option value="dispensed" <?= $status === 'dispensed' ? 'selected' : '' ?>>Dispensed</option>
      <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      <option value="" <?= $status === '' ? 'selected' : '' ?>>All</option>
    </select>
  </div>
  <div class="col-sm-4"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search patient..."></div>
  <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Prescription</th><th>Patient</th><th>Prescribed by</th><th>Date</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No prescriptions found.</td></tr>
      <?php else: foreach ($list as $x): ?>
        <tr>
          <td class="small text-muted"><?= e($x['prescription_no']) ?></td>
          <td class="fw-semibold"><a class="text-decoration-none" href="<?= base_url('patients/show/' . $x['patient_id']) ?>"><?= e($x['patient_last']) ?>, <?= e($x['patient_first']) ?></a></td>
          <td class="small"><?= e($x['doctor_name'] ?? '—') ?></td>
          <td class="small"><?= fmt_dt($x['prescribed_at']) ?></td>
          <td><?= status_badge($x['status']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= base_url('prescriptions/show/' . $x['id']) ?>">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>