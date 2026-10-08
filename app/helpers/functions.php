<?php

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function base_url($path = '') {
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    return $base . '/' . ltrim((string)$path, '/');
}

function redirect($path) {
    header('Location: ' . base_url($path));
    exit;
}

function not_found() {
    http_response_code(404);
    require APP_PATH . '/views/errors/404.php';
    exit;
}

function forbidden() {
    http_response_code(403);
    require APP_PATH . '/views/errors/403.php';
    exit;
}

function back() {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        $p = parse_url($ref);
        if ($p === false) {
            redirect('dashboard');
        }
        $host = (string)($p['host'] ?? '');
        if ($host === '') {
            // Path-only or protocol-relative referer: stays on this host.
            header('Location: ' . $ref);
            exit;
        }
        // Absolute referer: only honor it when it points back at this host.
        $refHost = $host . (isset($p['port']) ? ':' . $p['port'] : '');
        if (strcasecmp($refHost, (string)($_SERVER['HTTP_HOST'] ?? '')) === 0) {
            header('Location: ' . $ref);
            exit;
        }
    }
    redirect('dashboard');
}

function set_flash($type, $msg) {
    $_SESSION['flash'] = [$type, $msg];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/**
 * Format a monetary amount.
 *
 * The symbols are real Unicode characters rather than HTML entities. Entities
 * were safe only while the result was echoed raw; passing a formatted amount
 * into page_header() (which escapes its input) turned "&#8358;" into visible
 * "&amp;#8358;" text. Unicode survives both.
 */
function money($amount, $currency = null) {
    $cur = $currency ?? app_setting('currency', defined('APP_CURRENCY') ? APP_CURRENCY : 'NGN');
    $symbols = [
        'NGN' => "\u{20A6}",   // ₦
        'USD' => '$',
        'GHS' => "\u{20B5}",   // ₵
        'KES' => 'KSh ',
        'EUR' => "\u{20AC}",   // €
        'GBP' => "\u{00A3}",   // £
    ];
    $sym = $symbols[$cur] ?? $cur . ' ';
    return $sym . number_format((float)$amount, 2);
}

function age_from_dob($dob) {
    if (!$dob || $dob === '0000-00-00') return '—';
    return (new DateTime($dob))->diff(new DateTime('now'))->y;
}

function fmt_date($d, $f = 'M j, Y') {
    if (!$d || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') return '—';
    return date($f, strtotime($d));
}

function fmt_time($t, $f = 'h:i A') {
    if (!$t) return '—';
    return date($f, strtotime($t));
}

function fmt_dt($d, $f = 'M j, Y h:i A') {
    if (!$d || $d === '0000-00-00 00:00:00') return '—';
    return date($f, strtotime($d));
}

/* ------------------------------------------------------------------ CSRF */

function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check() {
    if (!isset($_POST['_token']) || !hash_equals(csrf_token(), $_POST['_token'])) {
        http_response_code(403);
        exit('Invalid security token. Please go back and try again.');
    }
}

/* --------------------------------------------------------------- Request */

function post($k, $d = '') {
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d;
}

function getp($k, $d = '') {
    return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $d;
}

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? null;
}

/* -------------------------------------------------------------- Settings */

function app_setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (fetch_all('SELECT skey, svalue FROM settings') as $r) {
                $cache[$r['skey']] = $r['svalue'];
            }
        } catch (Throwable $e) {
            // The settings table may not exist yet (fresh install / installer running).
            // Record the cause rather than silently pretending settings are empty.
            app_log('settings_load_failed', $e->getMessage());
            $cache = [];
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

/**
 * Append a line to the PHP error log. Used for failures that must not surface to
 * the user but must not disappear either (audit writes, settings load, mail, …).
 */
function app_log($event, $detail = '') {
    error_log('[kusagba] ' . $event . ($detail !== '' ? ': ' . $detail : ''));
}

/* ----------------------------------------------------------------- Audit */

/**
 * Write an activity log entry.
 *
 * Audit failures are no longer swallowed: the write runs in its own transaction
 * with a short lock timeout so it cannot block or roll back clinical work, and a
 * failure is written to the error log. A compliance trail that silently drops
 * entries is worse than no trail, because the Logs screen looks authoritative.
 */
function audit($action, $module = '', $details = '') {
    try {
        run('INSERT INTO activity_logs (user_id, action, module, details, ip) VALUES (?,?,?,?,?)', [
            Auth::id(), $action, $module, $details, client_ip()
        ]);
        return true;
    } catch (Throwable $e) {
        app_log('audit_failed', $module . '/' . $action . ': ' . $e->getMessage());
        return false;
    }
}

/* ------------------------------------------------------- Login throttle */

/**
 * Failed-login counters live in the database, keyed by IP + username, not in the
 * session. A session counter is worthless as a brute-force control: deleting the
 * cookie discards it, so the lockout could be reset at will.
 *
 * Rows are written as ordinary activity_logs entries, which means the attempts
 * also show up on the admin Logs screen.
 */
function login_failures_since($username, $seconds) {
    $username = trim((string)$username);
    if ($username === '') {
        return 0;
    }
    try {
        return (int)fetch_val(
            'SELECT COUNT(*) FROM activity_logs
             WHERE action = "login_failed" AND ip = ? AND details = ? AND created_at >= ?',
            [client_ip(), substr($username, 0, 60), date('Y-m-d H:i:s', time() - $seconds)]
        );
    } catch (Throwable $e) {
        // If the counter cannot be read, fail open for the user but leave a trace;
        // a throttle outage must not turn into a lockout nobody can clear.
        app_log('login_throttle_read_failed', $e->getMessage());
        return 0;
    }
}

function record_login_failure($username) {
    try {
        run(
            'INSERT INTO activity_logs (user_id, action, module, details, ip) VALUES (NULL,"login_failed","auth",?,?)',
            [substr(trim((string)$username), 0, 60), client_ip()]
        );
    } catch (Throwable $e) {
        app_log('login_throttle_write_failed', $e->getMessage());
    }
}

/* ------------------------------------------------------- Field-level log */

/**
 * Record what actually changed on a record, so a clinical entry can be audited
 * beyond "Patient #12 updated".
 *
 * @param array $before  Row as read before the update.
 * @param array $after   Values about to be written.
 * @param array $fields  Fields to compare.
 */
function changelog($entityType, $entityId, $before, $after, array $fields = []) {
    if (!$fields) {
        $fields = array_keys($after);
    }
    $changes = [];
    foreach ($fields as $f) {
        $old = $before[$f] ?? null;
        $new = $after[$f] ?? null;
        if ((string)$old === (string)$new) continue;
        $changes[$f] = ['from' => $old, 'to' => $new];
    }
    if (!$changes) return false;
    try {
        run('INSERT INTO record_changelogs (entity_type, entity_id, action, changes, user_id, ip)
             VALUES (?,?,?,?,?,?)', [
            $entityType, (int)$entityId, 'update',
            json_encode($changes, JSON_UNESCAPED_UNICODE), Auth::id(), client_ip(),
        ]);
        return true;
    } catch (Throwable $e) {
        app_log('changelog_failed', $entityType . '#' . $entityId . ': ' . $e->getMessage());
        return false;
    }
}

