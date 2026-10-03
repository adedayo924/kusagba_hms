<?php

function stock_add($itemId, $qty, $type, $notes, $reference = null, $batch = null, $expiry = null)
{
    if ($qty <= 0) throw new RuntimeException('Quantity must be positive.');
    run('INSERT INTO stock_movements (item_id, quantity, movement_type, reference, batch_no, expiry_date, notes, created_by)
        VALUES (?,?,?,?,?,?,?,?)',
        [$itemId, (int)$qty, $type, $reference, $batch, $expiry, $notes, Auth::id()]);
    // Guard against adding stock to an archived (soft-deleted) item.
    $upd = run('UPDATE inventory_items SET quantity = quantity + ? WHERE id = ? AND deleted_at IS NULL',
        [(int)$qty, $itemId])->rowCount();
    if ($upd !== 1) {
        throw new RuntimeException('Item #' . $itemId . ' no longer exists or has been archived.');
    }
}

function stock_deduct($itemId, $qty, $type, $notes, $reference = null)
{
    if ($qty <= 0) throw new RuntimeException('Quantity must be positive.');

    // Conditional UPDATE: availability is checked and decremented in a single atomic
    // statement. A prior SELECT-then-UPDATE allowed two concurrent dispensers to both
    // observe the same availability and drive quantity negative.
    // Ordered before the ledger insert so a failed deduction never leaves a movement row.
    $upd = run(
        'UPDATE inventory_items SET quantity = quantity - ?
         WHERE id = ? AND quantity >= ? AND deleted_at IS NULL',
        [(int)$qty, $itemId, (int)$qty]
    )->rowCount();

    if ($upd !== 1) {
        $avail = fetch_val('SELECT quantity FROM inventory_items WHERE id = ?', [$itemId]);
        if ($avail === null) {
            throw new RuntimeException('Item #' . $itemId . ' no longer exists or has been archived.');
        }
        throw new RuntimeException('Insufficient stock for this item (available: ' . (int)$avail . ', requested: ' . (int)$qty . ').');
    }

    run('INSERT INTO stock_movements (item_id, quantity, movement_type, reference, notes, created_by)
        VALUES (?,?,?,?,?,?)',
        [$itemId, -(int)$qty, $type, $reference, $notes, Auth::id()]);
}