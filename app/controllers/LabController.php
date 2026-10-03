<?php

class LabController extends Controller
{
    protected $title = 'Laboratory';
    protected $active = 'lab';

    public function __construct()
    {
        $this->guard(['admin', 'lab', 'doctor', 'nurse']);
    }

    public function index()
    {
        $status = getp('status', '');
        $q = getp('q');
        $where = 'r.id > 0';
        $params = [];
        if (in_array($status, ['pending', 'sample_collected', 'processing', 'resulted', 'cancelled'], true)) {
            $where .= ' AND r.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where .= ' AND (r.request_no LIKE ? OR CONCAT(p.first_name," ",p.last_name) LIKE ?)';
            $like = "%$q%";
            $params[] = $like;
            $params[] = $like;
        }
        $list = fetch_all(
            "SELECT r.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name, p.patient_no,
                    COUNT(t.id) AS test_count
             FROM lab_requests r
             JOIN patients p ON p.id = r.patient_id
             LEFT JOIN lab_request_tests t ON t.lab_request_id = r.id
             WHERE $where
             GROUP BY r.id
             ORDER BY r.requested_at DESC LIMIT 200",
            $params
        );
        $this->view('lab/index', compact('list', 'status', 'q'));
    }

    public function show($id)
    {
        $r = fetch(
            "SELECT r.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name, p.patient_no,
                    p.gender, p.dob, p.blood_group,
                    u.full_name AS requester_name
             FROM lab_requests r
             JOIN patients p ON p.id = r.patient_id
             LEFT JOIN users u ON u.id = r.requested_by
             WHERE r.id = ?",
            [$id]
        );
        if (!$r) not_found();

        $items = fetch_all(
            "SELECT t.id AS req_test_id, t.result_value, t.result_note,
                    lt.name, lt.category, lt.unit, lt.normal_range
             FROM lab_request_tests t
             JOIN lab_tests lt ON lt.id = t.test_id
             WHERE t.lab_request_id = ?
             ORDER BY lt.category, lt.name",
            [$id]
        );
        $canEnter = in_array(Auth::role(), ['admin', 'lab'], true)
            && in_array($r['status'], ['pending', 'sample_collected', 'processing'], true);
        $this->title = 'Lab ' . $r['request_no'];
        $this->view('lab/show', compact('r', 'items', 'canEnter'));
    }

    public function create()
    {
        $this->guard(['admin', 'lab', 'doctor', 'nurse']);
        $patients = fetch_all('SELECT id, patient_no, first_name, last_name FROM patients WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT 500');
        $tests = fetch_all('SELECT * FROM lab_tests WHERE active = 1 AND deleted_at IS NULL ORDER BY category, name');
        $preselectPatientId = 0;
        $preselectConsultationId = (int)getp('consultation');
        if ($preselectConsultationId) {
            $preselectPatientId = (int)fetch_val('SELECT patient_id FROM consultations WHERE id = ?', [$preselectConsultationId]);
        }
        $this->title = 'New lab request';
        $this->view('lab/form', compact('patients', 'tests', 'preselectPatientId', 'preselectConsultationId'));
    }

