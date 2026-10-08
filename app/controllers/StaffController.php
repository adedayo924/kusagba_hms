<?php

class StaffController extends Controller
{
    protected $title = 'Staff';
    protected $active = 'staff';

    public function __construct()
    {
        $this->guard(['admin']);
    }

    public function index()
    {
        $role = getp('role', '');
        $q = getp('q');
        $showArchived = getp('archived') === '1';
        $where = 'u.id > 0 AND u.role <> \'patient\'';
        $params = [];
        if (!$showArchived) {
            $where .= ' AND u.deleted_at IS NULL';
        }
        if (in_array($role, ['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier', 'caregiver'], true)) {
            $where .= ' AND u.role = ?';
            $params[] = $role;
        }
        if ($q !== '') {
            $where .= ' AND (u.full_name LIKE ? OR u.username LIKE ? OR u.staff_id LIKE ? OR u.email LIKE ?)';
            $like = "%$q%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        $list = fetch_all(
            "SELECT u.*, (SELECT COUNT(*) FROM activity_logs a WHERE a.user_id = u.id) AS actions
             FROM users u WHERE $where
             GROUP BY u.id ORDER BY u.deleted_at IS NOT NULL, u.role, u.full_name",
            $params
        );
        $this->view('staff/index', compact('list', 'role', 'q', 'showArchived'));
    }

    /**
     * Single dispatch for create and edit, decided by a hidden `id`. Keeps the form
     * working without JavaScript (previously Edit silently created a duplicate if
     * the script failed to load).
     */
    public function save()
    {
        csrf_check();
        $id = (int)post('id', 0);
        $username = post('username');
        $fullName = post('full_name');
        $staffRoles = ['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier', 'caregiver'];

        if ($fullName === '') {
            set_flash('error', 'Full name is required.');
            back();
        }
        $role = in_array(post('role', 'receptionist'), $staffRoles, true) ? post('role', 'receptionist') : 'receptionist';
        $gender = post('gender') ?: null;
        if ($gender !== null && !in_array($gender, ['Male', 'Female', 'Other'], true)) $gender = null;
        $dob = post('dob') ?: null;
        $email = post('email') ?: null;
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Invalid email address.');
            back();
        }
        $phone = post('phone') ?: null;
        $staffId = post('staff_id') ?: null;
        $specialty = post('specialty') ?: null;
        $licenseNo = post('license_no') ?: null;
        $department = post('department') ?: null;
        $address = post('address') ?: null;

        $password = (string)($_POST['password'] ?? '');
        if ($password !== '' && strlen($password) < 8) {
            set_flash('error', 'Password must be at least 8 characters.');
            back();
        }

        if ($id > 0) {
            $before = fetch('SELECT * FROM users WHERE id = ?', [$id]);
            if (!$before) not_found();
            if ($before['role'] === 'admin' && $role !== 'admin' && Auth::id() === $id) {
                set_flash('error', 'You cannot demote your own admin account.');
                back();
            }
            if ($before['role'] === 'admin' && $role !== 'admin' && Auth::id() !== $id) {
                // Never leave the installation without an administrator.
                $otherAdmins = (int)fetch_val(
                    'SELECT COUNT(*) FROM users WHERE role = "admin" AND active = 1 AND deleted_at IS NULL AND id != ?',
                    [$id]
                );
                if ($otherAdmins === 0) {
                    set_flash('error', 'At least one active administrator must remain.');
                    back();
                }
            }
            run(
                'UPDATE users SET role = ?, full_name = ?, gender = ?, dob = ?, phone = ?, email = ?,
                        staff_id = ?, specialty = ?, license_no = ?, department = ?, address = ?
                 WHERE id = ?',
                [$role, $fullName, $gender, $dob, $phone, $email, $staffId, $specialty,
                 $licenseNo, $department, $address, $id]
            );
            if ($password !== '') {
                run('UPDATE users SET password = ? WHERE id = ?',
                    [password_hash($password, PASSWORD_DEFAULT), $id]);
            }
            changelog('user', $id, $before, [
                'role' => $role, 'full_name' => $fullName, 'phone' => $phone, 'email' => $email,
                'staff_id' => $staffId, 'specialty' => $specialty, 'department' => $department,
            ]);
            audit('update', 'staff', "User #$id");
            set_flash('success', 'Staff updated.');
            redirect('staff');
        }

