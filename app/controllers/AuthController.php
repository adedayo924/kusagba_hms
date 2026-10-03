<?php

class AuthController extends Controller
{
    protected $title = 'Sign in';

    /** Failed attempts allowed per IP+username pair before a temporary lockout. */
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_SECONDS = 900; // 15 minutes

    public function index()
    {
        redirect(Auth::check() ? (Auth::is_patient() ? 'portal' : 'dashboard') : 'auth/login');
    }

    public function login()
    {
        if (Auth::check()) {
            redirect(Auth::is_patient() ? 'portal' : 'dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $username = post('username');
            $password = (string)($_POST['password'] ?? '');

            // Brute-force throttle, counted in the database so that clearing
            // cookies does not reset the counter.
            $failures = login_failures_since($username, self::LOCKOUT_SECONDS);
            if ($failures >= self::MAX_ATTEMPTS) {
                set_flash('error', 'Too many failed attempts. Try again in '
                    . ceil(self::LOCKOUT_SECONDS / 60) . ' minute(s).');
                $this->view('auth/login', [], null);
                return;
            }

            $user = Auth::attempt($username, $password);
            if ($user) {
                audit('login', 'auth', 'Signed in');
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect($user['role'] === 'patient' ? 'portal' : 'dashboard');
            }

            record_login_failure($username);
            set_flash('error', 'Invalid username or password.');
        }
        $this->view('auth/login', [], null);
    }

    /** POST only: a GET here lets any third-party image log a user out. */
    public function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Preserve convenience bookmarks without allowing a cross-site trigger.
            redirect(Auth::check() ? (Auth::is_patient() ? 'portal' : 'dashboard') : 'auth/login');
        }
        csrf_check();
        audit('logout', 'auth', 'Signed out');
        Auth::logout();
        redirect('auth/login');
    }
}