<?php
page_header('Inventory', 'Supplies, equipment and stock levels',
    '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal"><i class="bi bi-plus-lg"></i> New Item</button>');
$showArchived = $showArchived ?? false;
$currency = app_setting('currency', defined('APP_CURRENCY') ? APP_CURRENCY : 'NGN');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <form method="get" action="<?= base_url('inventory') ?>" class="row g-2">
    <div class="col-auto">
      <label class="visually-hidden" for="inv_cat">Category</label>
      <select class="form-select" id="inv_cat" name="category">
        <option value="" <?= $cat === '' ? 'selected' : '' ?>>All categories</option>
        <option value="drug" <?= $cat === 'drug' ? 'selected' : '' ?>>Drugs</option>
        <option value="supply" <?= $cat === 'supply' ? 'selected' : '' ?>>Supplies</option>
        <option value="equipment" <?= $cat === 'equipment' ? 'selected' : '' ?>>Equipment</option>
        <option value="other" <?= $cat === 'other' ? 'selected' : '' ?>>Other</option>
      </select>
    </div>
    <div class="col-sm-4">
      <label class="visually-hidden" for="inv_q">Search items</label>
      <input class="form-control" id="inv_q" type="search" name="q" value="<?= e($q) ?>" placeholder="Search item...">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </div>
  </form>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= base_url('inventory/movements') ?>"><i class="bi bi-arrow-left-right"></i> Stock movements</a>
    <a class="btn btn-outline-secondary" href="<?= base_url('inventory' . ($showArchived ? '' : '?archived=1')) ?>">
      <?= $showArchived ? 'Hide archived' : 'Show archived' ?>
    </a>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th scope="col">Item</th><th scope="col">Category</th><th scope="col">In stock</th>
          <th scope="col">Low</th><th scope="col">Price</th><th scope="col">Status</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$items): ?>
        <?= empty_row(7, 'No items found.') ?>
      <?php else: foreach ($items as $i): $low = (int)$i['quantity'] <= (int)$i['reorder_level']; ?>
        <tr class="<?= $i['deleted_at'] ? 'table-light' : ($i['active'] ? '' : 'table-secondary') ?>">
          <td class="fw-semibold">
            <?= e($i['name']) ?>
            <br><span class="text-muted small fw-normal"><?= e($i['code'] ?: '—') ?> · <?= e($i['unit']) ?></span>
          </td>
          <td><?= e(ucfirst($i['category'])) ?></td>
          <td class="fw-semibold <?= $low && $i['active'] && !$i['deleted_at'] ? 'text-danger' : 'text-success' ?>"><?= (int)$i['quantity'] ?></td>
          <td><?= (int)$i['reorder_level'] ?></td>
          <td><?= money($i['selling_price']) ?></td>
          <td>
            <?php if ($i['deleted_at']): ?>
              <span class="badge text-bg-secondary">Archived</span>
            <?php elseif ($i['active']): ?>
              <span class="badge text-bg-success">Active</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap">
            <?php if ($i['deleted_at']): ?>
              <?= icon_btn(base_url('inventory/restore/' . $i['id']), 'bi-arrow-counterclockwise', 'Restore ' . $i['name']) ?>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#adjustModal"
                      data-id="<?= (int)$i['id'] ?>" data-name="<?= e($i['name']) ?>" data-stock="<?= (int)$i['quantity'] ?>"
                      data-mode="in" aria-label="Add stock to <?= e($i['name']) ?>"><i class="bi bi-plus-circle"></i> In</button>
              <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#adjustModal"
                      data-id="<?= (int)$i['id'] ?>" data-name="<?= e($i['name']) ?>" data-stock="<?= (int)$i['quantity'] ?>"
                      data-mode="out" aria-label="Remove stock from <?= e($i['name']) ?>"><i class="bi bi-dash-circle"></i> Out</button>
              <button type="button" class="btn btn-sm btn-outline-secondary border-0" data-bs-toggle="modal" data-bs-target="#itemModal"
                      data-id="<?= (int)$i['id'] ?>" data-code="<?= e($i['code']) ?>" data-name="<?= e($i['name']) ?>"
                      data-category="<?= e($i['category']) ?>" data-unit="<?= e($i['unit']) ?>"
                      data-reorder="<?= (int)$i['reorder_level'] ?>" data-price="<?= e($i['selling_price']) ?>"
                      data-notes="<?= e($i['notes']) ?>" aria-label="Edit <?= e($i['name']) ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= base_url('inventory/toggle/' . $i['id']) ?>" class="d-inline"><?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-secondary border-0"
                        title="<?= $i['active'] ? 'Deactivate' : 'Activate' ?>"
                        aria-label="<?= $i['active'] ? 'Deactivate' : 'Activate' ?> <?= e($i['name']) ?>">
                  <i class="bi bi-<?= $i['active'] ? 'eye-slash' : 'eye' ?>"></i></button></form>
              <?php if (Auth::role() === 'admin'): ?>
                <?= icon_btn(base_url('inventory/delete/' . $i['id']), 'bi-archive', 'Archive ' . $i['name']) ?>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php modal_open('itemModal', 'Add item', 'itemForm', base_url('inventory/save')); ?>
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label required" for="iName">Item name</label>
      <input class="form-control" name="name" id="iName" required>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="iCode">Code</label>
      <input class="form-control" name="code" id="iCode">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="iCategory">Category</label>
      <select class="form-select" name="category" id="iCategory">
        <option value="supply">Supply</option>
        <option value="equipment">Equipment</option>
        <option value="other">Other</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="iUnit">Unit</label>
      <input class="form-control" name="unit" id="iUnit" value="unit">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="iReorder">Reorder level</label>
      <input class="form-control" type="number" min="0" name="reorder_level" id="iReorder" value="0">
    </div>
    <div class="col-md-6">
      <label class="form-label" for="iPrice">Selling price (<?= e($currency) ?>)</label>
      <input class="form-control" type="number" step="0.01" min="0" name="selling_price" id="iPrice" value="0">
    </div>
    <div class="col-md-6">
      <label class="form-label" for="iNotes">Notes</label>
      <input class="form-control" name="notes" id="iNotes">
    </div>
  </div>
