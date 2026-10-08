<?php
// page_header() escapes its arguments, so pass raw text — not pre-escaped HTML.
$patientName = $engagement['patient_name'];
$actions = '<a href="' . base_url('caregiving') . '" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> All Cases</a>';

// Billing action: same roles as BillingController's staff-only guard.
if (in_array(Auth::role(), ['admin', 'cashier', 'receptionist'], true)) {
    if (!$engagement['invoice_id']) {
        $actions .= ' <form method="post" action="' . base_url('caregiving/generate_bill/' . $engagement['id']) . '" class="d-inline">'
            . csrf_field() . '<button class="btn btn-outline-success"><i class="bi bi-receipt" aria-hidden="true"></i> Generate Bill</button></form>';
    }
}
if (Auth::role() === 'admin') {
    $actions .= ' <form method="post" action="' . base_url('caregiving/delete/' . $engagement['id']) . '" class="d-inline" onsubmit="return confirm(\'Archive this caregiving engagement?\')">'
        . csrf_field() . '<button class="btn btn-outline-danger" aria-label="Archive engagement" title="Archive engagement"><i class="bi bi-archive" aria-hidden="true"></i></button></form>';
}

page_header('Care Engagement ' . $engagement['request_no'], 'Patient: ' . $patientName . ' · Registered on ' . fmt_date($engagement['created_at']), $actions);
?>

