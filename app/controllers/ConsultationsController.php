<?php

class ConsultationsController extends Controller
{
    protected $title = 'Consultations';
    protected $active = 'consultations';

    public function __construct()
    {
        $this->guard(['admin', 'doctor', 'nurse']);
    }

    public function index()
    {
        $date = getp('date');               // optional filter date
        $doctorId = (int)getp('doctor');    // optional filter doctor
        $where = [];
        $params = [];

        if (Auth::role() === 'doctor' && $doctorId === 0) {
            $doctorId = (int)Auth::id();
        }
        if ($doctorId > 0) {
            $where[] = 'c.doctor_id = ?';
            $params[] = $doctorId;
        }
        if ($date !== '' && strtotime($date)) {
            $where[] = 'c.visit_date = ?';
            $params[] = $date;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $list = fetch_all(
            "SELECT c.*, u.full_name AS doctor_name,
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name, p.patient_no
             FROM consultations c
             JOIN patients p ON p.id = c.patient_id
             LEFT JOIN users u ON u.id = c.doctor_id
             $whereSql
             ORDER BY c.visit_date DESC, c.id DESC LIMIT 200",
            $params
        );
        $doctors = fetch_all('SELECT id, full_name FROM users WHERE role = "doctor" AND active = 1 AND deleted_at IS NULL ORDER BY full_name');

        $this->view('consultations/index', compact('list', 'doctors', 'date', 'doctorId'));
    }

    public function create()
    {
        $patientId = (int)getp('patient', 0);
        $patient = null;
        if ($patientId) {
            $patient = fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId]);
        }
        $appointmentId = (int)getp('appointment', 0);
        $appointment = null;
        if ($appointmentId) {
            $appointment = fetch('SELECT * FROM appointments WHERE id = ?', [$appointmentId]);
            if ($appointment && !$patient) {
                $patient = fetch('SELECT * FROM patients WHERE id = ?', [$appointment['patient_id']]);
            }
        }
        $patients = fetch_all('SELECT id, patient_no, first_name, last_name, phone FROM patients WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT 500');
        $consultServices = fetch_all(
            "SELECT id, name, price FROM services
             WHERE category = 'consultation' AND active = 1 AND deleted_at IS NULL
             ORDER BY price, name"
        );
        $this->title = 'New consultation';
        $this->view('consultations/form', [
            'consultation' => null, 'patient' => $patient, 'appointment' => $appointment,
            'patients' => $patients, 'consultServices' => $consultServices,
        ]);
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
        $date = post('visit_date', date('Y-m-d'));
        if (!strtotime($date)) $date = date('Y-m-d');
        $visitTypes = ['outpatient', 'inpatient'];
        $visitType = in_array(post('visit_type', 'outpatient'), $visitTypes, true) ? post('visit_type', 'outpatient') : 'outpatient';

        // Clinician attribution: only a doctor may be recorded as the consulting
        // doctor. A nurse recording a triage consultation must not appear as the
        // treating doctor, so the field stays null for them.
        $doctorId = null;
        if (in_array(Auth::role(), ['doctor', 'admin'], true) && !post('doctor_id')) {
            $doctorId = Auth::id();
        }
        if (post('doctor_id')) {
            $candidate = (int)post('doctor_id');
            if (fetch_val('SELECT id FROM users WHERE id = ? AND role = "doctor" AND deleted_at IS NULL', [$candidate])) {
                $doctorId = $candidate;
            }
        }

        $serviceId = (int)post('service_id', 0);
        $service = $serviceId ? fetch(
            "SELECT id, name, price FROM services
             WHERE id = ? AND category = 'consultation' AND deleted_at IS NULL",
            [$serviceId]
        ) : null;

        $aid = (int)post('appointment_id');

