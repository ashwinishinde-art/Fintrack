<?php
/**
 * FinTrack - Database Configuration
 * Default values match a stock XAMPP install (MySQL on localhost,
 * user "root", empty password). Edit if your setup differs.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'fintrack_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Category list used across the app (order matters for the UI)
define('CATEGORIES', [
    'Food',
    'Books & Stationery',
    'Entertainment',
    'Rent & Utilities',
    'Others'
]);

// Default proportional split when a budget total is set directly
define('CATEGORY_SPLIT', [
    'Food' => 0.20,
    'Books & Stationery' => 0.10,
    'Entertainment' => 0.10,
    'Rent & Utilities' => 0.50,
    'Others' => 0.10
]);

session_start();
