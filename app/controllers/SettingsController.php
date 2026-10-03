<?php

class SettingsController extends Controller
{
    protected $title = 'Settings';
    protected $active = 'settings';

    public function __construct()
    {
        $this->guard(['admin']);
    }

    public function index()
    {
        $settings = [];
        foreach (fetch_all('SELECT skey, svalue FROM settings') as $r) {
            $settings[$r['skey']] = $r['svalue'];
        }
        $showArchived = getp('archived') === '1';
        $services = fetch_all(
            'SELECT * FROM services '
            . ($showArchived ? '' : 'WHERE deleted_at IS NULL ')
            . 'ORDER BY deleted_at IS NOT NULL, category, name'
        );
        $this->view('settings/index', compact('settings', 'services', 'showArchived'));
    }

    public function update()
    {
        csrf_check();
        foreach (['hospital_name', 'hospital_address', 'hospital_phone', 'hospital_email', 'receipt_footer'] as $f) {
            if (isset($_POST[$f])) {
                run('INSERT INTO settings (skey, svalue) VALUES (?,?)
                     ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$f, post($f) ?: null]);
            }
        }

        $validCurrencies = ['NGN', 'USD', 'GHS', 'KES', 'EUR', 'GBP'];

        // The currency select posts its option text. Previously the option labels
        // carried no explicit value, so the controller read the label while the DB
        // held a code, and saving could silently overwrite the stored currency.
        $currency = post('currency');
        if ($currency !== '') {
            if (in_array($currency, $validCurrencies, true)) {
                $this->put_setting('currency', $currency);
            } else {
                set_flash('error', 'Unsupported currency.');
            }
        }

        $timezone = post('timezone');
        if ($timezone !== '') {
            try {
                new DateTimeZone($timezone);
                $this->put_setting('timezone', $timezone);
            } catch (Exception $e) {
                set_flash('error', 'Unrecognised timezone.');
            }
        }

        audit('update', 'settings', 'Hospital settings updated');
        set_flash('success', 'Settings saved.');
        back();
    }

    private function put_setting($key, $value)
    {
        run('INSERT INTO settings (skey, svalue) VALUES (?,?)
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$key, $value]);
    }

    /** Admin-only convenience route; all roles use ProfileController::password. */
    public function password()
    {
        csrf_check();
        $this->guard(['admin']);
        $u = Auth::user();
        if (!password_verify((string)($_POST['current_password'] ?? ''), $u['password'])) {
            set_flash('error', 'Current password is incorrect.');
            back();
        }
        $new = (string)($_POST['new_password'] ?? '');
        if (strlen($new) < 8) {
            set_flash('error', 'New password must be at least 8 characters.');
            back();
        }
        if ($new !== (string)($_POST['confirm_password'] ?? '')) {
            set_flash('error', 'Passwords do not match.');
            back();
        }
        run('UPDATE users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        audit('update', 'settings', 'Password changed');
        set_flash('success', 'Password updated.');
        redirect('settings');
    }

    /** Single dispatch for create and edit, decided by a hidden `id`. */
    public function services_save()
    {
        csrf_check();
        $id = (int)post('id', 0);
        $name = post('name');
        if ($name === '') {
            set_flash('error', 'Service name required.');
            back();
        }
        $categories = ['consultation', 'lab', 'drug', 'ward', 'procedure', 'other'];
        $category = in_array(post('category', 'other'), $categories, true) ? post('category', 'other') : 'other';
        $code = post('code') ?: null;
        $price = max(0, (float)post('price', 0));
        $desc = post('description') ?: null;

        if ($id > 0) {
            $before = fetch('SELECT * FROM services WHERE id = ?', [$id]);
            if (!$before) not_found();
            run('UPDATE services SET code = ?, name = ?, category = ?, price = ?, description = ? WHERE id = ?',
                [$code, $name, $category, $price, $desc, $id]);
            changelog('service', $id, $before, ['code' => $code, 'name' => $name, 'category' => $category, 'price' => $price]);
            audit('update', 'settings', "Service #$id");
            set_flash('success', 'Service updated.');
        } else {
            run('INSERT INTO services (code, name, category, price, description) VALUES (?,?,?,?,?)',
                [$code, $name, $category, $price, $desc]);
            $id = last_id();
            audit('create', 'settings', "Service: $name");
            set_flash('success', 'Service added.');
        }
        back();
    }

    public function services_store()
    {
        $this->services_save();
    }

    public function services_update($id)
    {
        $_POST['id'] = (int)$id;
        $this->services_save();
    }

    public function services_delete($id)
    {
        csrf_check();
        $s = fetch('SELECT * FROM services WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$s) not_found();
        soft_delete('services', $id, ['active' => 0]);
        audit('archive', 'settings', "Service #$id ({$s['name']})");
        set_flash('success', 'Service archived. Invoices that already reference it are unaffected.');
        back();
    }

    public function services_restore($id)
    {
        csrf_check();
        if (!fetch('SELECT id FROM services WHERE id = ?', [$id])) not_found();
        soft_restore('services', $id);
        audit('restore', 'settings', "Service #$id");
        set_flash('success', 'Service restored.');
        back();
    }
}