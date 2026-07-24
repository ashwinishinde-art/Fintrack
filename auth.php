<?php
require_once __DIR__ . '/../config.php';

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!current_user_id()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
        exit;
    }
}

function json_input() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response($payload, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}
