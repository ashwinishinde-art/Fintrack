<?php
header('Content-Type: application/json');

// Database connection based on your phpMyAdmin screenshot
$host = '127.0.0.1';
$dbname = 'fintrack_db';
$user = 'root'; // Update with your DB username
$pass = '';     // Update with your DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Assuming we want data for a specific user (e.g., user_id = 1)
    // Group by category to get total expenditure per category
    $stmt = $pdo->prepare("SELECT category, SUM(amount) as total_amount FROM expenses WHERE user_id = 1 GROUP BY category");
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $categories = [];
    $amounts = [];
    
    foreach ($data as $row) {
        $categories[] = $row['category'];
        $amounts[] = $row['total_amount'];
    }
    
    echo json_encode(['categories' => $categories, 'amounts' => $amounts]);

} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>