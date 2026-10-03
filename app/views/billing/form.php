<?php page_header('New bill', 'Add service charges to a patient\'s invoice'); ?>
<form method="post" action="<?= base_url('billing/store') ?>">
  <?= csrf_field() ?>
  <div class="card mb-3">
    <div class="card-header">Patient</div>
    <div class="card-body">
      <select class="form-select" name="patient_id" required>
        <option value="">— select patient —</option>
        <?php foreach (fetch_all('SELECT id, patient_no, first_name, last_name FROM patients ORDER BY last_name, first_name') as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $patient && (int)$p['id'] === (int)$patient['id'] ? 'selected' : '' ?>>
            <?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($patient): ?>
        <div class="form-text">Pre-filled from the patient record. Existing open invoices for this patient are reused.</div>
      <?php else: ?>
        <div class="form-text">Charges are grouped onto this patient's open invoice (new one if none is open).</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Service lines</span>
      <div class="d-flex gap-2">
        <select class="form-select form-select-sm w-auto" id="svcPick">
          <option value="">Quick add from price list…</option>
          <?php foreach ($services as $s): ?>
            <option value="<?= e($s['name']) ?>" data-price="<?= round((float)$s['price'], 2) ?>">
              <?= e($s['category']) ?> — <?= e($s['name']) ?> (<?= money($s['price']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addLine"><i class="bi bi-plus-lg"></i> Add line</button>
      </div>
    </div>
    <div class="card-body">
      <table class="table align-middle mb-0" id="lineTable">
        <thead><tr><th>Description</th><th class="w-25">Amount (NGN)</th><th></th></tr></thead>
        <tbody></tbody>
      </table>
      <div class="text-muted small py-2" id="lineEmpty">Add at least one service line.</div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-4">
    <button class="btn btn-primary px-4"><i class="bi bi-check2"></i> Save bill</button>
    <a class="btn btn-outline-secondary" href="<?= base_url('billing') ?>">Cancel</a>
  </div>
</form>

<script>
function billRow(desc, amount) {
  var tr = document.createElement('tr');
  var td1 = document.createElement('td');
  var inpD = document.createElement('input');
  inpD.className = 'form-control'; inpD.setAttribute('name', 'description[]'); inpD.value = desc || ''; inpD.required = true;
  td1.appendChild(inpD); tr.appendChild(td1);
  var td2 = document.createElement('td');
  var inpA = document.createElement('input');
  inpA.className = 'form-control text-end'; inpA.type = 'number'; inpA.min = '0'; inpA.step = '0.01';
  inpA.setAttribute('name', 'amount[]'); inpA.value = amount > 0 ? amount : ''; inpA.required = true;
  td2.appendChild(inpA); tr.appendChild(td2);
  var td3 = document.createElement('td');
  var b = document.createElement('button');
  b.type = 'button'; b.className = 'btn btn-sm btn-outline-danger'; b.innerHTML = '<i class="bi bi-x-lg"></i>';
  b.onclick = function () { tr.remove(); checkEmpty(); };
  td3.appendChild(b); tr.appendChild(td3);
  return tr;
}
function checkEmpty() {
  document.getElementById('lineEmpty').style.display = document.querySelectorAll('#lineTable tbody tr').length ? 'none' : '';
}
document.getElementById('addLine').onclick = function () {
  var pick = document.getElementById('svcPick');
  document.getElementById('lineTable').tBodies[0].appendChild(billRow(pick.value, pick.selectedOptions[0].dataset.price));
  checkEmpty();
};
document.getElementById('svcPick').onchange = function () {
  if (this.value) {
    document.getElementById('lineTable').tBodies[0].appendChild(billRow(this.value, this.selectedOptions[0].dataset.price));
    this.value = ''; checkEmpty();
  }
};
if (!document.querySelectorAll('#lineTable tbody tr').length) {
  document.getElementById('lineTable').tBodies[0].appendChild(billRow('', 0));
}
</script>