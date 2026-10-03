<?php
$canEdit = in_array(Auth::role(), ['admin', 'pharmacist'], true);
$actions = $canEdit
    ? '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#drugModal"><i class="bi bi-plus-lg"></i> New Drug</button>'
    : '';
page_header('Pharmacy', 'Drug catalogue and stock', $actions);
$currency = app_setting('currency', defined('APP_CURRENCY') ? APP_CURRENCY : 'NGN');
$showArchived = $showArchived ?? false;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <form method="get" action="<?= base_url('pharmacy') ?>" class="row g-2">
    <div class="col-sm-6">
      <label class="visually-hidden" for="ph_q">Search drugs</label>
      <input class="form-control" id="ph_q" type="search" name="q" value="<?= e($q) ?>"
             placeholder="Search drug...">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Search</button>
    </div>
  </form>
  <a class="small" href="<?= base_url('pharmacy' . ($showArchived ? '' : '?archived=1')) ?>">
    <?= $showArchived ? 'Hide archived' : 'Show archived' ?>
  </a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th scope="col">Drug</th><th scope="col">Code</th><th scope="col">Unit</th>
          <th scope="col">In stock</th><th scope="col">Reorder level</th><th scope="col">Price</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$drugs): ?>
        <?= empty_row(7, 'No drugs found' . ($q ? ' for "' . $q . '"' : '') . '.') ?>
      <?php else: foreach ($drugs as $d): $low = $d['quantity'] <= $d['reorder_level']; ?>
        <tr class="<?= $d['deleted_at'] ? 'table-light' : '' ?>">
          <td class="fw-semibold">
            <?= e($d['name']) ?>
            <?php if ($low && !$d['deleted_at']): ?><span class="badge text-bg-danger">Low stock</span><?php endif; ?>
            <?php if ($d['deleted_at']): ?><span class="badge bg-secondary">Archived</span><?php endif; ?>
          </td>
          <td class="small text-muted"><?= e($d['code'] ?: '—') ?></td>
          <td><?= e($d['unit']) ?></td>
          <td class="fw-semibold <?= $low ? 'text-danger' : 'text-success' ?>"><?= (int)$d['quantity'] ?></td>
          <td><?= (int)$d['reorder_level'] ?></td>
          <td><?= money($d['selling_price']) ?></td>
          <td class="text-end text-nowrap">
            <?php if ($canEdit): ?>
              <?php if ($d['deleted_at']): ?>
                <?= icon_btn(base_url('pharmacy/restore/' . $d['id']), 'bi-arrow-counterclockwise', 'Restore ' . $d['name']) ?>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#restockModal"
                        data-id="<?= (int)$d['id'] ?>" data-name="<?= e($d['name']) ?>"
                        aria-label="Restock <?= e($d['name']) ?>"><i class="bi bi-plus-circle"></i> Restock</button>
                <button type="button" class="btn btn-sm btn-outline-secondary border-0" data-bs-toggle="modal" data-bs-target="#drugModal"
                        data-id="<?= (int)$d['id'] ?>" data-code="<?= e($d['code']) ?>" data-name="<?= e($d['name']) ?>"
                        data-unit="<?= e($d['unit']) ?>" data-reorder="<?= (int)$d['reorder_level'] ?>"
                        data-price="<?= e($d['selling_price']) ?>" data-notes="<?= e($d['notes']) ?>"
                        aria-label="Edit <?= e($d['name']) ?>"><i class="bi bi-pencil"></i></button>
                <?php if (Auth::role() === 'admin'): ?>
                  <?= icon_btn(base_url('pharmacy/delete/' . $d['id']), 'bi-archive', 'Archive ' . $d['name']) ?>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canEdit): ?>
  <?php modal_open('drugModal', 'Add drug', 'drugForm', base_url('pharmacy/save')); ?>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label required" for="dName">Drug name</label>
        <input class="form-control" name="name" id="dName" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="dCode">Code</label>
        <input class="form-control" name="code" id="dCode">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="dUnit">Unit</label>
        <input class="form-control" name="unit" id="dUnit" value="tablet">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="dReorder">Reorder level</label>
        <input class="form-control" type="number" min="0" name="reorder_level" id="dReorder" value="5">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="dPrice">Selling price (<?= e($currency) ?>)</label>
        <input class="form-control" type="number" step="0.01" min="0" name="selling_price" id="dPrice" value="0">
      </div>
      <div class="col-12">
        <label class="form-label" for="dNotes">Notes</label>
        <input class="form-control" name="notes" id="dNotes">
      </div>
    </div>
  <?php modal_close(); ?>

  <?php modal_script('drugModal', 'drugForm', [
      'dName'    => 'data-name',
      'dCode'    => 'data-code',
      'dUnit'    => 'data-unit',
      'dReorder' => 'data-reorder',
      'dPrice'   => 'data-price',
      'dNotes'   => 'data-notes',
  ]); ?>

  <?php modal_open('restockModal', 'Restock', 'restockForm', base_url('pharmacy/restock')); ?>
    <input type="hidden" name="item_id" id="rsItem">
    <p class="mb-3 text-muted small" id="rsName"></p>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label required" for="rsQty">Quantity</label>
        <input class="form-control" id="rsQty" type="number" min="1" name="quantity" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="rsBatch">Batch no.</label>
        <input class="form-control" id="rsBatch" name="batch_no">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="rsExpiry">Expiry date</label>
        <input class="form-control" id="rsExpiry" type="date" name="expiry_date">
      </div>
    </div>
  <?php modal_close('Add stock', 'btn btn-success'); ?>

  <script>
  (function () {
    var m = document.getElementById('restockModal');
    if (!m) return;
    m.addEventListener('show.bs.modal', function (e) {
      var t = e.relatedTarget;
      document.getElementById('rsItem').value = t ? t.getAttribute('data-id') : '';
      document.getElementById('rsName').textContent = t ? t.getAttribute('data-name') : '';
    });
  })();
  </script>
<?php endif; ?>