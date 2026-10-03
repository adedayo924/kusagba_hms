<?php page_header('Dashboard', "Overview of today's activity and key figures"); ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= number_format($stats['patients']) ?></div>
          <div class="text-muted small">Total patients</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-calendar-check"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= number_format($stats['appointments']) ?></div>
          <div class="text-muted small">Today's appointments</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-activity"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= number_format($stats['consultations']) ?></div>
          <div class="text-muted small">Consultations today</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-hospital"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= number_format($stats['inpatients']) ?></div>
          <div class="text-muted small">Currently admitted</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= money($stats['revenue']) ?></div>
          <div class="text-muted small">Revenue today</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-wallet2"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= money($stats['outstanding']) ?></div>
          <div class="text-muted small">Outstanding balance</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-secondary-subtle text-secondary"><i class="bi bi-box-seam"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= number_format($stats['lowstock']) ?></div>
          <div class="text-muted small">Low-stock items</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-eyedropper"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= number_format($stats['pendinglab']) ?></div>
          <div class="text-muted small">Pending lab requests</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header">Last 7 days &mdash; Revenue &amp; appointments</div>
      <div class="card-body">
        <canvas id="trendChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">Bed occupancy</div>
      <div class="card-body">
        <?php if (!$bedStats): ?>
          <p class="text-muted small mb-0">No wards configured yet.</p>
        <?php else: ?>
          <?php foreach ($bedStats as $b): $pct = $b['total_beds'] ? round(($b['occupied'] / $b['total_beds']) * 100) : 0; ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between small">
                <span class="fw-semibold"><?= e($b['name']) ?></span>
                <span class="text-muted"><?= (int)$b['occupied'] ?> / <?= (int)$b['total_beds'] ?></span>
              </div>
              <div class="progress" style="height:8px">
                <div class="progress-bar <?= $pct >= 80 ? 'bg-danger' : ($pct >= 50 ? 'bg-warning' : 'bg-success') ?>"
                     style="width:<?= $pct ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Today's appointments</span>
        <a href="<?= base_url('appointments') ?>" class="btn btn-sm btn-outline-secondary">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (!$todayAppointments): ?>
            <tr><td colspan="4" class="text-muted text-center py-3">No appointments today.</td></tr>
          <?php else: foreach ($todayAppointments as $a): ?>
            <tr>
              <td><?= fmt_time($a['appointment_time']) ?></td>
              <td class="fw-semibold"><?= e($a['patient_name']) ?></td>
              <td><?= e($a['doctor_name'] ?: '—') ?></td>
              <td><?= status_badge($a['status']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recently registered patients</span>
        <a href="<?= base_url('patients') ?>" class="btn btn-sm btn-outline-secondary">All patients</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Patient</th><th>No</th><th>Age</th><th>Gender</th></tr></thead>
          <tbody>
          <?php if (!$recentPatients): ?>
            <tr><td colspan="4" class="text-muted text-center py-3">No patients yet.</td></tr>
          <?php else: foreach ($recentPatients as $p): ?>
            <tr>
              <td class="fw-semibold"><?= e(patient_full_name($p)) ?></td>
              <td class="text-muted small"><?= e($p['patient_no']) ?></td>
              <td><?= age_from_dob($p['dob']) ?></td>
              <td><?= e($p['gender']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php $scripts = '
<script>
  new Chart(document.getElementById("trendChart"), {
    type: "line",
    data: {
      labels: ' . json_encode($labels) . ',
      datasets: [
        { label: "Revenue", data: ' . json_encode($revenue) . ',
          borderColor: "#0d9488", backgroundColor: "rgba(13,148,136,.15)",
          fill: true, tension: .35, yAxisID: "y" },
        { label: "Appointments", data: ' . json_encode($appoint) . ',
          borderColor: "#6366f1", backgroundColor: "rgba(99,102,241,.12)",
          fill: true, tension: .35, yAxisID: "y1" }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: "index", intersect: false },
      plugins: { legend: { position: "bottom" } },
      scales: {
        y: { beginAtZero: true, position: "left", title: { display: true, text: "Amount" } },
        y1: { beginAtZero: true, position: "right", grid: { drawOnChartArea: false }, title: { display: true, text: "Count" } }
      }
    }
  });
</script>';