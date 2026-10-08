<?php
$isEdit = !is_null($appointment);
page_header($isEdit ? 'Edit appointment' : 'Book appointment', $isEdit ? 'Update appointment details' : 'Schedule a patient appointment');

$pname = $appointment ? patient_full_name($prePatient) : '';
$v = function ($k, $default = '') use ($appointment) {
    return old($k, $default);
};
$sel = function ($a, $b) {
    return (string)$a === (string)$b ? 'selected' : '';
};
?>
<div class="row">
  <div class="col-lg-8">
    <form method="post" action="<?= base_url($isEdit ? 'appointments/update/' . $appointment['id'] : 'appointments/store') ?>">
      <?= csrf_field() ?>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person me-1"></i> Patient</div>
        <div class="card-body">
          <label class="form-label required" for="patient_id">Patient</label>
          <?php if ($appointment): ?>
            <div class="form-control bg-light d-flex justify-content-between align-items-center">
              <span><?= e(patient_full_name($prePatient)) ?> <span class="text-muted">(<?= e($prePatient['patient_no']) ?>)</span></span>
              <input type="hidden" name="patient_id" value="<?= (int)$appointment['patient_id'] ?>">
            </div>
          <?php else: ?>
            <select class="form-select<?= invalid('patient_id') ?>" id="patient_id" name="patient_id" required aria-required="true" <?= old_error('patient_id') ? 'aria-invalid="true"' : '' ?>>
              <option value="">— select patient (or register first) —</option>
              <?php if ($prePatient): ?>
                <option value="<?= $prePatient['id'] ?>" selected><?= e(patient_full_name($prePatient)) ?> — <?= e($prePatient['patient_no']) ?></option>
              <?php endif; ?>
              <?php foreach ($patients as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $sel($v('patient_id'), $p['id']) ?>><?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?> <?= $p['phone'] ? '(' . e($p['phone']) . ')' : '' ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error('patient_id') ?>
            <div class="form-text"><a href="<?= base_url('patients/create') ?>">Register a new patient</a></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-calendar3 me-1"></i> Schedule</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label required" for="appointment_date">Date</label>
              <input class="form-control<?= invalid('appointment_date') ?>" id="appointment_date" type="date" name="appointment_date" value="<?= e($v('appointment_date', $dateValue)) ?>" required aria-required="true" <?= old_error('appointment_date') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('appointment_date') ?></div>
            <div class="col-md-6"><label class="form-label" for="appointment_time">Time</label>
              <input class="form-control<?= invalid('appointment_time') ?>" id="appointment_time" type="time" name="appointment_time" value="<?= e($v('appointment_time', $isEdit ? $appointment['appointment_time'] : '')) ?>" <?= old_error('appointment_time') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('appointment_time') ?></div>
            <div class="col-md-6">
              <label class="form-label" for="doctor_id">Doctor</label>
              <select class="form-select<?= invalid('doctor_id') ?>" id="doctor_id" name="doctor_id" <?= old_error('doctor_id') ? 'aria-invalid="true"' : '' ?>>
                <option value="">Unassigned</option>
                <?php foreach ($doctors as $d): ?>
                  <option value="<?= $d['id'] ?>" <?= $sel($v('doctor_id', $isEdit ? $appointment['doctor_id'] : ''), $d['id']) ?>>
                    <?= e($d['full_name']) ?><?= $d['specialty'] ? ' — ' . e($d['specialty']) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?= field_error('doctor_id') ?>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="status">Status</label>
              <select class="form-select<?= invalid('status') ?>" id="status" name="status" <?= old_error('status') ? 'aria-invalid="true"' : '' ?>>
                <?php foreach (['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'] as $s): ?>
                  <option value="<?= $s ?>" <?= $sel($v('status', $isEdit ? $appointment['status'] : ''), $s) ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
              </select>
              <?= field_error('status') ?>
            </div>
            <div class="col-12"><label class="form-label" for="reason">Reason</label>
              <textarea class="form-control<?= invalid('reason') ?>" id="reason" name="reason" rows="2" <?= old_error('reason') ? 'aria-invalid="true"' : '' ?>><?= e($v('reason', $isEdit ? $appointment['reason'] : '')) ?></textarea>
              <?= field_error('reason') ?></div>
            <div class="col-12"><label class="form-label" for="notes">Notes</label>
              <textarea class="form-control<?= invalid('notes') ?>" id="notes" name="notes" rows="2" <?= old_error('notes') ? 'aria-invalid="true"' : '' ?>><?= e($v('notes', $isEdit ? $appointment['notes'] : '')) ?></textarea>
              <?= field_error('notes') ?></div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary px-4"><?= $isEdit ? 'Save changes' : 'Book appointment' ?></button>
        <a class="btn btn-outline-secondary" href="<?= base_url('appointments') ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>