<?php
/** @var array $user  @var array $recent  @var array|null $patient */
$patient = $patient ?? null;
?>
<div class="row g-4">

  <div class="col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-body text-center">
        <span class="avatar avatar-lg"><?= e(initials($user['full_name'])) ?></span>
        <h2 class="h5 mt-3 mb-1"><?= e($user['full_name']) ?></h2>
        <p class="text-muted small mb-0"><?= e(role_label($user['role'])) ?></p>
        <p class="text-muted small mb-0">@<?= e($user['username']) ?></p>
      </div>
      <?php if ($patient): ?>
        <ul class="list-group list-group-flush small">
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">Patient number</span>
            <span class="fw-semibold"><?= e($patient['patient_no']) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">Date of birth</span>
            <span class="fw-semibold"><?= e($patient['dob'] ?: '—') ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">Blood group</span>
            <span class="fw-semibold"><?= e($patient['blood_group'] ?: '—') ?></span>
          </li>
        </ul>
        <div class="card-body border-top">
          <a class="btn btn-outline-primary btn-sm w-100" href="<?= base_url('portal') ?>">
            <i class="bi bi-person-heart me-1"></i>Go to my portal
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-8">

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-transparent">
        <h2 class="h6 mb-0">Change password</h2>
      </div>
      <div class="card-body">
        <form method="post" action="<?= base_url('profile/password') ?>" class="row g-3" autocomplete="off">
          <?= csrf_field() ?>
          <div class="col-md-4">
            <label class="form-label" for="current_password">Current password</label>
            <input type="password" class="form-control" id="current_password" name="current_password"
                   required autocomplete="current-password">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="new_password">New password</label>
            <input type="password" class="form-control" id="new_password" name="new_password"
                   required minlength="8" autocomplete="new-password">
            <div class="form-text">At least 8 characters.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="confirm_password">Confirm new password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                   required minlength="8" autocomplete="new-password">
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-key me-1"></i>Update password
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-transparent">
        <h2 class="h6 mb-0">Contact details</h2>
      </div>
      <div class="card-body">
        <form method="post" action="<?= base_url('profile/update_contact') ?>">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="cp_phone">Phone</label>
              <input type="text" class="form-control" id="cp_phone" name="phone"
                     value="<?= e($user['phone'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="cp_email">Email</label>
              <input type="email" class="form-control" id="cp_email" name="email"
                     value="<?= e($user['email'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="cp_address">Address</label>
              <input type="text" class="form-control" id="cp_address" name="address"
                     value="<?= e($user['address'] ?? '') ?>">
            </div>
            <div class="col-12">
              <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-save me-1"></i>Save contact details
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-transparent">
        <h2 class="h6 mb-0">My recent activity</h2>
      </div>
      <?php if (!$recent): ?>
        <div class="card-body text-center text-muted py-4">
          <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
          No activity recorded yet.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">When</th>
                <th scope="col">Action</th>
                <th scope="col">Module</th>
                <th scope="col">Details</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $log): ?>
                <tr>
                  <td class="text-nowrap small"><?= fmt_dt($log['created_at']) ?></td>
                  <td><span class="badge bg-secondary-subtle text-secondary"><?= e($log['action']) ?></span></td>
                  <td class="small"><?= e($log['module'] ?: '—') ?></td>
                  <td class="small text-muted"><?= e($log['details'] ?: '—') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>