<?php
$outstanding = (float)fetch_val('SELECT IFNULL(SUM(total - paid_amount),0) FROM invoices WHERE patient_id = ? AND status NOT IN ("void","paid")', [$patient['id']]);
$reqBtn = '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestCareModal"><i class="bi bi-heart-pulse me-1"></i> Request Caregiver</button>';
page_header('Welcome, ' . patient_full_name($patient), 'Patient portal', $reqBtn);
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center py-3">
        <div class="text-muted small">Patient no.</div>
        <div class="h5 mb-0 fw-bold"><?= e($patient['patient_no']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center py-3">
        <div class="text-muted small">Age / Gender</div>
        <div class="h5 mb-0 fw-bold"><?= $patient['dob'] ? age_from_dob($patient['dob']) . ' yrs' : '—' ?> · <?= e($patient['gender']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center py-3">
        <div class="text-muted small">Blood group</div>
        <div class="h5 mb-0 fw-bold"><?= e($patient['blood_group'] ?: '—') ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card">
      <div class="card-body text-center py-3">
        <div class="text-muted small">Outstanding balance</div>
        <div class="h5 mb-0 fw-bold <?= $outstanding > 0 ? 'text-danger' : 'text-success' ?>"><?= money($outstanding) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Caregiving Services Feature Section -->
<div class="card mb-4 border-primary-subtle shadow-sm">
  <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-primary"><i class="bi bi-heart-pulse me-1"></i> Caregiving Services &amp; Home Care</span>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#requestCareModal">
      <i class="bi bi-plus-lg me-1"></i> Request Caregiver
    </button>
  </div>
  <div class="card-body">
    <?php if (empty($careEngagements)): ?>
      <div class="text-center py-3 text-muted">
        <i class="bi bi-heart-pulse fs-3 d-block mb-1 opacity-50"></i>
        <p class="mb-2">Need home health assistance, recovery support, or dedicated hospital bedside care?</p>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestCareModal">
          <i class="bi bi-plus-lg me-1"></i> Book a Certified Caregiver
        </button>
      </div>
    <?php else: ?>
      <div class="table-responsive mb-3">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Request #</th>
              <th>Service Type</th>
              <th>Shift Package</th>
              <th>Dates</th>
              <th>Assigned Caregiver</th>
              <th>Status</th>
              <th>Completed Shifts</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($careEngagements as $cg): ?>
              <tr>
                <td class="fw-semibold"><?= e($cg['request_no']) ?></td>
                <td>
                  <?php if ($cg['care_type'] === 'home'): ?>
                    <span class="badge text-bg-info"><i class="bi bi-house-door"></i> Home Care</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary"><i class="bi bi-hospital"></i> Bedside Care</span>
                  <?php endif; ?>
                </td>
                <td class="small">
                  <?php
                    $sMap = ['day_8h' => 'Day Shift (8h)', 'night_12h' => 'Night Shift (12h)', 'full_24h' => '24h Live-in', 'custom' => 'Custom'];
                    echo e($sMap[$cg['shift_type']] ?? $cg['shift_type']);
                  ?>
                </td>
                <td class="small"><?= fmt_date($cg['start_date']) ?> <?= $cg['end_date'] ? '&rarr; ' . fmt_date($cg['end_date']) : '' ?></td>
                <td class="small">
                  <?php if ($cg['caregiver_name']): ?>
                    <strong><?= e($cg['caregiver_name']) ?></strong>
                    <?php if ($cg['caregiver_phone']): ?>
                      <div class="text-muted small"><i class="bi bi-telephone"></i> <?= e($cg['caregiver_phone']) ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted fst-italic">Pending assignment</span>
                  <?php endif; ?>
                </td>
                <td><?= status_badge($cg['status']) ?></td>
                <td class="small fw-semibold"><?= (int)$cg['completed_shifts'] ?> shifts recorded</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($careLogs)): ?>
        <h6 class="small fw-semibold text-uppercase text-muted border-top pt-3 mb-2">Recent Caregiver Shift Reports:</h6>
        <div class="row g-2">
          <?php foreach (array_slice($careLogs, 0, 3) as $cl): ?>
            <div class="col-md-4">
              <div class="p-2 border rounded bg-light small h-100">
                <div class="d-flex justify-content-between text-muted mb-1">
                  <span><?= fmt_date($cl['shift_date']) ?></span>
                  <strong><?= e($cl['caregiver_name']) ?></strong>
                </div>
                <?php if ($cl['vitals_summary']): ?>
                  <div><span class="fw-semibold text-danger">Vitals:</span> <?= e($cl['vitals_summary']) ?></div>
                <?php endif; ?>
                <?php if ($cl['medication_administered']): ?>
                  <div><span class="fw-semibold text-primary">Meds:</span> <?= e($cl['medication_administered']) ?></div>
                <?php endif; ?>
                <?php if ($cl['feeding_notes']): ?>
                  <div><span class="fw-semibold text-success">Meals:</span> <?= e($cl['feeding_notes']) ?></div>
                <?php endif; ?>
                <?php if ($cl['general_notes']): ?>
                  <div class="text-muted fst-italic mt-1">&ldquo;<?= mb_strimwidth(e($cl['general_notes']), 0, 70, '…') ?>&rdquo;</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header">Upcoming appointments</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$appointments): ?><tr><td class="text-center text-muted py-3">No appointments yet.</td></tr>
          <?php else: foreach ($appointments as $a): ?>
            <tr>
              <td class="fw-semibold"><?= fmt_date($a['appointment_date']) ?></td>
              <td><?= fmt_time($a['appointment_time']) ?></td>
              <td class="small text-muted"><?= e($a['doctor_name'] ?? '—') ?></td>
              <td><?= status_badge($a['status']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card h-100 mt-3"><div class="card-header">Visit history</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$visits): ?><tr><td class="text-center text-muted py-3">No consultations recorded.</td></tr>
          <?php else: foreach ($visits as $v): ?>
            <tr>
              <td class="fw-semibold"><?= fmt_date($v['visit_date']) ?></td>
              <td><?= e(ucfirst($v['visit_type'])) ?></td>
              <td class="small text-muted"><?= e($v['doctor_name'] ?? '—') ?></td>
              <td class="small text-muted"><?= e($v['diagnosis'] ? mb_strimwidth($v['diagnosis'], 0, 40, '…') : '—') ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header">My prescriptions</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$prescriptions): ?><tr><td class="text-center text-muted py-3">No prescriptions.</td></tr>
          <?php else: foreach ($prescriptions as $rx): ?>
            <tr>
              <td class="fw-semibold"><?= fmt_dt($rx['prescribed_at'], 'M j, Y') ?></td>
              <td class="small text-muted"><?= e($rx['doctor_name'] ?? '—') ?></td>
              <td><?= status_badge($rx['status']) ?></td>
              <td class="text-end text-muted small"><?= (int)fetch_val('SELECT COUNT(*) FROM prescription_items WHERE prescription_id = ?', [$rx['id']]) ?> drug(s)</td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card h-100 mt-3"><div class="card-header">Lab requests</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$lab): ?><tr><td class="text-center text-muted py-3">No lab requests.</td></tr>
          <?php else: foreach ($lab as $l): ?>
            <tr>
              <td class="fw-semibold"><?= fmt_dt($l['requested_at'], 'M j, Y') ?></td>
              <td><?= status_badge($l['status']) ?></td>
              <td class="text-end text-muted small"><?= (int)fetch_val('SELECT COUNT(*) FROM lab_request_tests WHERE lab_request_id = ?', [$l['id']]) ?> test(s)</td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card h-100 mt-3"><div class="card-header">Invoices</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <tbody>
          <?php if (!$invoices): ?><tr><td class="text-center text-muted py-3">No invoices yet.</td></tr>
          <?php else: foreach ($invoices as $i): ?>
            <tr>
              <td class="fw-semibold"><?= e($i['invoice_no']) ?></td>
              <td><?= fmt_date($i['invoice_date']) ?></td>
              <td class="text-end"><?= money($i['total']) ?></td>
              <td><?= status_badge($i['status']) ?></td>
              <td class="text-end"><a class="btn btn-sm btn-light" href="<?= base_url('billing/receipt/' . $i['id']) ?>" target="_blank">Receipt</a></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Request Caregiver -->
<div class="modal fade" id="requestCareModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="<?= base_url('portal/care_request') ?>">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-heart-pulse text-primary me-2"></i>Request Caregiving Services</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label required">Delivery Location</label>
              <div class="d-flex gap-3 mt-1">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="care_type" id="pTypeHome" value="home" checked>
                  <label class="form-check-label" for="pTypeHome">Home Visit (Domiciliary)</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="care_type" id="pTypeBedside" value="bedside">
                  <label class="form-check-label" for="pTypeBedside">In-Hospital Bedside Care</label>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label required" for="pShift">Shift Package</label>
              <select class="form-select" id="pShift" name="shift_type" required>
                <option value="day_8h">Day Shift (8 Hours &middot; 08:00 - 16:00)</option>
                <option value="night_12h">Night Shift (12 Hours &middot; 20:00 - 08:00)</option>
                <option value="full_24h">24-Hour Live-in Care Attendant</option>
                <option value="custom">Custom Hours</option>
              </select>
            </div>

            <?php if (!empty($carePackages)): ?>
            <div class="col-md-12">
              <label class="form-label" for="pPkg">Standard Package Rate</label>
              <select class="form-select" id="pPkg" name="service_id">
                <option value="">Select standard package...</option>
                <?php foreach ($carePackages as $pkg): ?>
                  <option value="<?= (int)$pkg['id'] ?>"><?= e($pkg['name']) ?> &mdash; <?= money($pkg['price']) ?> / shift</option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>

            <div class="col-md-6">
              <label class="form-label required" for="pStart">Preferred Start Date</label>
              <input class="form-control" type="date" id="pStart" name="start_date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label required" for="pDays">Duration (Days)</label>
              <input class="form-control" type="number" id="pDays" name="total_days" min="1" max="90" value="7" required>
            </div>

            <div class="col-12" id="pAddressWrap">
              <label class="form-label" for="pAddress">Home Address &amp; Landmarks</label>
              <textarea class="form-control" id="pAddress" name="location_address" rows="2" placeholder="Residential address, estate or gate instructions, landmark..."><?= e($patient['address'] ?: '') ?></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="pEmergName">Emergency Family Contact</label>
              <input class="form-control" id="pEmergName" name="emergency_contact_name" value="<?= e($patient['next_of_kin_name'] ?: '') ?>" placeholder="Name of relative">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="pEmergPhone">Emergency Contact Phone</label>
              <input class="form-control" id="pEmergPhone" name="emergency_contact_phone" value="<?= e($patient['next_of_kin_phone'] ?: '') ?>" placeholder="Phone number">
            </div>

            <div class="col-12">
              <label class="form-label" for="pNotes">Care Needs &amp; Health Conditions</label>
              <textarea class="form-control" id="pNotes" name="special_instructions" rows="2" placeholder="Briefly describe the patient's care needs (e.g. elderly assistance, post-surgery recovery, mobility support, medication management)..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Submit Care Request</button>
        </div>
      </form>
    </div>
  </div>
</div>