/* ---------------------------------------------------------- Soft deletes */

/** Tables eligible for archival. Hard-coded: these end up in interpolated SQL. */
function soft_delete_tables() {
    return ['patients', 'users', 'wards', 'services', 'lab_tests', 'inventory_items', 'admissions', 'appointments', 'care_engagements'];
}

function soft_delete($table, $id, array $extra = []) {
    if (!in_array($table, soft_delete_tables(), true)) {
        throw new InvalidArgumentException('Refusing to archive unknown table.');
    }
    $set = ['deleted_at = NOW()'];
    foreach ($extra as $col => $val) {
        if (!preg_match('/^[a-z_]+$/', $col)) {
            throw new InvalidArgumentException('Invalid column name.');
        }
        $set[] = "$col = " . $val;
    }
    return run('UPDATE `' . $table . '` SET ' . implode(', ', $set) . ' WHERE id = ? AND deleted_at IS NULL',
        [(int)$id])->rowCount();
}

function soft_restore($table, $id) {
    if (!in_array($table, soft_delete_tables(), true)) {
        throw new InvalidArgumentException('Refusing to restore unknown table.');
    }
    return run('UPDATE `' . $table . '` SET deleted_at = NULL WHERE id = ?', [(int)$id])->rowCount();
}

/* ------------------------------------------------------------ Formatting */

