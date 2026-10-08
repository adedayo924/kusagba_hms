<?php
$editBtn = in_array(Auth::role(), ['admin', 'receptionist', 'nurse'])
    ? '<a class="btn btn-outline-primary" href="' . base_url('patients/edit/' . $patient['id']) . '"><i class="bi bi-pencil"></i> Edit</a>'
    : '';
$actions = $editBtn;
if (Auth::role() === 'admin') {
    $actions .= '<form class="d-inline" method="post" action="' . base_url('patients/delete/' . $patient['id']) . '" onsubmit="return confirm(\'Delete this patient record?\')">'
        . csrf_field() . '<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
}
page_header('Patient record', 'Full medical and administrative record for this patient', $actions);
?>

<div class="card mb-3">
  <div class="card-body d-flex flex-wrap align-items-center gap-3">
    <span class="avatar" style="width:52px;height:52px;font-size:1.1rem"><?= e(initials(patient_full_name($patient))) ?></span>
    <div class="me-auto">
      <h5 class="mb-1"><?= e(patient_full_name($patient)) ?></h5>
      <div class="text-muted small">
        <?= e($patient['patient_no']) ?>
        <?php if ($patient['gender']): ?> &middot; <?= e($patient['gender']) ?><?php endif; ?>
        <?php if ($patient['dob']): ?> &middot; Age <?= age_from_dob($patient['dob']) ?> <?php endif; ?>
        <?php if ($patient['blood_group']): ?> &middot; <span class="badge text-bg-danger"><?= e($patient['blood_group']) ?></span><?php endif; ?>
      </div>
      <?php if ($patient['phone'] || $patient['email']): ?>
        <div class="small text-muted mt-1">
          <?= e($patient['phone'] ?: '') ?> <?= $patient['phone'] && $patient['email'] ? '&middot;' : '' ?> <?= e($patient['email'] ?: '') ?>
        </div>
      <?php endif; ?>
    </div>
    <a class="btn btn-sm btn-outline-success" href="<?= base_url('appointments/create?patient=' . $patient['id']) ?>"><i class="bi bi-calendar-plus"></i> Book appointment</a>
    <a class="btn btn-sm btn-outline-success" href="<?= base_url('consultations/create?patient=' . $patient['id']) ?>"><i class="bi bi-clipboard2-plus"></i> New consultation</a>
    <a class="btn btn-sm btn-outline-success" href="<?= base_url('caregiving/create?patient=' . $patient['id']) ?>"><i class="bi bi-heart-pulse"></i> Caregiver</a>
    <a class="btn btn-sm btn-outline-success" href="<?= base_url('billing/create?patient=' . $patient['id']) ?>"><i class="bi bi-receipt"></i> Bill</a>
  </div>
</div>

