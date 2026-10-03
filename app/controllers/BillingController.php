<?php

class BillingController extends Controller
{
    protected $title = 'Billing';
    protected $active = 'billing';

    public function __construct()
    {
        $this->guard(['admin', 'cashier', 'receptionist', 'patient']);
    }

    private function staffOnly()
    {
        $this->guard(['admin', 'cashier', 'receptionist']);
    }

    public function index()
    {
        $this->staffOnly();
        $status = getp('status', '');
        $q = getp('q');
        $where = 'i.id > 0';
        $params = [];
        if (in_array($status, ['unpaid', 'partial', 'paid', 'void'], true)) {
            $where .= ' AND i.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where .= ' AND (i.invoice_no LIKE ? OR CONCAT(p.first_name," ",p.last_name) LIKE ? OR p.patient_no LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        $list = fetch_all(
            "SELECT i.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name, p.patient_no
             FROM invoices i LEFT JOIN patients p ON p.id = i.patient_id
             WHERE $where ORDER BY i.id DESC LIMIT 200",
            $params
        );
        $todayRevenue = (float)fetch_val('SELECT IFNULL(SUM(amount),0) FROM payments WHERE paid_at >= ? AND paid_at < ?',
            [date('Y-m-d') . ' 00:00:00', date('Y-m-d', strtotime('+1 day')) . ' 00:00:00']);
        $todayReceived = (int)fetch_val('SELECT COUNT(*) FROM payments WHERE paid_at >= ? AND paid_at < ?',
            [date('Y-m-d') . ' 00:00:00', date('Y-m-d', strtotime('+1 day')) . ' 00:00:00']);
        $this->view('billing/index', compact('list', 'status', 'q', 'todayRevenue', 'todayReceived'));
    }

    public function show($id)
    {
        $this->staffOnly();
        $inv = fetch('SELECT * FROM invoices WHERE id = ?', [$id]);
        if (!$inv) not_found();
        $patient = fetch('SELECT * FROM patients WHERE id = ?', [$inv['patient_id']]);
        $items = fetch_all('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id', [$id]);
        $payments = fetch_all(
            'SELECT p.*, u.full_name AS received_by_name FROM payments p
             LEFT JOIN users u ON u.id = p.received_by WHERE p.invoice_id = ? ORDER BY p.paid_at DESC', [$id]
        );
        $balance = invoice_balance($inv);
        $this->view('billing/show', compact('inv', 'patient', 'items', 'payments', 'balance'));
    }

    public function create()
    {
        $this->staffOnly();
        $patientId = (int)getp('patient', 0);
        $patient = $patientId ? fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId]) : null;
        $patients = fetch_all('SELECT id, patient_no, first_name, last_name, phone FROM patients WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT 500');
        $services = fetch_all('SELECT * FROM services WHERE active = 1 AND deleted_at IS NULL ORDER BY category, name');
        $this->view('billing/form', compact('patient', 'patients', 'services'));
    }

    public function store()
    {
        $this->staffOnly();
        csrf_check();
        $patientId = (int)post('patient_id', 0);
        if (!$patientId || !fetch('SELECT id FROM patients WHERE id = ? AND deleted_at IS NULL', [$patientId])) {
            set_flash('error', 'Select a valid patient.');
            back();
        }
        $descs = $_POST['description'] ?? [];
        $amounts = $_POST['amount'] ?? [];
        $lines = [];
        foreach ($descs as $i => $desc) {
            $desc = trim((string)$desc);
            $amt = (float)($amounts[$i] ?? 0);
            if ($desc !== '' && $amt > 0) {
                $lines[] = [$desc, $amt];
            }
        }
        if (!$lines) {
            set_flash('error', 'Add at least one service line with a positive amount.');
            back();
        }
        $invId = tx(function () use ($patientId, $lines) {
            $id = open_invoice_id($patientId);
            foreach ($lines as [$desc, $amt]) {
                add_invoice_line($patientId, 'service', null, $desc, 1, $amt);
            }
            return $id;
        });
        audit('create', 'billing', "Invoice #$invId for patient #$patientId");
        set_flash('success', 'Invoice updated with ' . count($lines) . ' service line(s).');
        redirect('billing/show/' . $invId);
    }

