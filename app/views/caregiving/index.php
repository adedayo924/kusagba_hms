<?php
$newBtn = in_array(Auth::role(), ['admin', 'doctor', 'nurse', 'receptionist'])
    ? '<a class="btn btn-primary" href="' . base_url('caregiving/create') . '"><i class="bi bi-plus-lg"></i> New Care Engagement</a>'
    : '';
page_header('Caregiving Services', 'Home-based domiciliary care and in-hospital bedside caregiving management', $newBtn);
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-2">
    <div class="card stat-card">
      <div class="card-body py-3">
        <div class="text-muted small">Active Cases</div>
        <div class="fs-4 fw-bold text-primary"><?= (int)$stats['active'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="card stat-card">
      <div class="card-body py-3">
        <div class="text-muted small">Pending Requests</div>
        <div class="fs-4 fw-bold text-warning"><?= (int)$stats['pending'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="card stat-card">
      <div class="card-body py-3">
        <div class="text-muted small">Home Care</div>
        <div class="fs-4 fw-bold text-info"><?= (int)$stats['home'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="card stat-card">
      <div class="card-body py-3">
        <div class="text-muted small">Bedside Care</div>
        <div class="fs-4 fw-bold text-secondary"><?= (int)$stats['bedside'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="card stat-card">
      <div class="card-body py-3">
        <div class="text-muted small">Today's Shifts</div>
        <div class="fs-4 fw-bold text-success"><?= (int)$stats['today_shifts'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="card stat-card">
      <div class="card-body py-3">
        <div class="text-muted small">Caregivers</div>
        <div class="fs-4 fw-bold text-dark"><?= (int)$stats['caregivers_count'] ?></div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($todayShifts)): ?>
<div class="card mb-4 border-primary-subtle shadow-sm">
  <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-primary"><i class="bi bi-clock-history me-1"></i> Today's Caregiver Shifts (<?= date('M j, Y') ?>)</span>
    <span class="badge text-bg-primary"><?= count($todayShifts) ?> shifts</span>
  </div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Time / Shift</th>
          <th>Patient</th>
          <th>Caregiver</th>
          <th>Delivery Location</th>
          <th>Status</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($todayShifts as $ts): ?>
          <tr>
            <td class="small fw-semibold">
              <i class="bi bi-clock text-muted"></i>
              <?= $ts['shift_start'] ? fmt_time($ts['shift_start']) : 'All day' ?>
              <?= $ts['shift_end'] ? ' - ' . fmt_time($ts['shift_end']) : '' ?>
            </td>
            <td>
              <a href="<?= base_url('patients/show/' . $ts['patient_id']) ?>" class="fw-semibold text-decoration-none">
                <?= e($ts['patient_name']) ?>
              </a>
              <span class="text-muted small ms-1">(<?= e($ts['patient_no']) ?>)</span>
            </td>
            <td>
              <span class="badge text-bg-light border text-dark">
                <i class="bi bi-person me-1"></i><?= e($ts['caregiver_name'] ?: 'Unassigned') ?>
              </span>
            </td>
            <td class="small">
              <?php if ($ts['care_type'] === 'home'): ?>
                <span class="badge text-bg-info me-1"><i class="bi bi-house-door"></i> Home</span>
                <?= e($ts['location_address'] ?: 'Patient home address') ?>
              <?php else: ?>
                <span class="badge text-bg-secondary me-1"><i class="bi bi-hospital"></i> Bedside</span>
                <?= e($ts['ward_name'] ?: 'Hospital Ward') ?> <?= $ts['bed_label'] ? '(' . e($ts['bed_label']) . ')' : '' ?>
              <?php endif; ?>
            </td>
            <td><?= status_badge($ts['status']) ?></td>
            <td class="text-end">
              <a href="<?= base_url('caregiving/show/' . $ts['engagement_id']) ?>#shift-<?= (int)$ts['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-clipboard2-check"></i> Shift Log
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" action="<?= base_url('caregiving') ?>" class="row g-2 align-items-end">
      <div class="col-sm-3">
        <label class="form-label small mb-1" for="filter_q">Search</label>
        <input class="form-control form-control-sm" id="filter_q" type="search" name="q" value="<?= e($q) ?>" placeholder="Request #, patient name, address...">
      </div>
      <div class="col-sm-2">
        <label class="form-label small mb-1" for="filter_status">Status</label>
        <select class="form-select form-select-sm" id="filter_status" name="status">
          <option value="">All statuses</option>
          <?php foreach (['pending', 'approved', 'active', 'completed', 'cancelled'] as $st): ?>
            <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-2">
        <label class="form-label small mb-1" for="filter_type">Care Type</label>
        <select class="form-select form-select-sm" id="filter_type" name="care_type">
          <option value="">All types</option>
          <option value="home" <?= $careType === 'home' ? 'selected' : '' ?>>Home Visit</option>
          <option value="bedside" <?= $careType === 'bedside' ? 'selected' : '' ?>>Hospital Bedside</option>
        </select>
      </div>
      <?php if ($userRole !== 'caregiver'): ?>
      <div class="col-sm-3">
        <label class="form-label small mb-1" for="filter_cg">Caregiver</label>
        <select class="form-select form-select-sm" id="filter_cg" name="caregiver">
          <option value="0">All caregivers</option>
          <?php foreach ($caregivers as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $caregiverId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('caregiving') ?>">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Request #</th>
          <th>Patient</th>
          <th>Care Type</th>
          <th>Shift Package</th>
          <th>Dates &amp; Duration</th>
          <th>Assigned Caregiver</th>
          <th>Status</th>
          <th>Rate / Shift</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$engagements): ?>
          <?= empty_row(9, 'No caregiving engagements found.') ?>
        <?php else: foreach ($engagements as $e): ?>
          <tr>
            <td class="fw-semibold">
              <a href="<?= base_url('caregiving/show/' . $e['id']) ?>" class="text-decoration-none">
                <?= e($e['request_no']) ?>
              </a>
            </td>
            <td>
              <a href="<?= base_url('patients/show/' . $e['patient_id']) ?>" class="fw-semibold text-decoration-none">
                <?= e($e['patient_name']) ?>
              </a>
              <div class="text-muted small"><?= e($e['patient_no']) ?></div>
            </td>
            <td>
              <?php if ($e['care_type'] === 'home'): ?>
                <span class="badge text-bg-info"><i class="bi bi-house-door"></i> Home</span>
              <?php else: ?>
                <span class="badge text-bg-secondary"><i class="bi bi-hospital"></i> Bedside</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="small fw-semibold">
                <?php
                  $shiftNames = [
                    'day_8h' => 'Day Shift (8h)',
                    'night_12h' => 'Night Shift (12h)',
                    'full_24h' => '24h Live-in',
                    'custom' => 'Custom Shift'
                  ];
                  echo e($shiftNames[$e['shift_type']] ?? $e['shift_type']);
                ?>
              </span>
            </td>
            <td class="small">
              <div><?= fmt_date($e['start_date']) ?> <?= $e['end_date'] ? '&rarr; ' . fmt_date($e['end_date']) : '' ?></div>
              <span class="text-muted"><?= (int)$e['total_days'] ?> <?= (int)$e['total_days'] === 1 ? 'day' : 'days' ?></span>
            </td>
            <td>
              <?php if ($e['caregiver_name']): ?>
                <span class="d-inline-flex align-items-center gap-1">
                  <span class="avatar" style="width:24px;height:24px;font-size:0.65rem;"><?= initials($e['caregiver_name']) ?></span>
                  <span class="small"><?= e($e['caregiver_name']) ?></span>
                </span>
              <?php else: ?>
                <span class="text-muted fst-italic small">Unassigned</span>
              <?php endif; ?>
            </td>
            <td><?= status_badge($e['status']) ?></td>
            <td class="fw-semibold small"><?= money($e['rate_per_shift']) ?></td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="<?= base_url('caregiving/show/' . $e['id']) ?>" title="View dossier">
                <i class="bi bi-eye"></i> Details
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
