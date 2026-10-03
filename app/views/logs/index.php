<?php page_header('Activity Logs', 'Audit trail of system actions'); ?>
<form method="get" action="<?= base_url('logs') ?>" class="row g-2 mb-3">
  <div class="col-auto">
    <select class="form-select" name="module">
      <option value="" <?= $module === '' ? 'selected' : '' ?>>All modules</option>
      <?php foreach ($modules as $m): ?>
        <option value="<?= e($m['module']) ?>" <?= $module === $m['module'] ? 'selected' : '' ?>>
          <?= e(ucfirst($m['module'])) ?> (<?= (int)$m['c'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-4"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search details, action, user..."></div>
  <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>When</th><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th></tr></thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No activity recorded.</td></tr>
      <?php else: foreach ($list as $a): ?>
        <tr>
          <td class="small text-nowrap"><?= fmt_dt($a['created_at']) ?></td>
          <td class="small"><?= e($a['user_name'] ?: 'System') ?><br><span class="text-muted small"><?= e($a['username'] ?: '') ?></span></td>
          <td><span class="badge text-bg-light border"><?= e(ucfirst($a['action'])) ?></span></td>
          <td class="small"><?= e($a['module'] ? ucfirst($a['module']) : '—') ?></td>
          <td class="small"><?= e($a['details'] ?: '—') ?></td>
          <td class="small text-muted"><?= e($a['ip'] ?: '—') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>