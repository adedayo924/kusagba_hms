<?php
/**
 * Billing helpers: open invoice reuse + automatic charge lines.
 */

/**
 * Patient-level advisory lock guarding open-invoice creation.
 *
 * MySQL/MariaDB named locks are session-scoped, so the lock is deliberately
 * never released: it is held until the request's connection closes. Releasing
 * it while a creating transaction is still open would let a second request
 * acquire the lock, fail to see the uncommitted row and create a duplicate
 * open invoice anyway.
 */
function invoice_lock($patientId)
{
    $db = db();
    $ok = $db->query('SELECT GET_LOCK(' . $db->quote('kusagba_inv_' . (int)$patientId) . ', 5)')->fetchColumn();
    if ((int)$ok !== 1) {
        throw new RuntimeException('Could not lock the patient billing account. Please try again.');
    }
}

function open_invoice_id($patientId)
{
    invoice_lock($patientId);
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

/**
 * Recompute subtotal/total and re-derive the status from what has actually been
 * paid. Previously status was only ever written by the payment path, so adding
 * or removing a line on a paid invoice left `status='paid'` sitting on a
 * nonzero balance — a receivable that dashboard and reports excluded.
 * Void invoices stay void.
 */
function recompute_invoice($invId)
{
    $inv = fetch('SELECT discount, tax_amount, paid_amount, status FROM invoices WHERE id = ?', [$invId]);
    if (!$inv) return;
    $sub = (float)fetch_val('SELECT IFNULL(SUM(amount),0) FROM invoice_items WHERE invoice_id = ?', [$invId]);
    $total = round($sub - (float)$inv['discount'] + (float)$inv['tax_amount'], 2);
    $status = $inv['status'];
    if ($status !== 'void') {
        $paid = (float)$inv['paid_amount'];
        if ($paid > 0.001) {
            $status = ($paid >= $total - 0.001) ? 'paid' : 'partial';
        } else {
            $status = 'unpaid';
        }
    }
    run('UPDATE invoices SET subtotal = ?, total = ?, status = ? WHERE id = ?', [$sub, $total, $status, $invId]);
}

function invoice_balance($inv)
{
    return round((float)$inv['total'] - (float)$inv['paid_amount'], 2);
}
