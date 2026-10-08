<?php

class AdmissionsController extends Controller
{
    protected $title = 'Admissions';
    protected $active = 'admissions';

    public function __construct()
    {
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist']);
    }

    public function index()
    {
        $status = getp('status', 'admitted');
        $q = getp('q');
        $where = [];
        $params = [];
        if (in_array($status, ['admitted', 'discharged'], true)) {
            $where[] = 'x.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = '(x.admission_no LIKE ? OR CONCAT(p.first_name," ",p.last_name) LIKE ?)';
            $like = "%$q%";
            $params[] = $like;
            $params[] = $like;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $list = fetch_all(
            "SELECT x.*, w.name AS ward_name, CONCAT(p.first_name,' ',p.last_name) AS patient_name, p.patient_no,
                    cu.full_name AS consultant_name
             FROM admissions x
             JOIN wards w ON w.id = x.ward_id
             JOIN patients p ON p.id = x.patient_id
             LEFT JOIN users cu ON cu.id = x.consultant_id
             $whereSql
             ORDER BY x.admitted_at DESC LIMIT 200",
            $params
        );
        $this->view('admissions/index', compact('list', 'status', 'q'));
    }

    public function create()
    {
        $patientId = (int)getp('patient', 0);
        $patient = $patientId ? fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId]) : null;
        $patients = fetch_all('SELECT id, patient_no, first_name, last_name, phone FROM patients WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT 500');
        $wards = fetch_all(
            'SELECT w.*,
                    (SELECT COUNT(*) FROM admissions a WHERE a.ward_id = w.id AND a.status = "admitted" AND a.deleted_at IS NULL) AS occupied
             FROM wards w WHERE w.deleted_at IS NULL ORDER BY w.name'
        );
        $consultants = fetch_all('SELECT id, full_name FROM users WHERE role = "doctor" AND active = 1 AND deleted_at IS NULL ORDER BY full_name');
        $this->title = 'New admission';
        $this->view('admissions/form', compact('patients', 'wards', 'consultants', 'patient'));
    }

    public function store()
    {
        csrf_check();
        $patientId = (int)post('patient_id');
        $patient = fetch('SELECT id FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId]);
        if (!$patient) {
            set_flash('error', 'Please select a patient.');
            back();
        }
        $wardId = (int)post('ward_id');
        if (!fetch('SELECT id FROM wards WHERE id = ? AND deleted_at IS NULL', [$wardId])) {
            set_flash('error', 'Please select a ward.');
            back();
        }
        $consultantId = (int)post('consultant_id', 0);
        if ($consultantId && !fetch_val('SELECT id FROM users WHERE id = ? AND role = "doctor" AND deleted_at IS NULL', [$consultantId])) {
            $consultantId = 0;
        }

        // A patient with a live admission would double-book a bed and split the
        // clinical record across two stays; discharge first.
        $alreadyAdmitted = (int)fetch_val(
            'SELECT COUNT(*) FROM admissions WHERE patient_id = ? AND status = "admitted" AND deleted_at IS NULL',
            [$patientId]
        );
        if ($alreadyAdmitted) {
            set_flash('error', 'This patient already has an active admission. Discharge it before admitting again.');
            back();
        }

        // Capacity is checked *inside* the transaction with the ward row locked.
        // The previous check-then-insert let two clerks fill the same last bed.
        $result = tx(function () use ($patientId, $wardId, $consultantId) {
            $ward = fetch('SELECT id, name, total_beds FROM wards WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$wardId]);
            if (!$ward) {
                return ['error' => 'Please select a ward.'];
            }
            $occupied = (int)fetch_val('SELECT COUNT(*) FROM admissions WHERE ward_id = ? AND status = "admitted" AND deleted_at IS NULL', [$wardId]);
            if ($occupied >= (int)$ward['total_beds']) {
                return ['error' => $ward['name'] . ' is full (' . $ward['total_beds'] . ' beds).'];
            }

            run(
                'INSERT INTO admissions (admission_no, patient_id, ward_id, bed_label, consultant_id,
                        diagnosis_on_admit, amount_per_day, admitted_by, admitted_at)
                 VALUES (?,?,?,?,?,?,?,?,NOW())',
                [
                    'TMP', $patientId, $wardId, post('bed_label') ?: null, $consultantId ?: null,
                    post('diagnosis_on_admit') ?: null, (float)post('amount_per_day', 0), Auth::id(),
                ]
            );
            $id = last_id();
            run('UPDATE admissions SET admission_no = ? WHERE id = ?',
                ['ADM-' . date('ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT), $id]);
            return ['id' => $id];
        });

        if (isset($result['error'])) {
            set_flash('error', $result['error']);
            back();
        }
        $id = $result['id'];
        audit('create', 'admissions', "Admission #$id patient #$patientId");
        set_flash('success', 'Patient admitted.');
        redirect("admissions/show/$id");
    }

    public function show($id)
    {
        $x = $this->find($id);
        $patient = fetch('SELECT * FROM patients WHERE id = ?', [$x['patient_id']]);
        $ward = fetch('SELECT * FROM wards WHERE id = ?', [$x['ward_id']]);
        $consultant = fetch('SELECT full_name FROM users WHERE id = ?', [$x['consultant_id']]);
        $invoices = fetch_all(
            'SELECT i.* FROM invoices i
             JOIN invoice_items ii ON ii.invoice_id = i.id
             WHERE ii.item_type = "admission" AND ii.ref_id = ? GROUP BY i.id',
            [$id]
        );
        $this->view('admissions/show', compact('x', 'patient', 'ward', 'consultant', 'invoices'));
    }

    public function discharge($id)
    {
        csrf_check();
        if (!$this->find($id)) not_found();

        $ok = tx(function () use ($id) {
            // Lock the admission row so two simultaneous submits cannot both pass
            // the status check and each add a ward-charge line.
            $x = fetch('SELECT * FROM admissions WHERE id = ? FOR UPDATE', [(int)$id]);
            if (!$x) return ['error' => 'Admission not found.'];
            if ($x['status'] === 'discharged') {
                return ['error' => 'Patient already discharged.'];
            }

            $now = date('Y-m-d H:i:s');
            // Bill whole days, with the admission day counted as day 1.
            $days = (int)ceil((strtotime($now) - strtotime($x['admitted_at'])) / 86400);
            if ($days < 1) $days = 1;

            run('UPDATE admissions SET status = "discharged", discharged_at = ?, discharge_summary = ? WHERE id = ?', [
                $now, post('discharge_summary') ?: null, $id,
            ]);
            if ((float)$x['amount_per_day'] > 0) {
                add_invoice_line((int)$x['patient_id'], 'admission', $id,
                    'Ward admission — ' . $days . ' day(s)',
                    $days, (float)$x['amount_per_day']);
            }
            return ['days' => $days];
        });

        if (isset($ok['error'])) {
            set_flash('error', $ok['error']);
            redirect("admissions/show/$id");
        }
        audit('update', 'admissions', "Discharged admission #$id ({$ok['days']} day(s))");
        set_flash('success', 'Patient discharged. Ward charges were added to the open invoice.');
        redirect("admissions/show/$id");
    }

    /**
     * Archive the admission. A billed admission must not disappear: the ward-charge
     * invoice line references it and the patient's clinical history depends on it.
     */
    public function delete($id)
    {
        csrf_check();
        $this->guard(['admin']);
        $x = $this->find($id);
        if ($x['status'] === 'admitted') {
            set_flash('error', 'Discharge the patient before archiving this admission.');
            back();
        }
        soft_delete('admissions', $id);
        audit('archive', 'admissions', "Admission #$id");
        set_flash('success', 'Admission archived.');
        redirect('admissions');
    }

    public function restore($id)
    {
        csrf_check();
        $this->guard(['admin']);
        if (!fetch('SELECT id FROM admissions WHERE id = ?', [$id])) not_found();
        soft_restore('admissions', $id);
        audit('restore', 'admissions', "Admission #$id");
        set_flash('success', 'Admission restored.');
        redirect("admissions/show/$id");
    }

    private function find($id)
    {
        $a = fetch('SELECT * FROM admissions WHERE id = ?', [(int)$id]);
        if (!$a) not_found();
        return $a;
    }
}