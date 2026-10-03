<?php
$isAdmin = Auth::role() === 'admin';
$actions = $isAdmin
    ? '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#wardModal"><i class="bi bi-plus-lg"></i> New Ward</button>'
    : '';
page_header('Wards', 'Bed capacity and occupancy per ward', $actions);
$showArchived = $showArchived ?? false;
?>
<div class="d-flex justify-content-end mb-3">
  <a class="small" href="<?= base_url('wards' . ($showArchived ? '' : '?archived=1')) ?>">
    <?= $showArchived ? 'Hide archived' : 'Show archived' ?>
  </a>
</div>

<div class="row g-3">
  <?php if (!$wards): ?>
    <div class="col-12">
      <div class="card"><div class="card-body"><?= empty_block('No wards configured.') ?></div></div>
    </div>
  <?php endif; ?>
  <?php foreach ($wards as $w):
      $occ = (int)$w['occupied']; $total = (int)$w['total_beds'];
      $pct = $total ? round(($occ / $total) * 100) : 0; ?>
    <div class="col-md-6 col-xl-4">
      <div class="card h-100<?= $w['deleted_at'] ? ' opacity-50' : '' ?>">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h2 class="h5 mb-1">
                <?= e($w['name']) ?>
                <?php if ($w['deleted_at']): ?><span class="badge bg-secondary">Archived</span><?php endif; ?>
              </h2>
              <div class="text-muted small"><?= e($w['department'] ?: '—') ?></div>
            </div>
            <span class="fs-4 fw-bold <?= $pct >= 80 ? 'text-danger' : ($pct >= 50 ? 'text-warning' : 'text-success') ?>"><?= $pct ?>%</span>
          </div>
          <div class="progress my-3" style="height:10px">
            <div class="progress-bar <?= $pct >= 80 ? 'bg-danger' : ($pct >= 50 ? 'bg-warning' : 'bg-success') ?>"
                 style="width:<?= $pct ?>%" role="progressbar"
                 aria-label="<?= e($w['name']) ?> occupancy"
                 aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small"><?= $occ ?> of <?= $total ?> beds occupied</span>
            <?php if ($isAdmin): ?>
              <div class="btn-group">
                <?php if ($w['deleted_at']): ?>
                  <?= icon_btn(base_url('wards/restore/' . $w['id']), 'bi-arrow-counterclockwise', 'Restore ' . $w['name']) ?>
                <?php else: ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#wardModal"
                          data-id="<?= (int)$w['id'] ?>" data-name="<?= e($w['name']) ?>"
                          data-department="<?= e($w['department']) ?>" data-beds="<?= $total ?>"
                          data-notes="<?= e($w['notes']) ?>" aria-label="Edit <?= e($w['name']) ?>">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <?= icon_btn(base_url('wards/delete/' . $w['id']), 'bi-archive', 'Archive ' . $w['name']) ?>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
  <?php modal_open('wardModal', 'Add ward', 'wardForm', base_url('wards/save')); ?>
    <div class="row g-3">
      <div class="col-md-7">
        <label class="form-label required" for="wName">Ward name</label>
        <input class="form-control" name="name" id="wName" required>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="wDept">Department</label>
        <input class="form-control" name="department" id="wDept">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="wBeds">Total beds</label>
        <input class="form-control" type="number" min="1" name="total_beds" id="wBeds" value="10" required>
      </div>
      <div class="col-md-8">
        <label class="form-label" for="wNotes">Notes</label>
        <input class="form-control" name="notes" id="wNotes">
      </div>
    </div>
  <?php modal_close(); ?>

  <?php modal_script('wardModal', 'wardForm', [
      'wName'  => 'data-name',
      'wDept'  => 'data-department',
      'wBeds'  => 'data-beds',
      'wNotes' => 'data-notes',
  ]); ?>
<?php endif; ?>