<?php
/**
 * Billing helpers: open invoice reuse + automatic charge lines.
 */

function open_invoice_id($patientId)
{
    $inv = fetch('SELECT id FROM invoices WHERE patient_id = ? AND status IN ("unpaid","partial") ORDER BY id DESC LIMIT 1', [$patientId]);
    if ($inv) return (int)$inv['id'];
    $no = next_ticket('invoices', 'invoice_no', 'INV');
    run('INSERT INTO invoices (invoice_no, patient_id, invoice_date) VALUES (?,?,?)', [$no, $patientId, date('Y-m-d')]);
    return last_id();
}

function add_invoice_line($patientId, $type, $refId, $description, $qty = 1, $unitPrice = 0)
{
    $invId = open_invoice_id($patientId);
    run('INSERT INTO invoice_items (invoice_id, item_type, ref_id, description, qty, unit_price, amount) VALUES (?,?,?,?,?,?,?)', [
        $invId, $type, $refId ?: null, $description, (int)$qty, (float)$unitPrice, round((int)$qty * (float)$unitPrice, 2),
    ]);
    recompute_invoice($invId);
    return $invId;
}

function recompute_invoice($invId)
{
    $inv = fetch('SELECT discount, tax_amount FROM invoices WHERE id = ?', [$invId]);
    if (!$inv) return;
    $sub = (float)fetch_val('SELECT IFNULL(SUM(amount),0) FROM invoice_items WHERE invoice_id = ?', [$invId]);
    $total = round($sub - (float)$inv['discount'] + (float)$inv['tax_amount'], 2);
    run('UPDATE invoices SET subtotal = ?, total = ? WHERE id = ?', [$sub, $total, $invId]);
}

function invoice_balance($inv)
{
    return round((float)$inv['total'] - (float)$inv['paid_amount'], 2);
}