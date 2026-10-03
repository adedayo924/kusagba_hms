<?php
$buttons = '';
if ($r['status'] === 'pending') {
    $buttons .= '<form method="post" action="' . base_url('lab/collect/' . $r['id']) . '" class="d-inline">' . csrf_field() . '<button class="btn btn-outline-info">Collect sample</button></form>';
}
if (in_array($r['status'], ['pending', 'sample_collected', 'processing'], true)) {
    $buttons .= '<form method="post" action="' . base_url('lab/cancel/' . $r['id']) . '" class="d-inline" onsubmit="return confirm(\'Cancel this request?\')">' . csrf_field() . '<button class="btn btn-outline-danger"><i class="bi bi-x-octagon"></i></button></form>';
}
page_header('Lab ' . $r['request_no'], 'Laboratory request', $buttons);
?>
<div class="row g-3 mb-3">
  <div class="col-md-8">
    <div class="card h-100"><div class="card-body">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded bg-danger bg-opacity-10 p-3 text-danger text-center" style="width:64px">
          <i class="bi bi-eyedropper fs-3"></i>
        </div>
        <div>
          <div class="text-muted small"><?= e($r['request_no']) ?> · <?= status_badge($r['priority']) ?></div>
          <div class="h5 mb-0"><a class="text-decoration-none" href="<?= base_url('patients/show/' . $r['patient_id']) ?>"><?= e($r['patient_name']) ?></a></div>
          <div class="text-muted small"><?= e($r['patient_no']) ?>
            <?php if ($r['dob']): ?> · <?= age_from_dob($r['dob']) ?> yrs · <?= e($r['gender']) ?><?php endif; ?>
            <?= $r['blood_group'] ? ' · Blood ' . e($r['blood_group']) : '' ?></div>
          <div class="small mt-1">Requested by <strong><?= e($r['requester_name'] ?? '—') ?></strong> on <?= fmt_dt($r['requested_at']) ?></div>
          <?php if ($r['clinical_notes']): ?><div class="small text-muted mt-1"><?= e($r['clinical_notes']) ?></div><?php endif; ?>
        </div>
      </div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <div class="text-muted small">Status</div>
      <div class="h4 mb-0"><?= status_badge($r['status']) ?></div>
      <?php if ($r['resulted_at']): ?><div class="text-muted small mt-1">Resulted <?= fmt_dt($r['resulted_at']) ?></div><?php endif; ?>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header">Results</div>
  <form method="post" action="<?= base_url('lab/results/' . $r['id']) ?>">
    <?= csrf_field() ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th style="width:260px">Test</th><th>Result</th><th class="d-none d-md-table-cell">Normal range</th><th class="d-none d-md-table-cell">Notes</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td class="fw-semibold"><?= e($it['name']) ?><br>
              <span class="text-muted small fw-normal"><?= e($it['category'] ?: '') ?> <?= $it['unit'] ? '· ' . e($it['unit']) : '' ?></span></td>
            <td>
              <?php if ($canEnter): ?>
                <input class="form-control" name="results[<?= $it['req_test_id'] ?>][value]" value="<?= e($it['result_value'] ?? '') ?>">
              <?php else: ?>
                <span class="<?= $it['result_value'] || $it['result_value'] === null ? '' : 'text-muted' ?>"><?= e($it['result_value'] ?: '—') ?></span>
              <?php endif; ?>
            </td>
            <td class="small d-none d-md-table-cell text-muted"><?= e($it['normal_range'] ?: '—') ?></td>
            <td class="d-none d-md-table-cell">
              <?php if ($canEnter): ?>
                <input class="form-control" name="results[<?= $it['req_test_id'] ?>][note]" value="<?= e($it['result_note'] ?? '') ?>">
              <?php else: ?>
                <span class="small text-muted"><?= e($it['result_note'] ?: '—') ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($canEnter): ?>
      <div class="card-footer d-flex justify-content-end">
        <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Save results &amp; mark resulted</button>
      </div>
    <?php endif; ?>
  </form>
</div>