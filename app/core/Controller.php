<?php

class Controller
{
    protected $title = 'Dashboard';
    protected $active = '';

    protected function guard($roles)
    {
        Auth::guard($roles);
    }

    protected function view($view, $data = [], $layout = 'main')
    {
        extract($data);
        $content = APP_PATH . '/views/' . $view . '.php';
        if (!file_exists($content)) {
            http_response_code(500);
            exit('View not found: ' . e($view));
        }
        $page_title = $this->title;
        $active = $this->active;
        if (!empty($layout)) {
            $layoutFile = APP_PATH . '/views/layouts/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
                return;
            }
        }
        require $content;
    }

    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}