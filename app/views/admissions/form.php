<?php
page_header('Admit patient', 'Admit a patient to a ward');
$v = function ($k, $default = '') {
    return old($k, $default);
};
$sel = function ($a, $b) {
    return (string)$a === (string)$b ? 'selected' : '';
};
?>
<div class="row">
  <div class="col-lg-8">
    <form method="post" action="<?= base_url('admissions/store') ?>">
      <?= csrf_field() ?>
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person me-1"></i> Patient</div>
        <div class="card-body">
          <label class="form-label required" for="patient_id">Patient</label>
          <select class="form-select<?= invalid('patient_id') ?>" id="patient_id" name="patient_id" required aria-required="true" <?= old_error('patient_id') ? 'aria-invalid="true"' : '' ?>>
            <option value="">— select patient —</option>
            <?php if (isset($patient) && $patient): ?>
              <option value="<?= (int)$patient['id'] ?>" selected><?= e($patient['last_name']) ?>, <?= e($patient['first_name']) ?> — <?= e($patient['patient_no']) ?></option>
            <?php endif; ?>
            <?php foreach ($patients as $p): ?>
              <?php if (isset($patient) && (int)$p['id'] === (int)$patient['id']) continue; ?>
              <option value="<?= $p['id'] ?>" <?= $sel($v('patient_id'), $p['id']) ?>><?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?></option>
            <?php endforeach; ?>
          </select>
          <?= field_error('patient_id') ?>
          <div class="form-text"><a href="<?= base_url('patients/create') ?>">Register a new patient</a></div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-buildings me-1"></i> Ward placement</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label required" for="ward_id">Ward</label>
              <select class="form-select<?= invalid('ward_id') ?>" id="ward_id" name="ward_id" required aria-required="true" <?= old_error('ward_id') ? 'aria-invalid="true"' : '' ?>>
                <option value="">— select ward —</option>
                <?php foreach ($wards as $w): $free = $w['total_beds'] - $w['occupied']; ?>
                  <option value="<?= $w['id'] ?>" <?= $free <= 0 ? 'disabled' : '' ?> <?= $sel($v('ward_id'), $w['id']) ?>>
                    <?= e($w['name']) ?> (<?= $free ?> beds free)
                  </option>
                <?php endforeach; ?>
              </select>
              <?= field_error('ward_id') ?>
            </div>
            <div class="col-md-3"><label class="form-label" for="bed_label">Bed label</label>
              <input class="form-control<?= invalid('bed_label') ?>" id="bed_label" name="bed_label" value="<?= e($v('bed_label')) ?>" placeholder="e.g. A-2" <?= old_error('bed_label') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('bed_label') ?></div>
            <div class="col-md-3"><label class="form-label" for="amount_per_day">Daily rate (NGN)</label>
              <input class="form-control<?= invalid('amount_per_day') ?>" id="amount_per_day" type="number" step="0.01" min="0" name="amount_per_day" value="<?= e($v('amount_per_day', '15000')) ?>" <?= old_error('amount_per_day') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('amount_per_day') ?></div>
            <div class="col-md-6">
              <label class="form-label" for="consultant_id">Consultant</label>
              <select class="form-select<?= invalid('consultant_id') ?>" id="consultant_id" name="consultant_id" <?= old_error('consultant_id') ? 'aria-invalid="true"' : '' ?>>
                <option value="">—</option>
                <?php foreach ($consultants as $d): ?>
                  <option value="<?= $d['id'] ?>" <?= $sel($v('consultant_id'), $d['id']) ?>><?= e($d['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
              <?= field_error('consultant_id') ?>
            </div>
            <div class="col-md-6"><label class="form-label" for="diagnosis_on_admit">Diagnosis on admission</label>
              <input class="form-control<?= invalid('diagnosis_on_admit') ?>" id="diagnosis_on_admit" name="diagnosis_on_admit" value="<?= e($v('diagnosis_on_admit')) ?>" <?= old_error('diagnosis_on_admit') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('diagnosis_on_admit') ?></div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary px-4"><i class="bi bi-plus-circle"></i> Admit patient</button>
        <a class="btn btn-outline-secondary" href="<?= base_url('admissions') ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>