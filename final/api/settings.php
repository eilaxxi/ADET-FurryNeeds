<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$adminId = (int)($_SESSION['user_id'] ?? 1); // default admin for local demo/testing
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function get_setting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['setting_value'] : $default;
}
function set_setting(PDO $pdo, string $key, $value): void {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $stmt->execute([$key, (string)$value]);
}

if ($method === 'GET') {
    if ($action === 'getProfile') {
        $stmt = $pdo->prepare("SELECT user_id AS UserID, first_name AS FirstName, last_name AS LastName, email AS Email, phone_num AS PhoneNum FROM users WHERE user_id=? AND role_type='Admin'");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        respond(['success' => (bool)$admin, 'admin' => $admin, 'message' => $admin ? null : 'Admin not found.']);
    }

    if ($action === 'getStoreSettings') {
        respond(['success' => true, 'settings' => [
            'StoreName' => get_setting($pdo, 'StoreName', 'FurryNeeds'),
            'StoreEmail' => get_setting($pdo, 'StoreEmail', 'support@furryneeds.com'),
            'StorePhone' => get_setting($pdo, 'StorePhone', '+63 900 000 0000'),
            'StoreAddress' => get_setting($pdo, 'StoreAddress', 'Philippines'),
            'StoreDescription' => get_setting($pdo, 'StoreDescription', 'Pet care products and supplies'),
            'Currency' => get_setting($pdo, 'Currency', 'PHP'),
            'LowStockThreshold' => get_setting($pdo, 'LowStockThreshold', '10'),
            'DefaultDeliveryFee' => get_setting($pdo, 'DefaultDeliveryFee', '99')
        ]]);
    }

    if ($action === 'getNotificationPrefs') {
        respond(['success' => true, 'prefs' => [
            'notif_new_order' => get_setting($pdo, 'notif_new_order', '1'),
            'notif_low_stock' => get_setting($pdo, 'notif_low_stock', '1'),
            'notif_payment' => get_setting($pdo, 'notif_payment', '1'),
            'notif_email' => get_setting($pdo, 'notif_email', '1'),
            'notif_sms' => get_setting($pdo, 'notif_sms', '0')
        ]]);
    }

    if ($action === 'exportBackup') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="furryneeds_backup_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Table', 'ID', 'Name/Email/Status', 'Amount/Stock', 'Date']);
        foreach ($pdo->query("SELECT user_id, email, role_type, date_created FROM users") as $r) fputcsv($out, ['users', $r['user_id'], $r['email'].' ('.$r['role_type'].')', '', $r['date_created']]);
        foreach ($pdo->query("SELECT product_id, product_name, stock_quantity, date_created FROM products") as $r) fputcsv($out, ['products', $r['product_id'], $r['product_name'], $r['stock_quantity'], $r['date_created']]);
        foreach ($pdo->query("SELECT order_id, order_status, final_amount, order_date FROM orders") as $r) fputcsv($out, ['orders', $r['order_id'], $r['order_status'], $r['final_amount'], $r['order_date']]);
        fclose($out);
        exit;
    }

    respond(['success' => false, 'message' => 'Unknown action.'], 400);
}

if ($method === 'POST') {
    $body = getBody();
    $action = $body['action'] ?? $action;

    if ($action === 'updateProfile') {
        $name = trim($body['name'] ?? (($body['FirstName'] ?? '') . ' ' . ($body['LastName'] ?? '')));
        $parts = preg_split('/\s+/', $name, 2);
        $first = $body['first_name'] ?? $body['FirstName'] ?? ($parts[0] ?? 'Admin');
        $last = $body['last_name'] ?? $body['LastName'] ?? ($parts[1] ?? 'User');
        $email = $body['email'] ?? $body['Email'] ?? '';
        $phone = $body['phone'] ?? $body['PhoneNum'] ?? '';
        if (!$email) respond(['success' => false, 'message' => 'Email is required.'], 400);
        $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone_num=? WHERE user_id=? AND role_type='Admin'");
        $stmt->execute([$first, $last, $email, $phone, $adminId]);
        respond(['success' => true, 'message' => 'Profile updated.']);
    }

    if ($action === 'changePassword') {
        $current = $body['currentPassword'] ?? $body['current_password'] ?? '';
        $new = $body['newPassword'] ?? $body['new_password'] ?? '';
        if (strlen($new) < 8) respond(['success'=>false,'message'=>'New password must be at least 8 characters.'], 400);
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id=?");
        $stmt->execute([$adminId]);
        $u = $stmt->fetch();
        if ($u && $current && !password_verify($current, $u['password_hash'])) respond(['success'=>false,'message'=>'Current password is incorrect.'], 401);
        $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")->execute([password_hash($new, PASSWORD_BCRYPT), $adminId]);
        respond(['success'=>true,'message'=>'Password changed.']);
    }

    if (in_array($action, ['updateStoreSettings','updatePaymentMethods','updatePaymentDetails','updateNotificationPrefs'], true)) {
        foreach ($body as $k => $v) {
            if ($k === 'action') continue;
            if (is_array($v)) $v = json_encode($v);
            set_setting($pdo, $k, $v);
        }
        respond(['success'=>true,'message'=>'Settings saved.']);
    }

    if ($action === 'dangerZone') {
        $task = $body['task'] ?? '';
        if ($task === 'clearCancelled') {
            $pdo->exec("DELETE oi FROM order_items oi JOIN orders o ON oi.order_id=o.order_id WHERE o.order_status='Cancelled'");
            $pdo->exec("DELETE FROM orders WHERE order_status='Cancelled'");
            respond(['success'=>true,'message'=>'Cancelled orders cleared.']);
        }
        if ($task === 'resetInventory') {
            $pdo->exec("UPDATE products SET stock_quantity=0");
            respond(['success'=>true,'message'=>'Inventory quantities reset to 0.']);
        }
        respond(['success'=>false,'message'=>'Unknown danger-zone task.'], 400);
    }
}

respond(['success'=>false,'message'=>'Method not allowed.'], 405);
