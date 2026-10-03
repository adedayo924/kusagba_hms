<?php

class PharmacyController extends Controller
{
    protected $title = 'Pharmacy';
    protected $active = 'pharmacy';

    public function __construct()
    {
        $this->guard(['admin', 'pharmacist', 'nurse']);
    }

    public function index()
    {
        $q = getp('q');
        $showArchived = getp('archived') === '1';
        $where = "category = 'drug'";
        $params = [];
        if (!$showArchived) {
            $where .= ' AND deleted_at IS NULL';
        }
        if ($q !== '') {
            $where .= ' AND (name LIKE ? OR code LIKE ?)';
            $like = "%$q%";
            $params = [$like, $like];
        }
        $drugs = fetch_all("SELECT * FROM inventory_items WHERE $where ORDER BY deleted_at IS NOT NULL, name", $params);
        $this->view('pharmacy/index', compact('drugs', 'q', 'showArchived'));
    }

    /** Single dispatch for create and edit, decided by a hidden `id`. */
    public function save()
    {
        csrf_check();
        $this->guard(['admin', 'pharmacist']);
        $id = (int)post('id', 0);
        $name = post('name');
        if ($name === '') {
            set_flash('error', 'Drug name is required.');
            back();
        }
        $code = post('code') ?: null;
        $unit = post('unit', 'unit') ?: 'unit';
        $reorder = max(0, (int)post('reorder_level', 5));
        $price = (float)post('selling_price', 0);
        $notes = post('notes') ?: null;

        if ($id > 0) {
            $before = fetch('SELECT * FROM inventory_items WHERE id = ?', [$id]);
            if (!$before) not_found();
            run(
                'UPDATE inventory_items SET code = ?, name = ?, unit = ?, reorder_level = ?, selling_price = ?, notes = ?
                 WHERE id = ? AND category = "drug"',
                [$code, $name, $unit, $reorder, $price, $notes, $id]
            );
            changelog('inventory_item', $id, $before, [
                'code' => $code, 'name' => $name, 'unit' => $unit,
                'reorder_level' => $reorder, 'selling_price' => $price,
            ]);
            audit('update', 'pharmacy', "Drug #$id");
            set_flash('success', 'Drug updated.');
        } else {
            run(
                'INSERT INTO inventory_items (code, name, category, unit, quantity, reorder_level, selling_price, notes)
                 VALUES (?,?, "drug", ?, 0, ?, ?, ?)',
                [$code, $name, $unit, $reorder, $price, $notes]
            );
            $id = last_id();
            audit('create', 'pharmacy', "Drug: $name");
            set_flash('success', 'Drug added to pharmacy. Use Restock to add opening stock.');
        }
        redirect('pharmacy');
    }

    public function store()
    {
        $this->save();
    }

    public function update($id)
    {
        $_POST['id'] = (int)$id;
        $this->save();
    }

    public function restock($id = null)
    {
        csrf_check();
        $this->guard(['admin', 'pharmacist']);
        $itemId = ($id !== '' && $id !== null) ? (int)$id : (int)post('item_id', 0);
        $qty = (int)post('quantity', 0);
        if ($itemId <= 0) {
            set_flash('error', 'Item not found.');
            back();
        }
        if ($qty <= 0) {
            set_flash('error', 'Quantity must be more than zero.');
            back();
        }
        try {
            tx(function () use ($itemId, $qty) {
                stock_add($itemId, $qty, 'purchase', 'Pharmacy restock', null, post('batch_no') ?: null, post('expiry_date') ?: null);
            });
        } catch (RuntimeException $e) {
            set_flash('error', $e->getMessage());
            back();
        }
        audit('restock', 'pharmacy', "Drug #$itemId +$qty");
        set_flash('success', 'Stock added.');
        back();
    }

    /**
     * Archive a drug. Previously a hard DELETE that ignored prescription_items, so
     * deleting a drug stripped the name from every historical prescription that
     * referenced it.
     */
    public function delete($id)
    {
        csrf_check();
        $this->guard(['admin']);
        $item = fetch('SELECT * FROM inventory_items WHERE id = ? AND category = "drug" AND deleted_at IS NULL', [$id]);
        if (!$item) not_found();
        if ((int)$item['quantity'] > 0) {
            set_flash('error', 'This drug still has ' . (int)$item['quantity'] . ' unit(s) in stock. Write the balance off with an adjustment first.');
            back();
        }
        soft_delete('inventory_items', $id, ['active' => 0]);
        audit('archive', 'pharmacy', "Drug #$id ({$item['name']})");
        set_flash('success', 'Drug archived. Prescriptions already issued against it are unaffected.');
        redirect('pharmacy');
    }

    public function restore($id)
    {
        csrf_check();
        $this->guard(['admin', 'pharmacist']);
        if (!fetch('SELECT id FROM inventory_items WHERE id = ?', [$id])) not_found();
        soft_restore('inventory_items', $id);
        audit('restore', 'pharmacy', "Drug #$id");
        set_flash('success', 'Drug restored.');
        redirect('pharmacy');
    }
}