<?php
require_once 'config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();
$action = $_GET['action'] ?? '';

// GET profile
if ($method === 'GET' && $action === 'profile') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    if (!$user_id) respond(['success'=>false,'error'=>'user_id required.'], 400);

    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, phone_num, role_type, date_created FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) respond(['success'=>false,'error'=>'User not found.'], 404);

    // Addresses
    $addr_stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC");
    $addr_stmt->execute([$user_id]);
    $user['addresses'] = $addr_stmt->fetchAll();

    respond(['success'=>true,'user'=>$user]);
}

// POST - update profile
if ($method === 'POST' && $action === 'update_profile') {
    $body    = getBody();
    $user_id = (int)($body['user_id'] ?? 0);
    if (!$user_id) respond(['success'=>false,'error'=>'user_id required.'], 400);

    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone_num=? WHERE user_id=?");
    $stmt->execute([$body['first_name'], $body['last_name'], $body['phone_num'] ?? '', $user_id]);
    respond(['success'=>true,'message'=>'Profile updated.']);
}

// POST - change password
if ($method === 'POST' && $action === 'change_password') {
    $body       = getBody();
    $user_id    = (int)($body['user_id'] ?? 0);
    $old_pass   = $body['old_password'] ?? '';
    $new_pass   = $body['new_password'] ?? '';
    if (!$user_id || !$old_pass || !$new_pass) {
        respond(['success'=>false,'error'=>'user_id, old_password and new_password required.'], 400);
    }
    if (strlen($new_pass) < 8) respond(['success'=>false,'error'=>'New password must be at least 8 characters.'], 400);

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($old_pass, $user['password_hash'])) {
        respond(['success'=>false,'error'=>'Current password is incorrect.'], 401);
    }
    $hash = password_hash($new_pass, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")->execute([$hash, $user_id]);
    respond(['success'=>true,'message'=>'Password changed successfully.']);
}

// POST - add or update address
if ($method === 'POST' && $action === 'save_address') {
    $body    = getBody();
    $user_id = (int)($body['user_id'] ?? 0);
    if (!$user_id) respond(['success'=>false,'error'=>'user_id required.'], 400);

    if (!empty($body['address_id'])) {
        // Update
        $pdo->prepare("UPDATE addresses SET phone=?,street=?,barangay=?,city=?,province=?,zip_code=?,is_default=? WHERE address_id=? AND user_id=?")
            ->execute([$body['phone']??'',$body['street'],$body['barangay']??'',$body['city'],$body['province'],$body['zip_code']??'',
                       (int)($body['is_default']??0),(int)$body['address_id'],$user_id]);
    } else {
        // Insert
        $pdo->prepare("INSERT INTO addresses (user_id,phone,street,barangay,city,province,zip_code,is_default) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$user_id,$body['phone']??'',$body['street'],$body['barangay']??'',$body['city'],$body['province'],$body['zip_code']??'',
                       (int)($body['is_default']??0)]);
    }
    if (!empty($body['is_default'])) {
        $pdo->prepare("UPDATE addresses SET is_default=0 WHERE user_id=? AND address_id!=?")->execute([$user_id, $pdo->lastInsertId()]);
    }
    respond(['success'=>true,'message'=>'Address saved.']);
}

// POST - delete address
if ($method === 'POST' && $action === 'delete_address') {
    $body       = getBody();
    $user_id    = (int)($body['user_id'] ?? 0);
    $address_id = (int)($body['address_id'] ?? 0);
    if (!$user_id || !$address_id) respond(['success'=>false,'error'=>'user_id and address_id required.'], 400);
    $pdo->prepare("DELETE FROM addresses WHERE address_id=? AND user_id=?")->execute([$address_id, $user_id]);
    respond(['success'=>true,'message'=>'Address deleted.']);
}

respond(['success'=>false,'error'=>'Invalid action.'], 400);
