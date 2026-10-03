<?php if (in_array(Auth::role(), ['admin', 'receptionist', 'nurse'])): ?>
  <?php $actions = '<a class="btn btn-primary" href="' . base_url('patients/create') . '"><i class="bi bi-person-plus"></i> New Patient</a>' ?>
<?php else: $actions = ''; endif; ?>
<?php page_header('Patients', "Registered patients ($total)", $actions); ?>

<div class="card">
  <div class="card-body pb-1">
    <form method="get" action="<?= base_url('patients') ?>" class="row g-2 align-items-center">
      <div class="col-sm-5">
        <input class="form-control" type="search" name="q" value="<?= e($q) ?>"
               placeholder="Search name, ID, phone...">
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Search</button>
      </div>
      <div class="col-auto ms-auto">
        <a class="btn btn-outline-secondary" href="<?= base_url('patients/export') ?>"><i class="bi bi-download"></i> Export CSV</a>
      </div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
      <tr><th>Patient ID</th><th>Name</th><th>Gender</th><th>Age</th><th>Phone</th><th>Blood</th><th>Visits</th><th>Registered</th><th></th></tr>
      </thead>
      <tbody>
      <?php if (!$patients): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No patients found<?= $q ? ' for "' . e($q) . '"' : '' ?>.</td></tr>
      <?php else: foreach ($patients as $p): ?>
        <tr>
          <td class="text-muted small"><?= e($p['patient_no']) ?></td>
          <td>
            <a href="<?= base_url('patients/show/' . $p['id']) ?>" class="fw-semibold text-decoration-none"><?= e(patient_full_name($p)) ?></a>
          </td>
          <td><?= e($p['gender']) ?></td>
          <td><?= age_from_dob($p['dob']) ?></td>
          <td><?= e($p['phone'] ?: '—') ?></td>
          <td><?= e($p['blood_group'] ?: '—') ?></td>
          <td><?= (int)$p['visits'] ?></td>
          <td class="small text-muted"><?= fmt_date($p['created_at']) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-light" href="<?= base_url('patients/show/' . $p['id']) ?>" title="Open record"><i class="bi bi-folder2-open"></i></a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
    <div class="card-body border-top">
      <nav>
        <ul class="pagination justify-content-center mb-0">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="<?= base_url('patients?page=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>