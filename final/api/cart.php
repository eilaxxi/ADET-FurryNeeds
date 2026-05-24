<?php
require_once 'config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// Helper: get or create active cart for user
function getOrCreateCart($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id=? AND is_active=1 LIMIT 1");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();
    if ($cart) return $cart['cart_id'];
    $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)")->execute([$user_id]);
    return $pdo->lastInsertId();
}

// GET - get cart contents for user
if ($method === 'GET') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    if (!$user_id) respond(['success'=>false,'error'=>'user_id required.'], 400);

    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, ci.quantity, ci.unit_price, ci.discount_amount, ci.subtotal,
               p.product_id, p.product_name, p.image, p.stock_quantity
        FROM carts c
        JOIN cart_items ci ON ci.cart_id = c.cart_id
        JOIN products p ON p.product_id = ci.product_id
        WHERE c.user_id=? AND c.is_active=1
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
    $total = array_sum(array_column($items, 'subtotal'));
    respond(['success'=>true,'items'=>$items,'total'=>(float)$total,'count'=>(int)array_sum(array_map(fn($i)=>(int)$i['quantity'],$items))]);
}

if ($method === 'POST') {
    $body    = getBody();
    $action  = $_GET['action'] ?? 'add';
    $user_id = (int)($body['user_id'] ?? 0);
    if (!$user_id) respond(['success'=>false,'error'=>'user_id required.'], 400);

    // Add item to cart
    if ($action === 'add') {
        $product_id = (int)($body['product_id'] ?? 0);
        $qty        = max(1, (int)($body['quantity'] ?? 1));
        if (!$product_id) respond(['success'=>false,'error'=>'product_id required.'], 400);

        // Fetch product price + promo
        $stmt = $pdo->prepare("
            SELECT p.price, p.stock_quantity,
                   pr.promo_type, pr.discount_value,
                   CASE WHEN pr.is_active=1 AND pr.start_date<=CURDATE() AND pr.end_date>=CURDATE()
                        THEN 1 ELSE 0 END AS promo_active
            FROM products p
            LEFT JOIN promos pr ON pr.promo_id = p.promo_id
            WHERE p.product_id=? AND p.is_active=1
        ");
        $stmt->execute([$product_id]);
        $prod = $stmt->fetch();
        if (!$prod) respond(['success'=>false,'error'=>'Product not found.'], 404);
        if ($prod['stock_quantity'] < $qty) respond(['success'=>false,'error'=>'Not enough stock.'], 400);

        $unit_price = (float)$prod['price'];
        $discount   = 0;
        if ($prod['promo_active']) {
            if ($prod['promo_type'] === 'Percentage') {
                $discount = round($unit_price * $prod['discount_value'] / 100, 2);
            } else {
                $discount = min((float)$prod['discount_value'], $unit_price);
            }
        }
        $subtotal = round(($unit_price - $discount) * $qty, 2);

        $cart_id = getOrCreateCart($pdo, $user_id);

        // Check if item already in cart
        $existing = $pdo->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id=? AND product_id=?");
        $existing->execute([$cart_id, $product_id]);
        $row = $existing->fetch();
        if ($row) {
            $new_qty      = $row['quantity'] + $qty;
            $new_subtotal = round(($unit_price - $discount) * $new_qty, 2);
            $pdo->prepare("UPDATE cart_items SET quantity=?, subtotal=? WHERE cart_item_id=?")
                ->execute([$new_qty, $new_subtotal, $row['cart_item_id']]);
        } else {
            $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, unit_price, discount_amount, subtotal)
                VALUES (?,?,?,?,?,?)")->execute([$cart_id, $product_id, $qty, $unit_price, $discount, $subtotal]);
        }
        respond(['success'=>true,'message'=>'Item added to cart.']);
    }

    // Update quantity
    if ($action === 'update') {
        $cart_item_id = (int)($body['cart_item_id'] ?? 0);
        $qty          = max(1, (int)($body['quantity'] ?? 1));
        if (!$cart_item_id) respond(['success'=>false,'error'=>'cart_item_id required.'], 400);

        $stmt = $pdo->prepare("SELECT ci.unit_price, ci.discount_amount FROM cart_items ci WHERE ci.cart_item_id=?");
        $stmt->execute([$cart_item_id]);
        $item = $stmt->fetch();
        if (!$item) respond(['success'=>false,'error'=>'Cart item not found.'], 404);
        $subtotal = round(($item['unit_price'] - $item['discount_amount']) * $qty, 2);
        $pdo->prepare("UPDATE cart_items SET quantity=?, subtotal=? WHERE cart_item_id=?")
            ->execute([$qty, $subtotal, $cart_item_id]);
        respond(['success'=>true,'message'=>'Cart updated.']);
    }

    // Remove item
    if ($action === 'remove') {
        $cart_item_id = (int)($body['cart_item_id'] ?? 0);
        if (!$cart_item_id) respond(['success'=>false,'error'=>'cart_item_id required.'], 400);
        $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id=?")->execute([$cart_item_id]);
        respond(['success'=>true,'message'=>'Item removed.']);
    }

    // Clear cart
    if ($action === 'clear') {
        $cart_id = getOrCreateCart($pdo, $user_id);
        $pdo->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$cart_id]);
        respond(['success'=>true,'message'=>'Cart cleared.']);
    }
}

respond(['success'=>false,'error'=>'Method not allowed.'], 405);
