<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// GET - inventory list with stock status
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $where  = ['p.is_active=1'];
        $params = [];
        if (!empty($_GET['status'])) {
            if ($_GET['status'] === 'Low Stock') {
                $where[] = 'p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_level';
            } elseif ($_GET['status'] === 'Out of Stock') {
                $where[] = 'p.stock_quantity = 0';
            } elseif ($_GET['status'] === 'In Stock') {
                $where[] = 'p.stock_quantity > p.low_stock_level';
            }
        }
        if (!empty($_GET['search'])) {
            $where[]  = '(p.product_name LIKE ? OR p.sku LIKE ?)';
            $s        = '%'.$_GET['search'].'%';
            $params[] = $s;
            $params[] = $s;
        }

        $sql = "SELECT p.product_id, p.product_name, p.sku, p.stock_quantity, p.low_stock_level,
                       c.category_name,
                       CASE WHEN p.stock_quantity=0 THEN 'Out of Stock'
                            WHEN p.stock_quantity<=p.low_stock_level THEN 'Low Stock'
                            ELSE 'In Stock' END AS stock_status
                FROM products p
                JOIN categories c ON c.category_id=p.category_id
                WHERE ".implode(' AND ',$where)."
                ORDER BY p.stock_quantity ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        // Summary counts
        $total    = count($products);
        $low      = count(array_filter($products, fn($p) => $p['stock_status']==='Low Stock'));
        $out      = count(array_filter($products, fn($p) => $p['stock_status']==='Out of Stock'));
        $in_stock = $total - $low - $out;

        respond([
            'success'  => true,
            'products' => $products,
            'summary'  => ['total'=>$total,'in_stock'=>$in_stock,'low_stock'=>$low,'out_of_stock'=>$out],
        ]);
    }

    if ($action === 'transactions') {
        $product_id = (int)($_GET['product_id'] ?? 0);
        $sql = "SELECT it.inventory_transaction_id, it.transaction_type, it.quantity, it.notes,
                       it.transaction_date, p.product_name, s.supplier_name
                FROM inventory_transactions it
                JOIN products p ON p.product_id=it.product_id
                LEFT JOIN suppliers s ON s.supplier_id=it.supplier_id
                " . ($product_id ? "WHERE it.product_id=$product_id" : "") . "
                ORDER BY it.transaction_date DESC LIMIT 100";
        $stmt = $pdo->query($sql);
        respond(['success'=>true,'transactions'=>$stmt->fetchAll()]);
    }

    if ($action === 'suppliers') {
        $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY supplier_name");
        respond(['success'=>true,'suppliers'=>$stmt->fetchAll()]);
    }
}

// POST - restock / adjust
if ($method === 'POST') {
    $body        = getBody();
    $product_id  = (int)($body['product_id'] ?? 0);
    $qty         = (int)($body['quantity'] ?? 0);
    $type        = $body['transaction_type'] ?? 'Restock';   // Restock | Adjustment | Sale
    $supplier_id = $body['supplier_id'] ?? null;
    $notes       = $body['notes'] ?? '';

    if (!$product_id || $qty <= 0) {
        respond(['success'=>false,'error'=>'product_id and quantity > 0 required.'], 400);
    }

    $delta = ($type === 'Sale') ? -$qty : $qty;
    $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity + ?) WHERE product_id=?")
        ->execute([$delta, $product_id]);

    $pdo->prepare("INSERT INTO inventory_transactions (product_id, supplier_id, transaction_type, quantity, notes)
        VALUES (?,?,?,?,?)")->execute([$product_id, $supplier_id ?: null, $type, $qty, $notes]);

    // Fetch new stock level
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id=?");
    $stmt->execute([$product_id]);
    $new_stock = $stmt->fetchColumn();

    respond(['success'=>true,'message'=>'Stock updated.','new_stock'=>(int)$new_stock]);
}

respond(['success'=>false,'error'=>'Method not allowed.'], 405);
