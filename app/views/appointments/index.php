<?php
page_header('Appointments', 'Manage scheduled patient appointments');
$actionBtn = '<a class="btn btn-primary" href="' . base_url('appointments/create') . '"><i class="bi bi-calendar-plus"></i> New Appointment</a>';
?>
<form method="get" action="<?= base_url('appointments') ?>" class="row g-2 mb-3">
  <div class="col-auto">
    <input class="form-control" type="date" name="date" value="<?= e($date) ?>">
  </div>
  <div class="col-auto">
    <select class="form-select" name="status">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
  <div class="col-auto ms-auto">
    <a class="btn btn-outline-secondary" href="<?= base_url('appointments/calendar') ?>"><i class="bi bi-calendar-month"></i> Calendar</a>
    <?= $actionBtn ?>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th>Time</th><th>Patient</th><th>Doctor</th><th>Reason</th><th>Status</th><th></th>
      </tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No appointments for <?= fmt_date($date, 'M j, Y') ?><?= $status ? ' (' . e($status) . ')' : '' ?>.</td></tr>
      <?php else: foreach ($list as $a): ?>
        <tr id="appt-<?= $a['id'] ?>">
          <td class="text-nowrap"><?= fmt_time($a['appointment_time']) ?></td>
          <td class="fw-semibold"><a href="<?= base_url('patients/show/' . $a['patient_id']) ?>" class="text-decoration-none"><?= e($a['patient_name']) ?></a>
            <br><span class="text-muted small"><?= e($a['patient_no']) ?></span></td>
          <td><?= e($a['doctor_name'] ?: '—') ?></td>
          <td class="small text-muted"><?= e(mb_strimwidth($a['reason'] ?: '', 0, 40, '…')) ?></td>
          <td><?= status_badge($a['status']) ?></td>
          <td class="text-end">
            <?php if (in_array($a['status'], ['pending', 'confirmed'])): ?>
              <?php if ($a['status'] === 'pending'): ?>
                <button class="btn btn-sm btn-outline-primary js-appt-status" data-id="<?= $a['id'] ?>" data-href="<?= base_url('appointments/status/' . $a['id']) ?>" data-action="confirm" title="Confirm"><i class="bi bi-check2"></i></button>
              <?php endif; ?>
              <button class="btn btn-sm btn-outline-success js-appt-status" data-id="<?= $a['id'] ?>" data-href="<?= base_url('appointments/status/' . $a['id']) ?>" data-action="checkin" title="Check in"><i class="bi bi-door-open"></i></button>
            <?php endif; ?>
            <?php if (in_array($a['status'], ['checked_in', 'confirmed'])): ?>
              <button class="btn btn-sm btn-success js-appt-status" data-id="<?= $a['id'] ?>" data-href="<?= base_url('appointments/status/' . $a['id']) ?>" data-action="complete" title="Complete"><i class="bi bi-check2-all"></i></button>
            <?php endif; ?>
            <?php if ($a['status'] !== 'completed' && $a['status'] !== 'cancelled'): ?>
              <button class="btn btn-sm btn-outline-danger js-appt-status" data-id="<?= $a['id'] ?>" data-href="<?= base_url('appointments/status/' . $a['id']) ?>" data-action="cancel" title="Cancel"><i class="bi bi-x-lg"></i></button>
            <?php endif; ?>
            <a class="btn btn-sm btn-light" href="<?= base_url('appointments/edit/' . $a['id']) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>