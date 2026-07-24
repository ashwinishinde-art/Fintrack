-- ============================================================
-- FinTrack - Database Schema
-- Import this file in phpMyAdmin (or via MySQL CLI) BEFORE
-- running the app. Creates the database, tables, and a demo
-- account so you can log in immediately.
-- ============================================================

CREATE DATABASE IF NOT EXISTS fintrack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fintrack_db;

-- ---------------------------------------------------
-- Users
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Budgets (one active budget row per user)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    total_budget DECIMAL(12,2) NOT NULL DEFAULT 1500,
    cat_food DECIMAL(12,2) NOT NULL DEFAULT 300,
    cat_books DECIMAL(12,2) NOT NULL DEFAULT 150,
    cat_entertainment DECIMAL(12,2) NOT NULL DEFAULT 100,
    cat_rent DECIMAL(12,2) NOT NULL DEFAULT 950,
    cat_others DECIMAL(12,2) NOT NULL DEFAULT 0,
    month VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Expenses
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    category VARCHAR(60) NOT NULL,
    description VARCHAR(255) NOT NULL,
    date_created DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- No demo user is pre-seeded with a password here, because a
-- hand-written bcrypt hash can't be verified inside this SQL file.
-- Instead: after setup, open the app and use "Register Account"
-- to create your first login (see SETUP.md, Step 5). Everything
-- you enter there is written into these tables for real.
