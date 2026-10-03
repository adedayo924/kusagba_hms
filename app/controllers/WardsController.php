<?php

class WardsController extends Controller
{
    protected $title = 'Wards';
    protected $active = 'wards';

    public function __construct()
    {
        $this->guard(['admin', 'nurse']);
    }

    public function index()
    {
        $showArchived = getp('archived') === '1';
        $wards = fetch_all(
            'SELECT w.*,
                    (SELECT COUNT(*) FROM admissions a WHERE a.ward_id = w.id AND a.status = "admitted" AND a.deleted_at IS NULL) AS occupied
             FROM wards w '
            . ($showArchived ? '' : 'WHERE w.deleted_at IS NULL ')
            . 'ORDER BY w.deleted_at IS NOT NULL, w.name'
        );
        $this->view('wards/index', compact('wards', 'showArchived'));
    }

    /**
     * Single dispatch for create and edit. A hidden `id` decides which, so the form
     * works identically with or without JavaScript. Previously the edit buttons
     * rewrote the form action in JS, and a JS failure silently created duplicates.
     */
    public function save()
    {
        csrf_check();
        $this->guard(['admin']);
        $id = (int)post('id', 0);
        $name = post('name');
        if ($name === '') {
            set_flash('error', 'Ward name is required.');
            back();
        }
        $total = max(1, (int)post('total_beds', 1));
        $dept = post('department') ?: null;
        $notes = post('notes') ?: null;

        if ($id > 0) {
            $before = fetch('SELECT * FROM wards WHERE id = ?', [$id]);
            if (!$before) not_found();
            run('UPDATE wards SET name = ?, department = ?, total_beds = ?, notes = ? WHERE id = ?',
                [$name, $dept, $total, $notes, $id]);
            changelog('ward', $id, $before, ['name' => $name, 'department' => $dept, 'total_beds' => $total, 'notes' => $notes]);
            audit('update', 'wards', "Ward #$id");
            set_flash('success', 'Ward updated.');
        } else {
            run('INSERT INTO wards (name, department, total_beds, notes) VALUES (?,?,?,?)',
                [$name, $dept, $total, $notes]);
            $id = last_id();
            audit('create', 'wards', "Ward: $name");
            set_flash('success', 'Ward added.');
        }
        redirect('wards');
    }

    // Kept for direct/bookmarked URLs and progressive enhancement.
    public function store()
    {
        $this->save();
    }

    public function update($id)
    {
        $_POST['id'] = (int)$id;
        $this->save();
    }

    public function delete($id)
    {
        csrf_check();
        $this->guard(['admin']);
        // Archived rather than deleted: discharged admissions still reference the ward.
        $active = (int)fetch_val('SELECT COUNT(*) FROM admissions WHERE ward_id = ? AND status = "admitted" AND deleted_at IS NULL', [$id]);
        if ($active > 0) {
            set_flash('error', 'Cannot archive a ward with active admissions.');
            back();
        }
        soft_delete('wards', $id);
        audit('archive', 'wards', "Ward #$id");
        set_flash('success', 'Ward archived.');
        redirect('wards');
    }

    public function restore($id)
    {
        csrf_check();
        $this->guard(['admin']);
        if (!fetch('SELECT id FROM wards WHERE id = ?', [$id])) not_found();
        soft_restore('wards', $id);
        audit('restore', 'wards', "Ward #$id");
        set_flash('success', 'Ward restored.');
        redirect('wards');
    }
}