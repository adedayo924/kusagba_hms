<?php page_header('Admit patient', 'Admit a patient to a ward'); ?>
<div class="row">
  <div class="col-lg-8">
    <form method="post" action="<?= base_url('admissions/store') ?>">
      <?= csrf_field() ?>
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person me-1"></i> Patient</div>
        <div class="card-body">
          <label class="form-label required">Patient</label>
          <select class="form-select" name="patient_id" required>
            <option value="">— select patient —</option>
            <?php if (isset($patient) && $patient): ?>
              <option value="<?= (int)$patient['id'] ?>" selected><?= e($patient['last_name']) ?>, <?= e($patient['first_name']) ?> — <?= e($patient['patient_no']) ?></option>
            <?php endif; ?>
            <?php foreach ($patients as $p): ?>
              <?php if (isset($patient) && (int)$p['id'] === (int)$patient['id']) continue; ?>
              <option value="<?= $p['id'] ?>"><?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text"><a href="<?= base_url('patients/create') ?>">Register a new patient</a></div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-buildings me-1"></i> Ward placement</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label required">Ward</label>
              <select class="form-select" name="ward_id" required>
                <option value="">— select ward —</option>
                <?php foreach ($wards as $w): $free = $w['total_beds'] - $w['occupied']; ?>
                  <option value="<?= $w['id'] ?>" <?= $free <= 0 ? 'disabled' : '' ?>>
                    <?= e($w['name']) ?> (<?= $free ?> beds free)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Bed label</label>
              <input class="form-control" name="bed_label" placeholder="e.g. A-2"></div>
            <div class="col-md-3"><label class="form-label">Daily rate (NGN)</label>
              <input class="form-control" type="number" step="0.01" min="0" name="amount_per_day" value="15000"></div>
            <div class="col-md-6">
              <label class="form-label">Consultant</label>
              <select class="form-select" name="consultant_id">
                <option value="">—</option>
                <?php foreach ($consultants as $d): ?>
                  <option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Diagnosis on admission</label>
              <input class="form-control" name="diagnosis_on_admit"></div>
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