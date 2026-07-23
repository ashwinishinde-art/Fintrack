<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$userId = current_user_id();
if (!$userId) {
    json_response(['success' => true, 'authenticated' => false]);
}

$pdo = get_db();

$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    // Stale session pointing at a deleted user
    $_SESSION = [];
    session_destroy();
    json_response(['success' => true, 'authenticated' => false]);
}

$stmt = $pdo->prepare('SELECT total_budget, cat_food, cat_books, cat_entertainment, cat_rent, cat_others, month, year FROM budgets WHERE user_id = ?');
$stmt->execute([$userId]);
$budget = $stmt->fetch();

$stmt = $pdo->prepare('SELECT id, amount, category, description, date_created FROM expenses WHERE user_id = ? ORDER BY date_created DESC, id DESC');
$stmt->execute([$userId]);
$expenses = $stmt->fetchAll();

json_response([
    'success' => true,
    'authenticated' => true,
    'user' => $user,
    'budget' => $budget,
    'expenses' => $expenses,
]);
