<?php

class AppointmentsController extends Controller
{
    protected $title = 'Appointments';
    protected $active = 'appointments';

    public function __construct()
    {
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist']);
    }

    public function index()
    {
$date = getp('date', date('Y-m-d'));
        if (!strtotime($date)) $date = date('Y-m-d');

        $statuses = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'];
        $status = getp('status');
        if (!in_array($status, $statuses, true)) $status = '';

        $params = [$date];
        $statusWhere = '';
        if ($status !== '') {
            $statusWhere = 'AND a.status = ?';
            $params[] = $status;
        }

$list = fetch_all(
            "SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) AS patient_name, p.patient_no,
                    u.full_name AS doctor_name
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.doctor_id
             WHERE a.appointment_date = ? AND a.deleted_at IS NULL $statusWhere
             ORDER BY a.appointment_time ASC, a.id ASC",
$params
        );

        $this->view('appointments/index', compact('date', 'status', 'statuses', 'list'));
    }

    public function calendar()
    {
        $month = (int)getp('month', date('n'));
        $year = (int)getp('year', date('Y'));
        if ($month < 1) $month = 1;
        if ($month > 12) $month = 12;
        $first = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int)date('t', $first);
        $startDow = (int)date('w', $first); // 0=Sun

$counts = [];
        $firstDay = date('Y-m-01', $first);
        $lastDay = date('Y-m-t', $first);
        foreach (fetch_all('SELECT appointment_date, COUNT(*) c FROM appointments WHERE appointment_date BETWEEN ? AND ? AND deleted_at IS NULL GROUP BY appointment_date', [$firstDay, $lastDay]) as $r) {
            $counts[$r['appointment_date']] = (int)$r['c'];
        }

        $this->view('appointments/calendar', compact('month', 'year', 'daysInMonth', 'startDow', 'counts'));
    }

    public function create()
    {
        $patientId = (int)getp('patient', 0);
        $patient = null;
        if ($patientId) {
            $patient = fetch('SELECT * FROM patients WHERE id = ?', [$patientId]);
        }
$doctors = fetch_all('SELECT id, full_name, specialty FROM users WHERE role = "doctor" AND active = 1 AND deleted_at IS NULL ORDER BY full_name');
        $patients = fetch_all('SELECT id, patient_no, first_name, last_name, phone FROM patients WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT 500');
        $this->title = 'Book appointment';
        $this->view('appointments/form', [
            'appointment' => null,
            'doctors' => $doctors,
            'patients' => $patients,
            'prePatient' => $patient,
            'dateValue' => getp('date', date('Y-m-d')),
        ]);
    }

public function store()
    {
        csrf_check();
        $patientId = (int)post('patient_id');
        $patient = fetch('SELECT id, first_name, last_name FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId]);
        if (!$patient) {
            set_flash('error', 'Please select a patient.');
            back();
        }
        $date = post('appointment_date');
        $time = post('appointment_time');
        if ($date === '' || !strtotime($date)) {
            set_flash('error', 'A valid appointment date is required.');
            back();
        }
        $doctorId = $this->valid_doctor(post('doctor_id'));

        run(
            'INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?)',
            [
                $patientId, $doctorId, $date, $time ?: null,
                post('reason') ?: null, 'pending', post('notes') ?: null, Auth::id(),
            ]
        );
        $id = last_id();
        audit('create', 'appointments', "Appointment #$id for {$patient['first_name']} {$patient['last_name']}");
        set_flash('success', 'Appointment booked.');
        redirect('appointments?date=' . $date);
    }

    public function edit($id)
    {
        $a = $this->find($id);
$doctors = fetch_all('SELECT id, full_name, specialty FROM users WHERE role = "doctor" AND active = 1 AND deleted_at IS NULL ORDER BY full_name');
        $patient = fetch('SELECT * FROM patients WHERE id = ?', [$a['patient_id']]);
        $patients = fetch_all('SELECT id, patient_no, first_name, last_name, phone FROM patients WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT 500');
        $this->title = 'Edit appointment';
        $this->view('appointments/form', [
            'appointment' => $a, 'doctors' => $doctors, 'patients' => $patients, 'prePatient' => $patient, 'dateValue' => $a['appointment_date'],
        ]);
    }

    public function update($id)
    {
        csrf_check();
        $this->find($id);
        $date = post('appointment_date');
        if ($date === '' || !strtotime($date)) {
            set_flash('error', 'A valid appointment date is required.');
            back();
        }
        $status = post('status', 'pending');
        $allowed = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'];
        if (!in_array($status, $allowed, true)) $status = 'pending';

        run(
            'UPDATE appointments SET doctor_id = ?, appointment_date = ?, appointment_time = ?, reason = ?,
                    status = ?, notes = ? WHERE id = ?',
[$this->valid_doctor(post('doctor_id')), $date, post('appointment_time') ?: null, post('reason') ?: null, $status, post('notes') ?: null, $id]
        );
        audit('update', 'appointments', "Appointment #$id -> $status");
        set_flash('success', 'Appointment updated.');
        redirect('appointments?date=' . $date);
    }

    public function status($id)
    {
        csrf_check();
        $a = fetch('SELECT * FROM appointments WHERE id = ?', [(int)$id]);
        if (!$a) {
            $this->json(['error' => 'Appointment not found'], 404);
        }
        $status = post('status');
        $map = ['confirm' => 'confirmed', 'checkin' => 'checked_in', 'complete' => 'completed', 'cancel' => 'cancelled', 'noshow' => 'no_show'];
        $new = $map[$status] ?? null;
        if (!$new) {
            $this->json(['error' => 'Unknown status action'], 400);
        }
        run('UPDATE appointments SET status = ? WHERE id = ?', [$new, $id]);
        audit('update', 'appointments', "Appointment #$id -> $new");
        $this->json(['ok' => true, 'status' => $new]);
    }

public function delete($id)
    {
        csrf_check();
        $this->guard(['admin', 'receptionist']);
        $this->find($id);
        // Archived, not deleted: a consultation may reference this appointment and
        // the ON DELETE RESTRICT constraint would otherwise reject the removal.
        soft_delete('appointments', $id);
        audit('archive', 'appointments', "Appointment #$id");
        set_flash('success', 'Appointment cancelled and removed from the book.');
        back();
    }

    /** Accept only a real, active doctor id; otherwise store NULL. */
    private function valid_doctor($raw)
    {
        $id = (int)$raw;
        if (!$id) return null;
        return fetch_val('SELECT id FROM users WHERE id = ? AND role = "doctor" AND deleted_at IS NULL', [$id])
            ? $id : null;
    }

private function find($id)
    {
        $a = fetch('SELECT * FROM appointments WHERE id = ? AND deleted_at IS NULL', [(int)$id]);
        if (!$a) not_found();
        return $a;
    }
}
