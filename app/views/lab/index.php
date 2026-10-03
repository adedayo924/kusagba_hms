<?php
$actions = in_array(Auth::role(), ['admin', 'doctor'])
    ? '<a class="btn btn-primary" href="' . base_url('lab/create') . '"><i class="bi bi-plus-lg"></i> New Request</a>'
    : '';
page_header('Laboratory', 'Lab requests and results', $actions);
?>
<form method="get" action="<?= base_url('lab') ?>" class="row g-2 mb-3">
  <div class="col-auto">
    <select class="form-select" name="status">
      <option value="" <?= $status === '' ? 'selected' : '' ?>>All statuses</option>
      <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="sample_collected" <?= $status === 'sample_collected' ? 'selected' : '' ?>>Sample collected</option>
      <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
      <option value="resulted" <?= $status === 'resulted' ? 'selected' : '' ?>>Resulted</option>
      <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>
  </div>
  <div class="col-sm-4"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search request no or patient..."></div>
  <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Request</th><th>Patient</th><th>Tests</th><th>Priority</th><th>Requested</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No lab requests found.</td></tr>
      <?php else: foreach ($list as $x): ?>
        <tr>
          <td class="small text-muted"><?= e($x['request_no']) ?></td>
          <td class="fw-semibold"><a class="text-decoration-none" href="<?= base_url('patients/show/' . $x['patient_id']) ?>"><?= e($x['patient_name']) ?></a></td>
          <td><?= (int)$x['test_count'] ?></td>
          <td><?= status_badge($x['priority']) ?></td>
          <td class="small"><?= fmt_dt($x['requested_at']) ?></td>
          <td><?= status_badge($x['status']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= base_url('lab/show/' . $x['id']) ?>">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>