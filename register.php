<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

$data = json_input();
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$budget = isset($data['budget']) ? (float) $data['budget'] : 1500;

if ($name === '' || $email === '' || $password === '') {
    json_response(['success' => false, 'message' => 'Name, email and password are required.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Please enter a valid email address.'], 422);
}
if (strlen($password) < 6) {
    json_response(['success' => false, 'message' => 'Password must be at least 6 characters.'], 422);
}
if ($budget < 100) {
    $budget = 100;
}

$pdo = get_db();

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['success' => false, 'message' => 'This email is already registered. Try logging in instead.'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $hash]);
    $userId = (int) $pdo->lastInsertId();

    // Proportionally distribute the initial budget across categories
    $food = round($budget * CATEGORY_SPLIT['Food']);
    $books = round($budget * CATEGORY_SPLIT['Books & Stationery']);
    $ent = round($budget * CATEGORY_SPLIT['Entertainment']);
    $rent = round($budget * CATEGORY_SPLIT['Rent & Utilities']);
    $others = round($budget * CATEGORY_SPLIT['Others']);

    $month = date('F');
    $year = (int) date('Y');

    $stmt = $pdo->prepare('INSERT INTO budgets (user_id, total_budget, cat_food, cat_books, cat_entertainment, cat_rent, cat_others, month, year)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $budget, $food, $books, $ent, $rent, $others, $month, $year]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
}

$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;

json_response(['success' => true, 'message' => 'Account created', 'user' => ['id' => $userId, 'name' => $name, 'email' => $email]], 201);