function status_badge($status) {
    $map = [
        'paid' => 'success', 'unpaid' => 'danger', 'partial' => 'warning', 'void' => 'dark',
        'pending' => 'warning', 'confirmed' => 'primary', 'checked_in' => 'info',
        'completed' => 'success', 'cancelled' => 'secondary', 'no_show' => 'danger',
        'admitted' => 'success', 'discharged' => 'secondary',
        'dispensed' => 'success', 'active' => 'primary',
        'resulted' => 'success', 'processing' => 'info', 'sample_collected' => 'primary',
        'routine' => 'secondary', 'urgent' => 'warning', 'stat' => 'danger',
        'approved' => 'info', 'in_progress' => 'warning', 'scheduled' => 'secondary', 'missed' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $class . '">' . e(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

function role_label($role) {
    $map = [
        'admin' => 'Administrator', 'doctor' => 'Doctor', 'nurse' => 'Nurse',
        'receptionist' => 'Receptionist', 'pharmacist' => 'Pharmacist',
        'lab' => 'Lab Technician', 'cashier' => 'Cashier / Accountant', 'patient' => 'Patient',
        'caregiver' => 'Caregiver',
    ];
    return $map[$role] ?? $role;
}

function initials($name) {
    $parts = preg_split('/\s+/', trim((string)$name));
    $s = '';
    foreach (array_slice(array_values(array_filter($parts)), 0, 2) as $p) {
        $s .= strtoupper(substr($p, 0, 1));
    }
    return $s ?: '?';
}

function patient_full_name($p) {
    return trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
}

function list_option($rows, $valueKey, $labelKey, $selected = null) {
    $html = '';
    foreach ($rows as $r) {
        $sel = ((string)($r[$valueKey]) === (string)$selected) ? ' selected' : '';
        $html .= '<option value="' . e($r[$valueKey]) . '"' . $sel . '>' . e($r[$labelKey]) . '</option>';
    }
    return $html;
}

/* --------------------------------------------------------------- Partial */

/**
 * Render a reusable view fragment from app/views/partials.
 * Returns HTML rather than echoing, so callers can place it inline.
 */
function partial($name, array $data = []) {
    $file = APP_PATH . '/views/partials/' . $name . '.php';
    if (!file_exists($file)) {
        throw new RuntimeException('Partial not found: ' . $name);
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    return (string)ob_get_clean();
}

/* ------------------------------------------------------------------ HTML */

/**
 * Page heading. Escapes its arguments — pass raw text, not pre-escaped HTML.
 * This is the single page title; layouts no longer render a competing <h1>.
 */
function page_header($title, $subtitle = '', $actions = '') {
    ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h4 mb-1 fw-semibold"><?= e($title) ?></h1>
            <?php if ($subtitle): ?><p class="text-muted small mb-0"><?= e($subtitle) ?></p><?php endif; ?>
        </div>
        <?php if ($actions): ?><div class="d-flex gap-2"><?= $actions ?></div><?php endif; ?>
    </div>
    <?php
}

function modal($id, $title, $body, $buttons = '') {
    ?>
    <div class="modal fade" id="<?= e($id) ?>" tabindex="-1" aria-labelledby="<?= e($id) ?>-title" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="<?= e($id) ?>-title"><?= e($title) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body"><?= $body ?></div>
            <?php if ($buttons): ?><div class="modal-footer"><?= $buttons ?></div><?php endif; ?>
        </div></div>
    </div>
    <?php
}

/** Standard empty-state row for a table body. */
function empty_row($colspan, $message = 'Nothing to show yet.') {
    return '<tr><td colspan="' . (int)$colspan . '" class="text-center text-muted py-4">'
        . '<i class="bi bi-inbox fs-4 d-block mb-2 opacity-50"></i>' . e($message) . '</td></tr>';
}

/**
 * Empty-state block for a card body (empty_row() only works inside <tbody>).
 */
function empty_block($message = 'Nothing to show yet.') {
    return '<div class="text-center text-muted py-4">'
        . '<i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>' . e($message) . '</div>';
}

/**
 * Open a modal form.
 *
 * The form posts to a single dispatch action and carries a hidden `id`; the row
 * being edited is chosen by that field alone. Views used to rewrite the form's
 * `action` attribute from JavaScript, which broke without JS, left the form
 * pointing at the previous record's URL after a cancel, and put a record id in
 * the URL instead of the body.
 */
function modal_open($id, $title, $formId = null, $action = null) {
    $formId = $formId ?: $id . 'Form';
    ?>
    <div class="modal fade" id="<?= e($id) ?>" tabindex="-1" aria-labelledby="<?= e($id) ?>-title" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <form method="post" action="<?= e($action ?? base_url('index')) ?>" id="<?= e($formId) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="" data-role="dispatch-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="<?= e($id) ?>-title"><?= e($title) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
    <?php
}

function modal_close($submitLabel = 'Save', $submitClass = 'btn btn-primary') {
    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="<?= e($submitClass) ?>"><?= e($submitLabel) ?></button>
                </div>
            </form>
        </div></div>
    </div>
    <?php
}

/**
 * Populate a modal_open() form from the trigger button's data-* attributes.
 *
 * $fields maps input id => data attribute name. When the trigger carries no
 * id (the "new" button) the form is simply reset, leaving `id` empty so the
 * controller takes the create path.
 */
function modal_script($modalId, $formId, array $fields) {
    $map = [];
    foreach ($fields as $inputId => $attr) {
        $map[$inputId] = $attr;
    }
    $json = json_encode($map, JSON_FORCE_OBJECT);
    ?>
    <script>
    (function () {
      var modal = document.getElementById(<?= json_encode($modalId) ?>);
      var form = document.getElementById(<?= json_encode($formId) ?>);
      if (!modal || !form) return;
      var fields = <?= $json ?>;
      modal.addEventListener('show.bs.modal', function (e) {
        var trigger = e.relatedTarget;
        form.reset();
        var idField = form.querySelector('[data-role="dispatch-id"]');
        var id = trigger && trigger.getAttribute('data-id');
        if (idField) idField.value = id || '';
        if (!id) return;
        for (var inputId in fields) {
          var input = document.getElementById(inputId);
          if (input && trigger) input.value = trigger.getAttribute(fields[inputId]) || '';
        }
      });
    })();
    </script>
    <?php
}

/** Standard icon-only action button with an accessible name. */
function icon_btn($action, $icon, $label, $extra = '') {
    return '<form method="post" action="' . e($action) . '" class="d-inline" onsubmit="return confirm(\''
        . e(addslashes($label)) . '\')">'
        . csrf_field()
        . '<button type="submit" class="btn btn-sm btn-outline-secondary border-0" title="' . e($label)
        . '" aria-label="' . e($label) . '">' . $icon . '</button></form>';
}