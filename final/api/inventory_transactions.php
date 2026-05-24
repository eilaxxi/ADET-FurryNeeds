<?php
// api/inventory_transactions.php
// Full CRUD for inventory transaction logs
// Supports: list, add (restock/adjustment/sale), get suppliers

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();
$action = $_GET['action'] ?? 'list';

// ──────────────────────────────────────────────
// GET
// ──────────────────────────────────────────────
if ($method === 'GET') {

    // List transactions (optional ?product_id=, ?type=, ?limit=)
    if ($action === 'list') {
        $where  = [];
        $params = [];

        if (!empty($_GET['product_id'])) {
            $where[]  = 'it.product_id = ?';
            $params[] = (int)$_GET['product_id'];
        }
        if (!empty($_GET['type'])) {
            $where[]  = 'it.transaction_type = ?';
            $params[] = $_GET['type'];
        }
        if (!empty($_GET['search'])) {
            $where[]  = '(p.product_name LIKE ? OR s.supplier_name LIKE ? OR it.notes LIKE ?)';
            $s        = '%' . $_GET['search'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit       = min((int)($_GET['limit'] ?? 100), 500);

        $sql = "
            SELECT
                it.inventory_transaction_id,
                it.transaction_type,
                it.quantity,
                it.notes,
                it.transaction_date,
                p.product_id,
                p.product_name,
                p.sku,
                p.stock_quantity AS current_stock,
                s.supplier_name
            FROM inventory_transactions it
            JOIN products p ON p.product_id = it.product_id
            LEFT JOIN suppliers s ON s.supplier_id = it.supplier_id
            $whereClause
            ORDER BY it.transaction_date DESC
            LIMIT $limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        // Summary counts
        $countSql = "
            SELECT transaction_type, COUNT(*) AS cnt, SUM(quantity) AS total_qty
            FROM inventory_transactions it
            JOIN products p ON p.product_id = it.product_id
            $whereClause
            GROUP BY transaction_type
        ";
        $cStmt = $pdo->prepare($countSql);
        $cStmt->execute($params);
        $summary = [];
        foreach ($cStmt->fetchAll() as $row) {
            $summary[$row['transaction_type']] = [
                'count'     => (int)$row['cnt'],
                'total_qty' => (int)$row['total_qty'],
            ];
        }

        respond([
            'success'      => true,
            'transactions' => $transactions,
            'summary'      => $summary,
        ]);
    }

    // Get all suppliers for dropdown
    if ($action === 'suppliers') {
        $stmt = $pdo->query("SELECT supplier_id, supplier_name, contact_person, phone FROM suppliers ORDER BY supplier_name");
        respond(['success' => true, 'suppliers' => $stmt->fetchAll()]);
    }

    // Get products for dropdown (active only)
    if ($action === 'products') {
        $stmt = $pdo->query("
            SELECT p.product_id, p.product_name, p.sku, p.stock_quantity, p.low_stock_level,
                   c.category_name
            FROM products p
            JOIN categories c ON c.category_id = p.category_id
            WHERE p.is_active = 1
            ORDER BY p.product_name
        ");
        respond(['success' => true, 'products' => $stmt->fetchAll()]);
    }

    respond(['success' => false, 'error' => 'Unknown action.'], 400);
}

// ──────────────────────────────────────────────
// POST — add a new transaction (restock / adjustment / sale)
// ──────────────────────────────────────────────
if ($method === 'POST') {
    $body        = getBody();
    $product_id  = (int)($body['product_id']       ?? 0);
    $qty         = (int)($body['quantity']          ?? 0);
    $type        = trim($body['transaction_type']   ?? '');
    $supplier_id = !empty($body['supplier_id'])     ? (int)$body['supplier_id'] : null;
    $notes       = trim($body['notes']              ?? '');

    $allowed_types = ['Restock', 'Sale', 'Adjustment'];

    if (!$product_id)                        respond(['success' => false, 'error' => 'product_id is required.'], 400);
    if ($qty <= 0)                           respond(['success' => false, 'error' => 'quantity must be greater than 0.'], 400);
    if (!in_array($type, $allowed_types))    respond(['success' => false, 'error' => 'transaction_type must be Restock, Sale, or Adjustment.'], 400);

    // Check product exists
    $pCheck = $pdo->prepare("SELECT product_id, product_name, stock_quantity FROM products WHERE product_id = ? AND is_active = 1");
    $pCheck->execute([$product_id]);
    $product = $pCheck->fetch();
    if (!$product) respond(['success' => false, 'error' => 'Product not found or inactive.'], 404);

    // Compute stock delta
    $delta = ($type === 'Sale') ? -$qty : $qty;
    $new_stock = $product['stock_quantity'] + $delta;

    if ($new_stock < 0) {
        respond([
            'success' => false,
            'error'   => "Insufficient stock. Current: {$product['stock_quantity']}, requested sale: $qty.",
        ], 422);
    }

    $pdo->beginTransaction();
    try {
        // Update product stock
        $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?")
            ->execute([$new_stock, $product_id]);

        // Log the transaction
        $pdo->prepare("
            INSERT INTO inventory_transactions
                (product_id, supplier_id, transaction_type, quantity, notes)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$product_id, $supplier_id, $type, $qty, $notes]);

        $transaction_id = $pdo->lastInsertId();
        $pdo->commit();

        respond([
            'success'        => true,
            'message'        => "Transaction recorded. New stock: $new_stock.",
            'transaction_id' => (int)$transaction_id,
            'new_stock'      => $new_stock,
            'product_name'   => $product['product_name'],
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        respond(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()], 500);
    }
}

respond(['success' => false, 'error' => 'Method not allowed.'], 405);
