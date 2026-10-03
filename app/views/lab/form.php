<?php page_header('New lab request', 'Request laboratory investigations for a patient'); ?>
<div class="row">
  <div class="col-lg-8">
    <form method="post" action="<?= base_url('lab/store') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="consultation_id" value="<?= (int)($preselectConsultationId ?? 0) ?>">
      <div class="card mb-3">
        <div class="card-header">Request details</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label required">Patient</label>
              <select class="form-select" name="patient_id" required>
                <option value="">— select patient —</option>
                <?php foreach ($patients as $p): ?>
                  <option value="<?= $p['id'] ?>" <?= (int)($preselectPatientId ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ((int)($preselectConsultationId ?? 0)): ?>
                <div class="form-text">Pre-filled from consultation #<?= (int)$preselectConsultationId ?>.</div>
              <?php endif; ?>
            </div>
            <div class="col-md-5">
              <label class="form-label">Priority</label>
              <select class="form-select" name="priority">
                <option value="routine">Routine</option>
                <option value="urgent">Urgent</option>
                <option value="stat">STAT</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Clinical notes / provisional diagnosis</label>
              <textarea class="form-control" name="clinical_notes" rows="2"></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">Tests</div>
        <div class="card-body">
          <?php $cat = null; $open = false; ?>
          <?php foreach ($tests as $t):
            if ($t['category'] !== $cat) {
              if ($open) echo '</div>';
              $cat = $t['category'];
              $open = true;
              echo '<h6 class="mt-2 mb-2 text-primary">' . e($cat ?: 'Other') . '</h6><div class="row g-2">';
            } ?>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="test_id[]" value="<?= $t['id'] ?>" id="t<?= $t['id'] ?>">
                <label class="form-check-label" for="t<?= $t['id'] ?>">
                  <?= e($t['name']) ?>
                  <?php if ((float)$t['price'] > 0): ?><span class="text-muted small"><?= money($t['price']) ?></span><?php endif; ?>
                </label>
              </div>
            </div>
          <?php endforeach; if ($open) echo '</div>'; ?>
          <?php if (!$tests): ?>
            <div class="alert alert-warning py-2 small mb-0">No tests defined. <a href="<?= base_url('lab/tests') ?>">Add tests first</a>.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary px-4"><i class="bi bi-check2"></i> Create request</button>
        <a class="btn btn-outline-secondary" href="<?= base_url('lab') ?>">Cancel</a>
      </div>
      <p class="text-muted small">Charges for selected tests are added to the patient's open invoice automatically.</p>
    </form>
  </div>
</div>