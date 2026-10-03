<?php
page_header('Settings', 'Hospital information and configuration');
$currency = $settings['currency'] ?? (defined('APP_CURRENCY') ? APP_CURRENCY : 'NGN');
$showArchived = $showArchived ?? false;
?>
<ul class="nav nav-pills mb-3" id="setTabs">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabHospital"
            aria-controls="tabHospital" aria-selected="true">Hospital</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabServices"
            aria-controls="tabServices">Services (<?= count($services) ?>)</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabPassword"
            aria-controls="tabPassword">Change password</button>
  </li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tabHospital" role="tabpanel">
    <form method="post" action="<?= base_url('settings/update') ?>" class="col-lg-8">
      <?= csrf_field() ?>
      <div class="card">
        <div class="card-header">Hospital details</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="s_hospital_name">Hospital name</label>
              <input class="form-control" id="s_hospital_name" name="hospital_name"
                     value="<?= e($settings['hospital_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="s_currency">Currency</label>
              <?php /* Explicit value attributes: the option text is what gets posted,
                       so a label change used to silently change the stored currency. */ ?>
              <select class="form-select" id="s_currency" name="currency">
                <?php foreach (['NGN', 'USD', 'GHS', 'KES', 'EUR', 'GBP'] as $cur): ?>
                  <option value="<?= e($cur) ?>" <?= $currency === $cur ? 'selected' : '' ?>><?= e($cur) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="s_hospital_address">Address</label>
              <input class="form-control" id="s_hospital_address" name="hospital_address"
                     value="<?= e($settings['hospital_address'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="s_hospital_phone">Phone</label>
              <input class="form-control" id="s_hospital_phone" name="hospital_phone"
                     value="<?= e($settings['hospital_phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="s_hospital_email">Email</label>
              <input class="form-control" id="s_hospital_email" type="email" name="hospital_email"
                     value="<?= e($settings['hospital_email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="s_timezone">Timezone</label>
              <input class="form-control" id="s_timezone" name="timezone"
                     value="<?= e($settings['timezone'] ?? (defined('TIMEZONE') ? TIMEZONE : 'Africa/Lagos')) ?>">
              <div class="form-text">IANA name, e.g. <code>Africa/Lagos</code>.</div>
            </div>
            <div class="col-12">
              <label class="form-label" for="s_receipt_footer">Receipt footer</label>
              <input class="form-control" id="s_receipt_footer" name="receipt_footer"
                     value="<?= e($settings['receipt_footer'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save settings</button>
        </div>
      </div>
    </form>
  </div>

  <div class="tab-pane fade" id="tabServices" role="tabpanel">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Price list</span>
        <a class="small" href="<?= base_url('settings' . ($showArchived ? '' : '?archived=1')) ?>">
          <?= $showArchived ? 'Hide archived' : 'Show archived' ?>
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Code</th><th scope="col">Service</th><th scope="col">Category</th>
              <th scope="col" class="text-end">Price</th><th scope="col">Description</th>
              <th scope="col" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$services): ?>
            <?= empty_row(6, 'No services defined yet.') ?>
          <?php else: foreach ($services as $s): ?>
            <tr>
              <td class="small text-muted"><?= e($s['code'] ?: '—') ?></td>
              <td class="fw-semibold">
                <?= e($s['name']) ?>
                <?php if ($s['deleted_at']): ?><span class="badge bg-secondary ms-1">Archived</span><?php endif; ?>
              </td>
              <td><?= e(ucfirst($s['category'])) ?></td>
              <td class="text-end"><?= money($s['price']) ?></td>
              <td class="small text-muted"><?= e($s['description'] ?: '—') ?></td>
              <td class="text-end text-nowrap">
                <?php if ($s['deleted_at']): ?>
                  <?= icon_btn(base_url('settings/services_restore/' . $s['id']), 'bi-arrow-counterclockwise', 'Restore ' . $s['name']) ?>
                <?php else: ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary border-0"
                          data-bs-target="#serviceModal" data-bs-toggle="modal"
                          data-id="<?= (int)$s['id'] ?>" data-code="<?= e($s['code']) ?>"
                          data-name="<?= e($s['name']) ?>" data-category="<?= e($s['category']) ?>"
                          data-price="<?= e($s['price']) ?>" data-description="<?= e($s['description']) ?>"
                          aria-label="Edit <?= e($s['name']) ?>"><i class="bi bi-pencil"></i></button>
                  <?= icon_btn(base_url('settings/services_delete/' . $s['id']), 'bi-archive', 'Archive ' . $s['name']) ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#serviceModal">
          <i class="bi bi-plus-lg"></i> Add service
        </button>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tabPassword" role="tabpanel">
    <form method="post" action="<?= base_url('profile/password') ?>" class="col-lg-6">
      <?= csrf_field() ?>
      <div class="card">
        <div class="card-header">Change my password</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="sp_current">Current password</label>
            <input class="form-control" id="sp_current" type="password" name="current_password"
                   required autocomplete="current-password">
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="sp_new">New password</label>
              <input class="form-control" id="sp_new" type="password" name="new_password"
                     required minlength="8" autocomplete="new-password">
              <div class="form-text">At least 8 characters.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="sp_confirm">Confirm</label>
              <input class="form-control" id="sp_confirm" type="password" name="confirm_password"
                     required minlength="8" autocomplete="new-password">
            </div>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-warning" type="submit"><i class="bi bi-key"></i> Change password</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php modal_open('serviceModal', 'Add service', 'serviceForm', base_url('settings/services_save')); ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label" for="vCode">Code</label>
      <input class="form-control" name="code" id="vCode">
    </div>
    <div class="col-md-8">
      <label class="form-label required" for="vName">Service name</label>
      <input class="form-control" name="name" id="vName" required>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="vCategory">Category</label>
      <select class="form-select" name="category" id="vCategory">
        <?php foreach (['consultation', 'lab', 'drug', 'ward', 'procedure', 'other'] as $c): ?>
          <option value="<?= e($c) ?>"><?= e(ucfirst($c)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="vPrice">Price (<?= e($currency) ?>)</label>
      <input class="form-control" id="vPrice" type="number" step="0.01" min="0" name="price" value="0">
    </div>
    <div class="col-12">
      <label class="form-label" for="vDescription">Description</label>
      <input class="form-control" name="description" id="vDescription">
    </div>
  </div>
<?php modal_close(); ?>

<?php modal_script('serviceModal', 'serviceForm', [
  'vCode'        => 'data-code',
  'vName'        => 'data-name',
  'vCategory'    => 'data-category',
  'vPrice'       => 'data-price',
  'vDescription' => 'data-description',
]); ?>