<?php modal_close(); ?>

<?php modal_script('itemModal', 'itemForm', [
    'iName'    => 'data-name',
    'iCode'    => 'data-code',
    'iCategory' => 'data-category',
    'iUnit'    => 'data-unit',
    'iReorder' => 'data-reorder',
    'iPrice'   => 'data-price',
    'iNotes'   => 'data-notes',
]); ?>

<?php modal_open('adjustModal', 'Stock in', 'adjustForm', base_url('inventory/adjust')); ?>
  <input type="hidden" name="item_id" id="ajItem">
  <input type="hidden" name="quantity" id="ajQty" value="0">
  <p class="mb-2 text-muted" id="ajName"></p>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label required" for="ajQtyIn">Quantity</label>
      <input class="form-control" type="number" min="1" id="ajQtyIn" required>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="ajBatch">Batch no. (stock in)</label>
      <input class="form-control" id="ajBatch" name="batch_no">
    </div>
    <div class="col-md-6">
      <label class="form-label" for="ajExpiry">Expiry (stock in)</label>
      <input class="form-control" id="ajExpiry" type="date" name="expiry_date">
    </div>
    <div class="col-12">
      <label class="form-label" for="ajReason">Reason</label>
      <input class="form-control" id="ajReason" name="reason" placeholder="e.g. Purchase, wastage, return...">
    </div>
  </div>
<?php modal_close('Apply'); ?>

<script>
(function () {
  var m = document.getElementById('adjustModal');
  if (!m) return;
  m.addEventListener('show.bs.modal', function (e) {
    var b = e.relatedTarget;
    var id = b ? b.getAttribute('data-id') : '';
    var stock = b ? parseInt(b.getAttribute('data-stock'), 10) : 0;
    var mode = b ? b.getAttribute('data-mode') : 'in';
    document.getElementById('ajItem').value = id;
    document.getElementById('ajName').textContent =
      (b ? b.getAttribute('data-name') : '') + ' — currently ' + stock + ' in stock';
    document.getElementById('adjustModal-title').textContent = mode === 'in' ? 'Stock in' : 'Stock out';
    var input = document.getElementById('ajQtyIn');
    input.value = '';
    input.min = 1;
  });
  // "Out" submits a negative quantity; the controller signs it.
  m.addEventListener('shown.bs.modal', function () {
    var title = document.getElementById('adjustModal-title').textContent;
    document.getElementById('ajQtyIn').value = '';
  });
  document.getElementById('ajQtyIn').addEventListener('input', function () {
    var v = parseInt(this.value, 10);
    var isOut = document.getElementById('adjustModal-title').textContent === 'Stock out';
    document.getElementById('ajQty').value = isNaN(v) || v === 0 ? 0 : (isOut ? -v : v);
  });
})();
</script>