<?php if (in_array(Auth::role(), ['admin', 'receptionist'])): ?>
<div class="card mb-3">
  <div class="card-body d-flex flex-wrap align-items-center gap-3">
    <div class="me-auto">
      <h6 class="mb-1"><i class="bi bi-person-lock"></i> Patient portal account</h6>
      <?php if ($account): ?>
        <div class="small text-muted">
          Username <strong><?= e($account['username']) ?></strong>
          <?= $account['active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?>
          <?php if ($account['last_login']): ?> &middot; last login <?= fmt_dt($account['last_login']) ?><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="small text-muted">No portal account yet. Create one so this patient can log in to their portal.</div>
      <?php endif; ?>
    </div>
    <?php if ($account): ?>
      <form class="d-inline" method="post" action="<?= base_url('patients/account_toggle/' . $account['id']) ?>"><?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-<?= $account['active'] ? 'secondary' : 'success' ?>">
          <i class="bi bi-power"></i> <?= $account['active'] ? 'Deactivate' : 'Activate' ?>
        </button>
      </form>
    <?php else: ?>
      <form class="d-flex flex-wrap gap-2 align-items-end" method="post" action="<?= base_url('patients/account_store/' . $patient['id']) ?>">
        <?= csrf_field() ?>
        <div><label class="form-label small mb-0">Username</label>
          <input class="form-control form-control-sm" name="username" placeholder="e.g. john.stephen" required></div>
        <div><label class="form-label small mb-0">Password</label>
          <input class="form-control form-control-sm" type="password" name="password" minlength="6" required></div>
        <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Create account</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<ul class="nav nav-tabs" id="recordTabs" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview">Overview</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-visits">Consultations (<?= count($visits) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-appointments">Appointments (<?= count($appointments) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-admissions">Admissions (<?= count($admissions) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-rx">Prescriptions (<?= count($prescriptions) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-lab">Laboratory (<?= count($labRequests) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-caregiving">Caregiving (<?= count($careEngagements ?? []) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-billing">Billing (<?= count($invoices) ?>)</button></li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3 bg-white">
  <div class="tab-pane fade show active" id="tab-overview">
    <div class="row g-4">
      <div class="col-md-6">
        <h6 class="text-uppercase text-muted small">Contact</h6>
        <dl class="row mb-0">
          <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?= e($patient['address'] ?: '—') ?></dd>
          <dt class="col-sm-4">Occupation</dt><dd class="col-sm-8"><?= e($patient['occupation'] ?: '—') ?></dd>
          <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?= e($patient['phone'] ?: '—') ?></dd>
          <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($patient['email'] ?: '—') ?></dd>
          <dt class="col-sm-4">Registered</dt><dd class="col-sm-8"><?= fmt_dt($patient['created_at']) ?></dd>
        </dl>
      </div>
      <div class="col-md-6">
        <h6 class="text-uppercase text-muted small">Next of kin</h6>
        <dl class="row mb-0">
          <dt class="col-sm-4">Name</dt><dd class="col-sm-8"><?= e($patient['next_of_kin_name'] ?: '—') ?></dd>
          <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?= e($patient['next_of_kin_phone'] ?: '—') ?></dd>
          <dt class="col-sm-4">Relation</dt><dd class="col-sm-8"><?= e($patient['next_of_kin_relation'] ?: '—') ?></dd>
        </dl>
        <h6 class="text-uppercase text-muted small mt-3">Allergies</h6>
        <p class="mb-0"><?= $patient['allergies'] ? '<span class="badge text-bg-danger">' . e($patient['allergies']) . '</span>' : 'None recorded' ?></p>
        <h6 class="text-uppercase text-muted small mt-3">Medical history</h6>
        <p class="mb-0 small"><?= e($patient['medical_history'] ?: '—') ?></p>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-visits">
    <?php if (!$visits): ?><p class="text-muted">No consultations yet.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Date</th><th>Doctor</th><th>Type</th><th>Complaint</th><th>Diagnosis</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($visits as $c): ?>
          <tr>
            <td><?= fmt_date($c['visit_date']) ?></td>
            <td><?= e($c['doctor_name'] ?: '—') ?></td>
            <td><?= ucfirst($c['visit_type']) ?></td>
            <td class="small"><?= e(mb_strimwidth($c['chief_complaint'] ?: '', 0, 60, '…')) ?></td>
            <td class="small"><?= e(mb_strimwidth($c['diagnosis'] ?: '', 0, 60, '…')) ?></td>
            <td><a class="btn btn-sm btn-light" href="<?= base_url('consultations/show/' . $c['id']) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tab-appointments">
    <?php if (!$appointments): ?><p class="text-muted">No appointments.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Reason</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($appointments as $a): ?>
          <tr>
            <td><?= fmt_date($a['appointment_date']) ?></td>
            <td><?= fmt_time($a['appointment_time']) ?></td>
            <td><?= e($a['doctor_name'] ?: '—') ?></td>
            <td class="small"><?= e($a['reason'] ?: '') ?></td>
            <td><?= status_badge($a['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tab-admissions">
    <?php if (!$admissions): ?><p class="text-muted">No admissions.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Admission No</th><th>Ward</th><th>Admitted</th><th>Discharged</th><th>Days</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($admissions as $x): ?>
          <?php $days = $x['status'] === 'discharged' && $x['discharged_at']
              ? max(0, (int)((strtotime($x['discharged_at']) - strtotime($x['admitted_at'])) / 86400))
              : (int)((time() - strtotime($x['admitted_at'])) / 86400); ?>
          <tr>
            <td><?= e($x['admission_no']) ?></td>
            <td><?= e($x['ward_name']) ?> <?= $x['bed_label'] ? '· ' . e($x['bed_label']) : '' ?></td>
            <td class="small"><?= fmt_dt($x['admitted_at']) ?></td>
            <td class="small"><?= $x['discharged_at'] ? fmt_dt($x['discharged_at']) : '—' ?></td>
            <td><?= $days ?></td>
            <td><?= status_badge($x['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tab-rx">
    <?php if (!$prescriptions): ?><p class="text-muted">No prescriptions.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Rx No</th><th>Drug</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Status</th><th>Dispensed</th></tr></thead>
        <tbody>
        <?php foreach ($prescriptions as $rx): ?>
          <tr>
            <td class="small"><?= e($rx['prescription_no']) ?></td>
            <td class="fw-semibold"><?= e($rx['drug_name'] ?? 'Deleted drug') ?></td>
            <td><?= e($rx['dosage'] ?: '') ?></td>
            <td class="small"><?= e($rx['frequency'] ?: '') ?></td>
            <td class="small"><?= e($rx['duration'] ?: '') ?></td>
            <td><?= status_badge($rx['status']) ?></td>
            <td class="small"><?= $rx['dispensed_at'] ? fmt_dt($rx['dispensed_at']) . ' by ' . e($rx['dispensed_by_name'] ?: '') : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tab-lab">
    <?php if (!$labRequests): ?><p class="text-muted">No lab requests.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Request</th><th>Date</th><th>Priority</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($labRequests as $lr): ?>
          <tr>
            <td class="small"><?= e($lr['request_no']) ?></td>
            <td class="small"><?= fmt_dt($lr['requested_at']) ?></td>
            <td><?= status_badge($lr['priority']) ?></td>
            <td><?= status_badge($lr['status']) ?></td>
            <td><a class="btn btn-sm btn-light" href="<?= base_url('lab/show/' . $lr['id']) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tab-billing">
    <?php if (!$invoices): ?><p class="text-muted">No invoices.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Invoice</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $inv): $bal = $inv['total'] - $inv['paid_amount']; ?>
          <tr>
            <td class="small"><?= e($inv['invoice_no']) ?></td>
            <td class="small"><?= fmt_date($inv['invoice_date']) ?></td>
            <td><?= money($inv['total']) ?></td>
            <td><?= money($inv['paid_amount']) ?></td>
            <td class="<?= $bal > 0 ? 'text-danger fw-semibold' : 'text-success' ?>"><?= money($bal) ?></td>
            <td><?= status_badge($inv['status']) ?></td>
            <td><a class="btn btn-sm btn-light" href="<?= base_url('billing/show/' . $inv['id']) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tab-caregiving">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0 fw-semibold text-secondary">Caregiving Engagements &amp; Home Care</h6>
      <a class="btn btn-sm btn-primary" href="<?= base_url('caregiving/create?patient=' . $patient['id']) ?>">
        <i class="bi bi-plus-lg"></i> New Care Engagement
      </a>
    </div>
    <?php if (empty($careEngagements)): ?>
      <?= empty_block('No caregiving services recorded for this patient yet.') ?>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Request #</th>
              <th>Delivery Type</th>
              <th>Shift Package</th>
              <th>Dates</th>
              <th>Assigned Caregiver</th>
              <th>Rate / Shift</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($careEngagements as $ce): ?>
              <tr>
                <td class="fw-semibold"><a href="<?= base_url('caregiving/show/' . $ce['id']) ?>" class="text-decoration-none"><?= e($ce['request_no']) ?></a></td>
                <td>
                  <?php if ($ce['care_type'] === 'home'): ?>
                    <span class="badge text-bg-info"><i class="bi bi-house-door"></i> Home</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary"><i class="bi bi-hospital"></i> Bedside</span>
                  <?php endif; ?>
                </td>
                <td class="small"><?= e(ucfirst(str_replace('_', ' ', $ce['shift_type']))) ?></td>
                <td class="small"><?= fmt_date($ce['start_date']) ?> <?= $ce['end_date'] ? '&rarr; ' . fmt_date($ce['end_date']) : '' ?></td>
                <td class="small"><?= e($ce['caregiver_name'] ?: 'Unassigned') ?></td>
                <td class="small fw-semibold"><?= money($ce['rate_per_shift']) ?></td>
                <td><?= status_badge($ce['status']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= base_url('caregiving/show/' . $ce['id']) ?>">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>