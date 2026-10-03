<?php

class LogsController extends Controller
{
    protected $title = 'Activity Logs';
    protected $active = 'logs';

    public function __construct()
    {
        $this->guard(['admin']);
    }

    public function index()
    {
        $module = getp('module');
        $q = getp('q');
        $where = '1=1';
        $params = [];
        $modules = fetch_all('SELECT module, COUNT(*) AS c FROM activity_logs WHERE module IS NOT NULL AND module != "" GROUP BY module ORDER BY module');
        if ($module) {
            $where .= ' AND module = ?';
            $params[] = $module;
        }
        if ($q !== '') {
            $where .= ' AND (details LIKE ? OR action LIKE ? OR users.full_name LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        $list = fetch_all(
            "SELECT a.*, users.full_name AS user_name, users.username
             FROM activity_logs a LEFT JOIN users ON users.id = a.user_id
             WHERE $where ORDER BY a.id DESC LIMIT 300",
            $params
        );
        $this->view('logs/index', compact('list', 'modules', 'module', 'q'));
    }
}