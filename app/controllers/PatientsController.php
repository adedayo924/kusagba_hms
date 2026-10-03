<?php

class PatientsController extends Controller
{
    protected $title = 'Patients';
    protected $active = 'patients';

    public function __construct()
    {
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier']);
    }

    public function index()
    {
        $q = getp('q');
        $page = max(1, (int)getp('page', 1));
        $per = 25;
        $offset = ($page - 1) * $per;

        $where = 'p.deleted_at IS NULL';
        $params = [];
        if ($q !== '') {
            $where .= " AND (p.patient_no LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.phone LIKE ?)";
            $like = "%$q%";
            $params = [$like, $like, $like, $like];
        }

        $total = (int)fetch_val("SELECT COUNT(*) FROM patients p WHERE $where", $params);
        $pages = max(1, (int)ceil($total / $per));
        if ($page > $pages) $page = $pages;
        $offset = ($page - 1) * $per;

        $patients = fetch_all(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM consultations c WHERE c.patient_id = p.id) AS visits
             FROM patients p WHERE $where
             ORDER BY p.id DESC LIMIT $per OFFSET $offset",
            $params
        );

        $this->view('patients/index', compact('q', 'patients', 'page', 'pages', 'total'));
    }

    public function create()
    {
        $this->title = 'New patient';
        $this->view('patients/form', ['patient' => null]);
    }

    public function store()
    {
        csrf_check();
        $data = $this->payload();
        $errors = $this->validate($data);
        if ($errors) {
            set_flash('error', implode(' ', $errors));
            back();
        }
        $id = tx(function () use ($data) {
            run(
                'INSERT INTO patients
                 (first_name, last_name, gender, dob, blood_group, phone, email, address, occupation,
                  next_of_kin_name, next_of_kin_phone, next_of_kin_relation, allergies, medical_history, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $data['first_name'], $data['last_name'], $data['gender'],
                    $data['dob'] ?: null, $data['blood_group'] ?: null, $data['phone'] ?: null,
                    $data['email'] ?: null, $data['address'] ?: null, $data['occupation'] ?: null,
                    $data['nk_name'] ?: null, $data['nk_phone'] ?: null, $data['nk_relation'] ?: null,
                    $data['allergies'] ?: null, $data['medical_history'] ?: null, $data['notes'] ?: null,
                    Auth::id(),
                ]
            );
            $id = last_id();
            $no = 'PT-' . date('ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
            run('UPDATE patients SET patient_no = ? WHERE id = ?', [$no, $id]);
            return $id;
        });
        audit('create', 'patients', "Patient #{$id}");
        set_flash('success', 'Patient registered successfully.');
        redirect("patients/show/$id");
    }

    public function show($id)
    {
        $patient = $this->find($id);
        $visits = fetch_all('SELECT c.*, u.full_name AS doctor_name FROM consultations c LEFT JOIN users u ON u.id = c.doctor_id WHERE c.patient_id = ? ORDER BY c.visit_date DESC, c.id DESC LIMIT 30', [$id]);
        $appointments = fetch_all('SELECT a.*, u.full_name AS doctor_name FROM appointments a LEFT JOIN users u ON u.id = a.doctor_id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC LIMIT 20', [$id]);
        $admissions = fetch_all('SELECT x.*, w.name AS ward_name FROM admissions x JOIN wards w ON w.id = x.ward_id WHERE x.patient_id = ? ORDER BY x.admitted_at DESC LIMIT 10', [$id]);
        $prescriptions = fetch_all(
    'SELECT p.id, p.prescription_no, p.status, p.dispensed_at, p.prescribed_at,
            pi.dosage, pi.frequency, pi.duration, i.name AS drug_name,
            u.full_name AS dispensed_by_name
     FROM prescriptions p
     JOIN prescription_items pi ON pi.prescription_id = p.id
     LEFT JOIN inventory_items i ON i.id = pi.item_id
     LEFT JOIN users u ON u.id = p.dispensed_by
     WHERE p.patient_id = ? ORDER BY p.id DESC, pi.id LIMIT 20',
    [$id]
        );
        $labRequests = fetch_all('SELECT lr.* FROM lab_requests lr WHERE lr.patient_id = ? ORDER BY lr.id DESC LIMIT 20', [$id]);
        $invoices = fetch_all('SELECT * FROM invoices WHERE patient_id = ? ORDER BY id DESC LIMIT 20', [$id]);
        $account = fetch('SELECT id, username, active, last_login FROM users WHERE patient_id = ? AND deleted_at IS NULL', [$id]);
        $changes = fetch_all(
            'SELECT c.*, u.full_name AS user_name FROM record_changelogs c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.entity_type = "patient" AND c.entity_id = ? ORDER BY c.id DESC LIMIT 20',
            [$id]
        );

        $this->view('patients/show', [
            'patient' => $patient, 'visits' => $visits, 'appointments' => $appointments,
            'admissions' => $admissions, 'prescriptions' => $prescriptions,
            'labRequests' => $labRequests, 'invoices' => $invoices, 'account' => $account,
            'changes' => $changes,
        ]);
    }

    public function account_store($patientId)
    {
        csrf_check();
        $this->guard(['admin', 'receptionist']);
        $patient = $this->find($patientId);
        $username = post('username');
        $password = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,}$/', $username)) {
            set_flash('error', 'Username must be at least 3 characters using letters, numbers, dots, dashes or underscores.');
            back();
        }
        if (strlen($password) < 8) {
            set_flash('error', 'Portal password must be at least 8 characters.');
            back();
        }
        if (fetch_val('SELECT id FROM users WHERE username = ?', [$username])) {
            set_flash('error', 'That username is already taken.');
            back();
        }
        if (fetch_val('SELECT id FROM users WHERE patient_id = ?', [$patientId])) {
            set_flash('error', 'This patient already has a portal account.');
            back();
        }
        run(
            'INSERT INTO users (username, password, role, full_name, gender, phone, email, patient_id, active)
             VALUES (?,?,?,?,?,?,?,?,1)',
            [$username, password_hash($password, PASSWORD_DEFAULT), 'patient',
             patient_full_name($patient), $patient['gender'] ?: null, $patient['phone'] ?: null,
             $patient['email'] ?: null, (int)$patientId]
        );
        audit('create', 'patients', "Portal account for patient #$patientId");
        set_flash('success', 'Portal account created. The patient can now log in.');
        back();
    }

    public function account_toggle($accountId)
    {
        csrf_check();
        $this->guard(['admin', 'receptionist']);
        $acc = fetch(
            'SELECT u.id, u.username, u.active, u.full_name FROM users u
             WHERE u.id = ? AND u.role = "patient" AND u.deleted_at IS NULL',
            [$accountId]
        );
        if (!$acc) not_found();
        $newActive = $acc['active'] ? 0 : 1;
        run('UPDATE users SET active = ? WHERE id = ?', [$newActive, $acc['id']]);
        audit('update', 'patients', "Portal account {$acc['username']} " . ($newActive ? 'enabled' : 'disabled'));
        set_flash('success', 'Account ' . ($newActive ? 'activated' : 'deactivated') . '.');
        back();
    }

    public function edit($id)
    {
        $patient = $this->find($id);
        $this->title = 'Edit patient';
        $this->view('patients/form', compact('patient'));
    }

    public function update($id)
    {
        csrf_check();
        $before = $this->find($id);
        $data = $this->payload();
        unset($data['first_name'], $data['last_name'], $data['gender'], $data['dob']);

        $after = [
            'blood_group' => $data['blood_group'] ?: null,
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'address' => $data['address'] ?: null,
            'occupation' => $data['occupation'] ?: null,
            'next_of_kin_name' => $data['nk_name'] ?: null,
            'next_of_kin_phone' => $data['nk_phone'] ?: null,
            'next_of_kin_relation' => $data['nk_relation'] ?: null,
            'allergies' => $data['allergies'] ?: null,
            'medical_history' => $data['medical_history'] ?: null,
            'notes' => $data['notes'] ?: null,
        ];
        $errors = $this->validate($data);
        if ($errors) {
            set_flash('error', implode(' ', $errors));
            back();
        }

        run(
            'UPDATE patients SET
                blood_group = ?, phone = ?, email = ?, address = ?, occupation = ?,
                next_of_kin_name = ?, next_of_kin_phone = ?, next_of_kin_relation = ?,
                allergies = ?, medical_history = ?, notes = ?, updated_at = NOW()
             WHERE id = ?',
            array_merge(array_values($after), [$id])
        );
        changelog('patient', $id, $before, $after, array_keys($after));
        audit('update', 'patients', "Patient #{$id}");
        set_flash('success', 'Patient record updated.');
        redirect("patients/show/$id");
    }

    /**
     * Archive the patient. Never hard-deletes: consultations, prescriptions, lab
     * requests and invoices reference this row, and an invoice with no reachable
     * patient is an uncollectable balance.
     */
    public function delete($id)
    {
        csrf_check();
        $this->guard(['admin']);
        $p = $this->find($id);

        // A live inpatient cannot be archived — discharge them first.
        $active = (int)fetch_val('SELECT COUNT(*) FROM admissions WHERE patient_id = ? AND status = "admitted"', [$id]);
        if ($active > 0) {
            set_flash('error', 'This patient is currently admitted. Discharge them before archiving the record.');
            back();
        }

        $outstanding = (float)fetch_val(
            'SELECT IFNULL(SUM(total - paid_amount),0) FROM invoices WHERE patient_id = ? AND status != "void" AND status != "paid"',
            [$id]
        );
        if ($outstanding > 0) {
            set_flash('error', 'This patient has an outstanding balance of ' . money($outstanding) . '. Settle the account before archiving.');
            back();
        }

        soft_delete('patients', $id);
        // Their portal login must stop working immediately.
        run('UPDATE users SET active = 0 WHERE patient_id = ?', [$id]);
        audit('archive', 'patients', "Patient #{$id} ({$p['patient_no']})");
        set_flash('success', 'Patient record archived. History and invoices remain intact.');
        redirect('patients');
    }

    public function restore($id)
    {
        csrf_check();
        $this->guard(['admin']);
        if (!fetch('SELECT id FROM patients WHERE id = ?', [$id])) not_found();
        soft_restore('patients', $id);
        audit('restore', 'patients', "Patient #$id");
        set_flash('success', 'Patient record restored.');
        redirect("patients/show/$id");
    }

    public function search()
    {
        $q = getp('q');
        $limit = 20;
        if ($q === '') return $this->json([]);
        $like = "%$q%";
        $rows = fetch_all(
            'SELECT id, patient_no, first_name, last_name, phone, gender, dob
             FROM patients
             WHERE deleted_at IS NULL
               AND (patient_no LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)
             ORDER BY last_name ASC LIMIT ' . (int)$limit,
            [$like, $like, $like, $like]
        );
        $out = array_map(function ($r) {
            $r['label'] = patient_full_name($r) . ' — ' . $r['patient_no'] . ($r['phone'] ? ' (' . $r['phone'] . ')' : '');
            return $r;
        }, $rows);
        $this->json($out);
    }

    public function export()
    {
        $rows = fetch_all('SELECT * FROM patients WHERE deleted_at IS NULL ORDER BY id');
        $cols = ['patient_no', 'first_name', 'last_name', 'gender', 'dob', 'phone', 'email', 'address', 'occupation', 'blood_group', 'next_of_kin_name', 'next_of_kin_phone', 'allergies', 'created_at'];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=patients_' . date('Ymd_His') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_map('ucfirst', str_replace('_', ' ', $cols)));
        foreach ($rows as $r) {
            fputcsv($out, array_map(fn($c) => $r[$c] ?? '', $cols));
        }
        fclose($out);
        exit;
    }

    private function payload()
    {
        return [
            'first_name' => post('first_name'),
            'last_name'  => post('last_name'),
            'gender'     => post('gender', 'Male'),
            'dob'        => post('dob'),
            'blood_group' => post('blood_group'),
            'phone'      => post('phone'),
            'email'      => post('email'),
            'address'    => post('address'),
            'occupation' => post('occupation'),
            'nk_name'    => post('next_of_kin_name'),
            'nk_phone'   => post('next_of_kin_phone'),
            'nk_relation' => post('next_of_kin_relation'),
            'allergies'  => post('allergies'),
            'medical_history' => post('medical_history'),
            'notes'      => post('notes'),
        ];
    }

    private function validate($d)
    {
        $errors = [];
        if ($d['first_name'] === '' || $d['last_name'] === '') $errors[] = 'First and last name are required.';
        if (!in_array($d['gender'], ['Male', 'Female', 'Other'], true)) $errors[] = 'Invalid gender.';
        if ($d['dob'] !== '' && !strtotime($d['dob'])) $errors[] = 'Invalid date of birth.';
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        return $errors;
    }

    private function find($id)
    {
        $p = fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [(int)$id]);
        if (!$p) not_found();
        return $p;
    }
}