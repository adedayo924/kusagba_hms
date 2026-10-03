<?php

if (!defined('DB_NAME')) {
    require dirname(__DIR__) . '/config.php';
}

$debug = defined('APP_DEBUG') && APP_DEBUG;
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set(defined('TIMEZONE') ? TIMEZONE : 'UTC');

define('APP_PATH', __DIR__);

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

spl_autoload_register(function ($class) {
    foreach (['core', 'models'] as $dir) {
        $file = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require __DIR__ . '/helpers/functions.php';
require __DIR__ . '/helpers/billing.php';
require __DIR__ . '/helpers/inventory.php';
require __DIR__ . '/core/Database.php';

// Drop sessions that have outlived the idle or absolute limit.
Auth::enforce_timeout();

// Override timezone from settings if stored; fall back to the config constant.
try {
    $tz = app_setting('timezone');
    if ($tz !== null && $tz !== '') {
        $dtz = new DateTimeZone($tz);
        date_default_timezone_set($tz);
    }
} catch (Throwable $e) {
    // Settings table may not exist yet (installer not run), or invalid tz — keep the constant.
    app_log('timezone_override_failed', $e->getMessage());
}

// Baseline security headers. Set only when headers have not been sent yet and
// the SAPI allows it (CLI writes no headers, which is fine).
if (!headers_sent() && PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

/**
 * Uncaught exceptions previously surfaced as a raw PHP fatal error, which on a
 * production server meant either a blank page or, with display_errors on, a full
 * stack trace including file paths, SQL and connection details in the browser.
 * Everything is now logged to the server log; the browser only ever sees a
 * generic message, with detail available when APP_DEBUG is deliberately on.
 */
set_exception_handler(function (Throwable $e) use ($debug) {
    error_log('[kusagba] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent() && PHP_SAPI !== 'cli') {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
        exit(1);
    }
    if ($debug) {
        echo '<h1>Application error</h1><pre>'
            . e(get_class($e) . ': ' . $e->getMessage() . "\n\n" . $e->getTraceAsString())
            . '</pre>';
        exit;
    }
    $file = APP_PATH . '/views/errors/500.php';
    if (file_exists($file)) {
        require $file;
    } else {
        echo '<h1>Something went wrong</h1><p>Please try again, or contact the administrator if the problem continues.</p>';
    }
    exit;
});

// Diagnostics: PHP warnings/notices were silently discarded, so real problems
// (bad array keys, undefined properties) produced no evidence at all. They are
// now logged rather than promoted to exceptions, so legacy code paths that
// tolerate a warning do not suddenly become fatal.
set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log('[kusagba] ' . $message . ' in ' . $file . ':' . $line);
    return true;
});