<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'login') {
    $body = getBody();
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (!$email || !$password) {
        respond(['success' => false, 'error' => 'Email and password are required.'], 400);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, password_hash, role_type FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        respond(['success' => false, 'error' => 'Invalid email or password.'], 401);
    }

    // Store session info (simple token via session)
    session_start();
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['role_type'] = $user['role_type'];

    respond([
        'success'   => true,
        'user_id'   => $user['user_id'],
        'name'      => $user['first_name'] . ' ' . $user['last_name'],
        'email'     => $user['email'],
        'role_type' => $user['role_type'],
    ]);
}

if ($method === 'POST' && $action === 'register') {
    $body      = getBody();
    $full_name = trim($body['full_name'] ?? '');
    $email     = trim($body['email'] ?? '');
    $phone     = trim($body['phone'] ?? '');
    $password  = $body['password'] ?? '';
    $confirm   = $body['confirm_password'] ?? '';

    if (!$full_name || !$email || !$password) {
        respond(['success' => false, 'error' => 'Name, email and password are required.'], 400);
    }
    if ($password !== $confirm) {
        respond(['success' => false, 'error' => 'Passwords do not match.'], 400);
    }
    if (strlen($password) < 8) {
        respond(['success' => false, 'error' => 'Password must be at least 8 characters.'], 400);
    }

    $parts      = explode(' ', $full_name, 2);
    $first_name = $parts[0];
    $last_name  = $parts[1] ?? '';

    $pdo  = getDB();
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        respond(['success' => false, 'error' => 'Email already registered.'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, phone_num, role_type) VALUES (?,?,?,?,?,'Customer')");
    $stmt->execute([$first_name, $last_name, $email, $hash, $phone]);

    respond(['success' => true, 'message' => 'Account created successfully! Please log in.']);
}

respond(['success' => false, 'error' => 'Invalid action.'], 400);
