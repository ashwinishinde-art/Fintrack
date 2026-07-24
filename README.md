# FinTrack

A personal finance / expense-tracking web app with a category-based budgeting system, live analytics, and per-user accounts — built as a PHP + MySQL client-server app (no frameworks).

> Currency is displayed in **₹ (INR)** throughout the UI.

## Features

- **Accounts** — register/sign in with email + password (bcrypt-hashed via PHP's `password_hash`), session-based auth
- **Dashboard** — total budget, total spent, and remaining balance cards; a spend progress bar; an overspend warning banner
- **Category budgets** — five built-in categories (Food, Books & Stationery, Entertainment, Rent & Utilities, Others) each with their own limit, editable from a modal that auto-recalculates the total
- **Expense tracking** — add expenses (amount, category, description, date), filter by category, delete single rows, or clear all transactions
- **Analytics tab** — savings rate, daily average spend, top category, a risk indicator, and a Chart.js bar chart of spend-by-category
- **Profile** — update display name; view email and current budget allowance (read-only)
- **Quality-of-life touches** — toast notifications, a rotating quotes/tips widget, and a Chart.js CDN with automatic fallback across two mirrors if the primary CDN is blocked

## Tech stack

| Layer | Technology |
|---|---|
| Frontend | Plain HTML + vanilla JS (`assets/js/app.js`), Tailwind CSS (CDN), Chart.js (CDN, with fallback mirrors), Font Awesome |
| Backend | PHP (no framework), PDO for database access |
| Database | MySQL / MariaDB |
| Auth | PHP sessions + `password_hash()` / `password_verify()` |

## Project structure

```
fintrack
├── config.php              # DB credentials + category list/split constants; starts the session
├── database.sql            # Creates fintrack_db and its 3 tables (run once)
├── index.php                # Single-page app shell: login/register view + dashboard/analytics/profile tabs
├── includes/
│   ├── db.php                # PDO connection singleton (get_db())
│   └── auth.php              # Session helpers: current_user_id(), require_login(), json_input(), json_response()
├── api/
│   ├── register.php          # POST   — create account, seeds a proportionally-split budget
│   ├── login.php              # POST   — authenticate, starts session
│   ├── logout.php             # POST   — destroy session
│   ├── session.php            # GET    — current user + budget + all expenses (used to bootstrap the SPA)
│   ├── expenses.php           # GET/POST/DELETE — list / add / clear-all / delete-one expenses
│   ├── budget.php             # GET/POST — read / update the five category limits
│   └── profile.php            # POST   — update display name
└── assets/js/app.js           # All frontend logic + fetch() calls into /api
```

## Database schema

Three InnoDB tables, defined in `database.sql`:

- **`users`** — `id`, `name`, `email` (unique), `password_hash`, `created_at`
- **`budgets`** — one row per user (`user_id` unique, FK → `users`), `total_budget` plus five per-category columns (`cat_food`, `cat_books`, `cat_entertainment`, `cat_rent`, `cat_others`), `month`, `year`
- **`expenses`** — `user_id` (FK), `amount`, `category`, `description`, `date_created`, `created_at`

No demo account is pre-seeded — the schema only creates empty tables. You create your first account through the app's Register form.

## Getting started

This app is designed to run on **XAMPP** (Apache + MySQL + PHP). Quick version:

1. Copy the `fintrack` folder into your XAMPP `htdocs` directory.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Import `database.sql` via phpMyAdmin (or the MySQL CLI) to create `fintrack_db`.
4. If your MySQL root user has a password, update `DB_PASS` in `config.php` to match (default assumes no password).
5. Visit the app in your browser and click **Register Account** to create your first login.

For the full step-by-step walkthrough (including troubleshooting for common XAMPP issues like port conflicts or a failed DB connection), see **[SETUP.md](SETUP.md)**, which is included in the project folder.

## API reference

All endpoints under `/api` return JSON (`{"success": true|false, ...}`) and require an active session (via `require_login()`) except `register.php`, `login.php`, and `session.php`.

| Endpoint | Method | Purpose |
|---|---|---|
| `api/register.php` | POST | Create a user + seed their initial budget split |
| `api/login.php` | POST | Verify credentials, start session |
| `api/logout.php` | POST | Destroy the session |
| `api/session.php` | GET | Bootstrap: returns auth state, user, budget, and expenses |
| `api/expenses.php` | GET | List expenses, optional `?category=` filter |
| `api/expenses.php` | POST | Add an expense, or clear all with `{"action":"clear_all"}` |
| `api/expenses.php` | DELETE | Delete one expense via `?id=` |
| `api/budget.php` | GET | Fetch the current category budgets |
| `api/budget.php` | POST | Update all five category budgets (total is derived) |
| `api/profile.php` | POST | Update display name |

## Security notes

- All SQL queries use **PDO prepared statements** — no string-concatenated queries.
- Passwords are hashed with PHP's `password_hash()` (bcrypt) and never stored or returned in plaintext.
- User-supplied text (e.g. expense descriptions) is HTML-escaped on render in `app.js`, mitigating stored-XSS in the transactions table.
- `config.php` stores DB credentials in plaintext — fine for local XAMPP development, but should be moved to an environment variable or a git-ignored file before any real deployment.
- There's no CSRF token on the state-changing POST/DELETE endpoints; acceptable for a local single-user dev setup, but worth adding if this is ever exposed beyond localhost.

## Known limitations

- Single "current" budget per user (no historical month-by-month budget tracking, though `budgets.month`/`year` columns exist for future use).
- No password reset / email verification flow.
- Categories are fixed at five (defined in `config.php`'s `CATEGORIES` constant) rather than user-customizable.
