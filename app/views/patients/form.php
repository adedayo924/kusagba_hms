<?php
$isEdit = !is_null($patient);
page_header(
    $isEdit ? 'Edit patient' : 'Register new patient',
    $isEdit ? 'Update demographic and contact information' : 'Create a new patient record'
);
$v = function ($k, $default = '') use ($patient) {
    return $patient ? ($patient[$k] ?? $default) : $default;
};
$sel = function ($a, $b) {
    return $a === $b ? 'selected' : '';
};
$formUrl = $isEdit ? 'patients/update/' . $patient['id'] : 'patients/store';
?>
<div class="row">
  <div class="col-lg-9">
    <form method="post" action="<?= base_url($formUrl) ?>">
      <?= csrf_field() ?>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person me-1"></i> Demographics</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label required">First name</label>
              <input class="form-control" name="first_name" value="<?= e($v('first_name')) ?>" required
                     <?= $isEdit ? 'disabled' : '' ?>></div>
            <div class="col-md-6"><label class="form-label required">Last name</label>
              <input class="form-control" name="last_name" value="<?= e($v('last_name')) ?>" required
                     <?= $isEdit ? 'disabled' : '' ?>></div>
            <div class="col-md-4">
              <label class="form-label">Gender</label>
              <select class="form-select" name="gender" <?= $isEdit ? 'disabled' : '' ?>>
                <option value="Male" <?= $sel($v('gender', 'Male'), 'Male') ?>>Male</option>
                <option value="Female" <?= $sel($v('gender'), 'Female') ?>>Female</option>
                <option value="Other" <?= $sel($v('gender'), 'Other') ?>>Other</option>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Date of birth</label>
              <input class="form-control" type="date" name="dob" value="<?= e($v('dob')) ?>" <?= $isEdit ? 'disabled' : '' ?>></div>
            <div class="col-md-4"><label class="form-label">Blood group</label>
              <select class="form-select" name="blood_group">
                <option value="">—</option>
                <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                  <option value="<?= $bg ?>" <?= $sel($v('blood_group'), $bg) ?>><?= $bg ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Phone</label>
              <input class="form-control" name="phone" value="<?= e($v('phone')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" value="<?= e($v('email')) ?>"></div>
            <div class="col-12"><label class="form-label">Address</label>
              <input class="form-control" name="address" value="<?= e($v('address')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Occupation</label>
              <input class="form-control" name="occupation" value="<?= e($v('occupation')) ?>"></div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-people me-1"></i> Next of kin</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label>
              <input class="form-control" name="nk_name" value="<?= e($v('next_of_kin_name')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Phone</label>
              <input class="form-control" name="nk_phone" value="<?= e($v('next_of_kin_phone')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Relation</label>
              <input class="form-control" name="nk_relation" value="<?= e($v('next_of_kin_relation')) ?>"></div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-heart-pulse me-1"></i> Medical</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Allergies</label>
              <textarea class="form-control" name="allergies" rows="2"><?= e($v('allergies')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Medical history</label>
              <textarea class="form-control" name="medical_history" rows="2"><?= e($v('medical_history')) ?></textarea></div>
            <div class="col-12"><label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" rows="2"><?= e($v('notes')) ?></textarea></div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Save changes' : 'Register patient' ?></button>
        <a class="btn btn-outline-secondary" href="<?= base_url('patients' . ($patient ? '/show/' . $patient['id'] : '')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>