        $id = tx(function () use ($patientId, $doctorId, $aid, $date, $visitType, $service) {
            run(
                'INSERT INTO consultations
                 (patient_id, doctor_id, appointment_id, visit_date, visit_type,
                  chief_complaint, history, examination,
                  temperature, blood_pressure, pulse, respiratory_rate, weight, height, spo2,
                  diagnosis, treatment_plan, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $patientId, $doctorId, $aid ?: null, $date, $visitType,
                    post('chief_complaint') ?: null, post('history') ?: null, post('examination') ?: null,
                    post('temperature') !== '' ? post('temperature') : null,
                    post('blood_pressure') ?: null,
                    post('pulse') !== '' ? post('pulse') : null,
                    post('respiratory_rate') !== '' ? post('respiratory_rate') : null,
                    post('weight') !== '' ? post('weight') : null,
                    post('height') !== '' ? post('height') : null,
                    post('spo2') !== '' ? post('spo2') : null,
                    post('diagnosis') ?: null, post('treatment_plan') ?: null, post('notes') ?: null,
                ]
            );
            $id = last_id();

            // Consultation fee was previously never charged: the record was filed
            // but no invoice line created, while the invoice lookup on the record
            // page expected one.
            if ($service && (float)$service['price'] > 0) {
                add_invoice_line($patientId, 'consultation', $id, $service['name'], 1, (float)$service['price']);
            }

            if ($aid) {
                run('UPDATE appointments SET status = "completed" WHERE id = ? AND status != "cancelled"', [$aid]);
            }
            return $id;
        });
        audit('create', 'consultations', "Consultation #$id for patient #$patientId");
        set_flash('success', $service && (float)$service['price'] > 0
            ? 'Consultation recorded. Fee added to the patient invoice.'
            : 'Consultation recorded.');
        redirect("consultations/show/$id");
    }

    public function show($id)
    {
        $c = $this->find($id);
        $patient = fetch('SELECT * FROM patients WHERE id = ?', [$c['patient_id']]);
        $doctor = fetch('SELECT full_name, specialty, license_no FROM users WHERE id = ?', [$c['doctor_id']]);
        $admission = fetch(
            'SELECT x.*, w.name AS ward_name FROM admissions x JOIN wards w ON w.id = x.ward_id
             WHERE x.patient_id = ? AND x.status = "admitted" ORDER BY x.id DESC LIMIT 1',
            [$c['patient_id']]
        );
        $prescriptions = fetch_all(
    'SELECT p.id, p.prescription_no, p.status, p.dispensed_at,
            pi.dosage, pi.frequency, pi.duration, i.name AS drug_name
     FROM prescriptions p
     JOIN prescription_items pi ON pi.prescription_id = p.id
     LEFT JOIN inventory_items i ON i.id = pi.item_id
     WHERE p.consultation_id = ? ORDER BY p.id, pi.id',
    [$id]
        );
        $labRequests = fetch_all('SELECT lr.* FROM lab_requests lr WHERE lr.consultation_id = ? ORDER BY lr.id', [$id]);
        $invoices = fetch_all(
            'SELECT i.* FROM invoices i
             JOIN invoice_items ii ON ii.invoice_id = i.id
             WHERE ii.item_type = "consultation" AND ii.ref_id = ? GROUP BY i.id',
            [$id]
        );
        $changes = fetch_all(
            'SELECT c.*, u.full_name AS user_name FROM record_changelogs c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.entity_type = "consultation" AND c.entity_id = ? ORDER BY c.id DESC LIMIT 20',
            [$id]
        );

        $this->view('consultations/show', compact('c', 'patient', 'doctor', 'admission', 'prescriptions', 'labRequests', 'invoices', 'changes'));
    }

    public function edit($id)
    {
        $c = $this->find($id);
        $patient = fetch('SELECT * FROM patients WHERE id = ?', [$c['patient_id']]);
        $consultServices = fetch_all(
            "SELECT id, name, price FROM services
             WHERE category = 'consultation' AND active = 1 AND deleted_at IS NULL
             ORDER BY price, name"
        );
        $doctors = fetch_all('SELECT id, full_name FROM users WHERE role = "doctor" AND active = 1 AND deleted_at IS NULL ORDER BY full_name');
        $this->title = 'Edit consultation';
        $this->view('consultations/form', [
            'consultation' => $c, 'patient' => $patient, 'appointment' => null,
            'patients' => [], 'consultServices' => $consultServices, 'doctors' => $doctors,
        ]);
    }

    public function update($id)
    {
        csrf_check();
        $before = $this->find($id);
        $date = post('visit_date', date('Y-m-d'));
        if (!strtotime($date)) $date = date('Y-m-d');
        $visitTypes = ['outpatient', 'inpatient'];
        $visitType = in_array(post('visit_type', 'outpatient'), $visitTypes, true) ? post('visit_type', 'outpatient') : 'outpatient';

        $after = [
            'visit_date' => $date,
            'visit_type' => $visitType,
            'chief_complaint' => post('chief_complaint') ?: null,
            'history' => post('history') ?: null,
            'examination' => post('examination') ?: null,
            'temperature' => post('temperature') !== '' ? post('temperature') : null,
            'blood_pressure' => post('blood_pressure') ?: null,
            'pulse' => post('pulse') !== '' ? post('pulse') : null,
            'respiratory_rate' => post('respiratory_rate') !== '' ? post('respiratory_rate') : null,
            'weight' => post('weight') !== '' ? post('weight') : null,
            'height' => post('height') !== '' ? post('height') : null,
            'spo2' => post('spo2') !== '' ? post('spo2') : null,
            'diagnosis' => post('diagnosis') ?: null,
            'treatment_plan' => post('treatment_plan') ?: null,
            'notes' => post('notes') ?: null,
        ];

        run(
            'UPDATE consultations SET visit_date = ?, visit_type = ?,
                chief_complaint = ?, history = ?, examination = ?,
                temperature = ?, blood_pressure = ?, pulse = ?, respiratory_rate = ?, weight = ?, height = ?, spo2 = ?,
                diagnosis = ?, treatment_plan = ?, notes = ?
             WHERE id = ?',
            array_merge(array_values($after), [$id])
        );
        changelog('consultation', $id, $before, $after, array_keys($after));
        audit('update', 'consultations', "Consultation #$id");
        set_flash('success', 'Consultation updated.');
        redirect("consultations/show/$id");
    }

    private function find($id)
    {
        $c = fetch('SELECT * FROM consultations WHERE id = ?', [(int)$id]);
        if (!$c) not_found();
        return $c;
    }
}