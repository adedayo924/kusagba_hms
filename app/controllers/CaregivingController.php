<?php

class CaregivingController extends Controller
{
    protected $title = 'Caregiving Services';
    protected $active = 'caregiving';

    public function __construct()
    {
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist', 'caregiver']);
    }

    public function index()
    {
        $userRole = Auth::role();
        $userId = Auth::id();

        $status = getp('status', '');
        $careType = getp('care_type', '');
        $caregiverId = (int)getp('caregiver', 0);
        $q = getp('q');

        $where = 'ce.deleted_at IS NULL';
        $params = [];

        // If logged-in user is a caregiver, default or restrict to their assigned cases
        if ($userRole === 'caregiver') {
            $where .= ' AND ce.primary_caregiver_id = ?';
            $params[] = $userId;
        } elseif ($caregiverId > 0) {
            $where .= ' AND ce.primary_caregiver_id = ?';
            $params[] = $caregiverId;
        }

        if (in_array($status, ['pending', 'approved', 'active', 'completed', 'cancelled'], true)) {
            $where .= ' AND ce.status = ?';
            $params[] = $status;
        }

        if (in_array($careType, ['home', 'bedside'], true)) {
            $where .= ' AND ce.care_type = ?';
            $params[] = $careType;
        }

        if ($q !== '') {
            $where .= ' AND (ce.request_no LIKE ? OR CONCAT(p.first_name, " ", p.last_name) LIKE ? OR p.patient_no LIKE ? OR ce.location_address LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like, $like);
        }

        $engagements = fetch_all(
            "SELECT ce.*, 
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name, p.patient_no, p.phone AS patient_phone,
                    cg.full_name AS caregiver_name,
                    w.name AS ward_name,
                    (SELECT COUNT(*) FROM care_shift_logs sl WHERE sl.engagement_id = ce.id) AS total_shifts,
                    (SELECT COUNT(*) FROM care_shift_logs sl WHERE sl.engagement_id = ce.id AND sl.status = 'completed') AS completed_shifts
             FROM care_engagements ce
             JOIN patients p ON p.id = ce.patient_id
             LEFT JOIN users cg ON cg.id = ce.primary_caregiver_id
             LEFT JOIN wards w ON w.id = ce.ward_id
             WHERE $where
             ORDER BY ce.id DESC LIMIT 150",
            $params
        );

        $today = date('Y-m-d');
        $shiftWhere = 'sl.shift_date = ? AND ce.deleted_at IS NULL';
        $shiftParams = [$today];
        if ($userRole === 'caregiver') {
            $shiftWhere .= ' AND sl.caregiver_id = ?';
            $shiftParams[] = $userId;
        }

        $todayShifts = fetch_all(
            "SELECT sl.*, ce.request_no, ce.care_type, ce.location_address, ce.bed_label,
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name, p.patient_no, p.phone AS patient_phone,
                    cg.full_name AS caregiver_name, w.name AS ward_name
             FROM care_shift_logs sl
             JOIN care_engagements ce ON ce.id = sl.engagement_id
             JOIN patients p ON p.id = ce.patient_id
             LEFT JOIN users cg ON cg.id = sl.caregiver_id
             LEFT JOIN wards w ON w.id = ce.ward_id
             WHERE $shiftWhere
             ORDER BY sl.shift_start ASC, sl.id ASC",
            $shiftParams
        );

        // Stats summary
        $stats = [
            'active' => (int)fetch_val("SELECT COUNT(*) FROM care_engagements WHERE status = 'active' AND deleted_at IS NULL"),
            'pending' => (int)fetch_val("SELECT COUNT(*) FROM care_engagements WHERE status = 'pending' AND deleted_at IS NULL"),
            'home' => (int)fetch_val("SELECT COUNT(*) FROM care_engagements WHERE care_type = 'home' AND status IN ('approved','active') AND deleted_at IS NULL"),
            'bedside' => (int)fetch_val("SELECT COUNT(*) FROM care_engagements WHERE care_type = 'bedside' AND status IN ('approved','active') AND deleted_at IS NULL"),
            'today_shifts' => count($todayShifts),
            'caregivers_count' => (int)fetch_val("SELECT COUNT(*) FROM users WHERE role = 'caregiver' AND active = 1 AND deleted_at IS NULL"),
        ];

        $caregivers = fetch_all("SELECT id, full_name, phone, specialty FROM users WHERE role IN ('caregiver', 'nurse') AND active = 1 AND deleted_at IS NULL ORDER BY full_name");

        $this->view('caregiving/index', compact(
            'engagements', 'todayShifts', 'stats', 'caregivers', 'status', 'careType', 'caregiverId', 'q', 'userRole'
        ));
    }

