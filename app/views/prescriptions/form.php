<?php page_header('New prescription', 'Prescribe drugs for a patient'); ?>
<div class="row">
  <div class="col-lg-9">
    <form method="post" action="<?= base_url('prescriptions/store') ?>">
<?= csrf_field() ?>
          <input type="hidden" name="consultation_id" value="<?= (int)($preselectConsultationId ?? 0) ?>">
          <div class="card mb-3">
            <div class="card-header">Patient</div>
            <div class="card-body">
              <label class="form-label required">Patient / consultation</label>
              <?php if ($consultations): ?>
                <select class="form-select" name="patient_id" required>
                  <option value="">— select patient —</option>
                  <?php foreach ($consultations as $c): ?>
                    <option value="<?= $c['patient_id'] ?>" <?= (int)($preselectPatientId ?? 0) === (int)$c['patient_id'] ? 'selected' : '' ?>>
                      <?= e($c['patient_last']) ?>, <?= e($c['patient_first']) ?> — <?= e($c['patient_no']) ?> (consulted <?= fmt_date($c['visit_date']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">
                  <?= (int)($preselectConsultationId ?? 0) ? 'Pre-filled from consultation #' . (int)$preselectConsultationId . '. ' : '' ?>
                  Recent completed consultations listed first. You can still prescribe for any patient below.
                </div>
              <?php else: ?>
                <select class="form-select" name="patient_id" required>
                  <option value="">— select patient —</option>
                  <?php foreach ($patients as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int)($preselectPatientId ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                      <?= e($p['last_name']) ?>, <?= e($p['first_name']) ?> — <?= e($p['patient_no']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between">
          <span>Drug list</span>
          <button type="button" class="btn btn-sm btn-outline-primary" id="addDrug"><i class="bi bi-plus-lg"></i> Add drug</button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
          <table class="table align-middle mb-0" id="rxTable">
            <thead><tr><th>Drug</th><th class="w-25">Dosage</th><th>Frequency</th><th>Duration</th><th style="width:90px">Qty</th><th>Notes</th><th></th></tr></thead>
            <tbody></tbody>
          </table>
          <div class="text-muted small py-2" id="rxEmpty">Add at least one drug to prescribe.</div>
          </div>
          <label class="form-label mt-3">Prescribing notes</label>
          <textarea class="form-control" name="notes" rows="2"></textarea>
        </div>
      </div>

      <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary px-4" id="saveRx"><i class="bi bi-check2"></i> Save prescription</button>
        <a class="btn btn-outline-secondary" href="<?= base_url('prescriptions') ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
<?php
$rxDrugData = [];
foreach ($drugs as $d) {
    $rxDrugData[] = [(int)$d['id'], $d['name'], $d['unit'], (int)$d['quantity']];
}
echo 'var rxDrugs = ' . json_encode($rxDrugData) . ";\n";
?>
var rowNo = 0;
function drugRow() {
  rowNo++;
  var tr = document.createElement('tr');
  if (rxDrugs.length) {
    var sel = document.createElement('select');
    sel.setAttribute('name', 'drug_id[]'); sel.className = 'form-select'; sel.required = true;
    rxDrugs.forEach(function (o) {
      var op = document.createElement('option');
      op.value = o[0]; op.textContent = o[1] + ' (' + o[3] + ' ' + o[2] + ' in stock)';
      sel.appendChild(op);
    });
    var td = document.createElement('td'); td.appendChild(sel);
    tr.appendChild(td);
  } else {
    var td = document.createElement('td');
    td.innerHTML = '<span class="text-danger">No drugs in pharmacy.</span>'; tr.appendChild(td);
  }
  ['dosage', 'frequency', 'duration', 'qty', 'item_notes'].forEach(function (n, i) {
    var td = document.createElement('td');
    var inp = document.createElement('input');
    inp.className = 'form-control';
    if (n === 'qty') { inp.type = 'number'; inp.min = 1; inp.value = 1; inp.name = 'qty[]'; }
    else if (n === 'item_notes') { inp.setAttribute('name', 'item_notes[]'); }
    else { inp.setAttribute('name', n + '[]'); }
    td.appendChild(inp); tr.appendChild(td);
  });
  var bt = document.createElement('td');
  var b = document.createElement('button');
  b.type = 'button'; b.className = 'btn btn-sm btn-outline-danger';
  b.innerHTML = '<i class="bi bi-x-lg"></i>';
  b.onclick = function () { tr.remove(); checkEmpty(); };
  bt.appendChild(b); tr.appendChild(bt);
  return tr;
}
function checkEmpty() {
  document.getElementById('rxEmpty').style.display = document.querySelectorAll('#rxTable tbody tr').length ? 'none' : '';
}
document.getElementById('addDrug').onclick = function () {
  document.getElementById('rxTable').tBodies[0].appendChild(drugRow());
  checkEmpty();
};
</script>