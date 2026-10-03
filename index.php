<?php

if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: install/');
    exit;
}

require __DIR__ . '/app/init.php';

function app_not_found()
{
    http_response_code(404);
    require APP_PATH . '/views/errors/404.php';
    exit;
}

$path = isset($_GET['url']) ? rtrim((string)$_GET['url'], '/') : '';
if ($path === '' && isset($_SERVER['PATH_INFO'])) {
    $path = trim($_SERVER['PATH_INFO'], '/');
}
if ($path === '') {
    $path = 'dashboard';
}

$parts = explode('/', $path);
$module = preg_replace('/[^a-z0-9_-]/i', '', $parts[0]);
$action = isset($parts[1]) && $parts[1] !== '' ? preg_replace('/[^a-z0-9_-]/i', '', $parts[1]) : 'index';
$params = array_map('urldecode', array_slice($parts, 2));

$cfile = __DIR__ . '/app/controllers/' . ucfirst($module) . 'Controller.php';
if (!file_exists($cfile)) {
    app_not_found();
}

require_once $cfile;
$class = ucfirst($module) . 'Controller';
if (!class_exists($class)) {
    app_not_found();
}

$controller = new $class();
if (!method_exists($controller, $action)) {
    app_not_found();
}

$controller->$action(...$params);