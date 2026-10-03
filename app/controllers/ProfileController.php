<?php

/**
 * Self-service account page. Reachable by every signed-in role, including
 * patients — previously the only password route lived behind the admin guard,
 * so non-admin staff could never change their password.
 */
class ProfileController extends Controller
{
    protected $title = 'My account';
    protected $active = '';

    public function index()
    {
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier', 'patient']);
        $user = Auth::user();
        $recent = fetch_all(
            'SELECT * FROM activity_logs WHERE user_id = ? ORDER BY id DESC LIMIT 15',
            [Auth::id()]
        );
        $patient = null;
        if (Auth::is_patient() && !empty($user['patient_id'])) {
            $patient = fetch('SELECT * FROM patients WHERE id = ?', [$user['patient_id']]);
        }
        $this->view('profile/index', compact('user', 'recent', 'patient'));
    }

    public function password()
    {
        csrf_check();
        if (!Auth::check()) redirect('auth/login');
        $u = Auth::user();
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (!password_verify($current, $u['password'])) {
            set_flash('error', 'Current password is incorrect.');
            back();
        }
        if (strlen($new) < 8) {
            set_flash('error', 'New password must be at least 8 characters.');
            back();
        }
        if ($new !== $confirm) {
            set_flash('error', 'New passwords do not match.');
            back();
        }
        if (password_verify($new, $u['password'])) {
            set_flash('error', 'The new password must be different from the current one.');
            back();
        }

        run('UPDATE users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), Auth::id()]);
        audit('update', 'auth', 'Changed own password');
        // Rotate the session id so a fixated session cannot survive a credential change.
        session_regenerate_id(true);
        set_flash('success', 'Password updated.');
        redirect('profile');
    }

    public function update_contact()
    {
        csrf_check();
        if (!Auth::check()) redirect('auth/login');
        $id = Auth::id();
        $before = Auth::user();

        $phone = post('phone') ?: null;
        $email = post('email') ?: null;
        $address = post('address') ?: null;

        // An omitted or blank field means "no value", so it is stored as NULL and must
        // not be run through email validation. Validating null made it impossible
        // to clear an existing email address.
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Invalid email address.');
            back();
        }

        run('UPDATE users SET phone = ?, email = ?, address = ? WHERE id = ?', [$phone, $email, $address, $id]);
        changelog('user', $id, $before, ['phone' => $phone, 'email' => $email, 'address' => $address]);
        audit('update', 'auth', 'Updated own contact details');
        set_flash('success', 'Contact details updated.');
        redirect('profile');
    }
}