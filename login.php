<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

$data = json_input();
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($email === '' || $password === '') {
    json_response(['success' => false, 'message' => 'Email and password are required.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['success' => false, 'message' => 'Account not found or password incorrect.'], 401);
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];

json_response(['success' => true, 'message' => 'Login successful', 'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']]]);
