<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    // Admin pages use ?action=getAll and ?action=getDetail&id=...
    if ($action === 'getAll') {
        $sql = "
            SELECT o.order_id,
                   CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                   o.order_date,
                   o.total_amount,
                   o.delivery_fee,
                   o.discount_amount,
                   o.final_amount,
                   o.order_status,
                   pm.method_type AS payment_method,
                   p.payment_status,
                   (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.order_id) AS item_count
            FROM orders o
            JOIN users u ON u.user_id=o.user_id
            LEFT JOIN payments p ON p.order_id=o.order_id
            LEFT JOIN payment_methods pm ON pm.payment_method_id=p.payment_method_id
            ORDER BY o.order_date DESC
        ";
        respond(['success' => true, 'orders' => $pdo->query($sql)->fetchAll()]);
    }

    if ($action === 'getDetail') {
        $order_id = (int)($_GET['id'] ?? 0);
        if (!$order_id) respond(['success' => false, 'message' => 'Invalid order ID.'], 400);

        $stmt = $pdo->prepare(" 
            SELECT o.order_id, o.order_date, o.total_amount, o.delivery_fee, o.discount_amount, o.final_amount,
                   o.order_status, CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                   u.phone_num AS phone,
                   CONCAT_WS(', ', a.street, a.barangay, a.city, a.province, a.zip_code) AS address_line,
                   pm.method_type AS payment_method, p.payment_status
            FROM orders o
            JOIN users u ON u.user_id=o.user_id
            LEFT JOIN addresses a ON a.address_id=o.address_id
            LEFT JOIN payments p ON p.order_id=o.order_id
            LEFT JOIN payment_methods pm ON pm.payment_method_id=p.payment_method_id
            WHERE o.order_id=?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        if (!$order) respond(['success' => false, 'message' => 'Order not found.'], 404);

        $stmt = $pdo->prepare("SELECT oi.order_item_id, p.product_name, oi.quantity, oi.unit_price, oi.subtotal FROM order_items oi JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=? ORDER BY oi.order_item_id");
        $stmt->execute([$order_id]);
        respond(['success' => true, 'order' => $order, 'items' => $stmt->fetchAll()]);
    }

    $user_id = (int)($_GET['user_id'] ?? 0);
    $admin = ($_GET['admin'] ?? '') === '1';
    if ($admin) {
        $stmt = $pdo->query("SELECT o.order_id, o.order_date, o.total_amount, o.delivery_fee, o.discount_amount, o.final_amount, o.order_status, u.first_name, u.last_name, u.email, a.street, a.city, a.province, p.payment_status, pm.method_type as payment_method FROM orders o JOIN users u ON u.user_id=o.user_id LEFT JOIN addresses a ON a.address_id=o.address_id LEFT JOIN payments p ON p.order_id=o.order_id LEFT JOIN payment_methods pm ON pm.payment_method_id=p.payment_method_id ORDER BY o.order_date DESC");
    } else {
        if (!$user_id) respond(['success'=>false,'error'=>'user_id required.'], 400);
        $stmt = $pdo->prepare("SELECT o.order_id, o.order_date, o.total_amount, o.delivery_fee, o.discount_amount, o.final_amount, o.order_status, a.street, a.city, a.province, p.payment_status, pm.method_type as payment_method FROM orders o LEFT JOIN addresses a ON a.address_id=o.address_id LEFT JOIN payments p ON p.order_id=o.order_id LEFT JOIN payment_methods pm ON pm.payment_method_id=p.payment_method_id WHERE o.user_id=? ORDER BY o.order_date DESC");
        $stmt->execute([$user_id]);
    }
    $orders = $stmt->fetchAll();
    foreach ($orders as &$order) {
        $istmt = $pdo->prepare("SELECT oi.quantity, oi.unit_price, oi.subtotal, p.product_name, p.image FROM order_items oi JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=?");
        $istmt->execute([$order['order_id']]);
        $order['items'] = $istmt->fetchAll();
    }
    respond(['success'=>true,'orders'=>$orders]);
}

