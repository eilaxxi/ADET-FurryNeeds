<?php
// product-detail.php
// Put this file in: C:\xampp\htdocs\project\product-detail.php
// Database name: furryneeds_db
// Test: http://localhost/project/product-detail.php?action=test

header('Content-Type: application/json; charset=UTF-8');

$host = 'localhost';
$dbname = 'furryneeds_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Make sure MySQL is running and the database name is furryneeds_db.',
        'error' => $e->getMessage()
    ]);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = 3; // temporary customer account while login/session is not yet connected

function json_response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_or_create_cart(PDO $pdo, int $user_id): int {
    $stmt = $pdo->prepare('SELECT cart_id FROM carts WHERE user_id = ? AND is_active = 1 ORDER BY cart_id DESC LIMIT 1');
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();

    if ($cart) {
        return (int)$cart['cart_id'];
    }

    $stmt = $pdo->prepare('INSERT INTO carts (user_id, is_active) VALUES (?, 1)');
    $stmt->execute([$user_id]);
    return (int)$pdo->lastInsertId();
}

if ($action === 'test') {
    json_response([
        'success' => true,
        'message' => 'Connected to furryneeds_db successfully!'
    ]);
}

if ($action === 'products') {
    $stmt = $pdo->query("SELECT p.product_id, p.category_id, p.product_name, p.description, p.price, p.image, p.ingredients, p.stock_quantity, p.is_active, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_id ASC");
    json_response([
        'success' => true,
        'products' => $stmt->fetchAll()
    ]);
}

if ($action === 'product') {
    $product_id = (int)($_GET['id'] ?? 1);

    $stmt = $pdo->prepare("SELECT p.product_id, p.category_id, p.product_name, p.description, p.price, p.image, p.sku, p.ingredients, p.stock_quantity, p.is_active, c.category_name, pr.promo_type, pr.discount_value FROM products p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN promos pr ON p.promo_id = pr.promo_id AND pr.is_active = 1 WHERE p.product_id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        json_response([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }

    json_response([
        'success' => true,
        'product' => $product
    ]);
}

if ($action === 'cart_count') {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(ci.quantity), 0) AS cart_count FROM carts c LEFT JOIN cart_items ci ON c.cart_id = ci.cart_id WHERE c.user_id = ? AND c.is_active = 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    json_response([
        'success' => true,
        'cart_count' => (int)($row['cart_count'] ?? 0)
    ]);
}

if ($action === 'add_to_cart') {
    $product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? $_GET['quantity'] ?? 1);

    if ($product_id <= 0) {
        json_response([
            'success' => false,
            'message' => 'Invalid product ID'
        ]);
    }

    if ($quantity <= 0) {
        $quantity = 1;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT product_id, product_name, price, stock_quantity FROM products WHERE product_id = ? LIMIT 1');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception('Product not found');
        }

        if ((int)$product['stock_quantity'] < $quantity) {
            throw new Exception('Not enough stock');
        }

        $cart_id = get_or_create_cart($pdo, $user_id);
        $unit_price = (float)$product['price'];

        $stmt = $pdo->prepare('SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? ORDER BY cart_item_id ASC LIMIT 1');
        $stmt->execute([$cart_id, $product_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $new_quantity = (int)$existing['quantity'] + $quantity;

            if ((int)$product['stock_quantity'] < $new_quantity) {
                throw new Exception('Not enough stock for this quantity');
            }

            $new_subtotal = $unit_price * $new_quantity;
            $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ?, unit_price = ?, subtotal = ? WHERE cart_item_id = ?');
            $stmt->execute([$new_quantity, $unit_price, $new_subtotal, $existing['cart_item_id']]);
        } else {
            $subtotal = $unit_price * $quantity;
            $stmt = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity, unit_price, discount_amount, subtotal) VALUES (?, ?, ?, ?, 0.00, ?)');
            $stmt->execute([$cart_id, $product_id, $quantity, $unit_price, $subtotal]);
        }

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) AS cart_count FROM cart_items WHERE cart_id = ?');
        $stmt->execute([$cart_id]);
        $count = $stmt->fetch();

        $pdo->commit();

        json_response([
            'success' => true,
            'message' => 'Added to cart',
            'cart_count' => (int)($count['cart_count'] ?? 0)
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        json_response([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

json_response([
    'success' => false,
    'message' => 'No valid action selected',
    'available_actions' => ['test', 'products', 'product', 'cart_count', 'add_to_cart']
]);
