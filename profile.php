<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$userId = current_user_id();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

$data = json_input();
$name = trim($data['name'] ?? '');

if ($name === '') {
    json_response(['success' => false, 'message' => 'Name cannot be empty.'], 422);
}

$stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
$stmt->execute([$name, $userId]);
$_SESSION['user_name'] = $name;

json_response(['success' => true, 'message' => 'Profile updated', 'name' => $name]);
