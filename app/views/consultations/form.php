<?php
$isEdit = !is_null($consultation);
page_header(
    $isEdit ? 'Edit consultation' : 'New consultation',
    $isEdit ? 'Update the clinical record' : 'Record a patient consultation'
);
$pname = $consultation ? patient_full_name($patient) : ($patient ? patient_full_name($patient) : '');
$formUrl = $isEdit ? 'consultations/update/' . $consultation['id'] : 'consultations/store';
$g = function ($k, $default = '') use ($consultation) {
    return $consultation && isset($consultation[$k]) && $consultation[$k] !== null ? $consultation[$k] : ($_POST[$k] ?? $default);
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
              <label class="form-label required">Patient</label>
              <?php if ($isEdit): ?>
                <div class="form-control bg-light"><?= e($pname) ?></div>
                <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
              <?php else: ?>
                <select class="form-select" name="patient_id" required>
                  <option value="">— select patient —</option>
                  <?php if ($patient): ?>
                    <option value="<?= $patient['id'] ?>" selected><?= e($pname) ?> — <?= e($patient['patient_no']) ?></option>
                  <?php endif; ?>
                  <?php foreach ($patients as $p): ?>
                    <?php if ($patient && (int)$p['id'] === (int)$patient['id']) continue; ?>
                    <option value="<?= $p['id'] ?>"><?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?></option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </div>
            <div class="col-md-3"><label class="form-label">Visit date</label>
              <input class="form-control" type="date" name="visit_date" value="<?= e($g('visit_date', date('Y-m-d'))) ?>"></div>
            <div class="col-md-3">
              <label class="form-label">Type</label>
              <select class="form-select" name="visit_type">
                <option value="outpatient" <?= $g('visit_type', 'outpatient') === 'outpatient' ? 'selected' : '' ?>>Outpatient</option>
                <option value="inpatient" <?= $g('visit_type') === 'inpatient' ? 'selected' : '' ?>>Inpatient</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Appointment</label>
              <?php if ($appointment): ?>
                <div class="form-control bg-light small">#<?= (int)$appointment['id'] ?></div>
                <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">
              <?php else: ?>
                <input type="hidden" name="appointment_id" value="<?= (int)$g('appointment_id') ?>">
                <div class="form-control-plaintext text-muted small"><?= $g('appointment_id') ? '#' . (int)$g('appointment_id') : '—' ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-activity me-1"></i> Vitals</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-2"><label class="form-label">Temp (°C)</label>
              <input class="form-control" type="number" step="0.1" name="temperature" value="<?= e($g('temperature')) ?>"></div>
            <div class="col-md-2"><label class="form-label">BP (mmHg)</label>
              <input class="form-control" name="blood_pressure" placeholder="120/80" value="<?= e($g('blood_pressure')) ?>"></div>
            <div class="col-md-2"><label class="form-label">Pulse (bpm)</label>
              <input class="form-control" type="number" name="pulse" value="<?= e($g('pulse')) ?>"></div>
            <div class="col-md-2"><label class="form-label">Resp. rate</label>
              <input class="form-control" type="number" name="respiratory_rate" value="<?= e($g('respiratory_rate')) ?>"></div>
            <div class="col-md-2"><label class="form-label">Weight (kg)</label>
              <input class="form-control" type="number" step="0.1" name="weight" value="<?= e($g('weight')) ?>"></div>
            <div class="col-md-2"><label class="form-label">Height (cm)</label>
              <input class="form-control" type="number" step="0.1" name="height" value="<?= e($g('height')) ?>"></div>
            <div class="col-md-2"><label class="form-label">O₂ (%)</label>
              <input class="form-control" type="number" name="spo2" value="<?= e($g('spo2')) ?>"></div>
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