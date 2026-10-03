<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Africa/Lagos');

$root = dirname(__DIR__);
$configFile = $root . '/config.php';
$alreadyInstalled = file_exists($configFile);

$errors = [];
$ok = false;

function detect_base_url()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '/install/index.php';
    $dir = rtrim(dirname($script), '/');
    $base = rtrim(dirname($dir), '/');
    return ($base === '' || $base === '/') ? '' : $base;
}

// CSRF token for the installer
if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
    if (!hash_equals($_SESSION['install_csrf'], $_POST['_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please reload the page.';
    } else {
        $host     = trim($_POST['db_host'] ?? 'localhost');
        $port     = trim($_POST['db_port'] ?? '3306');
        $dbname   = trim($_POST['db_name'] ?? '');
        $dbuser   = trim($_POST['db_user'] ?? '');
        $dbpass   = (string)($_POST['db_pass'] ?? '');
        $createDb = isset($_POST['db_create']);
        $hname    = trim($_POST['hospital_name'] ?? '') ?: 'Kusagba Hospital';
        $auser    = trim($_POST['admin_user'] ?? '');
        $aname    = trim($_POST['admin_name'] ?? '') ?: 'System Administrator';
        $aemail   = trim($_POST['admin_email'] ?? '');
        $apass    = (string)($_POST['admin_pass'] ?? '');
        $apass2   = (string)($_POST['admin_pass2'] ?? '');
        $base     = trim($_POST['base_url'] ?? detect_base_url());

        if ($host === '' || $dbname === '' || $dbuser === '') $errors[] = 'Database host, name and user are required.';
        if ($auser === '') $errors[] = 'Admin username is required.';
        if (strlen($apass) < 6) $errors[] = 'Admin password must be at least 6 characters.';
        if ($apass !== $apass2) $errors[] = 'Passwords do not match.';
        if (!filter_var($aemail, FILTER_VALIDATE_EMAIL) && $aemail !== '') $errors[] = 'Admin email is not valid.';

        if (!$errors) {
            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                $pdo = new PDO($dsn, $dbuser, $dbpass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                if ($createDb && strpos($e->getMessage(), 'Unknown database') !== false) {
                    try {
                        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $dbuser, $dbpass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        ]);
                        $pdo->exec("CREATE DATABASE `" . str_replace('`', '', $dbname) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $pdo = new PDO($dsn, $dbuser, $dbpass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    } catch (Exception $e2) {
                        $errors[] = 'Could not create database: ' . $e2->getMessage();
                    }
                } else {
                    $errors[] = 'Database connection failed: ' . $e->getMessage();
                }
            }
        }

        if (!$errors) {
            try {
                $schema = file_get_contents(__DIR__ . '/schema.sql');
                $pdo->exec($schema);

                $seed = [
                    ['hospital_name', $hname],
                    ['hospital_address', ''],
                    ['hospital_phone', ''],
                    ['hospital_email', ''],
                    ['receipt_footer', 'Thank you for choosing ' . $hname . '. Please keep this receipt.'],
                    ['currency', 'NGN'],
                    ['timezone', 'Africa/Lagos'],
                ];
                $st = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)');
                foreach ($seed as $s) $st->execute([$s[0], $s[1]]);

                $wards = [
                    ['Male Medical Ward', 'Medicine', 12],
                    ['Female Medical Ward', 'Medicine', 12],
                    ['Paediatric Ward', 'Paediatrics', 10],
                    ['Maternity Ward', 'Obstetrics & Gynaecology', 8],
                    ['Emergency Ward', 'Emergency', 6],
                    ['Surgical Ward', 'Surgery', 10],
                ];
                $st = $pdo->prepare('INSERT INTO wards (name, department, total_beds) VALUES (?,?,?)');
                foreach ($wards as $w) $st->execute($w);

                $services = [
                    ['General Consultation', 'consultation', 5000],
                    ['Follow-up Consultation', 'consultation', 3000],
                    ['Specialist Consultation', 'consultation', 10000],
                    ['Ward Admission (per day)', 'ward', 15000],
                    ['Blood Sample Collection', 'lab', 1000],
                    ['Intramuscular Injection', 'procedure', 3000],
                    ['IV Fluid Administration', 'procedure', 8000],
                    ['Wound Dressing', 'procedure', 4000],
                    ['Emergency Treatment', 'procedure', 20000],
                ];
                $st = $pdo->prepare('INSERT INTO services (name, category, price) VALUES (?,?,?)');
                foreach ($services as $s) $st->execute($s);

                $labTests = [
                    ['CBC', 'Full Blood Count / CBC', 'Haematology', 4500, 'x10^9/L', 'See report'],
                    ['FBC', 'Haemoglobin (Hb)', 'Haematology', 2000, 'g/dL', 'M 13-17, F 12-15'],
                    ['MPS', 'Malaria Parasite (MPS)', 'Haematology', 2500, '', 'Negative'],
                    ['FILM', 'Blood Film (BF / MP)', 'Haematology', 2500, '', 'Negative'],
                    ['GLU', 'Random Blood Sugar', 'Chemistry', 1500, 'mmol/L', '3.9 – 7.8'],
                    ['FBS', 'Fasting Blood Sugar', 'Chemistry', 1500, 'mmol/L', '3.9 – 6.1'],
                    ['LFT', 'Liver Function Test', 'Chemistry', 9000, '', 'See report'],
                    ['ELY', 'Serum Electrolytes (Na, K, Cl, HCO3)', 'Chemistry', 7000, 'mmol/L', 'Na 135-145, K 3.5-5.1'],
                    ['CRE', 'Creatinine', 'Chemistry', 3000, 'µmol/L', 'M 59-104, F 45-84'],
                    ['URE', 'Urea', 'Chemistry', 1500, 'mmol/L', '2.5 – 7.1'],
                    ['UR-AN', 'Urinalysis (Dipstick)', 'Urinalysis', 3000, '', 'See report'],
                    ['UR-MC', 'Urine MCS / Culture', 'Microbiology', 6000, '', 'No growth'],
                    ['ST-MC', 'Stool MCS / Culture', 'Microbiology', 6000, '', 'No growth'],
                    ['WR', 'Widal Test', 'Serology', 3000, '', 'Titre < 1:80'],
                    ['VDRL', 'VDRL / RPR', 'Serology', 2500, '', 'Non-reactive'],
                    ['ABO', 'Blood Group & Rhesus', 'Serology', 1500, '', 'e.g. O+'],
                    ['HCT', 'Packed Cell Volume (PCV)', 'Haematology', 1500, '%', 'M 40-52, F 36-48'],
                    ['PREG', 'Pregnancy Test (urine)', 'Serology', 1500, '', 'Negative'],
                ];
                $st = $pdo->prepare('INSERT INTO lab_tests (code, name, category, price, unit, normal_range) VALUES (?,?,?,?,?,?)');
                foreach ($labTests as $t) $st->execute($t);

                $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                if ($count === 0) {
                    $st = $pdo->prepare('INSERT INTO users (username, password, role, full_name, email) VALUES (?,?,?,?,?)');
                    $st->execute([$auser, password_hash($apass, PASSWORD_DEFAULT), 'admin', $aname, $aemail]);
                }

                $cfg = '<?php' . "\n\n/* Auto-generated by the web installer. */\n\n"
                    . 'define(\'DB_HOST\', ' . var_export($host, true) . ");\n"
                    . 'define(\'DB_PORT\', ' . var_export($port, true) . ");\n"
                    . 'define(\'DB_NAME\', ' . var_export($dbname, true) . ");\n"
                    . 'define(\'DB_USER\', ' . var_export($dbuser, true) . ");\n"
                    . 'define(\'DB_PASS\', ' . var_export($dbpass, true) . ");\n"
                    . 'define(\'BASE_URL\', ' . var_export($base, true) . ");\n"
                    . 'define(\'APP_NAME\', ' . var_export($hname, true) . ");\n"
                    . "define('APP_CURRENCY', 'NGN');\n"
                    . "define('TIMEZONE', 'Africa/Lagos');\n"
                    . "define('APP_DEBUG', false);\n"
                    . "define('INSTALLED', true);\n";

                if (file_put_contents($configFile, $cfg) === false) {
                    $errors[] = 'Could not write config.php. Check file permissions on the project folder.';
                } else {
                    $ok = true;
                    $loginUrl = ($base === '' ? '' : $base) . '/auth/login';
                }
            } catch (Exception $e) {
                $errors[] = 'Installation failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install &mdash; Hospital Management System</title>
<link rel="stylesheet" href="../assets/vendor/bootstrap.min.css">
<link rel="stylesheet" href="../assets/vendor/bootstrap-icons.min.css">
<style>
  body { background: #0f172a; min-height: 100vh; }
  .card-brand { color: #0ea5a4; }
</style>
</head>
<body>
<div class="container py-5" style="max-width:640px">
  <div class="text-center mb-4 text-white">
    <i class="bi bi-hospital display-4 text-info-emphasis"></i>
    <h1 class="h3 mt-2 mb-0">Hospital Management System</h1>
    <p class="text-white-50 mb-0">Web installer</p>
  </div>

  <?php if ($ok): ?>
    <div class="card shadow-sm">
      <div class="card-body text-center p-4">
        <i class="bi bi-check-circle-fill text-success display-5"></i>
        <h4 class="mt-2 mb-3">Installation complete</h4>
        <p class="text-muted">Your system is ready. Log in with your admin account to get started.</p>
        <a class="btn btn-success px-4" href="<?= htmlspecialchars($loginUrl) ?>">Go to Login</a>
      </div>
    </div>

  <?php elseif ($alreadyInstalled): ?>
    <div class="card shadow-sm">
      <div class="card-body text-center p-4">
        <i class="bi bi-info-circle-fill text-primary display-5"></i>
        <h4 class="mt-2 mb-3">Already installed</h4>
        <p class="text-muted">The application is configured already.</p>
        <a class="btn btn-primary px-4" href="<?= htmlspecialchars(detect_base_url()) ?>/auth/login">Go to Login</a>
      </div>
    </div>

  <?php else: ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <strong>There was a problem:</strong>
        <ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= htmlspecialchars($er) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" class="card shadow-sm">
      <div class="card-body p-4">
        <?= '<input type="hidden" name="_token" value="' . htmlspecialchars($_SESSION['install_csrf']) . '">' ?>

        <h5 class="fw-semibold mb-3"><i class="bi bi-database me-2"></i>Database</h5>
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Host</label>
            <input class="form-control" name="db_host" value="localhost" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Port</label>
            <input class="form-control" name="db_port" value="3306" required>
          </div>
          <div class="col-md-8">
            <label class="form-label">Database name</label>
            <input class="form-control" name="db_name" placeholder="e.g. if0_12345678_hospital" required>
            <div class="form-text">On InfinityFree use your prefixed name (if0_XXXXXX_...).</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">User</label>
            <input class="form-control" name="db_user" value="root" required>
          </div>
          <div class="col-12">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="db_pass" value="" autocomplete="off">
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="db_create" id="db_create">
              <label class="form-check-label" for="db_create">Create the database if it does not exist</label>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <h5 class="fw-semibold mb-3"><i class="bi bi-hospital me-2"></i>Organization</h5>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Hospital / Clinic name</label>
            <input class="form-control" name="hospital_name" value="Kusagba Hospital" required>
          </div>
          <div class="col-12">
            <label class="form-label">App base URL</label>
            <input class="form-control" name="base_url" value="<?= htmlspecialchars(detect_base_url()) ?>" placeholder="/subfolder or empty for domain root">
            <div class="form-text">Leave empty when installed at a domain root (e.g. your InfinityFree subdomain).</div>
          </div>
        </div>

        <hr class="my-4">

        <h5 class="fw-semibold mb-3"><i class="bi bi-person-gear me-2"></i>Admin account</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" name="admin_user" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Full name</label>
            <input class="form-control" name="admin_name" value="System Administrator">
          </div>
          <div class="col-12">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="admin_email">
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="admin_pass" autocomplete="new-password" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm password</label>
            <input class="form-control" type="password" name="admin_pass2" autocomplete="new-password" required>
          </div>
        </div>

        <div class="d-grid mt-4">
          <button class="btn btn-primary btn-lg">Install System</button>
        </div>
        <p class="text-muted small text-center mt-3 mb-0">Selecting a broom icon is not required. This installer creates tables,
          default wards, a service price list and your admin account.</p>
      </div>
    </form>
  <?php endif; ?>
</div>
</body>
</html>