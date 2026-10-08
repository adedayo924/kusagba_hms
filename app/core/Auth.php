<?php

class Auth
{
    private static $user = null;
    private static $loaded = false;

    /** Idle timeout: a session left open on a shared ward computer expires. */
    const IDLE_TIMEOUT = 3600;      // 1 hour of inactivity
    const ABSOLUTE_TIMEOUT = 43200;  // 12 hours regardless of activity

    public static function attempt($username, $password)
    {
        // Compare against a dummy hash when the user is unknown so that the
        // response time does not reveal whether the username exists.
        $u = fetch('SELECT * FROM users WHERE username = ? AND active = 1 AND deleted_at IS NULL LIMIT 1', [$username]);
        if (!$u) {
            password_verify($password, '$2y$10$usesomesillystringforsalt0123456789abcdefghijklmnopqrs');
            return null;
        }
        if (!password_verify($password, $u['password'])) {
            return null;
        }
        self::login((int)$u['id']);
        return $u;
    }

    public static function login($uid)
    {
        self::$user = null;
        self::$loaded = false;
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$uid;
        $_SESSION['login_at'] = time();
        $_SESSION['last_seen'] = time();
        run('UPDATE users SET last_login = NOW() WHERE id = ?', [$uid]);
    }

    public static function logout()
    {
        self::$user = null;
        self::$loaded = false;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check()
    {
        if (!isset($_SESSION['uid'])) return false;
        return !self::expired();
    }

    /**
     * Enforce idle and absolute session limits. The original login_at value was
     * written but never checked, so sessions never actually expired.
     */
    private static function expired()
    {
        $now = time();
        $lastSeen = $_SESSION['last_seen'] ?? ($_SESSION['login_at'] ?? $now);
        if (($now - $lastSeen) > self::IDLE_TIMEOUT) return true;
        if (isset($_SESSION['login_at']) && ($now - $_SESSION['login_at']) > self::ABSOLUTE_TIMEOUT) return true;
        return false;
    }

    /** Call once per request after booting the session. Ends an expired session. */
    public static function enforce_timeout()
    {
        if (isset($_SESSION['uid']) && self::expired()) {
            self::logout();
            return false;
        }
        if (isset($_SESSION['uid'])) {
            $_SESSION['last_seen'] = time();
        }
        return true;
    }

    public static function id()
    {
        return $_SESSION['uid'] ?? null;
    }

    public static function user()
    {
        if (!self::$loaded) {
            self::$user = self::check()
                ? fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [self::id()])
                : null;
            self::$loaded = true;
        }
        return self::$user;
    }

    public static function role()
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function is_patient()
    {
        return self::role() === 'patient';
    }

    public static function userPatientId()
    {
        $u = self::user();
        return $u['patient_id'] ?? null;
    }

    public static function guard($roles)
    {
        if (!self::check()) {
            redirect('auth/login');
        }
        if (in_array(self::role(), (array)$roles, true)) {
            return;
        }
        forbidden();
    }
}