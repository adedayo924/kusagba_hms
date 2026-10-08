<?php
/** Main admin layout. Expects: $content (view file path), $page_title, $active, $user */
$user = $user ?? Auth::user() ?? ['full_name' => 'User', 'role' => ''];
$flash = get_flash();
$appname = app_setting('hospital_name', defined('APP_NAME') ? APP_NAME : 'Hospital');
$role = Auth::role() ?? ($user['role'] ?? '');

$menu = [
    ['url' => 'dashboard',      'label' => 'Dashboard',     'icon' => 'bi-speedometer2',        'roles' => ['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier', 'caregiver']],
    ['url' => 'patients',       'label' => 'Patients',      'icon' => 'bi-people',              'roles' => ['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier']],
    ['url' => 'appointments',   'label' => 'Appointments',  'icon' => 'bi-calendar3',           'roles' => ['admin', 'doctor', 'nurse', 'receptionist']],
    ['url' => 'consultations',  'label' => 'Consultations', 'icon' => 'bi-clipboard2-pulse',    'roles' => ['admin', 'doctor', 'nurse']],
    ['url' => 'admissions',     'label' => 'Admissions',    'icon' => 'bi-hospital',            'roles' => ['admin', 'doctor', 'nurse', 'receptionist']],
    ['url' => 'wards',          'label' => 'Wards',         'icon' => 'bi-buildings',           'roles' => ['admin', 'nurse']],
    ['url' => 'caregiving',     'label' => 'Caregiving',     'icon' => 'bi-heart-pulse',         'roles' => ['admin', 'doctor', 'nurse', 'receptionist', 'caregiver']],
    ['url' => 'prescriptions',  'label' => 'Prescriptions', 'icon' => 'bi-prescription2',       'roles' => ['admin', 'doctor', 'pharmacist', 'nurse']],
    ['url' => 'pharmacy',       'label' => 'Pharmacy',      'icon' => 'bi-capsule',             'roles' => ['admin', 'pharmacist', 'nurse']],
    ['url' => 'lab',            'label' => 'Laboratory',    'icon' => 'bi-eyedropper',          'roles' => ['admin', 'lab', 'doctor', 'nurse']],
    /* Sub-item: the test price list was only reachable via a link buried in an
       error message, so admins could not find it without knowing the URL. */
    ['url' => 'lab/tests',      'label' => 'Lab Tests',     'icon' => 'bi-list-columns',        'roles' => ['admin', 'lab'], 'prefix' => true],
    ['url' => 'inventory',      'label' => 'Inventory',     'icon' => 'bi-box-seam',            'roles' => ['admin', 'pharmacist']],
    ['url' => 'billing',        'label' => 'Billing',       'icon' => 'bi-receipt',             'roles' => ['admin', 'cashier', 'receptionist']],
    ['url' => 'reports',        'label' => 'Reports',       'icon' => 'bi-graph-up-arrow',      'roles' => ['admin', 'cashier', 'doctor']],
    ['url' => 'staff',          'label' => 'Staff',         'icon' => 'bi-person-badge',        'roles' => ['admin']],
    ['url' => 'logs',           'label' => 'Activity Logs', 'icon' => 'bi-clock-history',        'roles' => ['admin']],
    ['url' => 'settings',       'label' => 'Settings',      'icon' => 'bi-gear',                'roles' => ['admin']],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<title><?= e($page_title) ?> &mdash; <?= e($appname) ?></title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-layout">

  <?php /* Dismissible layer for the mobile drawer; hidden on desktop by CSS. */ ?>
  <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

  <aside class="sidebar" id="sidebar" aria-hidden="true">
    <div class="sidebar-brand">
      <span class="brand-icon"><i class="bi bi-hospital"></i></span>
      <span class="brand-text"><?= e($appname) ?></span>
    </div>
    <nav class="sidebar-nav flex-grow-1" aria-label="Main navigation">
      <?php foreach ($menu as $m): if (!in_array($role, $m['roles'], true)) continue; ?>
        <?php
          // Prefix match so nested screens (lab/tests) highlight their section.
          // Exact equality is kept first so 'lab' does not light up 'labs'.
          $isActive = $active === $m['url']
              || (isset($m['prefix']) && $m['prefix'] && str_starts_with((string)$active, $m['url'] . '/'));
        ?>
        <a class="nav-item <?= $isActive ? 'active' : '' ?>" href="<?= base_url($m['url']) ?>"
           <?= $isActive ? ' aria-current="page"' : '' ?>>
          <i class="bi <?= e($m['icon']) ?>" aria-hidden="true"></i><span><?= e($m['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="page">
    <header class="topbar">
      <button class="btn btn-link sidebar-toggle" id="sidebarToggle"
              aria-label="Open menu" aria-expanded="false" aria-controls="sidebar">
        <i class="bi bi-list fs-4" aria-hidden="true"></i>
      </button>
      <div class="topbar-title"><?= e($page_title) ?></div>
      <div class="ms-auto d-flex align-items-center gap-2">
        <div class="dropdown">
          <button class="btn d-flex align-items-center gap-2 py-1" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar"><?= e(initials($user['full_name'])) ?></span>
            <span class="text-start d-none d-md-block">
              <span class="d-block small fw-semibold lh-1"><?= e($user['full_name']) ?></span>
              <span class="d-block text-muted small lh-1 mt-1"><?= e(role_label($role)) ?></span>
            </span>
            <i class="bi bi-chevron-down small text-muted"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <?php if ($role === 'admin'): ?>
              <li><a class="dropdown-item" href="<?= base_url('settings') ?>"><i class="bi bi-gear me-2"></i>Settings</a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="bi bi-person-circle me-2"></i>My account</a></li>
            <li><hr class="dropdown-divider"></li>
            <?php /* POST, not a plain link: a GET logout can be triggered by any
                     <img>/<a>/<form> on any page, including cross-site ones. */ ?>
            <li>
              <form method="post" action="<?= base_url('auth/logout') ?>" class="px-3 py-1">
                <?= csrf_field() ?>
                <button type="submit" class="dropdown-item text-danger">
                  <i class="bi bi-box-arrow-right me-2"></i>Log out
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </header>

    <main class="content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash[0] === 'error' ? 'danger' : $flash[0]) ?> alert-dismissible fade show" role="alert">
          <?= e($flash[1]) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <?php require $content; ?>
    </main>

    <footer class="app-footer">
      &copy; <?= date('Y') ?> <?= e($appname) ?> &mdash; Hospital Management System
    </footer>
  </div>
</div>

<script src="<?= base_url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/chart.umd.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= isset($scripts) ? $scripts : '' ?>
</body>
</html>