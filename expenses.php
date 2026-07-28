<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$userId = current_user_id();
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ---- GET: list expenses (optional ?category=) ----
if ($method === 'GET') {
    $category = $_GET['category'] ?? 'All';
    if ($category !== 'All' && in_array($category, CATEGORIES, true)) {
        $stmt = $pdo->prepare('SELECT id, amount, category, description, date_created FROM expenses WHERE user_id = ? AND category = ? ORDER BY date_created DESC, id DESC');
        $stmt->execute([$userId, $category]);
    } else {
        $stmt = $pdo->prepare('SELECT id, amount, category, description, date_created FROM expenses WHERE user_id = ? ORDER BY date_created DESC, id DESC');
        $stmt->execute([$userId]);
    }
    json_response(['success' => true, 'expenses' => $stmt->fetchAll()]);
}

// ---- POST: add expense, or clear_all via action flag ----
if ($method === 'POST') {
    $data = json_input();

    if (($data['action'] ?? '') === 'clear_all') {
        $stmt = $pdo->prepare('DELETE FROM expenses WHERE user_id = ?');
        $stmt->execute([$userId]);
        json_response(['success' => true, 'message' => 'All transactions cleared']);
    }

    $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
    $category = trim($data['category'] ?? '');
    $description = trim($data['description'] ?? '');
    $date = trim($data['date_created'] ?? '');

    if ($amount <= 0 || $category === '' || $description === '' || $date === '') {
        json_response(['success' => false, 'message' => 'Amount, category, description and date are all required.'], 422);
    }
    if (!in_array($category, CATEGORIES, true)) {
        json_response(['success' => false, 'message' => 'Unknown category.'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO expenses (user_id, amount, category, description, date_created) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $amount, $category, $description, $date]);

    json_response(['success' => true, 'message' => 'Expense recorded', 'id' => (int) $pdo->lastInsertId()], 201);
}

// ---- DELETE: remove a single expense (?id=) ----
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        json_response(['success' => false, 'message' => 'Missing expense id.'], 422);
    }
    $stmt = $pdo->prepare('DELETE FROM expenses WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    json_response(['success' => true, 'message' => 'Expense removed']);
}

json_response(['success' => false, 'message' => 'Invalid method'], 405);
