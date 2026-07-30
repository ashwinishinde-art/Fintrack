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
$nameProvided = array_key_exists('name', $data);
$themeProvided = array_key_exists('theme', $data);

if (!$nameProvided && !$themeProvided) {
    json_response(['success' => false, 'message' => 'Nothing to update.'], 422);
}

$response = ['success' => true, 'message' => 'Profile updated'];

if ($nameProvided) {
    $name = trim($data['name']);
    if ($name === '') {
        json_response(['success' => false, 'message' => 'Name cannot be empty.'], 422);
    }
    $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
    $stmt->execute([$name, $userId]);
    $_SESSION['user_name'] = $name;
    $response['name'] = $name;
}

if ($themeProvided) {
    $theme = $data['theme'];
    if (!in_array($theme, ['light', 'dark'], true)) {
        json_response(['success' => false, 'message' => 'Theme must be either "light" or "dark".'], 422);
    }
    $stmt = $pdo->prepare('UPDATE users SET theme = ? WHERE id = ?');
    $stmt->execute([$theme, $userId]);
    $response['theme'] = $theme;
}

json_response($response);
