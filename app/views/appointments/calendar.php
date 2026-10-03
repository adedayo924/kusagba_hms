<?php
page_header('Appointment calendar', date('F Y', mktime(0, 0, 0, $month, 1, $year)));

$prevMonth = $month === 1 ? 12 : $month - 1;
$prevYear  = $month === 1 ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1 : $month + 1;
$nextYear  = $month === 12 ? $year + 1 : $year;
$dowNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$today = date('Y-m-d');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('appointments/calendar?month=' . $prevMonth . '&year=' . $prevYear) ?>"><i class="bi bi-chevron-left"></i></a>
    <a class="btn btn-outline-secondary btn-sm ms-1" href="<?= base_url('appointments/calendar?month=' . $nextMonth . '&year=' . $nextYear) ?>"><i class="bi bi-chevron-right"></i></a>
    <a class="btn btn-outline-primary btn-sm ms-2" href="<?= base_url('appointments/calendar') ?>">Today</a>
  </div>
  <a class="btn btn-primary btn-sm" href="<?= base_url('appointments/create') ?>"><i class="bi bi-calendar-plus"></i> New</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-bordered text-center mb-0">
      <thead class="table-light">
        <tr><?php foreach ($dowNames as $d): ?><th class="py-2"><?= $d ?></th><?php endforeach; ?></tr>
      </thead>
      <tbody>
      <?php
      $cells = array_fill(0, $startDow, ['empty' => true]);
      for ($d = 1; $d <= $daysInMonth; $d++) {
          $ds = date('Y-m-d', mktime(0, 0, 0, $month, $d, $year));
          $cells[] = ['day' => $d, 'date' => $ds, 'count' => ($counts[$ds] ?? 0), 'today' => $ds === $today];
      }
      while (count($cells) % 7 !== 0) {
          $cells[] = ['empty' => true];
      }
      $chunks = array_chunk($cells, 7);
      foreach ($chunks as $week):
      ?>
        <tr>
          <?php foreach ($week as $c): ?>
            <?php if (!empty($c['empty'])): ?>
              <td class="bg-light"></td>
            <?php else: ?>
              <td class="<?= !empty($c['today']) ? 'table-primary' : '' ?>" style="height:92px;vertical-align:top;width:14.28%">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-semibold <?= !empty($c['today']) ? 'text-primary' : '' ?>"><?= $c['day'] ?></span>
                  <?php if ($c['count']): ?>
                    <a href="<?= base_url('appointments?date=' . $c['date']) ?>" class="badge text-bg-success text-decoration-none"><?= $c['count'] ?></a>
                  <?php endif; ?>
                </div>
                <a href="<?= base_url('appointments/create?date=' . $c['date']) ?>" class="d-inline-block mt-2"><i class="bi bi-plus-circle text-muted"></i></a>
              </td>
            <?php endif; ?>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="text-muted small mt-3 mb-0">Click the green badge to see that day's appointments, or the + to add one.</p>