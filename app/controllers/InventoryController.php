<?php

class InventoryController extends Controller
{
    protected $title = 'Inventory';
    protected $active = 'inventory';

    public function __construct()
    {
        $this->guard(['admin', 'pharmacist']);
    }

    public function index()
    {
        $cat = getp('category', '');
        $q = getp('q');
        $showArchived = getp('archived') === '1';
        $where = 'i.id > 0';
        $params = [];
        if (!$showArchived) {
            $where .= ' AND i.deleted_at IS NULL';
        }
        if (in_array($cat, ['supply', 'equipment', 'other', 'drug'], true)) {
            $where .= ' AND i.category = ?';
            $params[] = $cat;
        }
        if ($q !== '') {
            $where .= ' AND (i.name LIKE ? OR i.code LIKE ?)';
            $like = "%$q%";
            $params[] = $like;
            $params[] = $like;
        }
        $items = fetch_all(
            "SELECT i.*,
                    (SELECT IFNULL(SUM(quantity),0) FROM stock_movements m WHERE m.item_id = i.id AND m.quantity > 0) AS stocked_in,
                    (SELECT IFNULL(SUM(quantity),0) FROM stock_movements m WHERE m.item_id = i.id AND m.quantity < 0) AS stocked_out
             FROM inventory_items i WHERE $where ORDER BY i.deleted_at IS NOT NULL, i.category, i.name",
            $params
        );
        $this->view('inventory/index', compact('items', 'cat', 'q', 'showArchived'));
    }

    /** Single dispatch for create and edit, decided by a hidden `id` field. */
    public function save()
    {
        csrf_check();
        $id = (int)post('id', 0);
        $name = post('name');
        if ($name === '') {
            set_flash('error', 'Item name is required.');
            back();
        }
        $categories = ['drug', 'supply', 'equipment', 'other'];
        $category = in_array(post('category', 'supply'), $categories, true) ? post('category', 'supply') : 'supply';
        $code = post('code') ?: null;
        $unit = post('unit', 'unit') ?: 'unit';
        $reorder = max(0, (int)post('reorder_level', 0));
        $price = (float)post('selling_price', 0);
        $notes = post('notes') ?: null;

        if ($id > 0) {
            $before = fetch('SELECT * FROM inventory_items WHERE id = ?', [$id]);
            if (!$before) not_found();
            run(
                'UPDATE inventory_items SET code = ?, name = ?, category = ?, unit = ?, reorder_level = ?, selling_price = ?, notes = ?
                 WHERE id = ?',
                [$code, $name, $category, $unit, $reorder, $price, $notes, $id]
            );
            changelog('inventory_item', $id, $before, [
                'code' => $code, 'name' => $name, 'category' => $category, 'unit' => $unit,
                'reorder_level' => $reorder, 'selling_price' => $price,
            ]);
            audit('update', 'inventory', "Item #$id");
            set_flash('success', 'Item updated.');
        } else {
            run(
                'INSERT INTO inventory_items (code, name, category, unit, quantity, reorder_level, selling_price, notes)
                 VALUES (?,?,?,?,0,?,?,?)',
                [$code, $name, $category, $unit, $reorder, $price, $notes]
            );
            $id = last_id();
            audit('create', 'inventory', "Item: $name");
            set_flash('success', 'Item added.');
        }
        back();
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

    /**
 * Adjust stock. The item id is taken from the POST body when the URL omits it,
 * so the form works without JavaScript rewriting its action.
 */
    public function adjust($id = null)
    {
        csrf_check();
        $id = ($id === null || $id === '') ? (int)post('item_id', 0) : (int)$id;
        $qty = (int)post('quantity', 0);
        $reason = post('reason');
        if ($qty === 0) {
            set_flash('error', 'Quantity change must be non-zero (use positive to add, negative to remove).');
            back();
        }
        $item = fetch('SELECT name FROM inventory_items WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$item) not_found();
        try {
            tx(function () use ($id, $qty, $reason, $item) {
                if ($qty > 0) {
                    stock_add($id, $qty, 'adjustment', $reason ?: 'Stock adjustment', null, post('batch_no') ?: null, post('expiry_date') ?: null);
                } else {
                    stock_deduct($id, abs($qty), 'adjustment', $reason ?: 'Stock adjustment');
                }
            });
        } catch (RuntimeException $e) {
            set_flash('error', $e->getMessage());
            back();
        }
        audit('adjust', 'inventory', "{$item['name']} $qty");
        set_flash('success', 'Stock updated.');
        back();
    }

    /** Archive an item: keeps dispensing history, prescriptions and invoices intact. */
    public function delete($id)
    {
        csrf_check();
        $this->guard(['admin']);
        $item = fetch('SELECT * FROM inventory_items WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$item) not_found();
        if ((int)$item['quantity'] !== 0) {
            set_flash('error', 'This item still has stock on hand. Write it off with an adjustment before archiving.');
            back();
        }
        soft_delete('inventory_items', $id, ['active' => 0]);
        audit('archive', 'inventory', "Item #$id ({$item['name']})");
        set_flash('success', 'Item archived.');
        back();
    }

    public function restore($id)
    {
        csrf_check();
        $this->guard(['admin']);
        if (!fetch('SELECT id FROM inventory_items WHERE id = ?', [$id])) not_found();
        soft_restore('inventory_items', $id);
        audit('restore', 'inventory', "Item #$id");
        set_flash('success', 'Item restored.');
        back();
    }

    public function toggle($id)
    {
        csrf_check();
        $item = fetch('SELECT * FROM inventory_items WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$item) not_found();
        run('UPDATE inventory_items SET active = IF(active=1,0,1) WHERE id = ?', [$id]);
        audit('update', 'inventory', ($item['active'] ? 'Deactivated ' : 'Activated ') . $item['name']);
        back();
    }

    public function movements()
    {
        $itemId = getp('item', '');
        $q = getp('q');
        $where = 'm.id > 0';
        $params = [];
        if ($itemId !== '') {
            $where .= ' AND m.item_id = ?';
            $params[] = (int)$itemId;
        }
        if ($q !== '') {
            $where .= ' AND (m.reference LIKE ? OR m.notes LIKE ? OR i.name LIKE ?)';
            $like = "%$q%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $rows = fetch_all(
            "SELECT m.*, i.name AS item_name, u.full_name AS user_name
             FROM stock_movements m
             JOIN inventory_items i ON i.id = m.item_id
             LEFT JOIN users u ON u.id = m.created_by
             WHERE $where ORDER BY m.id DESC LIMIT 300",
            $params
        );
        $items = fetch_all('SELECT id, name FROM inventory_items ORDER BY name');
        $this->title = 'Stock movements';
        $this->view('inventory/movements', compact('rows', 'items', 'itemId', 'q'));
    }
}
