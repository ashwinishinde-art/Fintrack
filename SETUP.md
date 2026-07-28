# FinTrack (PHP + MySQL) — XAMPP Setup Guide

This is a real client-server rewrite of the original single-file HTML demo:
- **Frontend**: `index.php` + `assets/js/app.js` (same look, now calls a real backend)
- **Backend**: PHP scripts in `/api` (login, register, expenses, budget, profile)
- **Database**: MySQL (`fintrack_db`), schema in `database.sql`

The old "Dev Hub" tab (fake Flask/SQLite simulator with a console logger) has
been removed — that was just a mock of a backend. You now have a real one.

## 1. Install XAMPP
Download from https://www.apachefriends.org if you don't already have it,
and install it (Windows/macOS/Linux all work the same way below).

## 2. Copy the project files
Copy the entire `fintrack-php` folder into XAMPP's web root:
- Windows: `C:\xampp\htdocs\fintrack-php`
- macOS: `/Applications/XAMPP/htdocs/fintrack-php`
- Linux: `/opt/lampp/htdocs/fintrack-php`

## 3. Start Apache and MySQL
Open the **XAMPP Control Panel** and click **Start** next to both
`Apache` and `MySQL`. Both rows should turn green.

## 4. Create the database
1. Open http://localhost/phpmyadmin in your browser.
2. Click the **Import** tab.
3. Click **Choose File**, select `database.sql` from the project folder.
4. Click **Go** at the bottom.

This creates the `fintrack_db` database with three tables: `users`,
`budgets`, and `expenses`. No demo password is pre-seeded (see step 5).

> If your MySQL root user has a password (uncommon on a fresh XAMPP
> install), open `config.php` and set `DB_PASS` to match.

## 5. Open the app and create your account
Visit:

```
http://localhost/fintrack-php/
```

Click **Register Account**, fill in your name, email, starting budget,
and a password (6+ characters), then submit. You'll be logged straight
into the dashboard — your account, budget, and (empty) transaction list
now live in the `fintrack_db` database for real.

From there you can log out and log back in any time with that email/password.

## 6. Using the app
- **Add Expense** — records a row in the `expenses` table.
- **Configure Budget Limits** — updates the `budgets` table (total is the
  sum of the five category limits, same as the original design).
- **Transactions** — filter by category, delete single rows, or purge all.
- **Analytics** — computed live from your current expenses/budget.
- **Profile Settings** — update your display name.

## Project structure
```
fintrack-php/
├── config.php              # DB connection settings (edit if needed)
├── database.sql            # Run this once in phpMyAdmin
├── index.php                # Main page (login + dashboard/analytics/profile)
├── includes/
│   ├── db.php                # PDO connection helper
│   └── auth.php              # Session helpers used by every API file
├── api/
│   ├── register.php          # POST — create account
│   ├── login.php              # POST — authenticate
│   ├── logout.php             # POST — destroy session
│   ├── session.php            # GET  — current user + budget + expenses
│   ├── expenses.php           # GET/POST/DELETE — CRUD for expenses
│   ├── budget.php             # GET/POST — read/update category budgets
│   └── profile.php            # POST — update display name
└── assets/js/app.js           # All frontend logic (fetch calls to /api)
```

## Troubleshooting
- **"Database connection failed"** — MySQL isn't running, or `config.php`
  credentials don't match your XAMPP MySQL setup.
- **Blank page / PHP errors show as raw text** — enable error display in
  `php.ini` (`display_errors = On`) while developing, or check
  `xampp/apache/logs/error.log`.
- **"This email is already registered"** — you already created that
  account; use Sign In instead, or pick a different email.
- **Port 80 already in use** — if Apache won't start, another program
  (e.g. Skype, IIS) may be using port 80. Change Apache's port in
  XAMPP's `httpd.conf`, or stop the conflicting program.
