<?php

class PrescriptionsController extends Controller
{
    protected $title = 'Prescriptions';
    // Must match the 'url' of the Prescriptions entry in the sidebar menu, otherwise
    // the Pharmacy item stayed highlighted while viewing prescriptions.
    protected $active = 'prescriptions';

    public function __construct()
    {
        $this->guard(['admin', 'doctor', 'pharmacist', 'nurse']);
    }

    public function index()
    {
        $status = getp('status', 'active');
        $q = getp('q');
        $where = "p.id > 0";
        $params = [];
        if ($status) {
            $where .= ' AND p.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where .= ' AND (p.prescription_no LIKE ? OR patients.last_name LIKE ? OR patients.first_name LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        $list = fetch_all(
            "SELECT p.*, patients.patient_no, patients.last_name AS patient_last, patients.first_name AS patient_first,
                    users.full_name AS doctor_name
             FROM prescriptions p
             JOIN patients ON patients.id = p.patient_id
             LEFT JOIN users ON users.id = p.prescribed_by
             WHERE $where ORDER BY p.prescribed_at DESC",
            $params
        );
        $this->view('prescriptions/index', compact('list', 'status', 'q'));
    }

    public function create()
    {
        $this->guard(['admin', 'doctor']);
        $patients = fetch_all('SELECT * FROM patients ORDER BY last_name, first_name');
        $drugs = fetch_all('SELECT * FROM inventory_items WHERE category = "drug" ORDER BY name');
        $consultations = fetch_all(
            "SELECT c.*, patients.patient_no, patients.last_name AS patient_last, patients.first_name AS patient_first
             FROM consultations c JOIN patients ON patients.id = c.patient_id
             ORDER BY c.visit_date DESC LIMIT 20"
        );
        $preselectPatientId = 0;
        $preselectConsultationId = (int)getp('consultation');
        if ($preselectConsultationId) {
            $c = fetch('SELECT id, patient_id FROM consultations WHERE id = ?', [$preselectConsultationId]);
            if ($c) $preselectPatientId = (int)$c['patient_id'];
        }
        $this->view('prescriptions/form', compact('patients', 'drugs', 'consultations', 'preselectPatientId', 'preselectConsultationId'));
    }

    public function store()
    {
        csrf_check();
        $this->guard(['admin', 'doctor']);
        $patientId = (int)post('patient_id', 0);
        $consultationId = (int)post('consultation_id', 0);
        $drugs = array_values(array_filter(array_map('intval', $_POST['drug_id'] ?? [])));
        $qtys = $_POST['qty'] ?? [];
        $dosages = $_POST['dosage'] ?? [];
        $frequencies = $_POST['frequency'] ?? [];
        $durations = $_POST['duration'] ?? [];
        $itemNotes = $_POST['item_notes'] ?? [];
        if (!$patientId || !$drugs) {
            set_flash('error', 'Select a patient and at least one drug.');
            back();
        }
        $prescriptionNo = next_ticket('prescriptions', 'prescription_no', 'RX');
        tx(function () use ($patientId, $consultationId, $drugs, $qtys, $dosages, $frequencies, $durations, $itemNotes, $prescriptionNo) {
            run('INSERT INTO prescriptions (prescription_no, patient_id, consultation_id, prescribed_by, notes, prescribed_at)
                 VALUES (?,?,?,?,?,NOW())',
                [$prescriptionNo, $patientId, $consultationId ?: null, Auth::id(), post('notes') ?: null]);
            $rxId = last_id();
            foreach ($drugs as $i => $drugId) {
                run('INSERT INTO prescription_items (prescription_id, item_id, quantity, dosage, frequency, duration, notes)
                     VALUES (?,?,?,?,?,?,?)',
                    [$rxId, $drugId, (int)($qtys[$i] ?? 1), $dosages[$i] ?? '', $frequencies[$i] ?? '',
                     $durations[$i] ?? '', $itemNotes[$i] ?? null]);
            }
        });
        audit('create', 'prescriptions', "Rx $prescriptionNo");
        set_flash('success', 'Prescription ' . $prescriptionNo . ' saved.');
        redirect('prescriptions');
    }

    public function show($id)
    {
        $rx = fetch(
            "SELECT p.*, patients.patient_no, patients.last_name AS patient_last, patients.first_name AS patient_first,
                    patients.dob, patients.gender,
                    users.full_name AS doctor_name
             FROM prescriptions p
             JOIN patients ON patients.id = p.patient_id
             LEFT JOIN users ON users.id = p.prescribed_by
             WHERE p.id = ?",
            [$id]
        );
        if (!$rx) not_found();
        $items = fetch_all(
            "SELECT pi.*, i.name AS drug_name, i.unit
             FROM prescription_items pi LEFT JOIN inventory_items i ON i.id = pi.item_id
             WHERE pi.prescription_id = ? ORDER BY pi.id", [$id]
        );
        $disabled = $rx['status'] === 'dispensed';
        $this->view('prescriptions/show', compact('rx', 'items', 'disabled'));
    }

    public function cancel($id)
    {
        csrf_check();
        $this->guard(['admin', 'doctor']);
        run('UPDATE prescriptions SET status = "cancelled" WHERE id = ?', [$id]);
        audit('cancel', 'prescriptions', "Rx #$id");
        set_flash('success', 'Prescription cancelled.');
        back();
    }

    public function item_dispense($rxId, $itemId)
    {
        csrf_check();
        $this->guard(['admin', 'pharmacist', 'nurse']);
        $item = fetch(
            "SELECT pi.*, i.name, i.selling_price, r.prescription_no, r.status AS rx_status
             FROM prescription_items pi
             JOIN prescriptions r ON r.id = pi.prescription_id
             JOIN inventory_items i ON i.id = pi.item_id
             WHERE pi.id = ? AND pi.prescription_id = ?", [$itemId, $rxId]
        );
        if (!$item || $item['rx_status'] !== 'active') not_found();
        $qty = (int)post('qty', 0);
        if ($qty <= 0) {
            set_flash('error', 'Quantity must be positive.');
            back();
        }
        // Server-side cap: the qty input carries a max attribute client-side only,
        // which is trivially bypassed. Never dispense more than was prescribed.
        $remaining = (int)$item['quantity'] - (int)$item['quantity_dispensed'];
        if ($remaining <= 0) {
            set_flash('error', 'This line has already been fully dispensed.');
            back();
        }
        if ($qty > $remaining) {
            set_flash('error', "Cannot dispense $qty. Only $remaining remaining of {$item['name']} on this prescription.");
            back();
        }
        try {
            tx(function () use ($item, $itemId, $rxId, $qty) {
                // Conditional update: re-assert the cap inside the transaction so two
                // concurrent dispensers cannot both pass the check above.
                $claimed = run(
                    'UPDATE prescription_items SET quantity_dispensed = quantity_dispensed + ?, dispensed_at = NOW()
                     WHERE id = ? AND quantity_dispensed + ? <= quantity',
                    [$qty, $itemId, $qty]
                )->rowCount();
                if ($claimed !== 1) {
                    throw new RuntimeException('Dispensed quantity exceeds the prescribed amount. Reload and try again.');
                }
                stock_deduct($item['item_id'], $qty, 'dispense', "Rx {$item['prescription_no']}", "prescription_{$itemId}");
                $unit = (float)$item['selling_price'];
                $amount = $unit * $qty;
                $patientId = (int)fetch_val('SELECT patient_id FROM prescriptions WHERE id = ?', [$rxId]);
                add_invoice_line($patientId, 'drug', $itemId, "{$item['name']} × $qty ({$item['prescription_no']})", $qty, $unit);
            });
        } catch (RuntimeException $e) {
            set_flash('error', $e->getMessage());
            back();
        }
        audit('dispense', 'prescriptions', "{$item['name']} x$qty (#$rxId)");
        set_flash('success', 'Drug dispensed and billed.');
        back();
    }

    public function complete($rxId)
    {
        csrf_check();
        $this->guard(['admin', 'pharmacist', 'nurse']);
        $rx = fetch('SELECT id, prescription_no, status FROM prescriptions WHERE id = ?', [$rxId]);
        if (!$rx) not_found();
        if ($rx['status'] !== 'active') {
            set_flash('error', 'Only an active prescription can be marked as dispensed.');
            back();
        }
        // Outstanding = prescribed minus already dispensed, not merely "has no dispensed_at".
        $pending = (int)fetch_val(
            'SELECT COUNT(*) FROM prescription_items
             WHERE prescription_id = ? AND quantity_dispensed < quantity',
            [$rxId]
        );
        run('UPDATE prescriptions SET status = "dispensed", dispensed_at = NOW(), dispensed_by = ?
             WHERE id = ?', [Auth::id(), $rxId]);
        audit('complete', 'prescriptions', "Rx #$rxId");
        set_flash('success', $pending
            ? "Prescription marked dispensed with $pending line(s) still outstanding."
            : 'Prescription marked dispensed.');
        back();
    }
}