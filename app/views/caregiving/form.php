<?php
page_header('New Caregiving Engagement', 'Schedule home-based care or in-hospital bedside attendant services');
?>

<form method="post" action="<?= base_url('caregiving/store') ?>" id="caregivingForm">
  <?= csrf_field() ?>

  <div class="row g-4">
    <!-- Left Column: Patient & Service Details -->
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header bg-light fw-semibold"><i class="bi bi-person me-1"></i> Patient Information</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label required" for="cgPatient">Patient</label>
            <?php if ($patient): ?>
              <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
              <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                <div>
                  <strong><?= e(patient_full_name($patient)) ?></strong>
                  <span class="text-muted ms-2">(<?= e($patient['patient_no']) ?>)</span>
                  <div class="small text-muted"><?= e($patient['phone'] ?: '') ?> <?= $patient['blood_group'] ? '&middot; Blood: ' . e($patient['blood_group']) : '' ?></div>
                </div>
                <a href="<?= base_url('caregiving/create') ?>" class="btn btn-sm btn-outline-secondary">Change patient</a>
              </div>
            <?php else: ?>
              <select class="form-select" id="cgPatient" name="patient_id" required>
                <option value="">Select patient...</option>
                <?php foreach ($patients as $p): ?>
                  <option value="<?= (int)$p['id'] ?>">
                    <?= e($p['last_name'] . ' ' . $p['first_name']) ?> (<?= e($p['patient_no']) ?>) - <?= e($p['phone'] ?: 'No phone') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label required">Delivery Model</label>
              <div class="d-flex gap-3 mt-1">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="care_type" id="typeHome" value="home" checked>
                  <label class="form-check-label" for="typeHome">
                    <i class="bi bi-house-door text-info me-1"></i> <strong>Home Care</strong> (Domiciliary)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="care_type" id="typeBedside" value="bedside">
                  <label class="form-check-label" for="typeBedside">
                    <i class="bi bi-hospital text-secondary me-1"></i> <strong>Bedside Care</strong> (Hospital)
                  </label>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label required" for="cgShift">Shift Package</label>
              <select class="form-select" id="cgShift" name="shift_type" required>
                <option value="day_8h">Day Shift (8 Hours &middot; 08:00 - 16:00)</option>
                <option value="night_12h">Night Shift (12 Hours &middot; 20:00 - 08:00)</option>
                <option value="full_24h">24-Hour Live-in Care Attendant</option>
                <option value="custom">Custom Shift Hours</option>
              </select>
            </div>
          </div>

          <!-- Location Fields -->
          <div class="mt-3" id="homeFields">
            <label class="form-label required" for="cgAddress">Home Address &amp; Landmarks</label>
            <textarea class="form-control" id="cgAddress" name="location_address" rows="2" placeholder="Street address, apartment/estate, nearest landmark, instructions for caregiver..."><?= $patient ? e($patient['address'] ?: '') : '' ?></textarea>
          </div>

          <div class="row g-3 mt-1 d-none" id="bedsideFields">
            <div class="col-md-6">
              <label class="form-label required" for="cgWard">Hospital Ward</label>
              <select class="form-select" id="cgWard" name="ward_id">
                <option value="">Select ward...</option>
                <?php foreach ($wards as $w): ?>
                  <option value="<?= (int)$w['id'] ?>"><?= e($w['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="cgBed">Bed Label / Room No.</label>
              <input class="form-control" id="cgBed" name="bed_label" placeholder="e.g. Bed 04, Room 2B">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header bg-light fw-semibold"><i class="bi bi-clipboard2-pulse me-1"></i> Care Plan &amp; Clinical Precautions</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="cgInstructions">Special Instructions &amp; Care Requirements</label>
            <textarea class="form-control" id="cgInstructions" name="special_instructions" rows="3" placeholder="Mobility assistance, feeding preferences, diabetic diet, fall risk alerts, medication reminders, hygiene assistance..."></textarea>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="cgEmergName">Emergency Contact Name</label>
              <input class="form-control" id="cgEmergName" name="emergency_contact_name" value="<?= $patient ? e($patient['next_of_kin_name'] ?: '') : '' ?>" placeholder="Full name of family member">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="cgEmergPhone">Emergency Contact Phone</label>
              <input class="form-control" id="cgEmergPhone" name="emergency_contact_phone" value="<?= $patient ? e($patient['next_of_kin_phone'] ?: '') : '' ?>" placeholder="Phone number">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Staff, Schedule & Billing -->
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header bg-light fw-semibold"><i class="bi bi-person-badge me-1"></i> Caregiver Assignment</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="cgStaff">Primary Caregiver</label>
            <select class="form-select" id="cgStaff" name="primary_caregiver_id">
              <option value="">Assign later (Leave pending)</option>
              <?php foreach ($caregivers as $cg): ?>
                <option value="<?= (int)$cg['id'] ?>">
                  <?= e($cg['full_name']) ?> (<?= ucfirst($cg['role']) ?><?= $cg['specialty'] ? ' &middot; ' . e($cg['specialty']) : '' ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text small">You can also assign or change caregivers after saving.</div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header bg-light fw-semibold"><i class="bi bi-calendar3 me-1"></i> Schedule &amp; Dates</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label required" for="cgStart">Start Date</label>
            <input class="form-control" type="date" id="cgStart" name="start_date" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="cgEnd">End Date</label>
            <input class="form-control" type="date" id="cgEnd" name="end_date" value="<?= date('Y-m-d', strtotime('+6 days')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label required" for="cgDays">Total Days</label>
            <input class="form-control" type="number" id="cgDays" name="total_days" min="1" value="7" required>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header bg-light fw-semibold"><i class="bi bi-receipt me-1"></i> Billing &amp; Pricing</div>
        <div class="card-body">
          <?php if (!empty($services)): ?>
          <div class="mb-3">
            <label class="form-label" for="cgServicePkg">Standard Service Package</label>
            <select class="form-select" id="cgServicePkg" name="service_id">
              <option value="">Custom pricing</option>
              <?php foreach ($services as $srv): ?>
                <option value="<?= (int)$srv['id'] ?>" data-price="<?= (float)$srv['price'] ?>">
                  <?= e($srv['name']) ?> (<?= money($srv['price']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label required" for="cgRate">Rate per Shift (<?= app_setting('currency', 'NGN') ?>)</label>
            <input class="form-control" type="number" step="0.01" id="cgRate" name="rate_per_shift" value="12000.00" required>
          </div>

          <div class="p-2 bg-light rounded border mb-3">
            <div class="d-flex justify-content-between small text-muted">
              <span>Estimated Total:</span>
              <strong class="text-dark" id="estTotal"><?= money(84000) ?></strong>
            </div>
          </div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="auto_bill" id="cgAutoBill" value="1" checked>
            <label class="form-check-label small" for="cgAutoBill">
              Automatically add charge to patient's active hospital invoice
            </label>
          </div>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2-circle me-1"></i> Save Engagement</button>
        <a href="<?= base_url('caregiving') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </div>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var typeHome = document.getElementById('typeHome');
  var typeBedside = document.getElementById('typeBedside');
  var homeFields = document.getElementById('homeFields');
  var bedsideFields = document.getElementById('bedsideFields');

  function toggleLocation() {
    if (typeHome.checked) {
      homeFields.classList.remove('d-none');
      bedsideFields.classList.add('d-none');
    } else {
      homeFields.classList.add('d-none');
      bedsideFields.classList.remove('d-none');
    }
  }

  typeHome.addEventListener('change', toggleLocation);
  typeBedside.addEventListener('change', toggleLocation);

  // Auto rate calculation from package select
  var pkgSelect = document.getElementById('cgServicePkg');
  var rateInput = document.getElementById('cgRate');
  var daysInput = document.getElementById('cgDays');
  var estTotal = document.getElementById('estTotal');
  var startInput = document.getElementById('cgStart');
  var endInput = document.getElementById('cgEnd');

  if (pkgSelect) {
    pkgSelect.addEventListener('change', function () {
      var opt = pkgSelect.options[pkgSelect.selectedIndex];
      var price = opt.getAttribute('data-price');
      if (price) {
        rateInput.value = parseFloat(price).toFixed(2);
        recalcEst();
      }
    });
  }

  function recalcDays() {
    var s = new Date(startInput.value);
    var e = new Date(endInput.value);
    if (!isNaN(s) && !isNaN(e) && e >= s) {
      var diffTime = Math.abs(e - s);
      var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
      daysInput.value = diffDays;
    }
    recalcEst();
  }

  function recalcEst() {
    var rate = parseFloat(rateInput.value) || 0;
    var days = parseInt(daysInput.value) || 1;
    var total = rate * days;
    if (estTotal) {
      estTotal.textContent = total.toLocaleString(undefined, { minimumFractionDigits: 2 });
    }
  }

  startInput.addEventListener('change', recalcDays);
  endInput.addEventListener('change', recalcDays);
  daysInput.addEventListener('input', recalcEst);
  rateInput.addEventListener('input', recalcEst);
  recalcEst();
});
</script>