        if ($username === '') {
            set_flash('error', 'Username is required.');
            back();
        }
        if (!preg_match('/^[A-Za-z0-9_.-]{3,}$/', $username)) {
            set_flash('error', 'Username must be at least 3 characters using letters, numbers, dots, dashes or underscores.');
            back();
        }
        if (fetch_val('SELECT id FROM users WHERE username = ?', [$username])) {
            set_flash('error', 'Username already exists.');
            back();
        }
        if ($password === '') {
            set_flash('error', 'A password is required for a new account.');
            back();
        }

        run(
            'INSERT INTO users (username, password, role, full_name, gender, dob, phone, email, staff_id, specialty, license_no, department, address)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $username, password_hash($password, PASSWORD_DEFAULT), $role, $fullName, $gender, $dob,
                $phone, $email, $staffId, $specialty, $licenseNo, $department, $address,
            ]
        );
        $id = last_id();
        audit('create', 'staff', "Created $fullName ($role)");
        set_flash('success', 'Staff account created.');
        redirect('staff');
    }

    public function store()
    {
        $this->save();
    }

    public function update($id)
    {
        $_POST['id'] = (int)$id;
        $this->save();
    }

    public function toggle($id)
    {
        csrf_check();
        $id = (int)$id;
        if ($id === Auth::id()) {
            set_flash('error', 'You cannot deactivate your own account.');
            back();
        }
        $u = fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$u) not_found();
        // Never allow the last active administrator to be switched off.
        if ($u['role'] === 'admin' && $u['active']) {
            $others = (int)fetch_val(
                'SELECT COUNT(*) FROM users WHERE role = "admin" AND active = 1 AND deleted_at IS NULL AND id != ?',
                [$id]
            );
            if ($others === 0) {
                set_flash('error', 'At least one active administrator must remain.');
                back();
            }
        }
        run('UPDATE users SET active = IF(active=1,0,1) WHERE id = ?', [$id]);
        audit('update', 'staff', ($u['active'] ? 'Deactivated ' : 'Activated ') . $u['full_name']);
        set_flash('success', 'Account ' . ($u['active'] ? 'deactivated' : 'activated') . '.');
        redirect('staff');
    }

    /**
     * Archive a staff account. Never hard-deletes: a clinician's past consultations,
     * prescriptions and receipts must remain attributable, and the activity log
     * references them.
     */
    public function delete($id)
    {
        csrf_check();
        $id = (int)$id;
        if ($id === Auth::id()) {
            set_flash('error', 'You cannot archive your own account.');
            back();
        }
        $u = fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$u) not_found();
        if ($u['role'] === 'patient') {
            set_flash('error', 'Portal accounts are managed from the patient record.');
            back();
        }
        $others = (int)fetch_val(
            'SELECT COUNT(*) FROM users WHERE role = "admin" AND active = 1 AND deleted_at IS NULL AND id != ?',
            [$id]
        );
        if ($u['role'] === 'admin' && $others === 0) {
            set_flash('error', 'At least one active administrator must remain.');
            back();
        }
        soft_delete('users', $id, ['active' => 0]);
        audit('archive', 'staff', "Archived {$u['full_name']} ({$u['username']})");
        set_flash('success', 'Staff account archived. Their past activity remains on record.');
        redirect('staff');
    }

    public function restore($id)
    {
        csrf_check();
        $u = fetch('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$u) not_found();
        soft_restore('users', $id);
        audit('restore', 'staff', "Restored {$u['full_name']}");
        set_flash('success', 'Staff account restored (still deactivated — enable it manually if needed).');
        redirect('staff');
    }
}