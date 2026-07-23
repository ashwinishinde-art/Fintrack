<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinTrack - Personal Finance Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F4F6F9; }
        .code-font { font-family: 'Fira Code', monospace; }
        .sidebar-item-active { background-color: #1E293B; border-left: 4px solid #00C48C; color: #ffffff; }
    </style>
</head>
<body class="text-slate-800 min-h-screen flex flex-col">

    <div id="toast-container" class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full"></div>

    <!-- LOGIN / REGISTER -->
    <div id="view-login" class="flex-1 flex flex-col justify-center items-center p-4 bg-[#0B1220]">
        <div class="max-w-md w-full bg-[#0E1626] border border-slate-800 p-8 rounded-2xl shadow-2xl space-y-6">
            <div class="text-center space-y-2">
                <div class="flex flex-col items-center justify-center gap-3 mb-2">
                    <div class="h-16 w-16 bg-[#00C48C] rounded-2xl flex items-center justify-center text-white text-3xl font-black shadow-lg shadow-emerald-500/20">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <span class="text-3xl font-extrabold tracking-tight text-white flex items-center">FinTrack<span class="text-[#00C48C]">.</span></span>
                </div>
                <p class="text-slate-400 text-xs">Premium Student Budgeting & Strategic Finance Planner</p>
            </div>

            <div class="flex bg-[#070C16] p-1 rounded-xl border border-slate-800/80">
                <button type="button" onclick="toggleAuthMode('signin')" id="btn-toggle-signin" class="flex-1 py-2 text-xs font-bold text-white rounded-lg bg-slate-800/60 border border-slate-700/50 transition-all">Sign In</button>
                <button type="button" onclick="toggleAuthMode('signup')" id="btn-toggle-signup" class="flex-1 py-2 text-xs font-bold text-slate-400 rounded-lg hover:text-white transition-all">Register Account</button>
            </div>

            <form id="signin-form" onsubmit="handleLogin(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email</label>
                    <input type="email" id="login-email" required placeholder="yourname@domain.com" class="w-full bg-[#131B2E] border border-slate-800 rounded-xl py-3 px-4 text-white placeholder-slate-600 focus:outline-none focus:border-[#00C48C] transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="login-password" required placeholder="••••••••" class="w-full bg-[#131B2E] border border-slate-800 rounded-xl py-3 pl-4 pr-12 text-white placeholder-slate-600 focus:outline-none focus:border-[#00C48C] transition-all">
                        <button type="button" onclick="togglePasswordVisibility('login-password', 'login-eye-icon')" class="absolute inset-y-0 right-3 flex items-center px-2 text-slate-400 hover:text-white transition-all">
                            <i id="login-eye-icon" class="fa-regular fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-[#00C48C] hover:bg-[#00B07C] text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-emerald-600/20 hover:scale-[1.01] flex items-center justify-center gap-2">
                    <span>Unlock Dashboard</span><i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <form id="signup-form" onsubmit="handleRegister(event)" class="hidden space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Full Name</label>
                    <input type="text" id="reg-name" required placeholder="Rahul Sharma" class="w-full bg-[#131B2E] border border-slate-800 rounded-xl py-2.5 px-4 text-white placeholder-slate-600 focus:outline-none focus:border-[#00C48C] transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Your Email</label>
                    <input type="email" id="reg-email" required placeholder="yourname@gmail.com" class="w-full bg-[#131B2E] border border-slate-800 rounded-xl py-2.5 px-4 text-white placeholder-slate-600 focus:outline-none focus:border-[#00C48C] transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Initial Setup Budget Allowance (₹)</label>
                    <input type="number" id="reg-budget" required min="100" value="1500" class="w-full bg-[#131B2E] border border-slate-800 rounded-xl py-2.5 px-4 text-white placeholder-slate-600 focus:outline-none focus:border-[#00C48C] transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="reg-password" required minlength="6" placeholder="Create password (min 6 chars)" class="w-full bg-[#131B2E] border border-slate-800 rounded-xl py-2.5 pl-4 pr-12 text-white placeholder-slate-600 focus:outline-none focus:border-[#00C48C] transition-all">
                        <button type="button" onclick="togglePasswordVisibility('reg-password', 'reg-eye-icon')" class="absolute inset-y-0 right-3 flex items-center px-2 text-slate-400 hover:text-white transition-all">
                            <i id="reg-eye-icon" class="fa-regular fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-indigo-600/20 hover:scale-[1.01] flex items-center justify-center gap-2">
                    <span>Initialize Workspace</span><i class="fa-solid fa-user-plus"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- MAIN APP -->
    <div id="view-app" class="hidden flex-1 flex flex-col md:flex-row">
        <aside class="w-full md:w-64 bg-[#0B1220] text-slate-400 flex flex-col shrink-0 border-r border-slate-800/60">
            <div class="p-6 flex items-center gap-3 border-b border-slate-800/40">
                <div class="h-10 w-10 bg-[#00C48C] rounded-2xl flex items-center justify-center text-white text-lg font-black shadow-lg shadow-emerald-500/20"><i class="fa-solid fa-chart-pie"></i></div>
                <span class="text-xl font-extrabold tracking-tight text-white flex items-center">FinTrack<span class="text-[#00C48C] ml-0.5">.</span></span>
            </div>

            <nav class="flex-1 py-6 space-y-1 px-3">
                <button id="sidebar-dashboard-btn" onclick="switchTab('dashboard')" class="sidebar-item-active w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-150 hover:bg-slate-800/50 hover:text-white text-left">
                    <i class="fa-solid fa-house md:w-5 text-center fa-lg"></i><span>Dashboard</span>
                </button>
                <button id="sidebar-analytics-btn" onclick="switchTab('analytics')" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-150 hover:bg-slate-800/50 hover:text-slate-200 text-left">
                    <i class="fa-solid fa-chart-line md:w-5 text-center fa-lg"></i><span>Analytics</span>
                </button>
                <button onclick="openAddExpenseSection()" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-150 hover:bg-slate-800/50 hover:text-slate-200 text-left">
                    <i class="fa-solid fa-plus md:w-5 text-center fa-lg"></i><span>Add Expense</span>
                </button>
                <button id="sidebar-budget-btn" onclick="openSetBudgetModal()" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-150 hover:bg-slate-800/50 hover:text-slate-200 text-left">
                    <i class="fa-solid fa-sliders md:w-5 text-center fa-lg"></i><span>Budgets</span>
                </button>
                <button id="sidebar-transactions-btn" onclick="scrollToTransactions()" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-150 hover:bg-slate-800/50 hover:text-slate-200 text-left">
                    <i class="fa-solid fa-list-check md:w-5 text-center fa-lg"></i><span>Transactions</span>
                </button>
                <div class="h-px bg-slate-800/60 my-6 mx-4"></div>
                <button id="sidebar-profile-btn" onclick="switchTab('profile')" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-150 hover:bg-slate-800/50 hover:text-slate-200 text-left">
                    <i class="fa-solid fa-user-gear md:w-5 text-center fa-lg"></i><span>Profile Settings</span>
                </button>
            </nav>

            <div class="p-4 border-t border-slate-800/60 flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm"><span id="sidebar-avatar-letters">US</span></div>
                <div class="flex-1 min-w-0">
                    <span id="sidebar-username" class="text-xs font-bold block text-slate-100 truncate">User</span>
                    <span class="text-[10px] text-slate-500 block truncate">Active Budget Account</span>
                </div>
                <button onclick="handleLogout()" class="text-slate-500 hover:text-rose-400 p-1 rounded" title="Logout Session"><i class="fa-solid fa-right-from-bracket"></i></button>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-y-auto">
            <header class="bg-white border-b border-slate-100 py-4 px-6 md:px-8 flex justify-between items-center gap-4 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-[#2563EB] font-extrabold uppercase tracking-widest text-[11px]">System Status: Connected</span>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="openSetBudgetModal()" class="text-xs bg-[#00C48C]/10 text-[#00C48C] hover:bg-[#00C48C]/20 border border-[#00C48C]/20 px-3.5 py-2 rounded-xl transition-all font-semibold">Configure Budget Limits</button>
                    <button onclick="focusExpenseInput()" class="bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md transition-all flex items-center gap-1.5"><i class="fa-solid fa-plus"></i> Add Expense</button>
                </div>
            </header>

            <div class="flex-1 p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto">

                <!-- DASHBOARD -->
                <div id="tab-dashboard" class="space-y-6">
                    <div class="space-y-1">
                        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">Welcome back, <span id="greeting-username" class="text-slate-800">User</span> 👋</h1>
                        <p class="text-slate-500 text-sm font-medium" id="current-calendar-cycle"></p>
                    </div>

                    <div id="overspend-banner" class="hidden bg-[#FFF5F3] border border-[#FDE2DC] p-4 rounded-xl flex items-center gap-3 text-[#A83925]">
                        <div class="h-6 w-6 rounded bg-[#FCE3DD] flex items-center justify-center shrink-0"><i class="fa-solid fa-triangle-exclamation text-[#A83925]"></i></div>
                        <div class="text-xs md:text-sm"><span class="font-bold">Heads up — </span><span id="overspend-banner-message"></span></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Monthly Budget</span>
                                <div class="h-8 w-8 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-500"><i class="fa-regular fa-credit-card text-lg"></i></div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-baseline text-slate-900"><span class="text-4xl font-extrabold" id="card-total-budget">₹0</span></div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden mt-3"><div id="spent-progress-bar" class="bg-[#00C48C] h-full rounded-full transition-all duration-500" style="width: 0%"></div></div>
                                <div class="flex justify-between text-[11px] text-slate-400 font-semibold pt-1"><span id="progress-spent-hint">₹0 spent</span><span id="progress-left-hint">₹0 left</span></div>
                            </div>
                        </div>
                        <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Expenses · <span id="cycle-label-kpi"></span></span>
                                <div class="h-8 w-8 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-500"><i class="fa-regular fa-file-lines text-lg"></i></div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-baseline text-slate-900"><span class="text-4xl font-extrabold" id="card-total-spent">₹0</span></div>
                                <p class="text-xs text-slate-400 flex items-center gap-1 pt-3"><i class="fa-solid fa-arrow-trend-up text-indigo-400"></i><span>Calculated dynamically across active lists</span></p>
                            </div>
                        </div>
                        <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Remaining Balance</span>
                                <div id="balance-icon-bg" class="h-8 w-8 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-500"><i class="fa-regular fa-shield text-lg" id="balance-icon"></i></div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-baseline text-slate-900"><span class="text-4xl font-extrabold text-[#00C48C]" id="card-remaining-balance">₹0</span></div>
                                <p class="text-xs text-emerald-600 font-semibold pt-3" id="balance-status-alert">Funding is healthy for the monthly limit</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Budget Categories</h2>
                            <span class="text-xs font-bold text-slate-400">Where your money's going this month</span>
                        </div>
                        <div id="category-progress-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 w-full"></div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div id="expense-record-card" class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm space-y-4 transition-all duration-300">
                            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                                <div class="p-1.5 bg-[#2563EB]/10 text-[#2563EB] rounded-lg"><i class="fa-solid fa-money-bill-transfer"></i></div>
                                <h2 class="text-lg font-bold text-slate-800">Record Expenditure</h2>
                            </div>
                            <form id="expense-form" onsubmit="handleAddExpense(event)" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Amount (₹)</label>
                                        <div class="relative">
                                            <span class="absolute left-3.5 top-2.5 text-slate-400 font-extrabold">₹</span>
                                            <input type="number" id="form-amount" required min="1" step="0.01" placeholder="e.g. 150" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-8 pr-4 text-slate-800 focus:outline-none focus:border-[#2563EB] focus:bg-white transition-all duration-200">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Category</label>
                                        <select id="form-category" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 text-slate-800 focus:outline-none focus:border-[#2563EB] focus:bg-white transition-all duration-200">
                                            <option value="Food">🍔 Food</option>
                                            <option value="Books & Stationery">📚 Books & Stationery</option>
                                            <option value="Entertainment">🍿 Entertainment</option>
                                            <option value="Rent & Utilities">🏠 Rent & Utilities</option>
                                            <option value="Others">⚙️ Others</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Description</label>
                                        <input type="text" id="form-description" required placeholder="e.g. Grocery Items" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-slate-800 focus:outline-none focus:border-[#2563EB] focus:bg-white transition-all duration-200">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Date</label>
                                        <input type="date" id="form-date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-slate-800 focus:outline-none focus:border-[#2563EB] focus:bg-white transition-all duration-200">
                                    </div>
                                </div>
                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="bg-[#2563EB] hover:bg-[#1D4ED8] text-white font-bold px-6 py-3 rounded-xl transition-all duration-200 flex items-center gap-2 shadow-lg shadow-blue-500/10">
                                        <span>Save Expense Record</span><i class="fa-solid fa-cloud-arrow-up"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="transactions-section" class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">Transaction History</h2>
                                <p class="text-xs text-slate-400">Manage or purge registered ledger entries</p>
                            </div>
                            <div class="flex items-center gap-3 w-full sm:w-auto">
                                <select id="filter-category" onchange="renderApp()" class="bg-white border border-slate-200 rounded-xl text-xs py-2 px-3 text-slate-600 focus:outline-none focus:border-[#00C48C]">
                                    <option value="All">All Categories</option>
                                    <option value="Food">Food</option>
                                    <option value="Books & Stationery">Books & Stationery</option>
                                    <option value="Entertainment">Entertainment</option>
                                    <option value="Rent & Utilities">Rent & Utilities</option>
                                    <option value="Others">Others</option>
                                </select>
                                <button onclick="clearAllExpenses()" class="text-xs border border-rose-200 text-rose-500 bg-rose-50/50 hover:bg-rose-50 hover:border-rose-300 px-3 py-2 rounded-xl transition-all font-semibold">Purge All</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-100/30 text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                                        <th class="py-3.5 px-6">Date</th><th class="py-3.5 px-6">Description</th><th class="py-3.5 px-6">Category</th><th class="py-3.5 px-6 text-right">Amount</th><th class="py-3.5 px-6 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="transactions-table-body" class="divide-y divide-slate-100 text-sm"></tbody>
                            </table>
                            <div id="empty-state" class="hidden py-12 text-center text-slate-400 text-sm space-y-2">
                                <i class="fa-solid fa-receipt text-3xl text-slate-200"></i>
                                <p>No transactions registered matching criteria. Record one above!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ANALYTICS -->
                <div id="tab-analytics" class="hidden space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-900">📊 Analytics & Spend Reporting</h2>
                        <p class="text-slate-500 text-sm mt-1">Real-time metrics computed from your live database.</p>
                    </div>
                    <div class="space-y-4">
                        <div class="border-b border-slate-100 pb-2">
                            <h3 class="text-base font-extrabold text-slate-800 tracking-tight flex items-center gap-2"><i class="fa-solid fa-chart-pie text-indigo-500"></i> Category Allocation Distribution</h3>
                            <p class="text-xs text-slate-400">Circular representations of spend vs. each category's target.</p>
                        </div>
                        <div id="analytics-category-progress-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 w-full"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
                            <span class="text-xs text-slate-400 uppercase font-bold tracking-wider block">Savings Rate</span>
                            <h4 id="analytics-savings-rate" class="text-2xl font-extrabold text-[#00C48C] mt-2">0%</h4>
                            <p class="text-[10px] text-slate-400 mt-1">Allocated percentage remaining</p>
                        </div>
                        <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
                            <span class="text-xs text-slate-400 uppercase font-bold tracking-wider block">Average Daily Spend</span>
                            <h4 id="analytics-daily-average" class="text-2xl font-extrabold text-indigo-500 mt-2">₹0</h4>
                            <p class="text-[10px] text-slate-400 mt-1">Estimations over monthly scope</p>
                        </div>
                        <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
                            <span class="text-xs text-slate-400 uppercase font-bold tracking-wider block">Top Spending Target</span>
                            <h4 id="analytics-top-category" class="text-2xl font-extrabold text-amber-500 mt-2">None</h4>
                            <p class="text-[10px] text-slate-400 mt-1">Highest cumulative allocation</p>
                        </div>
                        <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
                            <span class="text-xs text-slate-400 uppercase font-bold tracking-wider block">Security Check</span>
                            <h4 id="analytics-risk-indicator" class="text-2xl font-extrabold text-[#00C48C] mt-2">Perfect</h4>
                            <p class="text-[10px] text-slate-400 mt-1">Automated depletion velocity check</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm space-y-4">
                            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                                <h3 class="font-bold text-base text-slate-800">Category Expenditure (₹)</h3>
                                <span class="text-[10px] bg-emerald-50 text-[#00C48C] px-2.5 py-0.5 rounded-full font-bold">Dynamic Scale</span>
                            </div>
                            <div class="relative h-64 pb-2 px-2 pt-4">
                                <canvas id="analytics-bar-chart"></canvas>
                            </div>
                        </div>
                        <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm flex flex-col justify-between space-y-4">
                            <div>
                                <h3 class="font-bold text-base text-slate-800 border-b border-slate-100 pb-3">Allowance Conservation Analysis</h3>
                                <div class="space-y-4 pt-3">
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-xs font-semibold"><span class="text-slate-400">Total Setup Capital</span><span id="analysis-allowance-val" class="text-slate-700 font-bold">₹0.00</span></div>
                                        <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden"><div class="bg-indigo-500 h-full w-full rounded-full"></div></div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-xs font-semibold"><span class="text-slate-400">Active Outflow</span><span id="analysis-consumed-val" class="text-rose-500 font-bold">₹0.00</span></div>
                                        <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden"><div id="analysis-consumed-bar" class="bg-[#A83925] h-full rounded-full transition-all duration-300" style="width: 0%"></div></div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-xs font-semibold"><span class="text-slate-400">Conserved Safety Threshold</span><span id="analysis-reserves-val" class="text-[#00C48C] font-bold">₹0.00</span></div>
                                        <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden"><div id="analysis-reserves-bar" class="bg-[#00C48C] h-full rounded-full transition-all duration-300" style="width: 0%"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs text-slate-500 flex items-start gap-3">
                                <i class="fa-regular fa-lightbulb text-indigo-500 text-lg mt-0.5 shrink-0"></i>
                                <p id="analysis-insight-message">Maintain a solid budget limit. Regularly audit your category targets for maximum monthly returns.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PROFILE -->
                <div id="tab-profile" class="hidden space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-900">👤 User Profile Setup</h2>
                        <p class="text-slate-500 text-sm mt-1">Configure your personal profile details and session allowance targets.</p>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm flex flex-col justify-between space-y-6">
                            <div class="space-y-4 text-center">
                                <div class="relative w-24 h-24 mx-auto rounded-full bg-[#1E293B] border-4 border-slate-100 flex items-center justify-center text-3xl font-extrabold text-white"><span id="profile-avatar-letters">US</span></div>
                                <div><h3 id="profile-card-name" class="font-bold text-lg text-slate-800">User</h3><p id="profile-card-email" class="text-xs text-slate-400">user@domain.com</p></div>
                            </div>
                            <div class="border-t border-slate-100 pt-4 space-y-3">
                                <div class="flex justify-between text-xs"><span class="text-slate-400 font-semibold uppercase">Limit Capital:</span><span id="profile-card-allowance" class="font-bold text-[#00C48C]">₹0.00</span></div>
                            </div>
                            <div class="bg-emerald-50 text-emerald-600 p-3 rounded-xl text-center text-xs font-semibold border border-emerald-100"><i class="fa-solid fa-user-shield mr-1"></i> Authorized User Session</div>
                        </div>
                        <div class="bg-white border border-slate-100 p-6 rounded-2xl lg:col-span-2 shadow-sm">
                            <h3 class="font-bold text-base text-slate-800 mb-4 border-b border-slate-100 pb-3">Edit Profile Properties</h3>
                            <form id="profile-edit-form" onsubmit="handleProfileUpdate(event)" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">User Name</label>
                                        <input type="text" id="profile-form-name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-slate-800 focus:outline-none focus:border-[#00C48C] focus:bg-white transition-all">
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="text-xs font-bold text-slate-500 uppercase">Monthly Allowance Target (₹)</label>
                                            <span class="text-[10px] text-amber-500 font-semibold italic">Configure via categories modal</span>
                                        </div>
                                        <input type="number" id="profile-form-allowance" readonly class="w-full bg-slate-100 border border-slate-200 rounded-xl py-2 px-3 text-slate-500 cursor-not-allowed" title="Update budgets using the categories configuration button.">
                                    </div>
                                </div>
                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="bg-[#00C48C] hover:bg-[#00B07C] text-white font-bold px-6 py-2.5 rounded-xl transition-all shadow-md shadow-emerald-500/10">Apply Profile Updates</button>
                                </div>
                            </form>
                        </div>
                        <div class="bg-white border border-slate-100 p-6 rounded-2xl lg:col-span-3 shadow-sm space-y-5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                                <div>
                                    <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2"><i class="fa-solid fa-quote-left text-[#00C48C]"></i> Financial Quotes &amp; Timely Investment Techniques</h3>
                                    <p class="text-slate-400 text-xs mt-1">Timely insights, auto-rotating principles, and key investment frameworks for wealth accumulation.</p>
                                </div>
                                <button onclick="advanceQuoteRotator()" class="shrink-0 text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 px-3.5 py-2 rounded-xl transition-all font-semibold flex items-center gap-1.5 self-start sm:self-auto"><i class="fa-solid fa-rotate"></i> Next Insight</button>
                            </div>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="bg-gradient-to-br from-[#0B1220] to-[#1E293B] text-white p-6 rounded-2xl relative overflow-hidden flex flex-col justify-between min-h-[220px]">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 flex items-center gap-1.5"><i class="fa-solid fa-rotate"></i> Quote of the Moment</span>
                                        <span id="quote-rotator-badge" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-[#00C48C]/20 text-[#00C48C] uppercase tracking-wide">Auto-Refreshes</span>
                                    </div>
                                    <p id="quote-rotator-text" class="text-lg sm:text-xl font-semibold leading-relaxed transition-opacity duration-500">Loading...</p>
                                    <p id="quote-rotator-author" class="text-xs text-slate-400 mt-3 transition-opacity duration-500">&nbsp;</p>
                                    <div class="w-full bg-white/10 h-1 rounded-full overflow-hidden mt-4">
                                        <div id="quote-rotator-progress" class="bg-[#00C48C] h-full rounded-full" style="width:0%"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="bg-slate-50 border border-slate-100 p-4 rounded-xl">
                                        <h4 class="text-xs font-extrabold text-slate-800 mb-1.5 flex items-center gap-1.5"><i class="fa-solid fa-percent text-emerald-500"></i> 1. The 50/30/20 Rule</h4>
                                        <p class="text-[11px] text-slate-500 leading-relaxed">Allocate 50% of income to Needs, 30% to Wants, and commit at least 20% immediately toward systematic savings and index funds.</p>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-100 p-4 rounded-xl">
                                        <h4 class="text-xs font-extrabold text-slate-800 mb-1.5 flex items-center gap-1.5"><i class="fa-solid fa-chart-line text-indigo-500"></i> 2. Dollar-Cost Averaging (DCA)</h4>
                                        <p class="text-[11px] text-slate-500 leading-relaxed">Invest a fixed rupee amount regularly regardless of market ups and downs. DCA lowers average acquisition cost over long investment horizons.</p>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-100 p-4 rounded-xl">
                                        <h4 class="text-xs font-extrabold text-slate-800 mb-1.5 flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-amber-500"></i> 3. Emergency Capital Cushion</h4>
                                        <p class="text-[11px] text-slate-500 leading-relaxed">Keep 3 to 6 months of baseline living expenses liquid in a high-yield savings account or liquid fund before locking capital into high-volatility assets.</p>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-100 p-4 rounded-xl">
                                        <h4 class="text-xs font-extrabold text-slate-800 mb-1.5 flex items-center gap-1.5"><i class="fa-solid fa-calculator text-rose-500"></i> 4. The Rule of 72</h4>
                                        <p class="text-[11px] text-slate-500 leading-relaxed">Divide 72 by your expected annual rate of return to calculate the approximate number of years it will take to double your invested capital.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- BUDGET MODAL -->
    <div id="budget-modal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex justify-center items-center p-4">
        <div class="bg-white border border-slate-100 max-w-md w-full rounded-2xl p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-800">Configure Category Budgets</h3>
                <button onclick="closeSetBudgetModal()" class="text-slate-400 hover:text-slate-600 transition-colors"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                <p class="text-xs text-slate-400 font-medium">Set custom allowances for each category. The total monthly budget will adjust automatically.</p>
                <div class="bg-indigo-50/50 p-3.5 rounded-xl border border-indigo-100/80 mb-2">
                    <label class="block text-xs font-extrabold text-indigo-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>💰 OVERALL BUDGET LIMIT (₹)</span>
                        <span class="text-[9px] bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full font-bold normal-case tracking-normal">Auto-distributes</span>
                    </label>
                    <input type="number" id="modal-overall-budget" min="100" oninput="handleOverallBudgetInput()" class="w-full bg-white border border-indigo-200 rounded-xl py-2 px-3 text-indigo-950 font-black focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm shadow-sm" placeholder="e.g. 15000">
                </div>
                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">🍔 Food Budget (₹)</label><input type="number" id="modal-cat-food" min="0" oninput="calculateTotalFromCategories()" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-slate-800 focus:outline-none focus:border-[#00C48C] transition-all"></div>
                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">📚 Books & Stationery Budget (₹)</label><input type="number" id="modal-cat-books" min="0" oninput="calculateTotalFromCategories()" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-slate-800 focus:outline-none focus:border-[#00C48C] transition-all"></div>
                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">🍿 Entertainment Budget (₹)</label><input type="number" id="modal-cat-ent" min="0" oninput="calculateTotalFromCategories()" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-slate-800 focus:outline-none focus:border-[#00C48C] transition-all"></div>
                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">🏠 Rent & Utilities Budget (₹)</label><input type="number" id="modal-cat-rent" min="0" oninput="calculateTotalFromCategories()" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-slate-800 focus:outline-none focus:border-[#00C48C] transition-all"></div>
                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">⚙️ Others Budget (₹)</label><input type="number" id="modal-cat-others" min="0" oninput="calculateTotalFromCategories()" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-slate-800 focus:outline-none focus:border-[#00C48C] transition-all"></div>
                <div class="pt-2 border-t border-slate-100">
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl"><span class="text-xs font-bold text-slate-500 uppercase">Calculated Total Budget:</span><span id="modal-calculated-total" class="text-base font-extrabold text-[#00C48C]">₹0.00</span></div>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button onclick="closeSetBudgetModal()" class="px-4 py-2 text-sm font-semibold text-slate-400 hover:text-slate-600">Cancel</button>
                <button onclick="handleSetBudget()" class="bg-[#00C48C] hover:bg-[#00B07C] text-white text-sm font-bold px-4 py-2 rounded-xl transition-all shadow-md shadow-emerald-500/10">Save Budgets</button>
            </div>
        </div>
    </div>

    <!-- BUDGET EXCEEDED MODAL -->
    <div id="budget-exceeded-modal" class="hidden fixed inset-0 z-[60] bg-slate-950/80 backdrop-blur-sm flex justify-center items-center p-4">
        <div class="bg-white border border-slate-100 max-w-sm w-full rounded-2xl p-6 shadow-2xl space-y-4 text-center">
            <div class="h-16 w-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center text-[#A83925] text-3xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-slate-800">Budget Exceeded!</h3>
                <p id="budget-exceeded-message" class="text-sm text-slate-500 mt-2">You have crossed your monthly spending limit.</p>
            </div>
            <button onclick="closeBudgetExceededModal()" class="w-full bg-[#A83925] hover:bg-[#8f2f1f] text-white font-bold py-2.5 rounded-xl transition-all">Got it</button>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <script src="script.js"></script>
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
