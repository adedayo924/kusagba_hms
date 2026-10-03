<?php
page_header('Lab tests', 'Price list and reference ranges', '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal"><i class="bi bi-plus-lg"></i> New Test</button>');
?>
  <div class="d-flex justify-content-end mb-2">
    <a class="small" href="<?= base_url("lab/tests" . ($showArchived ? "" : "?archived=1")) ?>">View <?= $showArchived ? "active" : "archived" ?> tests</a>
  </div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th scope="col">Code</th><th scope="col">Test</th><th scope="col">Category</th>
          <th scope="col">Unit</th><th scope="col">Price</th><th scope="col">Normal range</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$tests): ?>
        <?= empty_row(7, $showArchived ? 'No archived tests.' : 'No tests defined yet.') ?>
      <?php else: foreach ($tests as $t): ?>
        <tr class="<?= $t['deleted_at'] ? 'table-light' : '' ?>">
          <td class="small text-muted"><?= e($t['code'] ?: 'â€”') ?></td>
          <td class="fw-semibold">
            <?= e($t['name']) ?>
            <?php if ($t['deleted_at']): ?><span class="badge bg-secondary ms-1">Archived</span><?php endif; ?>
          </td>
          <td><?= e($t['category'] ?: 'â€”') ?></td>
          <td><?= e($t['unit'] ?: 'â€”') ?></td>
          <td><?= money($t['price']) ?></td>
          <td class="small text-muted"><?= e($t['normal_range'] ?: 'â€”') ?></td>
          <td class="text-end text-nowrap">
            <?php if ($t['deleted_at']): ?>
              <?= icon_btn(base_url('lab/tests_restore/' . $t['id']), 'bi-arrow-counterclockwise', 'Restore ' . $t['name']) ?>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-secondary border-0" data-bs-toggle="modal"
                      data-bs-target="#testModal" data-id="<?= (int)$t['id'] ?>"
                      data-code="<?= e($t['code']) ?>" data-name="<?= e($t['name']) ?>"
                      data-category="<?= e($t['category']) ?>" data-unit="<?= e($t['unit']) ?>"
                      data-price="<?= e($t['price']) ?>" data-range="<?= e($t['normal_range']) ?>"
                      aria-label="Edit <?= e($t['name']) ?>"><i class="bi bi-pencil"></i></button>
              <?php if (Auth::role() === 'admin'): ?>
                <?= icon_btn(base_url('lab/tests_delete/' . $t['id']), 'bi-archive', 'Archive ' . $t['name']) ?>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php modal_open('testModal', 'Add test', 'testForm', base_url('lab/tests_save')); ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label" for="tCode">Code</label>
      <input class="form-control" name="code" id="tCode">
    </div>
    <div class="col-md-8">
      <label class="form-label required" for="tName">Test name</label>
      <input class="form-control" name="name" id="tName" required>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="tCategory">Category</label>
      <input class="form-control" name="category" id="tCategory" placeholder="e.g. Haematology">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="tUnit">Unit</label>
      <input class="form-control" name="unit" id="tUnit" placeholder="e.g. mmol/L">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="tPrice">Price (<?= e(app_setting('currency', APP_CURRENCY)) ?>)</label>
      <input class="form-control" type="number" step="0.01" min="0" name="price" id="tPrice">
    </div>
    <div class="col-12">
      <label class="form-label" for="tRange">Normal range</label>
      <input class="form-control" name="normal_range" id="tRange" placeholder="e.g. 4.0 &ndash; 11.0">
    </div>
  </div>
<?php modal_close(); ?>

<?php modal_script('testModal', 'testForm', [
  'tCode'     => 'data-code',
  'tName'     => 'data-name',
  'tCategory' => 'data-category',
  'tUnit'     => 'data-unit',
  'tPrice'    => 'data-price',
  'tRange'    => 'data-range',
]); ?>