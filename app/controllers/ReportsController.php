<?php

class ReportsController extends Controller
{
    protected $title = 'Reports';
    protected $active = 'reports';

    public function __construct()
    {
        $this->guard(['admin', 'cashier', 'doctor']);
    }

    public function index()
    {
        $from = getp('from', date('Y-m-d', strtotime('-30 days')));
        $to = getp('to', date('Y-m-d'));
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        // Half-open datetime range: keeps paid_at/created_at/requested_at sargable
        // so their indexes are usable instead of wrapping the column in DATE().
        $start = $from . ' 00:00:00';
        $end = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00';
        $dates = [$from, $to];
        $range = [$start, $end];

        $income = fetch(
            'SELECT IFNULL(SUM(amount),0) AS total, COUNT(*) AS count
             FROM payments WHERE paid_at >= ? AND paid_at < ?', $range
        );
        $outstanding = (float)fetch_val(
            'SELECT IFNULL(SUM(total - paid_amount),0) FROM invoices WHERE status != "void" AND status != "paid"'
        );
        $registered = (int)fetch_val(
            'SELECT COUNT(*) FROM patients WHERE deleted_at IS NULL AND created_at >= ? AND created_at < ?', $range
        );
        $consultations = (int)fetch_val(
            'SELECT COUNT(*) FROM consultations WHERE visit_date BETWEEN ? AND ?', $dates
        );
        $appointments = (int)fetch_val(
            'SELECT COUNT(*) FROM appointments WHERE deleted_at IS NULL AND created_at >= ? AND created_at < ?', $range
        );
        $admissions = (int)fetch_val(
            'SELECT COUNT(*) FROM admissions WHERE deleted_at IS NULL AND admitted_at >= ? AND admitted_at < ?', $range
        );
        $labRequests = (int)fetch_val(
            'SELECT COUNT(*) FROM lab_requests WHERE requested_at >= ? AND requested_at < ?', $range
        );
        $prescriptions = (int)fetch_val(
            'SELECT COUNT(*) FROM prescriptions WHERE prescribed_at >= ? AND prescribed_at < ?', $range
        );

        $byMethod = fetch_all(
            'SELECT method, IFNULL(SUM(amount),0) AS total, COUNT(*) AS count
             FROM payments WHERE paid_at >= ? AND paid_at < ?
             GROUP BY method ORDER BY total DESC', $range
        );
        $byDay = fetch_all(
            'SELECT DATE(paid_at) AS d, IFNULL(SUM(amount),0) AS total, COUNT(*) AS count
             FROM payments WHERE paid_at >= ? AND paid_at < ?
             GROUP BY DATE(paid_at) ORDER BY d', $range
        );
        $byService = fetch_all(
            "SELECT LEFT(ii.description, 60) AS service, IFNULL(SUM(ii.amount),0) AS total, COUNT(*) AS count
             FROM invoice_items ii
             JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.created_at >= ? AND i.created_at < ? AND i.status != 'void'
             GROUP BY service ORDER BY total DESC LIMIT 15", $range
        );
        $byDoctor = fetch_all(
            'SELECT u.full_name, COUNT(c.id) AS count
             FROM consultations c LEFT JOIN users u ON u.id = c.doctor_id
             WHERE c.visit_date BETWEEN ? AND ?
             GROUP BY c.doctor_id ORDER BY count DESC', $dates
        );
        $recentPayments = fetch_all(
            'SELECT p.*, i.invoice_no, CONCAT(pat.first_name," ",pat.last_name) AS patient_name, u.full_name AS receiver
             FROM payments p
             JOIN invoices i ON i.id = p.invoice_id
             LEFT JOIN patients pat ON pat.id = i.patient_id
             LEFT JOIN users u ON u.id = p.received_by
             WHERE p.paid_at >= ? AND p.paid_at < ?
             ORDER BY p.paid_at DESC LIMIT 100', $range
        );

        $this->view('reports/index', compact(
            'from', 'to', 'income', 'outstanding', 'registered', 'consultations', 'appointments',
            'admissions', 'labRequests', 'prescriptions', 'byMethod', 'byDay', 'byService', 'byDoctor', 'recentPayments'
        ));
    }
}