<!-- Patient & Engagement Overview Banner -->
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="avatar" style="width:48px;height:48px;font-size:1.1rem;"><?= initials($engagement['patient_name']) ?></span>
          <div>
            <h5 class="mb-0">
              <a href="<?= base_url('patients/show/' . $engagement['patient_id']) ?>" class="text-decoration-none">
                <?= e($engagement['patient_name']) ?>
              </a>
            </h5>
            <div class="text-muted small">
              <?= e($engagement['patient_no']) ?> &middot;
              <?= $engagement['gender'] ? e($engagement['gender']) : '' ?> &middot;
              <?= $engagement['dob'] ? 'Age ' . age_from_dob($engagement['dob']) : '' ?>
              <?php if ($engagement['blood_group']): ?>
                &middot; <span class="badge text-bg-danger"><?= e($engagement['blood_group']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="ms-auto text-end">
            <div><?= status_badge($engagement['status']) ?></div>
            <div class="small text-muted mt-1"><?= fmt_date($engagement['start_date']) ?> to <?= $engagement['end_date'] ? fmt_date($engagement['end_date']) : 'Ongoing' ?></div>
          </div>
        </div>

        <div class="row g-2 border-top pt-3 small">
          <div class="col-sm-6">
            <span class="text-muted d-block">Delivery Model:</span>
            <strong>
              <?php if ($engagement['care_type'] === 'home'): ?>
                <i class="bi bi-house-door text-info me-1"></i> Home Care (Domiciliary)
              <?php else: ?>
                <i class="bi bi-hospital text-secondary me-1"></i> Bedside Care (Hospital)
              <?php endif; ?>
            </strong>
          </div>
          <div class="col-sm-6">
            <span class="text-muted d-block">Shift Package:</span>
            <strong>
              <?php
                $shiftMap = [
                  'day_8h' => 'Day Shift (8 Hours)',
                  'night_12h' => 'Night Shift (12 Hours)',
                  'full_24h' => '24-Hour Live-in Care Attendant',
                  'custom' => 'Custom Shift Hours'
                ];
                echo e($shiftMap[$engagement['shift_type']] ?? $engagement['shift_type']);
              ?>
            </strong>
          </div>

          <div class="col-12 mt-2">
            <span class="text-muted d-block">Location Details:</span>
            <?php if ($engagement['care_type'] === 'home'): ?>
              <div class="p-2 bg-light rounded border">
                <i class="bi bi-geo-alt text-danger me-1"></i> <?= nl2br(e($engagement['location_address'] ?: 'Patient home address on file')) ?>
              </div>
            <?php else: ?>
              <div class="p-2 bg-light rounded border">
                <i class="bi bi-buildings me-1"></i> <?= e($engagement['ward_name'] ?: 'General Ward') ?>
                <?= $engagement['bed_label'] ? ' &middot; Bed/Room: ' . e($engagement['bed_label']) : '' ?>
              </div>
            <?php endif; ?>
          </div>

          <?php if ($engagement['special_instructions']): ?>
          <div class="col-12 mt-2">
            <span class="text-muted d-block">Care Precautions &amp; Instructions:</span>
            <div class="p-2 bg-warning-subtle text-dark border border-warning rounded">
              <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
              <?= nl2br(e($engagement['special_instructions'])) ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($engagement['emergency_contact_name'] || $engagement['emergency_contact_phone']): ?>
          <div class="col-12 mt-2">
            <span class="text-muted d-block">Emergency Family Contact:</span>
            <div class="text-dark">
              <strong><?= e($engagement['emergency_contact_name'] ?: 'Contact') ?></strong>
              <?= $engagement['emergency_contact_phone'] ? ' &middot; ' . e($engagement['emergency_contact_phone']) : '' ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Column: Staff & Billing Summary -->
  <div class="col-lg-4">
    <!-- Caregiver Card -->
    <div class="card mb-3">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-person-badge me-1"></i> Primary Caregiver</span>
        <?php if (in_array(Auth::role(), ['admin', 'doctor', 'nurse', 'receptionist'])): ?>
          <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" data-bs-toggle="modal" data-bs-target="#assignCaregiverModal">
            <i class="bi bi-pencil"></i> Reassign
          </button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($engagement['caregiver_name']): ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="avatar" style="width:40px;height:40px;font-size:0.9rem;"><?= initials($engagement['caregiver_name']) ?></span>
            <div>
              <div class="fw-semibold"><?= e($engagement['caregiver_name']) ?></div>
              <div class="small text-muted"><?= e($engagement['caregiver_specialty'] ?: 'Registered Caregiver') ?></div>
            </div>
          </div>
          <div class="small text-muted">
            <?php if ($engagement['caregiver_phone']): ?>
              <div><i class="bi bi-telephone me-1"></i> <?= e($engagement['caregiver_phone']) ?></div>
            <?php endif; ?>
            <?php if ($engagement['caregiver_email']): ?>
              <div><i class="bi bi-envelope me-1"></i> <?= e($engagement['caregiver_email']) ?></div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-2 text-muted">
            <i class="bi bi-person-x fs-3 d-block mb-1 opacity-50"></i>
            <div>No caregiver assigned yet.</div>
            <?php if (in_array(Auth::role(), ['admin', 'doctor', 'nurse', 'receptionist'])): ?>
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#assignCaregiverModal">
                Assign Caregiver
              </button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Billing Summary Card -->
    <div class="card mb-3">
      <div class="card-header bg-light fw-semibold"><i class="bi bi-receipt me-1"></i> Billing &amp; Payment</div>
      <div class="card-body small">
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Rate / Shift:</span>
          <strong><?= money($engagement['rate_per_shift']) ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Duration:</span>
          <span><?= (int)$engagement['total_days'] ?> <?= (int)$engagement['total_days'] === 1 ? 'shift' : 'shifts' ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2 border-top pt-1">
          <span class="text-muted">Estimated Total:</span>
          <strong class="text-primary fs-6"><?= money($engagement['rate_per_shift'] * $engagement['total_days']) ?></strong>
        </div>

        <?php if ($engagement['invoice_id']): ?>
          <div class="p-2 border rounded bg-light mt-2">
            <div class="d-flex justify-content-between align-items-center">
              <span>Invoice <strong><?= e($engagement['invoice_no']) ?></strong></span>
              <?= status_badge($engagement['invoice_status']) ?>
            </div>
            <div class="d-flex justify-content-between text-muted mt-1">
              <span>Paid: <?= money($engagement['invoice_paid']) ?></span>
              <span>Total: <?= money($engagement['invoice_total']) ?></span>
            </div>
            <a href="<?= base_url('billing/show/' . $engagement['invoice_id']) ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
              <i class="bi bi-receipt"></i> View Invoice
            </a>
          </div>
        <?php else: ?>
          <div class="text-muted fst-italic mt-2">No invoice billed yet.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status Actions Card -->
    <?php if (in_array(Auth::role(), ['admin', 'doctor', 'nurse', 'receptionist'])): ?>
    <div class="card">
      <div class="card-header bg-light fw-semibold"><i class="bi bi-sliders me-1"></i> Status Control</div>
      <div class="card-body">
        <form method="post" action="<?= base_url('caregiving/status_update/' . $engagement['id']) ?>">
          <?= csrf_field() ?>
          <div class="input-group input-group-sm">
            <select class="form-select" name="status">
              <?php foreach (['pending', 'approved', 'active', 'completed', 'cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= $engagement['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary" type="submit">Update</button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Tabs: Shift Schedule & ADL Care Logs -->
<ul class="nav nav-tabs" id="careTabs" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-shifts" type="button">
      <i class="bi bi-calendar-check me-1"></i> Shift Schedule (<?= count($shiftLogs) ?>)
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-logs" type="button">
      <i class="bi bi-journal-medical me-1"></i> Care Reports &amp; Vitals
    </button>
  </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3 bg-white mb-4 shadow-sm">
  <!-- TAB 1: Shifts -->
  <div class="tab-pane fade show active" id="tab-shifts">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0 fw-semibold text-secondary">Scheduled &amp; Past Care Shifts</h6>
      <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleShiftModal">
        <i class="bi bi-plus-lg"></i> Schedule Shift
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Times</th>
            <th>Caregiver</th>
            <th>Status</th>
            <th>Vitals &amp; ADL Logged</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$shiftLogs): ?>
            <?= empty_row(6, 'No shifts scheduled yet. Click "Schedule Shift" to add one.') ?>
          <?php else: foreach ($shiftLogs as $sl): ?>
            <tr id="shift-<?= (int)$sl['id'] ?>">
              <td class="fw-semibold"><?= fmt_date($sl['shift_date']) ?></td>
              <td class="small">
                <?= $sl['shift_start'] ? fmt_time($sl['shift_start']) : 'Start TBD' ?>
                <?= $sl['shift_end'] ? ' - ' . fmt_time($sl['shift_end']) : '' ?>
              </td>
              <td>
                <span class="badge text-bg-light border text-dark">
                  <i class="bi bi-person me-1"></i><?= e($sl['caregiver_name'] ?: 'Unassigned') ?>
                </span>
              </td>
              <td><?= status_badge($sl['status']) ?></td>
              <td class="small">
                <?php if ($sl['vitals_summary'] || $sl['feeding_notes'] || $sl['medication_administered']): ?>
                  <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Report recorded</span>
                  <?php if ($sl['vitals_summary']): ?>
                    <div class="text-muted"><?= e($sl['vitals_summary']) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted fst-italic">Pending report</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-primary btn-log-shift" 
                        data-bs-toggle="modal" data-bs-target="#logShiftModal"
                        data-id="<?= (int)$sl['id'] ?>"
                        data-date="<?= e(fmt_date($sl['shift_date'])) ?>"
                        data-vitals="<?= e($sl['vitals_summary'] ?: '') ?>"
                        data-feeding="<?= e($sl['feeding_notes'] ?: '') ?>"
                        data-mobility="<?= e($sl['mobility_notes'] ?: '') ?>"
                        data-hygiene="<?= e($sl['hygiene_notes'] ?: '') ?>"
                        data-meds="<?= e($sl['medication_administered'] ?: '') ?>"
                        data-general="<?= e($sl['general_notes'] ?: '') ?>"
                        data-status="<?= e($sl['status']) ?>">
                  <i class="bi bi-clipboard2-pulse"></i> <?= $sl['status'] === 'completed' ? 'Edit Log' : 'Log Shift' ?>
                </button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- TAB 2: ADL Care Logs Timeline -->
  <div class="tab-pane fade" id="tab-logs">
    <h6 class="fw-semibold text-secondary mb-3">Daily Living Activity (ADL) Care Reports</h6>

    <?php 
      $completedLogs = array_filter($shiftLogs, function($l) { 
        return !empty($l['vitals_summary']) || !empty($l['feeding_notes']) || !empty($l['hygiene_notes']) || !empty($l['medication_administered']) || !empty($l['general_notes']); 
      }); 
    ?>

    <?php if (!$completedLogs): ?>
      <?= empty_block('No care shift reports documented yet. Caregivers can document reports by clicking "Log Shift" on any scheduled shift.') ?>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($completedLogs as $cl): ?>
          <div class="col-12">
            <div class="card border">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                  <span class="fw-semibold text-primary"><i class="bi bi-calendar3 me-1"></i> Shift: <?= fmt_date($cl['shift_date']) ?></span>
                  <span class="text-muted ms-2">(Caregiver: <?= e($cl['caregiver_name']) ?>)</span>
                </div>
                <?= status_badge($cl['status']) ?>
              </div>
              <div class="card-body">
                <div class="row g-3 small">
                  <?php if ($cl['vitals_summary']): ?>
                  <div class="col-md-6">
                    <span class="fw-semibold text-danger"><i class="bi bi-heart-pulse me-1"></i> Vitals Observed:</span>
                    <div class="p-2 bg-light rounded mt-1"><?= nl2br(e($cl['vitals_summary'])) ?></div>
                  </div>
                  <?php endif; ?>

                  <?php if ($cl['medication_administered']): ?>
                  <div class="col-md-6">
                    <span class="fw-semibold text-primary"><i class="bi bi-capsule me-1"></i> Medication Administered:</span>
                    <div class="p-2 bg-light rounded mt-1"><?= nl2br(e($cl['medication_administered'])) ?></div>
                  </div>
                  <?php endif; ?>

                  <?php if ($cl['feeding_notes']): ?>
                  <div class="col-md-6">
                    <span class="fw-semibold text-success"><i class="bi bi-cup-hot me-1"></i> Nutrition &amp; Feeding:</span>
                    <div class="p-2 bg-light rounded mt-1"><?= nl2br(e($cl['feeding_notes'])) ?></div>
                  </div>
                  <?php endif; ?>

                  <?php if ($cl['hygiene_notes']): ?>
                  <div class="col-md-6">
                    <span class="fw-semibold text-info"><i class="bi bi-droplet me-1"></i> Personal Hygiene &amp; Grooming:</span>
                    <div class="p-2 bg-light rounded mt-1"><?= nl2br(e($cl['hygiene_notes'])) ?></div>
                  </div>
                  <?php endif; ?>

                  <?php if ($cl['mobility_notes']): ?>
                  <div class="col-md-6">
                    <span class="fw-semibold text-secondary"><i class="bi bi-person-walking me-1"></i> Mobility &amp; Repositioning:</span>
                    <div class="p-2 bg-light rounded mt-1"><?= nl2br(e($cl['mobility_notes'])) ?></div>
                  </div>
                  <?php endif; ?>

                  <?php if ($cl['general_notes']): ?>
                  <div class="col-md-12">
                    <span class="fw-semibold text-dark"><i class="bi bi-chat-left-text me-1"></i> General Observations &amp; Handover Notes:</span>
                    <div class="p-2 bg-light rounded mt-1"><?= nl2br(e($cl['general_notes'])) ?></div>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal 1: Schedule Shift -->
<div class="modal fade" id="scheduleShiftModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= base_url('caregiving/shift_schedule/' . $engagement['id']) ?>">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Schedule Care Shift</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label required" for="sDate">Shift Date</label>
            <input class="form-control" type="date" id="sDate" name="shift_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label" for="sStart">Start Time</label>
              <input class="form-control" type="time" id="sStart" name="shift_start" value="08:00">
            </div>
            <div class="col-6">
              <label class="form-label" for="sEnd">End Time</label>
              <input class="form-control" type="time" id="sEnd" name="shift_end" value="16:00">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label required" for="sCg">Assign Caregiver</label>
            <select class="form-select" id="sCg" name="caregiver_id" required>
              <?php foreach ($caregivers as $cg): ?>
                <option value="<?= (int)$cg['id'] ?>" <?= (int)$engagement['primary_caregiver_id'] === (int)$cg['id'] ? 'selected' : '' ?>>
                  <?= e($cg['full_name']) ?> (<?= e($cg['specialty'] ?: 'Caregiver') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Schedule Shift</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 2: Reassign Caregiver -->
<div class="modal fade" id="assignCaregiverModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= base_url('caregiving/assign_caregiver/' . $engagement['id']) ?>">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Assign Primary Caregiver</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label required" for="reassignCg">Select Caregiver</label>
            <select class="form-select" id="reassignCg" name="caregiver_id" required>
              <option value="">Choose caregiver...</option>
              <?php foreach ($caregivers as $cg): ?>
                <option value="<?= (int)$cg['id'] ?>" <?= (int)$engagement['primary_caregiver_id'] === (int)$cg['id'] ? 'selected' : '' ?>>
                  <?= e($cg['full_name']) ?> (<?= e($cg['specialty'] ?: 'Caregiver') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Assignment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 3: Log Shift Report -->
<div class="modal fade" id="logShiftModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="" id="logShiftForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title" id="logModalTitle">Document Caregiver Shift Report</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="lVitals"><i class="bi bi-heart-pulse text-danger me-1"></i> Vitals Recorded</label>
              <input class="form-control form-control-sm" id="lVitals" name="vitals_summary" placeholder="e.g. BP: 120/80, Temp: 36.8 C, Pulse: 72 bpm, SpO2: 98%">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="lStatus">Shift Status</label>
              <select class="form-select form-select-sm" id="lStatus" name="status">
                <option value="completed">Completed</option>
                <option value="in_progress">In Progress</option>
                <option value="missed">Missed / Cancelled</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="lFeeding"><i class="bi bi-cup-hot text-success me-1"></i> Nutrition &amp; Feeding</label>
              <textarea class="form-control form-control-sm" id="lFeeding" name="feeding_notes" rows="2" placeholder="Meals consumed, water intake, assisted feeding notes..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="lHygiene"><i class="bi bi-droplet text-info me-1"></i> Hygiene &amp; Personal Care</label>
              <textarea class="form-control form-control-sm" id="lHygiene" name="hygiene_notes" rows="2" placeholder="Bed bath / shower, grooming, oral care, clothes changed..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="lMobility"><i class="bi bi-person-walking text-secondary me-1"></i> Mobility &amp; Repositioning</label>
              <textarea class="form-control form-control-sm" id="lMobility" name="mobility_notes" rows="2" placeholder="Assisted walking, wheelchair transfers, bed turning every 2h..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="lMeds"><i class="bi bi-capsule text-primary me-1"></i> Medication Administration</label>
              <textarea class="form-control form-control-sm" id="lMeds" name="medication_administered" rows="2" placeholder="Medications taken on schedule, dosage, time taken..."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label" for="lGeneral"><i class="bi bi-chat-left-text text-dark me-1"></i> General Observations &amp; Handover</label>
              <textarea class="form-control form-control-sm" id="lGeneral" name="general_notes" rows="2" placeholder="Mood, complaints, sleep quality, notes for next caregiver/nurse..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Save Report</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var logModal = document.getElementById('logShiftModal');
  var logForm = document.getElementById('logShiftForm');

  document.querySelectorAll('.btn-log-shift').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-id');
      var date = btn.getAttribute('data-date');
      logForm.action = '<?= base_url('caregiving/shift_log_save/') ?>' + id;
      document.getElementById('logModalTitle').textContent = 'Document Caregiver Report - ' + date;
      document.getElementById('lVitals').value = btn.getAttribute('data-vitals') || '';
      document.getElementById('lFeeding').value = btn.getAttribute('data-feeding') || '';
      document.getElementById('lMobility').value = btn.getAttribute('data-mobility') || '';
      document.getElementById('lHygiene').value = btn.getAttribute('data-hygiene') || '';
      document.getElementById('lMeds').value = btn.getAttribute('data-meds') || '';
      document.getElementById('lGeneral').value = btn.getAttribute('data-general') || '';
      document.getElementById('lStatus').value = btn.getAttribute('data-status') || 'completed';
    });
  });
});
</script>