    /**
     * Create a lab request and its test lines in one transaction, then raise the
     * matching invoice so an unbilled request cannot be lost.
     */
    public function store()
    {
        csrf_check();
        $this->guard(['admin', 'lab', 'doctor', 'nurse']);
        $patientId = (int)post('patient_id', 0);
        $testIds = (array)post('test_id', []);
        $testIds = array_values(array_unique(array_filter(array_map('intval', $testIds))));

        if ($patientId <= 0 || !$testIds) {
            set_flash('error', 'Select a patient and at least one test.');
            back();
        }
        if (!fetch('SELECT id FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId])) {
            set_flash('error', 'Patient not found.');
            back();
        }
        $doctorId = in_array(Auth::role(), ['doctor', 'lab'], true) ? (int)Auth::id() : null;
        $requestedBy = (int)Auth::id();

        try {
            $requestId = tx(function () use ($patientId, $testIds, $doctorId, $requestedBy) {
                $no = next_ticket('lab_requests', 'request_no', 'LR');
                run(
                    'INSERT INTO lab_requests
                        (request_no, patient_id, consultation_id, doctor_id, priority, clinical_notes, status, requested_by, requested_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $no,
                        $patientId,
                        post('consultation_id') ?: null,
                        $doctorId,
                        in_array(post('priority'), ['routine', 'urgent', 'stat'], true) ? post('priority') : 'routine',
                        post('clinical_notes') ?: null,
                        'pending',
                        $requestedBy,
                        date('Y-m-d H:i:s'),
                    ]
                );
                $id = last_id();
                foreach ($testIds as $testId) {
                    $t = fetch('SELECT name, price FROM lab_tests WHERE id = ? AND active = 1 AND deleted_at IS NULL', [$testId]);
                    if (!$t) continue;
                    run('INSERT INTO lab_request_tests (lab_request_id, test_id) VALUES (?, ?)', [$id, $testId]);
                    add_invoice_line($patientId, 'lab_request', $id, $t['name'], 1, $t['price']);
                }
                return $id;
            });
        } catch (RuntimeException $e) {
            set_flash('error', $e->getMessage());
            back();
        }
        audit('create', 'lab', "Lab request #$requestId");
        set_flash('success', 'Lab request created.');
        redirect('lab/' . $requestId);
    }

    public function results($id)
    {
        csrf_check();
        $this->guard(['admin', 'lab']);
        $r = fetch('SELECT * FROM lab_requests WHERE id = ?', [$id]);
        if (!$r || $r['status'] === 'cancelled') back();

        $results = post('results', []);
        if (!is_array($results)) $results = [];
        $ids = array_filter(array_map('intval', array_keys($results)));
        if (!$ids) {
            set_flash('error', 'No results submitted.');
            back();
        }
        $allowed = fetch_all(
            'SELECT t.id FROM lab_request_tests t
             WHERE t.lab_request_id = ? AND t.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')',
            array_merge([$id], $ids)
        );
        $allowed = array_map('intval', array_column($allowed, 'id'));
        foreach ($results as $rid => $v) {
            $rid = (int)$rid;
            if (!in_array($rid, $allowed, true)) continue;
            if (is_array($v)) {
                $value = trim((string)($v['value'] ?? '')) ?: null;
                $note = trim((string)($v['note'] ?? '')) ?: null;
            } else {
                $value = trim((string)$v) ?: null;
                $note = null;
            }
            run(
                'UPDATE lab_request_tests SET result_value = ?, result_note = ?, resulted_by = ?, resulted_at = ? WHERE id = ?',
                [$value, $note, (int)Auth::id(), date('Y-m-d H:i:s'), $rid]
            );
        }
        run("UPDATE lab_requests SET status = 'resulted', resulted_by = ?, resulted_at = ? WHERE id = ?",
            [(int)Auth::id(), date('Y-m-d H:i:s'), $id]);
        audit('update', 'lab', "Lab results #$id");
        set_flash('success', 'Results saved.');
        redirect('lab/' . $id);
    }

    public function collect($id)
    {
        csrf_check();
        $this->guard(['admin', 'lab', 'nurse']);
        $r = fetch('SELECT * FROM lab_requests WHERE id = ?', [$id]);
        if (!$r) not_found();
        if ($r['status'] !== 'pending') {
            set_flash('error', 'Only a pending request can have its sample collected.');
            back();
        }
        run("UPDATE lab_requests SET status = 'sample_collected' WHERE id = ?", [$id]);
        audit('update', 'lab', "Sample collected #$id");
        set_flash('success', 'Sample collected.');
        redirect('lab/' . $id);
    }

    public function cancel($id)
    {
        csrf_check();
        $this->guard(['admin', 'lab']);
        if (!fetch('SELECT id FROM lab_requests WHERE id = ?', [$id])) not_found();
        run("UPDATE lab_requests SET status = 'cancelled' WHERE id = ?", [$id]);
        audit('cancel', 'lab', "Lab request #$id");
        set_flash('success', 'Lab request cancelled.');
        redirect('lab');
    }

    // --- Test catalogue ---

    public function tests()
    {
        $this->guard(['admin', 'lab']);
        $this->active = 'lab/tests';
        $showArchived = (getp('archived', '') === '1');
        if ($showArchived) {
            $tests = fetch_all('SELECT * FROM lab_tests WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC, category, name');
        } else {
            $tests = fetch_all('SELECT * FROM lab_tests WHERE deleted_at IS NULL ORDER BY category, name');
        }
        $this->title = 'Lab tests';
        $this->view('lab/tests', compact('tests', 'showArchived'));
    }

    /** Single dispatch for create and edit, decided by a hidden `id`. */
    public function tests_save()
    {
        csrf_check();
        $this->guard(['admin', 'lab']);
        $id = (int)post('id', 0);
        $name = post('name');
        if ($name === '') {
            set_flash('error', 'Test name is required.');
            back();
        }
        $code = post('code') ?: null;
        $category = post('category') ?: null;
        $price = max(0, (float)post('price', 0));
        $unit = post('unit') ?: null;
        $range = post('normal_range') ?: null;

        if ($id > 0) {
            $before = fetch('SELECT * FROM lab_tests WHERE id = ?', [$id]);
            if (!$before) not_found();
            run('UPDATE lab_tests SET code = ?, name = ?, category = ?, price = ?, unit = ?, normal_range = ? WHERE id = ?',
                [$code, $name, $category, $price, $unit, $range, $id]);
            changelog('lab_test', $id, $before, [
                'code' => $code, 'name' => $name, 'category' => $category, 'price' => $price,
            ]);
            audit('update', 'lab', "Test #$id");
            set_flash('success', 'Lab test updated.');
        } else {
            run('INSERT INTO lab_tests (code, name, category, price, unit, normal_range, active) VALUES (?, ?, ?, ?, ?, ?, 1)',
                [$code, $name, $category, $price, $unit, $range]);
            $newId = last_id();
            changelog('lab_test', $newId, [], [
                'code' => $code, 'name' => $name, 'category' => $category, 'price' => $price,
            ]);
            audit('create', 'lab', "Test #$newId ($name)");
            set_flash('success', 'Lab test added.');
        }
        redirect('lab/tests');
    }

    /** Wrapper kept for the legacy route. */
    public function tests_store()
    {
        $_POST['id'] = 0;
        $this->tests_save();
    }

    /** Wrapper kept for the legacy route. */
    public function tests_update($id)
    {
        $_POST['id'] = (int)$id;
        $this->tests_save();
    }

    /**
     * Archive a test rather than deleting it: results already recorded against it
     * must keep their reference range and name.
     */
    public function tests_delete($id)
    {
        csrf_check();
        $this->guard(['admin']);
        $t = fetch('SELECT name FROM lab_tests WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$t) not_found();
        soft_delete('lab_tests', $id, ['active' => 0]);
        audit('archive', 'lab', "Lab test #$id ({$t['name']})");
        set_flash('success', 'Lab test archived. Results already recorded against it are retained.');
        redirect('lab/tests');
    }

    public function tests_restore($id)
    {
        csrf_check();
        $this->guard(['admin', 'lab']);
        $t = fetch('SELECT name FROM lab_tests WHERE id = ?', [$id]);
        if (!$t) not_found();
        soft_restore('lab_tests', $id);
        audit('restore', 'lab', "Lab test #$id ({$t['name']})");
        set_flash('success', 'Lab test restored.');
        redirect('lab/tests');
    }
}