    public function add_line($id)
    {
        $this->staffOnly();
        csrf_check();
        $inv = fetch('SELECT * FROM invoices WHERE id = ?', [$id]);
        if (!$inv || $inv['status'] === 'void') not_found();
        $desc = post('description');
        $amount = (float)post('amount', 0);
        if ($desc === '' || $amount <= 0) {
            set_flash('error', 'Description and a positive amount are required.');
            back();
        }
        run('INSERT INTO invoice_items (invoice_id, item_type, description, qty, unit_price, amount)
             VALUES (?, "service", ?, 1, ?, ?)', [$id, $desc, $amount, $amount]);
        recompute_invoice($id);
        audit('update', 'billing', "Invoice #$id +$desc");
        set_flash('success', 'Line added to invoice.');
        back();
    }

    public function remove_line($invId, $lineId)
    {
        $this->staffOnly();
        csrf_check();
        $inv = fetch('SELECT * FROM invoices WHERE id = ?', [$invId]);
        if (!$inv || $inv['status'] === 'void') not_found();
        $line = fetch('SELECT * FROM invoice_items WHERE id = ? AND invoice_id = ?', [$lineId, $invId]);
        if (!$line) not_found();
        if (!empty($line['item_type']) && in_array($line['item_type'], ['admission', 'drug', 'lab'])) {
            set_flash('error', 'Line ' . $lineId . ' is tied to a clinical record and cannot be removed.');
            back();
        }
        run('DELETE FROM invoice_items WHERE id = ?', [$lineId]);
        recompute_invoice($invId);
        audit('delete', 'billing', "Invoice #$invId line #$lineId");
        set_flash('success', 'Line removed.');
        back();
    }

    public function pay($id)
    {
        $this->staffOnly();
        csrf_check();
        $inv = fetch('SELECT * FROM invoices WHERE id = ?', [$id]);
        if (!$inv || $inv['status'] === 'void') not_found();
        $amount = (float)post('amount', 0);
        $balance = invoice_balance($inv);
        if ($amount <= 0) {
            set_flash('error', 'Payment amount must be positive.');
            back();
        }
        if ($amount > ($balance + 0.001)) {
            set_flash('error', 'Amount exceeds outstanding balance of ' . money($balance) . '.');
            back();
        }
        $methods = ['cash', 'card', 'transfer', 'bank', 'other'];
        $method = in_array(post('method', 'cash'), $methods, true) ? post('method', 'cash') : 'cash';
tx(function () use ($id, $amount, $balance, $inv, $method) {
            $paymentNo = next_ticket('payments', 'payment_no', 'PAY');
            run('INSERT INTO payments (payment_no, invoice_id, amount, method, reference_no, received_by, paid_at)
                 VALUES (?,?,?,?,?,?,NOW())',
                [$paymentNo, $id, $amount, $method, post('reference_no') ?: null, Auth::id()]);
            $paid = (float)fetch_val('SELECT IFNULL(SUM(amount),0) FROM payments WHERE invoice_id = ?', [$id]);
            $status = ($paid >= (float)$inv['total'] - 0.001) ? 'paid' : 'partial';
            run('UPDATE invoices SET paid_amount = ?, status = ? WHERE id = ?', [$paid, $status, $id]);
        });
        audit('create', 'billing', "Payment " . money($amount) . " on invoice #$id");
        set_flash('success', 'Payment recorded.');
        redirect('billing/show/' . $id);
    }

    public function void($id)
    {
        $this->staffOnly();
        csrf_check();
        $this->guard(['admin']);
        $inv = fetch('SELECT * FROM invoices WHERE id = ?', [$id]);
        if (!$inv) not_found();
        $paid = (float)fetch_val('SELECT IFNULL(SUM(amount),0) FROM payments WHERE invoice_id = ?', [$id]);
        if ($paid > 0) {
            set_flash('error', 'Voiding without a cash refund is not recommended. Reverse the payments first.');
            back();
        }
        run('UPDATE invoices SET status = "void" WHERE id = ?', [$id]);
        audit('update', 'billing', "Voided invoice #$id");
        set_flash('success', 'Invoice voided.');
        redirect('billing');
    }

    public function receipt($id)
    {
        $inv = fetch('SELECT * FROM invoices WHERE id = ?', [$id]);
        if (!$inv) not_found();
        if (Auth::is_patient()) {
            if ((int)$inv['patient_id'] !== (int)Auth::userPatientId()) {
                http_response_code(403);
                require APP_PATH . '/views/errors/403.php';
                exit;
            }
        } else {
            $this->staffOnly();
        }
        $patient = fetch('SELECT * FROM patients WHERE id = ?', [$inv['patient_id']]);
        $payments = fetch_all(
            'SELECT p.*, u.full_name AS received_by_name FROM payments p
             LEFT JOIN users u ON u.id = p.received_by WHERE p.invoice_id = ? ORDER BY p.paid_at DESC', [$id]
        );
        $items = fetch_all('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id', [$id]);
        $this->view('billing/receipt', compact('inv', 'patient', 'payments', 'items'), 'print');
    }
}