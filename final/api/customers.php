<?php
require_once 'config.php';

$action = $_GET['action'] ?? 'getAll';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if ($action === 'getAll') {
    $sql = "
        SELECT
            u.user_id AS UserID,
            u.first_name AS FirstName,
            u.last_name AS LastName,
            u.email AS Email,
            u.phone_num AS PhoneNum,
            u.date_created AS DateCreated,
            COUNT(DISTINCT o.order_id) AS TotalOrders,
            COALESCE(SUM(o.final_amount), 0) AS TotalSpent
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.user_id
        WHERE u.role_type = 'Customer'
        GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.phone_num, u.date_created
        ORDER BY u.date_created DESC
    ";
    $customers = $pdo->query($sql)->fetchAll();
    respond(['success' => true, 'customers' => $customers]);
}

if ($action === 'getDetail') {
    $user_id = (int)($_GET['id'] ?? 0);
    if (!$user_id) respond(['success' => false, 'message' => 'Invalid user ID.'], 400);

    $stmt = $pdo->prepare("SELECT user_id AS UserID, first_name AS FirstName, last_name AS LastName, email AS Email, phone_num AS PhoneNum, date_created AS DateCreated FROM users WHERE user_id=? AND role_type='Customer'");
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();
    if (!$customer) respond(['success' => false, 'message' => 'Customer not found.'], 404);

    $stmt = $pdo->prepare("SELECT address_id AS AddressID, phone AS Phone, street AS Street, barangay AS Barangay, city AS City, province AS Province, zip_code AS ZipCode, is_default AS IsDefault FROM addresses WHERE user_id=? ORDER BY is_default DESC");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll();

    $stmt = $pdo->prepare(" 
        SELECT o.order_id AS OrderID, o.order_date AS OrderDate, o.total_amount AS TotalAmount, o.final_amount AS FinalAmount,
               o.order_status AS OrderStatus, pm.method_type AS PaymentMethod, p.payment_status AS PaymentStatus
        FROM orders o
        LEFT JOIN payments p ON p.order_id = o.order_id
        LEFT JOIN payment_methods pm ON pm.payment_method_id = p.payment_method_id
        WHERE o.user_id=?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();

    respond(['success' => true, 'customer' => $customer, 'addresses' => $addresses, 'orders' => $orders]);
}

respond(['success' => false, 'message' => 'Unknown action.'], 400);
