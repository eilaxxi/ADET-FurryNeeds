<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// GET /api/products.php  - list all active products (optional ?category_id=&search=&pet_type=)
if ($method === 'GET') {
    $where  = ['p.is_active = 1'];
    $params = [];

    if (!empty($_GET['category_id'])) {
        $where[]  = 'p.category_id = ?';
        $params[] = (int)$_GET['category_id'];
    }
    if (!empty($_GET['search'])) {
        $where[]  = '(p.product_name LIKE ? OR p.sku LIKE ?)';
        $s        = '%' . $_GET['search'] . '%';
        $params[] = $s;
        $params[] = $s;
    }
    // pet_type filter via category name prefix
    if (!empty($_GET['pet_type'])) {
        $where[]  = 'c.category_name LIKE ?';
        $params[] = $_GET['pet_type'] . '%';
    }

    $sql = "SELECT p.product_id, p.product_name, p.description, p.price,
                   p.image, p.sku, p.ingredients, p.stock_quantity, p.low_stock_level,
                   c.category_name,
                   pr.promo_name, pr.promo_type, pr.discount_value,
                   CASE WHEN pr.is_active=1 AND pr.start_date<=CURDATE() AND pr.end_date>=CURDATE()
                        THEN 1 ELSE 0 END AS promo_active
            FROM products p
            JOIN categories c ON c.category_id = p.category_id
            LEFT JOIN promos pr ON pr.promo_id = p.promo_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.product_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Compute final price with promo
    foreach ($products as &$prod) {
        $prod['final_price'] = (float)$prod['price'];
        if ($prod['promo_active']) {
            if ($prod['promo_type'] === 'Percentage') {
                $prod['final_price'] = round($prod['price'] * (1 - $prod['discount_value'] / 100), 2);
            } else {
                $prod['final_price'] = max(0, round($prod['price'] - $prod['discount_value'], 2));
            }
        }
        // Stock status
        if ($prod['stock_quantity'] <= 0) {
            $prod['stock_status'] = 'Out of Stock';
        } elseif ($prod['stock_quantity'] <= $prod['low_stock_level']) {
            $prod['stock_status'] = 'Low Stock';
        } else {
            $prod['stock_status'] = 'In Stock';
        }
    }
    respond(['success' => true, 'products' => $products]);
}

// POST /api/products.php?action=add  - add new product (admin)
if ($method === 'POST') {
    $body        = getBody();
    $action      = $_GET['action'] ?? 'add';

    if ($action === 'add') {
        $required = ['product_name','category_id','price','stock_quantity'];
        foreach ($required as $f) {
            if (empty($body[$f])) respond(['success'=>false,'error'=>"$f is required."], 400);
        }
        $stmt = $pdo->prepare("INSERT INTO products
            (category_id, promo_id, product_name, description, price, image, sku, ingredients, stock_quantity, low_stock_level, is_active)
            VALUES (?,?,?,?,?,?,?,?,?,?,1)");
        $stmt->execute([
            (int)$body['category_id'],
            $body['promo_id'] ?: null,
            $body['product_name'],
            $body['description'] ?? '',
            (float)$body['price'],
            $body['image'] ?? '',
            $body['sku'] ?? '',
            $body['ingredients'] ?? '',
            (int)$body['stock_quantity'],
            (int)($body['low_stock_level'] ?? 10),
        ]);
        respond(['success'=>true,'product_id'=>$pdo->lastInsertId(),'message'=>'Product added.']);
    }

    if ($action === 'update') {
        $id = (int)($body['product_id'] ?? 0);
        if (!$id) respond(['success'=>false,'error'=>'product_id required.'], 400);
        $stmt = $pdo->prepare("UPDATE products SET
            category_id=?, promo_id=?, product_name=?, description=?, price=?,
            image=?, sku=?, ingredients=?, stock_quantity=?, low_stock_level=?, is_active=?
            WHERE product_id=?");
        $stmt->execute([
            (int)$body['category_id'],
            $body['promo_id'] ?: null,
            $body['product_name'],
            $body['description'] ?? '',
            (float)$body['price'],
            $body['image'] ?? '',
            $body['sku'] ?? '',
            $body['ingredients'] ?? '',
            (int)$body['stock_quantity'],
            (int)($body['low_stock_level'] ?? 10),
            isset($body['is_active']) ? (int)$body['is_active'] : 1,
            $id,
        ]);
        respond(['success'=>true,'message'=>'Product updated.']);
    }

    if ($action === 'delete') {
        $id = (int)($body['product_id'] ?? 0);
        if (!$id) respond(['success'=>false,'error'=>'product_id required.'], 400);
        $pdo->prepare("UPDATE products SET is_active=0 WHERE product_id=?")->execute([$id]);
        respond(['success'=>true,'message'=>'Product removed.']);
    }

    if ($action === 'update_stock') {
        $id       = (int)($body['product_id'] ?? 0);
        $qty      = (int)($body['quantity'] ?? 0);
        $type     = $body['transaction_type'] ?? 'Restock';
        $supplier = $body['supplier_id'] ?? null;
        $notes    = $body['notes'] ?? '';

        if (!$id) respond(['success'=>false,'error'=>'product_id required.'], 400);

        // Adjust stock
        $delta = ($type === 'Sale') ? -abs($qty) : abs($qty);
        $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id=?")->execute([$delta, $id]);

        // Log transaction
        $pdo->prepare("INSERT INTO inventory_transactions (product_id, supplier_id, transaction_type, quantity, notes)
            VALUES (?,?,?,?,?)")->execute([$id, $supplier, $type, abs($qty), $notes]);

        respond(['success'=>true,'message'=>'Stock updated.']);
    }
}

respond(['success'=>false,'error'=>'Method not allowed.'], 405);
