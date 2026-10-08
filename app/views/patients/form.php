<?php
$isEdit = !is_null($patient);
page_header(
    $isEdit ? 'Edit patient' : 'Register new patient',
    $isEdit ? 'Update demographic and contact information' : 'Create a new patient record'
);
// Rejected input wins over the stored record so a failed save keeps what was typed.
$v = function ($k, $default = '') use ($patient) {
    if (isset($_SESSION['old'][$k])) return $_SESSION['old'][$k];
    return $patient ? ($patient[$k] ?? $default) : $default;
};
$sel = function ($a, $b) {
    return (string)$a === (string)$b ? 'selected' : '';
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
            <div class="col-md-6">
              <label class="form-label required" for="first_name">First name</label>
              <input class="form-control<?= invalid('first_name') ?>" id="first_name" name="first_name"
                     value="<?= e($v('first_name')) ?>" required aria-required="true"
                     <?= $isEdit ? 'disabled' : '' ?>>
              <?= field_error('first_name') ?>
            </div>
            <div class="col-md-6">
              <label class="form-label required" for="last_name">Last name</label>
              <input class="form-control<?= invalid('last_name') ?>" id="last_name" name="last_name"
                     value="<?= e($v('last_name')) ?>" required aria-required="true"
                     <?= $isEdit ? 'disabled' : '' ?>>
              <?= field_error('last_name') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="gender">Gender</label>
              <select class="form-select<?= invalid('gender') ?>" id="gender" name="gender" <?= $isEdit ? 'disabled' : '' ?>>
                <option value="Male" <?= $sel($v('gender', 'Male'), 'Male') ?>>Male</option>
                <option value="Female" <?= $sel($v('gender'), 'Female') ?>>Female</option>
                <option value="Other" <?= $sel($v('gender'), 'Other') ?>>Other</option>
              </select>
              <?= field_error('gender') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="dob">Date of birth</label>
              <input class="form-control<?= invalid('dob') ?>" type="date" id="dob" name="dob"
                     value="<?= e($v('dob')) ?>" <?= $isEdit ? 'disabled' : '' ?>>
              <?= field_error('dob') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="blood_group">Blood group</label>
              <select class="form-select" id="blood_group" name="blood_group">
                <option value="">—</option>
                <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                  <option value="<?= $bg ?>" <?= $sel($v('blood_group'), $bg) ?>><?= $bg ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">Phone</label>
              <input class="form-control" type="tel" id="phone" name="phone" value="<?= e($v('phone')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="email">Email</label>
              <input class="form-control<?= invalid('email') ?>" type="email" id="email" name="email"
                     value="<?= e($v('email')) ?>" <?= old_error('email') ? 'aria-invalid="true"' : '' ?>>
              <?= field_error('email') ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="address">Address</label>
              <input class="form-control" id="address" name="address" value="<?= e($v('address')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="occupation">Occupation</label>
              <input class="form-control" id="occupation" name="occupation" value="<?= e($v('occupation')) ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-people me-1"></i> Next of kin</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="next_of_kin_name">Name</label>
              <input class="form-control" id="next_of_kin_name" name="next_of_kin_name" value="<?= e($v('next_of_kin_name')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="next_of_kin_phone">Phone</label>
              <input class="form-control" type="tel" id="next_of_kin_phone" name="next_of_kin_phone" value="<?= e($v('next_of_kin_phone')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="next_of_kin_relation">Relation</label>
              <input class="form-control" id="next_of_kin_relation" name="next_of_kin_relation" value="<?= e($v('next_of_kin_relation')) ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-heart-pulse me-1"></i> Medical</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="allergies">Allergies</label>
              <textarea class="form-control" id="allergies" name="allergies" rows="2"><?= e($v('allergies')) ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="medical_history">Medical history</label>
              <textarea class="form-control" id="medical_history" name="medical_history" rows="2"><?= e($v('medical_history')) ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label" for="notes">Notes</label>
              <textarea class="form-control" id="notes" name="notes" rows="2"><?= e($v('notes')) ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary px-4"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= $isEdit ? 'Save changes' : 'Register patient' ?></button>
        <a class="btn btn-outline-secondary" href="<?= base_url('patients' . ($patient ? '/show/' . $patient['id'] : '')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
