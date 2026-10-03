<?php
$isEdit = !is_null($appointment);
page_header($isEdit ? 'Edit appointment' : 'Book appointment', $isEdit ? 'Update appointment details' : 'Schedule a patient appointment');

$pname = $appointment ? patient_full_name($prePatient) : '';
?>
<div class="row">
  <div class="col-lg-8">
    <form method="post" action="<?= base_url($isEdit ? 'appointments/update/' . $appointment['id'] : 'appointments/store') ?>">
      <?= csrf_field() ?>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person me-1"></i> Patient</div>
        <div class="card-body">
          <label class="form-label required">Patient</label>
          <?php if ($appointment): ?>
            <div class="form-control bg-light d-flex justify-content-between align-items-center">
              <span><?= e(patient_full_name($prePatient)) ?> <span class="text-muted">(<?= e($prePatient['patient_no']) ?>)</span></span>
              <input type="hidden" name="patient_id" value="<?= (int)$appointment['patient_id'] ?>">
            </div>
          <?php else: ?>
            <select class="form-select" name="patient_id" required>
              <option value="">— select patient (or register first) —</option>
              <?php if ($prePatient): ?>
                <option value="<?= $prePatient['id'] ?>" selected><?= e(patient_full_name($prePatient)) ?> — <?= e($prePatient['patient_no']) ?></option>
              <?php endif; ?>
              <?php foreach ($patients as $p): ?>
                <option value="<?= $p['id'] ?>"><?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?> <?= $p['phone'] ? '(' . e($p['phone']) . ')' : '' ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text"><a href="<?= base_url('patients/create') ?>">Register a new patient</a></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-calendar3 me-1"></i> Schedule</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label required">Date</label>
              <input class="form-control" type="date" name="appointment_date" value="<?= e($dateValue) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Time</label>
              <input class="form-control" type="time" name="appointment_time" value="<?= $isEdit ? e($appointment['appointment_time']) : '' ?>"></div>
            <div class="col-md-6">
              <label class="form-label">Doctor</label>
              <select class="form-select" name="doctor_id">
                <option value="">Unassigned</option>
                <?php foreach ($doctors as $d): ?>
                  <option value="<?= $d['id'] ?>" <?= $isEdit && $appointment['doctor_id'] == $d['id'] ? 'selected' : '' ?>>
                    <?= e($d['full_name']) ?><?= $d['specialty'] ? ' — ' . e($d['specialty']) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <?php foreach (['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'] as $s): ?>
                  <option value="<?= $s ?>" <?= $isEdit && $appointment['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12"><label class="form-label">Reason</label>
              <textarea class="form-control" name="reason" rows="2"><?= $isEdit ? e($appointment['reason']) : '' ?></textarea></div>
            <div class="col-12"><label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" rows="2"><?= $isEdit ? e($appointment['notes']) : '' ?></textarea></div>
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