    public function create()
    {
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist']);
        $this->title = 'New Caregiving Engagement';

        $patientId = (int)getp('patient', 0);
        $patient = $patientId ? fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId]) : null;

        $patients = fetch_all('SELECT id, patient_no, first_name, last_name, phone FROM patients WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT 300');
        $caregivers = fetch_all("SELECT id, full_name, role, specialty, phone FROM users WHERE role IN ('caregiver', 'nurse') AND active = 1 AND deleted_at IS NULL ORDER BY role, full_name");
        $wards = fetch_all('SELECT id, name FROM wards WHERE deleted_at IS NULL ORDER BY name');
        $services = fetch_all("SELECT id, name, price, description FROM services WHERE category = 'caregiving' AND active = 1 AND deleted_at IS NULL ORDER BY price ASC");

        $this->view('caregiving/form', compact('patient', 'patients', 'caregivers', 'wards', 'services'));
    }

    public function store()
    {
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist']);
        csrf_check();

        $patientId = (int)post('patient_id', 0);
        if (!$patientId || !fetch('SELECT id FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId])) {
            set_flash('error', 'Select a valid patient.');
            back();
        }

        $careType = in_array(post('care_type'), ['home', 'bedside'], true) ? post('care_type') : 'home';
        $shiftType = in_array(post('shift_type'), ['day_8h', 'night_12h', 'full_24h', 'custom'], true) ? post('shift_type') : 'day_8h';
        $serviceId = (int)post('service_id', 0) ?: null;

        $startDate = post('start_date');
        if (!$startDate || !strtotime($startDate)) {
            set_flash('error', 'Valid start date is required.');
            back();
        }

        $endDate = post('end_date') ?: null;
        if ($endDate && strtotime($endDate) < strtotime($startDate)) {
            set_flash('error', 'End date cannot be earlier than start date.');
            back();
        }

        $totalDays = max(1, (int)post('total_days', 1));
        if ($endDate && !$totalDays) {
            $totalDays = max(1, (int)ceil((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);
        }

        $ratePerShift = max(0, (float)post('rate_per_shift', 0));
        if ($ratePerShift <= 0 && $serviceId) {
            $srvPrice = fetch_val('SELECT price FROM services WHERE id = ?', [$serviceId]);
            if ($srvPrice) $ratePerShift = (float)$srvPrice;
        }

        $locationAddress = post('location_address') ?: null;
        $wardId = (int)post('ward_id', 0) ?: null;
        $bedLabel = post('bed_label') ?: null;

        $primaryCaregiverId = (int)post('primary_caregiver_id', 0) ?: null;
        $specialInstructions = post('special_instructions') ?: null;
        $emergencyName = post('emergency_contact_name') ?: null;
        $emergencyPhone = post('emergency_contact_phone') ?: null;
        $autoBill = isset($_POST['auto_bill']);

        $engagementId = tx(function () use (
            $patientId, $careType, $shiftType, $serviceId, $ratePerShift, $totalDays,
            $startDate, $endDate, $locationAddress, $wardId, $bedLabel,
            $specialInstructions, $emergencyName, $emergencyPhone, $primaryCaregiverId, $autoBill
        ) {
            $requestNo = next_ticket('care_engagements', 'request_no', 'CG');
            $status = $primaryCaregiverId ? 'approved' : 'pending';

            run(
                "INSERT INTO care_engagements 
                 (request_no, patient_id, care_type, shift_type, service_id, rate_per_shift, total_days,
                  start_date, end_date, location_address, ward_id, bed_label, special_instructions,
                  emergency_contact_name, emergency_contact_phone, primary_caregiver_id, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $requestNo, $patientId, $careType, $shiftType, $serviceId, $ratePerShift, $totalDays,
                    $startDate, $endDate, $locationAddress, $wardId, $bedLabel, $specialInstructions,
                    $emergencyName, $emergencyPhone, $primaryCaregiverId, $status, Auth::id()
                ]
            );
            $engId = last_id();

            // Auto-bill invoice line if selected
            if ($autoBill && $ratePerShift > 0) {
                $shiftLabels = [
                    'day_8h' => 'Day Shift (8h)',
                    'night_12h' => 'Night Shift (12h)',
                    'full_24h' => '24-Hour Live-in',
                    'custom' => 'Custom Shift'
                ];
                $shiftLabel = $shiftLabels[$shiftType] ?? 'Shift';
                $desc = "Caregiving Services ({$shiftLabel}) - " . ($careType === 'home' ? 'Home Care' : 'Bedside Care') . " [{$requestNo}]";
                $invId = add_invoice_line($patientId, 'caregiving', $engId, $desc, $totalDays, $ratePerShift);
                run("UPDATE care_engagements SET invoice_id = ? WHERE id = ?", [$invId, $engId]);
            }

            // If caregiver is assigned, pre-generate initial shift log for the start date
            if ($primaryCaregiverId) {
                $shiftTimes = [
                    'day_8h' => ['08:00:00', '16:00:00'],
                    'night_12h' => ['20:00:00', '08:00:00'],
                    'full_24h' => ['08:00:00', '08:00:00'],
                    'custom' => ['09:00:00', '17:00:00']
                ];
                [$sStart, $sEnd] = $shiftTimes[$shiftType] ?? ['08:00:00', '16:00:00'];
                run(
                    "INSERT INTO care_shift_logs 
                     (engagement_id, caregiver_id, shift_date, shift_start, shift_end, status)
                     VALUES (?,?,?,?,?,'scheduled')",
                    [$engId, $primaryCaregiverId, $startDate, $sStart, $sEnd]
                );
            }

            return $engId;
        });

        audit('create', 'caregiving', "Care engagement #{$engagementId}");
        set_flash('success', 'Caregiving engagement created successfully.');
        redirect("caregiving/show/{$engagementId}");
    }

    public function show($id)
    {
        $id = (int)$id;
        $engagement = fetch(
            "SELECT ce.*, 
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name, p.patient_no, p.dob, p.gender,
                    p.blood_group, p.allergies, p.medical_history, p.phone AS patient_phone, p.email AS patient_email,
                    cg.full_name AS caregiver_name, cg.phone AS caregiver_phone, cg.email AS caregiver_email, cg.specialty AS caregiver_specialty,
                    w.name AS ward_name,
                    srv.name AS service_name,
                    i.invoice_no, i.total AS invoice_total, i.paid_amount AS invoice_paid, i.status AS invoice_status
             FROM care_engagements ce
             JOIN patients p ON p.id = ce.patient_id
             LEFT JOIN users cg ON cg.id = ce.primary_caregiver_id
             LEFT JOIN wards w ON w.id = ce.ward_id
             LEFT JOIN services srv ON srv.id = ce.service_id
             LEFT JOIN invoices i ON i.id = ce.invoice_id
             WHERE ce.id = ? AND ce.deleted_at IS NULL",
            [$id]
        );

        if (!$engagement) {
            not_found();
        }

        // Check caregiver authorization (caregivers can only see engagements assigned to them)
        if (Auth::role() === 'caregiver' && (int)$engagement['primary_caregiver_id'] !== (int)Auth::id()) {
            forbidden();
        }

        $shiftLogs = fetch_all(
            "SELECT sl.*, cg.full_name AS caregiver_name, sup.full_name AS supervisor_name
             FROM care_shift_logs sl
             JOIN users cg ON cg.id = sl.caregiver_id
             LEFT JOIN users sup ON sup.id = sl.supervisor_reviewed_by
             WHERE sl.engagement_id = ?
             ORDER BY sl.shift_date DESC, sl.shift_start DESC, sl.id DESC",
            [$id]
        );

        $caregivers = fetch_all("SELECT id, full_name, specialty, phone FROM users WHERE role IN ('caregiver', 'nurse') AND active = 1 AND deleted_at IS NULL ORDER BY full_name");

        $this->view('caregiving/show', compact('engagement', 'shiftLogs', 'caregivers'));
    }

    public function status_update($id)
    {
        csrf_check();
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist']);
        $id = (int)$id;
        $newStatus = post('status');

        if (!in_array($newStatus, ['approved', 'active', 'completed', 'cancelled'], true)) {
            set_flash('error', 'Invalid status.');
            back();
        }

        $eng = fetch('SELECT * FROM care_engagements WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$eng) not_found();

        run('UPDATE care_engagements SET status = ? WHERE id = ?', [$newStatus, $id]);
        audit('update', 'caregiving', "Engagement #{$id} status updated to {$newStatus}");
        set_flash('success', "Caregiving status updated to " . ucfirst($newStatus) . ".");
        back();
    }

    public function assign_caregiver($id)
    {
        csrf_check();
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist']);
        $id = (int)$id;
        $caregiverId = (int)post('caregiver_id', 0) ?: null;

        $eng = fetch('SELECT * FROM care_engagements WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$eng) not_found();

        if ($caregiverId && !fetch('SELECT id FROM users WHERE id = ? AND active = 1 AND deleted_at IS NULL', [$caregiverId])) {
            set_flash('error', 'Selected caregiver is invalid.');
            back();
        }

        $nextStatus = ($eng['status'] === 'pending' && $caregiverId) ? 'approved' : $eng['status'];
        run('UPDATE care_engagements SET primary_caregiver_id = ?, status = ? WHERE id = ?', [$caregiverId, $nextStatus, $id]);

        audit('update', 'caregiving', "Engagement #{$id} assigned to caregiver #{$caregiverId}");
        set_flash('success', 'Caregiver updated successfully.');
        back();
    }

    public function shift_schedule($id)
    {
        csrf_check();
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist', 'caregiver']);
        $id = (int)$id;
        $eng = fetch('SELECT * FROM care_engagements WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$eng) not_found();

        $caregiverId = (int)post('caregiver_id', 0) ?: (int)$eng['primary_caregiver_id'];
        $shiftDate = post('shift_date');
        $shiftStart = post('shift_start') ?: null;
        $shiftEnd = post('shift_end') ?: null;

        if (!$caregiverId || !$shiftDate) {
            set_flash('error', 'Caregiver and shift date are required.');
            back();
        }

        run(
            "INSERT INTO care_shift_logs (engagement_id, caregiver_id, shift_date, shift_start, shift_end, status)
             VALUES (?,?,?,?,?,'scheduled')",
            [$id, $caregiverId, $shiftDate, $shiftStart, $shiftEnd]
        );

        audit('create', 'caregiving', "Shift scheduled for engagement #{$id} on {$shiftDate}");
        set_flash('success', 'Care shift scheduled successfully.');
        back();
    }

    public function shift_log_save($shiftId)
    {
        csrf_check();
        $this->guard(['admin', 'nurse', 'caregiver']);
        $shiftId = (int)$shiftId;

        $shift = fetch(
            "SELECT sl.*, ce.primary_caregiver_id 
             FROM care_shift_logs sl 
             JOIN care_engagements ce ON ce.id = sl.engagement_id 
             WHERE sl.id = ? AND ce.deleted_at IS NULL",
            [$shiftId]
        );
        if (!$shift) not_found();

        // Caregiver check
        if (Auth::role() === 'caregiver' && (int)$shift['caregiver_id'] !== (int)Auth::id()) {
            forbidden();
        }

        $vitals = post('vitals_summary') ?: null;
        $feeding = post('feeding_notes') ?: null;
        $mobility = post('mobility_notes') ?: null;
        $hygiene = post('hygiene_notes') ?: null;
        $meds = post('medication_administered') ?: null;
        $general = post('general_notes') ?: null;
        $status = post('status', 'completed');
        if (!in_array($status, ['in_progress', 'completed', 'missed'], true)) {
            $status = 'completed';
        }

        run(
            "UPDATE care_shift_logs SET 
                vitals_summary = ?, feeding_notes = ?, mobility_notes = ?, 
                hygiene_notes = ?, medication_administered = ?, general_notes = ?, 
                status = ?
             WHERE id = ?",
            [$vitals, $feeding, $mobility, $hygiene, $meds, $general, $status, $shiftId]
        );

        audit('update', 'caregiving', "Shift log #{$shiftId} recorded");
        set_flash('success', 'Care shift report saved successfully.');
        back();
    }

    public function generate_bill($id)
    {
        csrf_check();
        $this->guard(['admin', 'cashier', 'receptionist']);
        $id = (int)$id;

        $eng = fetch('SELECT * FROM care_engagements WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$eng) not_found();

        if ($eng['invoice_id']) {
            set_flash('error', 'This engagement already has a linked invoice.');
            back();
        }

        $rate = (float)$eng['rate_per_shift'];
        $days = (int)$eng['total_days'];
        if ($rate <= 0) {
            set_flash('error', 'Engagement rate per shift is zero. Please set a rate first.');
            back();
        }

        $desc = "Caregiving Services (" . ucfirst(str_replace('_', ' ', $eng['shift_type'])) . ") - "
              . ($eng['care_type'] === 'home' ? 'Home Care' : 'Bedside Care') . " [{$eng['request_no']}]";

        $invId = add_invoice_line($eng['patient_id'], 'caregiving', $eng['id'], $desc, $days, $rate);
        run('UPDATE care_engagements SET invoice_id = ? WHERE id = ?', [$invId, $id]);

        audit('create', 'billing', "Invoice generated for care engagement #{$id}");
        set_flash('success', 'Caregiving charges billed to patient invoice.');
        redirect("billing/show/{$invId}");
    }

    public function delete($id)
    {
        csrf_check();
        $this->guard(['admin']);
        $id = (int)$id;

        $eng = fetch('SELECT * FROM care_engagements WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$eng) not_found();

        soft_delete('care_engagements', $id);
        audit('delete', 'caregiving', "Engagement #{$id} archived");
        set_flash('success', 'Caregiving engagement archived.');
        redirect('caregiving');
    }
}
