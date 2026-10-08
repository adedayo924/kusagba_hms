<?php
$isEdit = !is_null($consultation);
page_header(
    $isEdit ? 'Edit consultation' : 'New consultation',
    $isEdit ? 'Update the clinical record' : 'Record a patient consultation'
);
$pname = $consultation ? patient_full_name($patient) : ($patient ? patient_full_name($patient) : '');
$formUrl = $isEdit ? 'consultations/update/' . $consultation['id'] : 'consultations/store';
$v = function ($k, $default = '') use ($consultation) {
    if (isset($_SESSION['old'][$k])) return $_SESSION['old'][$k];
    if ($consultation && array_key_exists($k, $consultation) && $consultation[$k] !== null) return $consultation[$k];
    return $_POST[$k] ?? $default;
};
$sel = function ($a, $b) {
    return (string)$a === (string)$b ? 'selected' : '';
};
?>
<div class="row">
  <div class="col-lg-10">
    <form method="post" action="<?= base_url($formUrl) ?>">
      <?= csrf_field() ?>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person me-1"></i> Visit</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label required" for="patient_id">Patient</label>
              <?php if ($isEdit): ?>
                <div class="form-control bg-light"><?= e($pname) ?></div>
                <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
              <?php else: ?>
                <select class="form-select<?= invalid('patient_id') ?>" id="patient_id" name="patient_id" required aria-required="true" <?= old_error('patient_id') ? 'aria-invalid="true"' : '' ?>>
                  <option value="">— select patient —</option>
                  <?php if ($patient): ?>
                    <option value="<?= $patient['id'] ?>" selected><?= e($pname) ?> — <?= e($patient['patient_no']) ?></option>
                  <?php endif; ?>
                  <?php foreach ($patients as $p): ?>
                    <?php if ($patient && (int)$p['id'] === (int)$patient['id']) continue; ?>
                    <option value="<?= $p['id'] ?>" <?= $sel($v('patient_id'), $p['id']) ?>><?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?></option>
                  <?php endforeach; ?>
                </select>
                <?= field_error('patient_id') ?>
              <?php endif; ?>
            </div>
            <div class="col-md-3"><label class="form-label" for="visit_date">Visit date</label>
              <input class="form-control<?= invalid('visit_date') ?>" id="visit_date" type="date" name="visit_date" value="<?= e($v('visit_date', date('Y-m-d'))) ?>" <?= old_error('visit_date') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('visit_date') ?></div>
            <div class="col-md-3">
              <label class="form-label" for="visit_type">Type</label>
              <select class="form-select<?= invalid('visit_type') ?>" id="visit_type" name="visit_type" <?= old_error('visit_type') ? 'aria-invalid="true"' : '' ?>>
                <option value="outpatient" <?= $sel($v('visit_type', 'outpatient'), 'outpatient') ?>>Outpatient</option>
                <option value="inpatient" <?= $sel($v('visit_type'), 'inpatient') ?>>Inpatient</option>
              </select>
              <?= field_error('visit_type') ?>
            </div>
            <div class="col-md-2">
              <label class="form-label" for="appointment_id">Appointment</label>
              <?php if ($appointment): ?>
                <div class="form-control bg-light small">#<?= (int)$appointment['id'] ?></div>
                <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">
              <?php else: ?>
                <input type="hidden" name="appointment_id" value="<?= (int)$v('appointment_id') ?>">
                <div class="form-control-plaintext text-muted small"><?= $v('appointment_id') ? '#' . (int)$v('appointment_id') : '—' ?></div>
              <?php endif; ?>
              <?= field_error('appointment_id') ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-activity me-1"></i> Vitals</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-2"><label class="form-label" for="temperature">Temp (°C)</label>
              <input class="form-control<?= invalid('temperature') ?>" id="temperature" type="number" step="0.1" name="temperature" value="<?= e($v('temperature')) ?>" <?= old_error('temperature') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('temperature') ?></div>
            <div class="col-md-2"><label class="form-label" for="blood_pressure">BP (mmHg)</label>
              <input class="form-control<?= invalid('blood_pressure') ?>" id="blood_pressure" name="blood_pressure" placeholder="120/80" value="<?= e($v('blood_pressure')) ?>" <?= old_error('blood_pressure') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('blood_pressure') ?></div>
            <div class="col-md-2"><label class="form-label" for="pulse">Pulse (bpm)</label>
              <input class="form-control<?= invalid('pulse') ?>" id="pulse" type="number" name="pulse" value="<?= e($v('pulse')) ?>" <?= old_error('pulse') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('pulse') ?></div>
            <div class="col-md-2"><label class="form-label" for="respiratory_rate">Resp. rate</label>
              <input class="form-control<?= invalid('respiratory_rate') ?>" id="respiratory_rate" type="number" name="respiratory_rate" value="<?= e($v('respiratory_rate')) ?>" <?= old_error('respiratory_rate') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('respiratory_rate') ?></div>
            <div class="col-md-2"><label class="form-label" for="weight">Weight (kg)</label>
              <input class="form-control<?= invalid('weight') ?>" id="weight" type="number" step="0.1" name="weight" value="<?= e($v('weight')) ?>" <?= old_error('weight') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('weight') ?></div>
            <div class="col-md-2"><label class="form-label" for="height">Height (cm)</label>
              <input class="form-control<?= invalid('height') ?>" id="height" type="number" step="0.1" name="height" value="<?= e($v('height')) ?>" <?= old_error('height') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('height') ?></div>
            <div class="col-md-2"><label class="form-label" for="spo2">O₂ (%)</label>
              <input class="form-control<?= invalid('spo2') ?>" id="spo2" type="number" name="spo2" value="<?= e($v('spo2')) ?>" <?= old_error('spo2') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('spo2') ?></div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-clipboard2-pulse me-1"></i> Clinical notes</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12"><label class="form-label required">Chief complaint</label>
              <textarea class="form-control" name="chief_complaint" rows="2" required><?= e($g('chief_complaint')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">History of present illness</label>
              <textarea class="form-control" name="history" rows="3"><?= e($g('history')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Examination findings</label>
              <textarea class="form-control" name="examination" rows="3"><?= e($g('examination')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Diagnosis</label>
              <textarea class="form-control" name="diagnosis" rows="3"><?= e($g('diagnosis')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Treatment plan</label>
              <textarea class="form-control" name="treatment_plan" rows="3"><?= e($g('treatment_plan')) ?></textarea></div>
            <div class="col-12"><label class="form-label">Additional notes</label>
              <textarea class="form-control" name="notes" rows="2"><?= e($g('notes')) ?></textarea></div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Save changes' : 'Save consultation' ?></button>
        <a class="btn btn-outline-secondary" href="<?= base_url('consultations' . ($isEdit ? '/show/' . $consultation['id'] : '')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>