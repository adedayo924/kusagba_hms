<?php
$actions = $rx['status'] !== 'dispensed' && in_array(Auth::role(), ['admin', 'doctor'])
    ? '<form method="post" action="' . base_url('prescriptions/cancel/' . $rx['id']) . '" class="d-inline" onsubmit="return confirm(\'Cancel this prescription?\')">' . csrf_field() . '<button class="btn btn-outline-danger"><i class="bi bi-x-octagon"></i> Cancel</button></form>'
    : '';
page_header('Prescription ' . $rx['prescription_no'], 'Drug orders', $actions);
?>
<div class="row g-3 mb-3">
  <div class="col-md-8">
    <div class="card h-100"><div class="card-body">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded bg-primary bg-opacity-10 p-3 text-primary text-center" style="width:64px">
          <i class="bi bi-capsule fs-3"></i>
        </div>
        <div>
          <div class="text-muted small"><?= e($rx['prescription_no']) ?></div>
          <div class="h5 mb-0"><a class="text-decoration-none" href="<?= base_url('patients/show/' . $rx['patient_id']) ?>"><?= e($rx['patient_last']) ?>, <?= e($rx['patient_first']) ?></a></div>
          <div class="text-muted small"><?= e($rx['patient_no']) ?> · <?= $rx['dob'] ? age_from_dob($rx['dob']) . ' yrs · ' . e($rx['gender']) : e($rx['gender']) ?></div>
          <div class="small">Prescribed by <strong><?= e($rx['doctor_name'] ?? '—') ?></strong> on <?= fmt_dt($rx['prescribed_at']) ?></div>
        </div>
      </div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <div class="text-muted small">Status</div>
      <div class="h4 mb-1"><?= status_badge($rx['status']) ?></div>
      <?php if ($rx['dispensed_at']): ?><div class="text-muted small">Dispensed <?= fmt_dt($rx['dispensed_at']) ?></div><?php endif; ?>
      <?php if ($rx['notes']): ?><div class="small mt-2 text-muted"><?= e($rx['notes']) ?></div><?php endif; ?>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Drug lines</span>
    <?php if (!$disabled): ?>
      <form method="post" action="<?= base_url('prescriptions/complete/' . $rx['id']) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-primary"><i class="bi bi-check2-all"></i> Mark all dispensed</button>
      </form>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Drug</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th class="text-end">Qty</th><th class="text-end">Dispensed</th><th>Notes</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): $done = $it['quantity'] && $it['quantity_dispensed'] >= $it['quantity']; ?>
        <tr class="<?= $disabled && !$done ? 'text-muted' : '' ?>">
          <td class="fw-semibold"><?= e($it['drug_name'] ?? 'Deleted drug') ?></td>
          <td class="small"><?= e($it['dosage'] ?: '—') ?></td>
          <td class="small"><?= e($it['frequency'] ?: '—') ?></td>
          <td class="small"><?= e($it['duration'] ?: '—') ?></td>
          <td class="text-end"><?= (int)$it['quantity'] ?> <?= e($it['unit'] ?: '') ?></td>
          <td class="text-end"><?= (int)$it['quantity_dispensed'] ?> <?= $it['dispensed_at'] ? '<i class="bi bi-check2 text-success"></i>' : '' ?></td>
          <td class="small text-muted"><?= e($it['notes'] ?: '') ?></td>
          <td class="text-end" style="width:150px">
            <?php if (!$disabled && $it['quantity'] && $it['quantity_dispensed'] < $it['quantity']): ?>
              <form method="post" action="<?= base_url('prescriptions/item_dispense/' . $rx['id'] . '/' . $it['id']) ?>" class="input-group input-group-sm">
                <?= csrf_field() ?>
                <input class="form-control form-control-sm" type="number" min="1" max="<?= $it['quantity'] - $it['quantity_dispensed'] ?>" name="qty"
                       value="<?= $it['quantity'] - $it['quantity_dispensed'] ?>" style="width:70px">
                <button class="btn btn-outline-success btn-sm" title="Dispense and bill"><i class="bi bi-cash-coin"></i> Issue</button>
              </form>
            <?php else: ?>
              <span class="text-success small"><?= $done ? 'Fully dispensed' : '' ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>