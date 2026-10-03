<?php
page_header('Staff', 'System accounts',
    '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-plus-lg"></i> New Staff</button>');
$showArchived = $showArchived ?? false;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <form method="get" action="<?= base_url('staff') ?>" class="row g-2">
    <div class="col-auto">
      <label class="visually-hidden" for="st_role">Role</label>
      <select class="form-select" id="st_role" name="role">
        <option value="" <?= $role === '' ? 'selected' : '' ?>>All roles</option>
        <?php foreach (['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier'] as $r): ?>
          <option value="<?= e($r) ?>" <?= $role === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-4">
      <label class="visually-hidden" for="st_q">Search staff</label>
      <input class="form-control" id="st_q" type="search" name="q" value="<?= e($q) ?>" placeholder="Search staff...">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </div>
  </form>
  <a class="small" href="<?= base_url('staff' . ($showArchived ? '' : '?archived=1')) ?>">
    <?= $showArchived ? 'Hide archived' : 'Show archived' ?>
  </a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th scope="col">Staff</th><th scope="col">Username</th><th scope="col">Role</th>
          <th scope="col">Department</th><th scope="col">Contact</th><th scope="col">Status</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <?= empty_row(7, 'No staff found.') ?>
      <?php else: foreach ($list as $u): ?>
        <tr class="<?= $u['deleted_at'] ? 'table-light' : ($u['active'] ? '' : 'table-secondary') ?>">
          <td class="fw-semibold">
            <?= e($u['full_name']) ?>
            <?= Auth::id() === (int)$u['id'] ? ' <span class="badge text-bg-light">you</span>' : '' ?>
            <br><span class="text-muted small fw-normal"><?= e($u['specialty'] ?: '') ?></span>
          </td>
          <td class="small"><?= e($u['username']) ?></td>
          <td><?= e(role_label($u['role'])) ?></td>
          <td class="small"><?= e($u['department'] ?: '—') ?></td>
          <td class="small"><?= e($u['phone'] ?: '—') ?><br><?= e($u['email'] ?: '') ?></td>
          <td>
            <?php if ($u['deleted_at']): ?>
              <span class="badge text-bg-secondary">Archived</span>
            <?php elseif ($u['active']): ?>
              <span class="badge text-bg-success">Active</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap">
            <?php if ($u['deleted_at']): ?>
              <?= icon_btn(base_url('staff/restore/' . $u['id']), 'bi-arrow-counterclockwise', 'Restore ' . $u['full_name']) ?>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-secondary border-0" data-bs-toggle="modal" data-bs-target="#userModal"
                      data-id="<?= (int)$u['id'] ?>" data-username="<?= e($u['username']) ?>" data-fullname="<?= e($u['full_name']) ?>"
                      data-role="<?= e($u['role']) ?>" data-gender="<?= e($u['gender']) ?>" data-dob="<?= e($u['dob']) ?>"
                      data-phone="<?= e($u['phone']) ?>" data-email="<?= e($u['email']) ?>" data-staffid="<?= e($u['staff_id']) ?>"
                      data-specialty="<?= e($u['specialty']) ?>" data-license="<?= e($u['license_no']) ?>"
                      data-department="<?= e($u['department']) ?>" data-address="<?= e($u['address']) ?>"
                      aria-label="Edit <?= e($u['full_name']) ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= base_url('staff/toggle/' . $u['id']) ?>" class="d-inline"><?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-secondary border-0"
                        title="<?= $u['active'] ? 'Deactivate' : 'Activate' ?>"
                        aria-label="<?= $u['active'] ? 'Deactivate' : 'Activate' ?> <?= e($u['full_name']) ?>">
                  <i class="bi bi-<?= $u['active'] ? 'eye-slash' : 'eye' ?>"></i></button></form>
              <?php if (Auth::id() !== (int)$u['id']): ?>
                <?= icon_btn(base_url('staff/delete/' . $u['id']), 'bi-archive', 'Archive ' . $u['full_name']) ?>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php modal_open('userModal', 'Add staff', 'userForm', base_url('staff/save')); ?>
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label required" for="sName">Full name</label>
      <input class="form-control" name="full_name" id="sName" required>
    </div>
    <div class="col-md-4">
      <label class="form-label required" for="sRole">Role</label>
      <select class="form-select" name="role" id="sRole" required>
        <?php foreach (['doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier', 'admin'] as $r): ?>
          <option value="<?= e($r) ?>"><?= e(role_label($r)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label required" for="sUsername">Username</label>
      <input class="form-control" name="username" id="sUsername" required>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sPassword" id="sPasswordLabel">Password</label>
      <input class="form-control" type="password" name="password" id="sPassword" minlength="8" autocomplete="new-password">
      <div class="form-text">At least 8 characters.</div>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sStaffId">Staff ID</label>
      <input class="form-control" name="staff_id" id="sStaffId">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sGender">Gender</label>
      <select class="form-select" name="gender" id="sGender">
        <option value="">—</option>
        <option value="Male">Male</option><option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sDob">Date of birth</label>
      <input class="form-control" type="date" name="dob" id="sDob">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sPhone">Phone</label>
      <input class="form-control" name="phone" id="sPhone">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sEmail">Email</label>
      <input class="form-control" type="email" name="email" id="sEmail">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sDept">Department</label>
      <input class="form-control" name="department" id="sDept">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sSpecialty">Specialty</label>
      <input class="form-control" name="specialty" id="sSpecialty">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sLicense">License no.</label>
      <input class="form-control" name="license_no" id="sLicense">
    </div>
    <div class="col-12">
      <label class="form-label" for="sAddress">Address</label>
      <input class="form-control" name="address" id="sAddress">
    </div>
  </div>
<?php modal_close(); ?>

<?php modal_script('userModal', 'userForm', [
    'sName'      => 'data-fullname',
    'sUsername'  => 'data-username',
    'sRole'      => 'data-role',
    'sGender'    => 'data-gender',
    'sDob'       => 'data-dob',
    'sPhone'     => 'data-phone',
    'sEmail'     => 'data-email',
    'sStaffId'   => 'data-staffid',
    'sSpecialty' => 'data-specialty',
    'sLicense'   => 'data-license',
    'sDept'      => 'data-department',
    'sAddress'   => 'data-address',
]); ?>

<script>
(function () {
  var m = document.getElementById('userModal');
  var label = document.getElementById('sPasswordLabel');
  if (!m || !label) return;
  m.addEventListener('show.bs.modal', function (e) {
    var id = e.relatedTarget ? e.relatedTarget.getAttribute('data-id') : '';
    label.textContent = id ? 'New password (leave blank to keep current)' : 'Password';
    document.getElementById('sPassword').required = !id;
  });
})();
</script>