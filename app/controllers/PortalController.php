<?php

class PortalController extends Controller
{
    protected $title = 'My portal';
    protected $active = 'dashboard';

    public function __construct()
    {
        $this->guard(['patient']);
    }

    public function index()
    {
        $patientId = Auth::userPatientId();
        $patient = fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId]);
        if (!$patient) {
            set_flash('error', 'No active patient profile is linked to your account. Contact the front desk.');
            redirect('auth/logout');
        }
        $appointments = fetch_all(
            'SELECT a.*, u.full_name AS doctor_name, u.specialty
             FROM appointments a LEFT JOIN users u ON u.id = a.doctor_id
             WHERE a.patient_id = ? AND a.deleted_at IS NULL
             ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 6',
            [$patientId]
        );
        $visits = fetch_all(
            'SELECT c.*, u.full_name AS doctor_name FROM consultations c
             LEFT JOIN users u ON u.id = c.doctor_id
             WHERE c.patient_id = ? ORDER BY c.visit_date DESC LIMIT 6',
            [$patientId]
        );
        $prescriptions = fetch_all(
            'SELECT p.*, u.full_name AS doctor_name
             FROM prescriptions p LEFT JOIN users u ON u.id = p.prescribed_by
             WHERE p.patient_id = ? ORDER BY p.prescribed_at DESC LIMIT 6',
            [$patientId]
        );
        $lab = fetch_all(
            'SELECT r.* FROM lab_requests r WHERE r.patient_id = ?
             ORDER BY r.requested_at DESC LIMIT 6',
            [$patientId]
        );
        $invoices = fetch_all(
            'SELECT * FROM invoices WHERE patient_id = ? AND status != "void" ORDER BY id DESC LIMIT 6',
            [$patientId]
        );
        $this->view('portal/index', compact('patient', 'appointments', 'visits', 'prescriptions', 'lab', 'invoices'), 'portal');
    }
}