if ($method === 'POST') {
    $body = getBody();
    $action = $_GET['action'] ?? ($body['action'] ?? 'place');

    if ($action === 'update_status' || $action === 'updateStatus') {
        $order_id = (int)($body['order_id'] ?? 0);
        $status = $body['order_status'] ?? ($body['status'] ?? '');
        $allowed = ['Pending','Processing','Shipped','Delivered','Cancelled'];
        if (!$order_id || !in_array($status, $allowed, true)) respond(['success'=>false,'message'=>'Invalid order ID or status.'], 400);
        $pdo->prepare("UPDATE orders SET order_status=? WHERE order_id=?")->execute([$status, $order_id]);
        respond(['success'=>true,'message'=>'Order status updated.']);
    }

    if ($action === 'place') {
        $user_id = (int)($body['user_id'] ?? 0);
        $payment_method = $body['payment_method'] ?? 'Cash on Delivery';
        $address = $body['address'] ?? [];
        $items = $body['items'] ?? [];
        if (!$user_id || empty($items) || empty($address)) respond(['success'=>false,'error'=>'user_id, address and items are required.'], 400);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO addresses (user_id, phone, street, barangay, city, province, zip_code, is_default) VALUES (?,?,?,?,?,?,?,0)");
            $stmt->execute([$user_id,$address['phone']??'',$address['street']??'',$address['barangay']??'',$address['city']??'',$address['province']??'',$address['zip_code']??'']);
            $address_id = $pdo->lastInsertId();

            $total_amount = 0;
            foreach ($items as $item) $total_amount += (float)$item['unit_price'] * (int)$item['quantity'];
            $delivery_fee = $total_amount >= 2900 ? 0 : 99;
            $discount_amount = (float)($body['discount_amount'] ?? 0);
            $final_amount = $total_amount + $delivery_fee - $discount_amount;

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, address_id, total_amount, delivery_fee, discount_amount, final_amount, order_status) VALUES (?,?,?,?,?,?,'Pending')");
            $stmt->execute([$user_id,$address_id,$total_amount,$delivery_fee,$discount_amount,$final_amount]);
            $order_id = $pdo->lastInsertId();

            foreach ($items as $item) {
                $pid=(int)$item['product_id']; $qty=(int)$item['quantity']; $up=(float)$item['unit_price']; $sub=round($up*$qty,2);
                $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)")->execute([$order_id,$pid,$qty,$up,$sub]);
                $pdo->prepare("UPDATE products SET stock_quantity=GREATEST(0, stock_quantity-?) WHERE product_id=?")->execute([$qty,$pid]);
                $pdo->prepare("INSERT INTO inventory_transactions (product_id, transaction_type, quantity, notes) VALUES (?,'Sale',?,?)")->execute([$pid,$qty,"Order #$order_id"]);
            }

            $stmt = $pdo->prepare("SELECT payment_method_id FROM payment_methods WHERE method_type=?");
            $stmt->execute([$payment_method]);
            $pm = $stmt->fetch();
            $pm_id = $pm['payment_method_id'] ?? 1;
            $pay_status = ($payment_method === 'Cash on Delivery') ? 'Unpaid' : 'Pending';
            $pdo->prepare("INSERT INTO payments (order_id, payment_method_id, payment_amount, payment_status) VALUES (?,?,?,?)")->execute([$order_id,$pm_id,$final_amount,$pay_status]);
            $pay_id = $pdo->lastInsertId();
            if ($payment_method !== 'Cash on Delivery' && !empty($body['reference_number'])) {
                $pdo->prepare("INSERT INTO online_payment_details (payment_id, reference_number, account_name, mobile_number) VALUES (?,?,?,?)")->execute([$pay_id,$body['reference_number']??'',$body['account_name']??'',$body['mobile_number']??'']);
            }
            // Deactivate cart and delete its items so the cart is empty on return
            $cartStmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id=? AND is_active=1 LIMIT 1");
            $cartStmt->execute([$user_id]);
            $activeCart = $cartStmt->fetch();
            if ($activeCart) {
                $pdo->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$activeCart['cart_id']]);
                $pdo->prepare("UPDATE carts SET is_active=0 WHERE cart_id=?")->execute([$activeCart['cart_id']]);
            }
            // Create a fresh active cart so user can shop again immediately
            $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)")->execute([$user_id]);
            $pdo->commit();
            respond(['success'=>true,'order_id'=>$order_id,'final_amount'=>$final_amount,'message'=>'Order placed successfully!']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            respond(['success'=>false,'error'=>'Order failed: '.$e->getMessage()], 500);
        }
    }
}

respond(['success'=>false,'error'=>'Method not allowed.'], 405);
