<?php

class DashboardController extends Controller
{
    protected $title = 'Dashboard';
    protected $active = 'dashboard';

    public function index()
    {
        if (Auth::is_patient()) {
            redirect('portal');
        }
        if (Auth::role() === 'caregiver') {
            redirect('caregiving');
        }
        $this->guard(['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab', 'cashier']);

        $today = date('Y-m-d');
        $todayStart = $today . ' 00:00:00';
        $todayEnd = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';
        $weekStart = date('Y-m-d', strtotime('-6 day')) . ' 00:00:00';

        $stats = [
            'patients'   => fetch_val('SELECT COUNT(*) FROM patients WHERE deleted_at IS NULL'),
            'appointments' => fetch_val('SELECT COUNT(*) FROM appointments WHERE deleted_at IS NULL AND appointment_date = ?', [$today]),
            'consultations' => fetch_val('SELECT COUNT(*) FROM consultations WHERE visit_date = ?', [$today]),
            'inpatients' => fetch_val('SELECT COUNT(*) FROM admissions WHERE deleted_at IS NULL AND status = "admitted"'),
            // Range predicate instead of DATE(paid_at) = ? so the index on paid_at is usable.
            'revenue'    => (float)fetch_val('SELECT IFNULL(SUM(amount),0) FROM payments WHERE paid_at >= ? AND paid_at < ?', [$todayStart, $todayEnd]),
            'outstanding' => (float)fetch_val('SELECT IFNULL(SUM(total - paid_amount),0) FROM invoices WHERE status != "void" AND status != "paid"'),
            'lowstock'   => fetch_val('SELECT COUNT(*) FROM inventory_items WHERE active = 1 AND deleted_at IS NULL AND quantity <= reorder_level'),
            'pendinglab' => fetch_val('SELECT COUNT(*) FROM lab_requests WHERE status != "resulted" AND status != "cancelled"'),
        ];

        // One grouped query for the 7-day trend instead of 14 separate per-day lookups.
        $labels = [];
        $revenueMap = [];
        $appointMap = [];
        foreach (fetch_all(
            'SELECT DATE(paid_at) AS d, SUM(amount) AS total
             FROM payments WHERE paid_at >= ? AND paid_at < ? GROUP BY DATE(paid_at)',
            [$weekStart, $todayEnd]
        ) as $row) {
            $revenueMap[$row['d']] = (float)$row['total'];
        }
        foreach (fetch_all(
            'SELECT appointment_date AS d, COUNT(*) AS total
             FROM appointments WHERE deleted_at IS NULL AND appointment_date >= ? AND appointment_date <= ?
             GROUP BY appointment_date',
            [date('Y-m-d', strtotime('-6 day')), $today]
        ) as $row) {
            $appointMap[$row['d']] = (int)$row['total'];
        }
        $revenue = $appoint = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $labels[] = date('D', strtotime($d));
            $revenue[] = $revenueMap[$d] ?? 0;
            $appoint[] = $appointMap[$d] ?? 0;
        }

        $todayAppointments = fetch_all(
            'SELECT a.*, CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                    CONCAT(u.full_name) AS doctor_name
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.doctor_id
             WHERE a.deleted_at IS NULL AND a.appointment_date = ?
             ORDER BY a.appointment_time ASC LIMIT 10',
            [$today]
        );

        $recentPatients = fetch_all('SELECT * FROM patients WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 6');

        $bedStats = fetch_all(
            'SELECT w.name, w.total_beds, COUNT(a.id) AS occupied
             FROM wards w
             LEFT JOIN admissions a ON a.ward_id = w.id AND a.status = "admitted" AND a.deleted_at IS NULL
             WHERE w.deleted_at IS NULL
             GROUP BY w.id ORDER BY occupied DESC'
        );

        $this->view('dashboard/index', [
            'stats' => $stats,
            'labels' => $labels,
            'revenue' => $revenue,
            'appoint' => $appoint,
            'todayAppointments' => $todayAppointments,
            'recentPatients' => $recentPatients,
            'bedStats' => $bedStats,
        ]);
    }
}