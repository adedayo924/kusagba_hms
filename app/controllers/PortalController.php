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
        $patient = $patientId ? fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId]) : null;
        if (!$patient) {
            // End the session here. Redirecting to auth/logout used to bounce a
            // logged-in user straight back to portal — an endless redirect loop
            // with no way out except clearing cookies.
            audit('logout', 'auth', 'Session ended: no active patient profile linked');
            Auth::logout();
            set_flash('error', 'No active patient profile is linked to your account. Contact the front desk.');
            redirect('auth/login');
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

        $careEngagements = fetch_all(
            'SELECT ce.*, cg.full_name AS caregiver_name, cg.phone AS caregiver_phone,
                    (SELECT COUNT(*) FROM care_shift_logs sl WHERE sl.engagement_id = ce.id AND sl.status = "completed") AS completed_shifts
             FROM care_engagements ce 
             LEFT JOIN users cg ON cg.id = ce.primary_caregiver_id 
             WHERE ce.patient_id = ? AND ce.deleted_at IS NULL 
             ORDER BY ce.id DESC LIMIT 6',
            [$patientId]
        );

        $careLogs = fetch_all(
            'SELECT sl.*, cg.full_name AS caregiver_name, ce.request_no
             FROM care_shift_logs sl 
             JOIN care_engagements ce ON ce.id = sl.engagement_id 
             JOIN users cg ON cg.id = sl.caregiver_id 
             WHERE ce.patient_id = ? AND sl.status = "completed" 
             ORDER BY sl.shift_date DESC, sl.id DESC LIMIT 10',
            [$patientId]
        );

        $carePackages = fetch_all(
            "SELECT * FROM services WHERE category = 'caregiving' AND active = 1 AND deleted_at IS NULL ORDER BY price ASC"
        );

        $this->view('portal/index', compact(
            'patient', 'appointments', 'visits', 'prescriptions', 'lab', 'invoices',
            'careEngagements', 'careLogs', 'carePackages'
        ), 'portal');
    }

    public function care_request()
    {
        csrf_check();
        $patientId = (int)Auth::userPatientId();
        if (!$patientId) {
            set_flash('error', 'Profile not found.');
            back();
        }

        $careType = in_array(post('care_type'), ['home', 'bedside'], true) ? post('care_type') : 'home';
        $shiftType = in_array(post('shift_type'), ['day_8h', 'night_12h', 'full_24h', 'custom'], true) ? post('shift_type') : 'day_8h';
        $serviceId = (int)post('service_id', 0) ?: null;

        $startDate = post('start_date');
        if (!$startDate || !strtotime($startDate)) {
            set_flash('error', 'Please provide a valid start date.');
            back();
        }

        $totalDays = max(1, (int)post('total_days', 7));
        $endDate = date('Y-m-d', strtotime($startDate . " +" . ($totalDays - 1) . " days"));

        $rate = 0;
        if ($serviceId) {
            $srv = fetch(
                "SELECT price FROM services
                 WHERE id = ? AND category = 'caregiving' AND active = 1 AND deleted_at IS NULL",
                [$serviceId]
            );
            if (!$srv) {
                set_flash('error', 'The selected care package is no longer available. Please choose another.');
                back();
            }
            $rate = (float)$srv['price'];
        }
        if ($rate <= 0) {
            // Fall back to configurable stand-alone rates; the figures are stored
            // in the hospital's configured currency rather than assumed NGN.
            $defaultRates = [
                'day_8h' => (float)app_setting('care_rate_day_8h', 12000),
                'night_12h' => (float)app_setting('care_rate_night_12h', 18000),
                'full_24h' => (float)app_setting('care_rate_full_24h', 30000),
                'custom' => (float)app_setting('care_rate_day_8h', 12000),
            ];
            $rate = max(0, (float)($defaultRates[$shiftType] ?? 12000));
            if ($rate <= 0) {
                set_flash('error', 'Caregiving rates are not configured yet. Please contact the front desk.');
                back();
            }
        }

        $address = post('location_address') ?: null;
        $instructions = post('special_instructions') ?: null;
        $emergName = post('emergency_contact_name') ?: null;
        $emergPhone = post('emergency_contact_phone') ?: null;

        $requestNo = next_ticket('care_engagements', 'request_no', 'CG');

        run(
            "INSERT INTO care_engagements 
             (request_no, patient_id, care_type, shift_type, service_id, rate_per_shift, total_days,
              start_date, end_date, location_address, special_instructions,
              emergency_contact_name, emergency_contact_phone, status, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',?)",
            [
                $requestNo, $patientId, $careType, $shiftType, $serviceId, $rate, $totalDays,
                $startDate, $endDate, $address, $instructions,
                $emergName, $emergPhone, Auth::id()
            ]
        );

        audit('create', 'caregiving', "Portal request #{$requestNo} created by patient");
        set_flash('success', "Your caregiving request ({$requestNo}) has been submitted successfully! Our care coordinator will contact you shortly to confirm caregiver assignment.");
        redirect('portal');
    }
}