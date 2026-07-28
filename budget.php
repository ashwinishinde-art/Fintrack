<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$userId = current_user_id();
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT total_budget, cat_food, cat_books, cat_entertainment, cat_rent, cat_others FROM budgets WHERE user_id = ?');
    $stmt->execute([$userId]);
    json_response(['success' => true, 'budget' => $stmt->fetch()]);
}

if ($method === 'POST') {
    $data = json_input();
    $food = isset($data['food']) ? (float) $data['food'] : 0;
    $books = isset($data['books']) ? (float) $data['books'] : 0;
    $ent = isset($data['entertainment']) ? (float) $data['entertainment'] : 0;
    $rent = isset($data['rent']) ? (float) $data['rent'] : 0;
    $others = isset($data['others']) ? (float) $data['others'] : 0;

    $total = $food + $books + $ent + $rent + $others;
    if ($total <= 0) {
        json_response(['success' => false, 'message' => 'Total budget must be greater than ₹0.'], 422);
    }

    $stmt = $pdo->prepare('UPDATE budgets SET total_budget = ?, cat_food = ?, cat_books = ?, cat_entertainment = ?, cat_rent = ?, cat_others = ? WHERE user_id = ?');
    $stmt->execute([$total, $food, $books, $ent, $rent, $others, $userId]);

    json_response(['success' => true, 'message' => 'Budgets updated', 'total' => $total]);
}

json_response(['success' => false, 'message' => 'Invalid method'], 405);
