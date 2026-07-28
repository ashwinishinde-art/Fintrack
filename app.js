/* FinTrack - Frontend logic. All data now lives in MySQL via the
   PHP endpoints in /api — nothing is stored in localStorage. */
console.log('FinTrack app.js build: bar-chart-fix + budget-exceeded-popup v2');

const CATEGORY_COLORS = {
    'Food': '#3B82F6',
    'Books & Stationery': '#8B5CF6',
    'Entertainment': '#F59E0B',
    'Rent & Utilities': '#A83925',
    'Others': '#00C48C'
};

let state = {
    isAuthenticated: false,
    user: { name: '', email: '', theme: 'light' },
    budget: { total_budget: 0, cat_food: 0, cat_books: 0, cat_entertainment: 0, cat_rent: 0, cat_others: 0 },
    expenses: []
};

let categoryBarChart = null;

const prefersReducedMotion = () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Puts a submit button into a disabled, spinning state while an async
// request is in flight, and restores its original label afterward — so
// every form gives immediate feedback instead of looking unresponsive
// during the network round-trip.
function setButtonLoading(btn, isLoading, loadingText) {
    if (!btn) return;
    if (isLoading) {
        if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-wait');
        btn.innerHTML = `<span class="btn-spinner"></span><span>${loadingText || 'Working…'}</span>`;
    } else {
        btn.disabled = false;
        btn.classList.remove('opacity-80', 'cursor-wait');
        if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
    }
}

// Brief shake + red flash on a form's inputs to make a failed submission
// (bad credentials, validation error, etc.) register instantly, before the
// person even reads the toast.
function flashFormError(formEl) {
    if (!formEl || prefersReducedMotion()) return;
    formEl.classList.remove('animate-shake');
    void formEl.offsetWidth; // restart animation if triggered twice in a row
    formEl.classList.add('animate-shake');
    formEl.querySelectorAll('input').forEach(inp => {
        inp.classList.add('field-error');
        setTimeout(() => inp.classList.remove('field-error'), 900);
    });
    setTimeout(() => formEl.classList.remove('animate-shake'), 500);
}

// Animates a KPI figure counting up/down to its new value instead of
// snapping instantly, so updates (new expense, budget change, etc.) feel
// alive rather than static text swaps.
function animateNumber(el, endValue, prefix = '₹') {
    if (!el) return;
    endValue = Math.round(endValue);
    if (prefersReducedMotion()) { el.innerText = `${prefix}${endValue.toLocaleString()}`; return; }

    const startValue = parseFloat((el.innerText || '').replace(/[^0-9.-]/g, '')) || 0;
    if (startValue === endValue) { el.innerText = `${prefix}${endValue.toLocaleString()}`; return; }

    const duration = 650;
    const startTime = performance.now();
    const easeOutExpo = t => (t >= 1 ? 1 : 1 - Math.pow(2, -10 * t));

    if (el._animFrame) cancelAnimationFrame(el._animFrame);
    function tick(now) {
        const t = Math.min(1, (now - startTime) / duration);
        const current = Math.round(startValue + (endValue - startValue) * easeOutExpo(t));
        el.innerText = `${prefix}${current.toLocaleString()}`;
        if (t < 1) el._animFrame = requestAnimationFrame(tick);
    }
    el._animFrame = requestAnimationFrame(tick);
}

function isDarkMode() {
    return document.documentElement.classList.contains('dark');
}

// Keeps the header icon button and the Profile → Appearance switch in
// sync with whatever the current theme actually is.
function updateThemeControls(theme, animateIcons = false) {
    const isDark = theme === 'dark';

    const track = document.getElementById('theme-toggle-switch');
    const thumb = document.getElementById('theme-toggle-thumb');
    const icon = document.getElementById('theme-toggle-icon');
    const label = document.getElementById('theme-status-label');
    const headerIcon = document.getElementById('header-theme-icon');

    if (track) {
        track.setAttribute('aria-checked', String(isDark));
        track.classList.toggle('bg-slate-700', isDark);
        track.classList.toggle('bg-slate-200', !isDark);
    }
    if (thumb) {
        thumb.classList.toggle('translate-x-5', isDark);
        thumb.classList.toggle('translate-x-0', !isDark);
    }
    if (icon) icon.className = (isDark ? 'fa-solid fa-moon' : 'fa-solid fa-sun');
    if (label) label.innerText = isDark ? 'Dark Mode' : 'Light Mode';
    if (headerIcon) headerIcon.className = (isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon') + ' text-sm';

    if (animateIcons && !prefersReducedMotion()) {
        [icon, headerIcon].forEach(el => {
            if (!el) return;
            el.classList.remove('theme-icon-pop');
            void el.offsetWidth;
            el.classList.add('theme-icon-pop');
        });
    }
}

// Switches between light and dark. When triggered by a user click (an
// originEvent is supplied) and the browser supports the View Transitions
// API, this expands a circle from the click point to reveal the new
// theme — otherwise (Safari/Firefox, or the silent initial apply on
// login) it just swaps the class instantly and lets the CSS
// background-color/color transitions declared in <style> handle the rest.
function applyTheme(theme, opts = {}) {
    const { animate = false, originEvent = null } = opts;
    const root = document.documentElement;

    const doApply = () => {
        root.classList.toggle('dark', theme === 'dark');
        updateThemeControls(theme, animate);
    };

    const rerenderChartIfVisible = () => {
        const analyticsTab = document.getElementById('tab-analytics');
        if (analyticsTab && !analyticsTab.classList.contains('hidden')) renderAnalyticsCharts();
    };

    const canAnimateReveal = animate && !prefersReducedMotion() && typeof document.startViewTransition === 'function';
    if (!canAnimateReveal) {
        doApply();
        rerenderChartIfVisible();
        return;
    }

    let x = window.innerWidth - 48;
    let y = 48;
    if (originEvent && typeof originEvent.clientX === 'number' && (originEvent.clientX || originEvent.clientY)) {
        x = originEvent.clientX;
        y = originEvent.clientY;
    } else {
        const toggleEl = document.getElementById('theme-toggle-switch');
        if (toggleEl) {
            const rect = toggleEl.getBoundingClientRect();
            x = rect.left + rect.width / 2;
            y = rect.top + rect.height / 2;
        }
    }
    const endRadius = Math.hypot(Math.max(x, window.innerWidth - x), Math.max(y, window.innerHeight - y));

    const transition = document.startViewTransition(doApply);
    transition.ready.then(() => {
        root.animate(
            { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${endRadius}px at ${x}px ${y}px)`] },
            { duration: 600, easing: 'cubic-bezier(.4,0,.2,1)', pseudoElement: '::view-transition-new(root)' }
        );
    }).catch(() => {}).finally(rerenderChartIfVisible);
}

async function handleThemeToggleClick(e) {
    const next = isDarkMode() ? 'light' : 'dark';
    applyTheme(next, { animate: true, originEvent: e });
    state.user.theme = next;

    const data = await api('api/profile.php', { method: 'POST', body: JSON.stringify({ theme: next }) });
    if (data.success) {
        showToast(next === 'dark' ? 'Dark Mode' : 'Light Mode', `Switched to ${next} mode.`, 'info');
    } else {
        showToast('Error', data.message || 'Could not save theme preference.', 'error');
    }
}

const QUOTES_AND_TIPS = [
    { type: 'Quote', text: 'An investment in knowledge pays the best interest.', author: 'Benjamin Franklin' },
    { type: 'Tip', text: 'Track every rupee for 30 days before you set a budget — you can\'t manage what you don\'t measure.' },
    { type: 'Quote', text: 'Do not save what is left after spending; spend what is left after saving.', author: 'Warren Buffett' },
    { type: 'Tip', text: 'Automate a fixed transfer to savings on payday so it never feels like a choice.' },
    { type: 'Quote', text: 'The stock market is a device for transferring money from the impatient to the patient.', author: 'Warren Buffett' },
    { type: 'Tip', text: 'Build an emergency fund covering 3–6 months of expenses before chasing higher-risk returns.' },
    { type: 'Quote', text: 'It\'s not how much money you make, but how much money you keep.', author: 'Robert Kiyosaki' },
    { type: 'Tip', text: 'Review your category budgets monthly — small recurring subscriptions add up fast.' },
    { type: 'Quote', text: 'Risk comes from not knowing what you are doing.', author: 'Warren Buffett' },
    { type: 'Tip', text: 'Diversify across a few asset types rather than putting every rupee in one place.' },
    { type: 'Quote', text: 'A budget is telling your money where to go instead of wondering where it went.', author: 'Dave Ramsey' },
    { type: 'Tip', text: 'Compounding rewards time in the market more than perfectly timing the market.' },
    { type: 'Quote', text: 'Beware of little expenses; a small leak will sink a great ship.', author: 'Benjamin Franklin' },
    { type: 'Tip', text: 'Set a fixed \'fun money\' cap each month so discretionary spending stays guilt-free and controlled.' },
    { type: 'Quote', text: 'The individual investor should act consistently as an investor and not as a speculator.', author: 'Benjamin Graham' },
    { type: 'Tip', text: 'Before any purchase over your comfort threshold, wait 24 hours — impulse fades fast.' }
];
let quoteRotatorIndex = -1;
let quoteRotatorInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('form-date');
    if (dateInput) dateInput.value = today;
    bootstrapSession();
    initLoginCursorEffects();
});

// Makes the login screen respond to the cursor: the ambient blobs drift
// a little toward the pointer (parallax) and a soft green spotlight
// follows it. Purely cosmetic, so it's skipped entirely when the person
// prefers reduced motion, and resets smoothly when the cursor leaves.
function initLoginCursorEffects() {
    const view = document.getElementById('view-login');
    const spotlight = document.getElementById('login-spotlight');
    const wrapA = document.getElementById('login-blob-wrap-a');
    const wrapB = document.getElementById('login-blob-wrap-b');
    if (!view || prefersReducedMotion()) return;

    view.addEventListener('mousemove', (e) => {
        const rect = view.getBoundingClientRect();
        const px = ((e.clientX - rect.left) / rect.width) * 100;
        const py = ((e.clientY - rect.top) / rect.height) * 100;
        if (spotlight) {
            spotlight.style.setProperty('--mx', `${px}%`);
            spotlight.style.setProperty('--my', `${py}%`);
        }
        const dx = (px - 50) / 50; // -1 .. 1
        const dy = (py - 50) / 50;
        if (wrapA) wrapA.style.transform = `translate(${dx * 22}px, ${dy * 22}px)`;
        if (wrapB) wrapB.style.transform = `translate(${dx * -18}px, ${dy * -18}px)`;
    });

    view.addEventListener('mouseleave', () => {
        if (wrapA) wrapA.style.transform = 'translate(0, 0)';
        if (wrapB) wrapB.style.transform = 'translate(0, 0)';
    });
}

window.addEventListener('resize', () => {
    if (categoryBarChart) categoryBarChart.resize();
});

async function api(path, options = {}) {
    const opts = Object.assign({ headers: { 'Content-Type': 'application/json' } }, options);
    const res = await fetch(path, opts);
    let data;
    try {
        data = await res.json();
    } catch (e) {
        data = { success: false, message: 'Unexpected server response.' };
    }
    if (!res.ok && !data.message) {
        data.message = `Request failed (HTTP ${res.status})`;
    }
    return data;
}

async function bootstrapSession() {
    const data = await api('api/session.php');
    if (data.authenticated) {
        applySessionData(data);
    } else {
        showLoginView();
    }
}

function applySessionData(data) {
    state.isAuthenticated = true;
    state.user = data.user;
    state.budget = data.budget || state.budget;
    state.expenses = data.expenses || [];
    applyTheme(state.user.theme === 'dark' ? 'dark' : 'light', { animate: false });
    showAppView();
    renderApp();
}

function showLoginView() {
    document.getElementById('view-login').classList.remove('hidden');
    document.getElementById('view-app').classList.add('hidden');
}

function showAppView() {
    document.getElementById('view-login').classList.add('hidden');
    document.getElementById('view-app').classList.remove('hidden');
    document.getElementById('profile-form-name').value = state.user.name;
    document.getElementById('profile-form-allowance').value = state.budget.total_budget;
    startQuoteRotator();
}

const QUOTE_ROTATOR_INTERVAL_MS = 6000;

function showQuoteOrTip(i) {
    const textEl = document.getElementById('quote-rotator-text');
    const authorEl = document.getElementById('quote-rotator-author');
    const badgeEl = document.getElementById('quote-rotator-badge');
    const progressEl = document.getElementById('quote-rotator-progress');
    if (!textEl || !authorEl || !badgeEl) return;
    const item = QUOTES_AND_TIPS[i];

    textEl.style.opacity = 0;
    authorEl.style.opacity = 0;
    if (progressEl) {
        progressEl.style.transition = 'none';
        progressEl.style.width = '0%';
    }
    setTimeout(() => {
        textEl.innerText = item.type === 'Tip' ? `💡 ${item.text}` : `“${item.text}”`;
        authorEl.innerText = item.author ? `— ${item.author}` : '\u00A0';
        badgeEl.innerText = item.type === 'Tip' ? 'Tip' : 'Auto-Refreshes';
        badgeEl.className = item.type === 'Tip'
            ? 'text-[9px] font-bold px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-300 uppercase tracking-wide'
            : 'text-[9px] font-bold px-2 py-0.5 rounded-full bg-[#00C48C]/20 text-[#00C48C] uppercase tracking-wide';
        textEl.style.opacity = 1;
        authorEl.style.opacity = 1;
        if (progressEl) {
            requestAnimationFrame(() => {
                progressEl.style.transition = `width ${QUOTE_ROTATOR_INTERVAL_MS}ms linear`;
                progressEl.style.width = '100%';
            });
        }
    }, 300);
}

function startRotatorInterval() {
    if (quoteRotatorInterval) clearInterval(quoteRotatorInterval);
    quoteRotatorInterval = setInterval(() => {
        quoteRotatorIndex = (quoteRotatorIndex + 1) % QUOTES_AND_TIPS.length;
        showQuoteOrTip(quoteRotatorIndex);
    }, QUOTE_ROTATOR_INTERVAL_MS);
}

function startQuoteRotator() {
    if (!document.getElementById('quote-rotator-text')) return;
    quoteRotatorIndex = 0;
    showQuoteOrTip(quoteRotatorIndex);
    startRotatorInterval();
}

function advanceQuoteRotator() {
    quoteRotatorIndex = (quoteRotatorIndex + 1) % QUOTES_AND_TIPS.length;
    showQuoteOrTip(quoteRotatorIndex);
    startRotatorInterval();
}

function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye text-sm text-white';
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye-slash text-sm';
    }
}

function toggleAuthMode(mode) {
    const btnSignin = document.getElementById('btn-toggle-signin');
    const btnSignup = document.getElementById('btn-toggle-signup');
    const formSignin = document.getElementById('signin-form');
    const formSignup = document.getElementById('signup-form');

    if (mode === 'signin') {
        btnSignin.className = 'flex-1 py-2 text-xs font-bold text-white rounded-lg bg-slate-800/60 border border-slate-700/50 transition-all duration-300';
        btnSignup.className = 'flex-1 py-2 text-xs font-bold text-slate-400 rounded-lg hover:text-white transition-all duration-300';
        formSignin.classList.remove('hidden');
        formSignin.classList.add('animate-in');
        formSignup.classList.add('hidden');
    } else {
        btnSignup.className = 'flex-1 py-2 text-xs font-bold text-white rounded-lg bg-indigo-600 transition-all duration-300';
        btnSignin.className = 'flex-1 py-2 text-xs font-bold text-slate-400 rounded-lg hover:text-white transition-all duration-300';
        formSignin.classList.add('hidden');
        formSignup.classList.remove('hidden');
        formSignup.classList.add('animate-in');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const payload = {
        name: document.getElementById('reg-name').value,
        email: document.getElementById('reg-email').value,
        budget: parseFloat(document.getElementById('reg-budget').value) || 1500,
        password: document.getElementById('reg-password').value
    };

    setButtonLoading(btn, true, 'Creating workspace…');
    const data = await api('api/register.php', { method: 'POST', body: JSON.stringify(payload) });
    setButtonLoading(btn, false);

    if (data.success) {
        showToast('Workspace Active', `Welcome aboard, ${payload.name}!`, 'success');
        await bootstrapSession();
    } else {
        showToast('Registration Refused', data.message || 'Could not create account.', 'error');
        flashFormError(e.target);
    }
}

async function handleLogin(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const payload = {
        email: document.getElementById('login-email').value,
        password: document.getElementById('login-password').value
    };
    setButtonLoading(btn, true, 'Signing in…');
    const data = await api('api/login.php', { method: 'POST', body: JSON.stringify(payload) });
    setButtonLoading(btn, false);

    if (data.success) {
        showToast('Access Granted', `Welcome back, ${data.user.name}!`, 'success');
        await bootstrapSession();
    } else {
        showToast('Error', data.message || 'Login failed.', 'error');
        flashFormError(e.target);
    }
}

async function handleLogout() {
    await api('api/logout.php', { method: 'POST' });
    document.getElementById('login-password').value = '';
    document.getElementById('reg-password').value = '';
    state.isAuthenticated = false;
    showLoginView();
    showToast('Session Closed', 'Successfully logged out from FinTrack.', 'info');
}

async function handleProfileUpdate(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const name = document.getElementById('profile-form-name').value;
    setButtonLoading(btn, true, 'Saving…');
    const data = await api('api/profile.php', { method: 'POST', body: JSON.stringify({ name }) });
    setButtonLoading(btn, false);
    if (data.success) {
        state.user.name = name;
        renderApp();
        showToast('Success', 'Profile updated!', 'success');
    } else {
        showToast('Error', data.message || 'Update failed.', 'error');
        flashFormError(e.target);
    }
}

function switchTab(tabId) {
    const tabs = ['dashboard', 'analytics', 'profile'];
    tabs.forEach(t => {
        const el = document.getElementById(`tab-${t}`);
        el.classList.toggle('hidden', t !== tabId);
        if (t === tabId) {
            el.classList.remove('tab-fade-in');
            void el.offsetWidth; // restart the animation each time the tab is shown
            el.classList.add('tab-fade-in');
        }
    });

    const btnMap = { dashboard: 'sidebar-dashboard-btn', analytics: 'sidebar-analytics-btn', profile: 'sidebar-profile-btn' };
    Object.keys(btnMap).forEach(key => {
        const btn = document.getElementById(btnMap[key]);
        if (!btn) return;
        btn.className = key === tabId
            ? 'sidebar-item-active w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-150 hover:bg-slate-800/50 hover:text-white text-left'
            : 'w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-150 hover:bg-slate-800/50 hover:text-slate-200 text-left';
    });

    if (tabId === 'analytics') {
        // Defer until after the tab's display change has been laid out,
        // otherwise Chart.js can measure a zero-size canvas and draw blank bars.
        requestAnimationFrame(() => requestAnimationFrame(renderAnalyticsCharts));
    }
}

function scrollToTransactions() {
    switchTab('dashboard');
    document.getElementById('transactions-section').scrollIntoView({ behavior: 'smooth' });
}

function focusExpenseInput() {
    switchTab('dashboard');
    document.getElementById('expense-record-card').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('form-amount').focus();
}

function openAddExpenseSection() { focusExpenseInput(); }

function getPieChartSVG(percent, colorHex) {
    const r = 40, cx = 50, cy = 50;
    percent = Math.round(percent);

    if (percent >= 100) {
        return `<svg viewBox="0 0 100 100" class="w-24 h-24 sm:w-28 sm:h-28">
            <circle cx="${cx}" cy="${cy}" r="${r}" fill="${colorHex}" />
            <text x="${cx}" y="${cy + 4}" fill="#ffffff" font-size="12" font-weight="800" text-anchor="middle">100%</text>
        </svg>`;
    }
    if (percent <= 0) {
        return `<svg viewBox="0 0 100 100" class="w-24 h-24 sm:w-28 sm:h-28">
            <circle cx="${cx}" cy="${cy}" r="${r}" fill="#E2E8F0" />
            <text x="${cx}" y="${cy + 4}" fill="#64748B" font-size="12" font-weight="800" text-anchor="middle">0%</text>
        </svg>`;
    }

    const angle = (percent / 100) * 360;
    const startRad = -Math.PI / 2;
    const endRad = startRad + (angle * Math.PI) / 180;
    const x1 = cx + r * Math.cos(startRad);
    const y1 = cy + r * Math.sin(startRad);
    const x2 = cx + r * Math.cos(endRad);
    const y2 = cy + r * Math.sin(endRad);
    const largeArc = percent > 50 ? 1 : 0;
    const pathData = `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2} Z`;

    return `<svg viewBox="0 0 100 100" class="w-24 h-24 sm:w-28 sm:h-28">
        <circle cx="${cx}" cy="${cy}" r="${r}" fill="#E2E8F0" />
        <path d="${pathData}" fill="${colorHex}" />
        <text x="${cx}" y="${cy + 4}" fill="#ffffff" font-size="11" font-weight="900" text-anchor="middle" style="text-shadow: 0px 1px 2px rgba(0,0,0,0.5);">${percent}%</text>
    </svg>`;
}

function categorySums() {
    const sums = { 'Food': 0, 'Books & Stationery': 0, 'Entertainment': 0, 'Rent & Utilities': 0, 'Others': 0 };
    state.expenses.forEach(exp => {
        const amt = parseFloat(exp.amount);
        if (sums.hasOwnProperty(exp.category)) sums[exp.category] += amt;
        else sums['Others'] += amt;
    });
    return sums;
}

function categoryLimitsMap() {
    return {
        'Food': parseFloat(state.budget.cat_food) || 0,
        'Books & Stationery': parseFloat(state.budget.cat_books) || 0,
        'Entertainment': parseFloat(state.budget.cat_entertainment) || 0,
        'Rent & Utilities': parseFloat(state.budget.cat_rent) || 0,
        'Others': parseFloat(state.budget.cat_others) || 0
    };
}

function renderApp() {
    const budget = parseFloat(state.budget.total_budget) || 0;

    document.getElementById('greeting-username').innerText = state.user.name;
    document.getElementById('sidebar-username').innerText = state.user.name;
    document.getElementById('profile-card-name').innerText = state.user.name;
    document.getElementById('profile-card-email').innerText = state.user.email;
    document.getElementById('profile-card-allowance').innerText = `₹${budget.toLocaleString()}`;
    document.getElementById('profile-form-allowance').value = budget;

    const initials = state.user.name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    document.getElementById('profile-avatar-letters').innerText = initials;
    document.getElementById('sidebar-avatar-letters').innerText = initials;

    const now = new Date();
    document.getElementById('current-calendar-cycle').innerText =
        `${now.toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })} · ${now.toLocaleDateString('en-IN', { month: 'long' })} budget cycle`;
    document.getElementById('cycle-label-kpi').innerText = now.toLocaleDateString('en-IN', { month: 'long' });

    const sums = categorySums();
    let totalSpent = 0;
    Object.values(sums).forEach(v => totalSpent += v);
    // Once spending reaches or passes the budget, treat balance/percent as
    // fully depleted (0) instead of drifting into negative numbers.
    const remaining = Math.max(0, budget - totalSpent);
    const spentPercent = budget > 0 ? Math.min(100, Math.round((totalSpent / budget) * 100)) : 0;

    animateNumber(document.getElementById('card-total-budget'), budget);
    animateNumber(document.getElementById('card-total-spent'), totalSpent);
    animateNumber(document.getElementById('card-remaining-balance'), remaining);
    document.getElementById('progress-spent-hint').innerText = `₹${totalSpent.toLocaleString()} spent`;
    document.getElementById('progress-left-hint').innerText = `₹${remaining.toLocaleString()} left`;

    const progressBar = document.getElementById('spent-progress-bar');
    progressBar.style.width = `${Math.min(spentPercent, 100)}%`;

    const warningBanner = document.getElementById('overspend-banner');
    const alertText = document.getElementById('balance-status-alert');
    const alertIconBg = document.getElementById('balance-icon-bg');
    const alertIcon = document.getElementById('balance-icon');
    const bannerWasHidden = warningBanner.classList.contains('hidden');

    if (spentPercent >= 90) {
        progressBar.className = 'bg-[#A83925] h-full rounded-full transition-all duration-500';
        warningBanner.classList.remove('hidden');
        if (bannerWasHidden) { warningBanner.classList.add('animate-attention'); setTimeout(() => warningBanner.classList.remove('animate-attention'), 3600); }
        document.getElementById('overspend-banner-message').innerText = `Warning — Active targets have depleted over ${spentPercent}% of your setup monthly target.`;
        alertText.innerText = `Only ${100 - spentPercent}% of budget left this month`;
        alertText.className = 'text-xs text-[#A83925] font-bold pt-3';
        alertIconBg.className = 'h-8 w-8 bg-rose-50 rounded-xl flex items-center justify-center text-[#A83925]';
        alertIcon.className = 'fa-regular fa-shield-xmark text-lg';
    } else if (spentPercent >= 75) {
        progressBar.className = 'bg-amber-500 h-full rounded-full transition-all duration-500';
        warningBanner.classList.remove('hidden');
        if (bannerWasHidden) { warningBanner.classList.add('animate-attention'); setTimeout(() => warningBanner.classList.remove('animate-attention'), 3600); }
        document.getElementById('overspend-banner-message').innerText = `Heads up — You have spent ${spentPercent}% of your target monthly allowance. Plan carefully.`;
        alertText.innerText = `${100 - spentPercent}% of monthly budget remains`;
        alertText.className = 'text-xs text-amber-600 font-semibold pt-3';
        alertIconBg.className = 'h-8 w-8 bg-amber-50 rounded-xl flex items-center justify-center text-amber-500';
        alertIcon.className = 'fa-regular fa-shield text-lg';
    } else {
        progressBar.className = 'bg-[#00C48C] h-full rounded-full transition-all duration-500';
        warningBanner.classList.add('hidden');
        alertText.innerText = `Funding is healthy. ${100 - spentPercent}% remaining.`;
        alertText.className = 'text-xs text-emerald-600 font-semibold pt-3';
        alertIconBg.className = 'h-8 w-8 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-500';
        alertIcon.className = 'fa-regular fa-shield text-lg';
    }

    renderTransactionsTable();
    renderCategoryBudgets(sums);
}

function renderTransactionsTable() {
    const tableBody = document.getElementById('transactions-table-body');
    const emptyState = document.getElementById('empty-state');
    tableBody.innerHTML = '';

    const activeFilter = document.getElementById('filter-category').value;
    const filtered = state.expenses.filter(exp => activeFilter === 'All' || exp.category === activeFilter);
    const sorted = [...filtered].sort((a, b) => new Date(b.date_created) - new Date(a.date_created));

    if (sorted.length === 0) {
        emptyState.classList.remove('hidden');
        return;
    }
    emptyState.classList.add('hidden');

    sorted.forEach((exp, idx) => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-slate-50 transition-colors animate-fade-row';
        row.style.animationDelay = `${Math.min(idx, 10) * 35}ms`;
        const dateFormatted = new Date(exp.date_created).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });

        let pillColors = 'bg-slate-100 text-slate-700';
        if (exp.category === 'Food') pillColors = 'bg-amber-50 text-amber-700 border border-amber-100';
        if (exp.category === 'Books & Stationery') pillColors = 'bg-cyan-50 text-cyan-700 border border-cyan-100';
        if (exp.category === 'Entertainment') pillColors = 'bg-purple-50 text-purple-700 border border-purple-100';
        if (exp.category === 'Rent & Utilities') pillColors = 'bg-rose-50 text-rose-700 border border-rose-100';

        row.innerHTML = `
            <td class="py-3.5 px-6 text-slate-400 font-medium text-xs">${dateFormatted}</td>
            <td class="py-3.5 px-6 text-slate-800 font-bold">${escapeHTML(exp.description)}</td>
            <td class="py-3.5 px-6"><span class="px-2.5 py-1 text-[11px] rounded-full font-bold border ${pillColors}">${exp.category}</span></td>
            <td class="py-3.5 px-6 text-right text-slate-900 font-extrabold">₹${parseFloat(exp.amount).toLocaleString()}</td>
            <td class="py-3.5 px-6 text-center">
                <button onclick="handleDeleteExpense(${exp.id})" class="text-slate-400 hover:text-rose-500 transition-colors p-1" title="Purge Record" aria-label="Delete this expense">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function renderCategoryBudgets(sums) {
    const limits = categoryLimitsMap();
    const container = document.getElementById('category-progress-list');
    container.innerHTML = '';

    Object.keys(limits).forEach((cat, idx) => {
        const spent = sums[cat] || 0;
        const limit = limits[cat] || 0;
        const percent = limit > 0 ? Math.round((spent / limit) * 100) : 0;
        const color = CATEGORY_COLORS[cat] || '#64748B';

        const card = document.createElement('div');
        card.className = 'bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex flex-col items-center text-center space-y-2 animate-in';
        card.style.animationDelay = `${idx * 60}ms`;
        card.innerHTML = `
            ${getPieChartSVG(percent, color)}
            <span class="text-xs font-bold text-slate-700">${cat}</span>
            <span class="text-[11px] text-slate-400">₹${spent.toLocaleString()} / ₹${limit.toLocaleString()}</span>
        `;
        container.appendChild(card);
    });
}

function renderAnalyticsCharts() {
    const sums = categorySums();
    const limits = categoryLimitsMap();
    const budget = parseFloat(state.budget.total_budget) || 0;
    let totalSpent = 0;
    Object.values(sums).forEach(v => totalSpent += v);
    const remaining = Math.max(0, budget - totalSpent);
    const spentPercent = budget > 0 ? Math.min(100, Math.round((totalSpent / budget) * 100)) : 0;

    // Category allocation circles (analytics tab)
    const progressList = document.getElementById('analytics-category-progress-list');
    progressList.innerHTML = '';
    Object.keys(limits).forEach((cat, idx) => {
        const spent = sums[cat] || 0;
        const limit = limits[cat] || 0;
        const percent = limit > 0 ? Math.round((spent / limit) * 100) : 0;
        const color = CATEGORY_COLORS[cat] || '#64748B';
        const card = document.createElement('div');
        card.className = 'bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex flex-col items-center text-center space-y-2 animate-in';
        card.style.animationDelay = `${idx * 60}ms`;
        card.innerHTML = `${getPieChartSVG(percent, color)}<span class="text-xs font-bold text-slate-700">${cat}</span><span class="text-[11px] text-slate-400">₹${spent.toLocaleString()} / ₹${limit.toLocaleString()}</span>`;
        progressList.appendChild(card);
    });

    // KPI stats
    const savingsRate = budget > 0 ? Math.max(0, Math.round((remaining / budget) * 100)) : 0;
    animateNumber(document.getElementById('analytics-savings-rate'), savingsRate, '');
    document.getElementById('analytics-savings-rate').innerText += '%';

    const dayOfMonth = new Date().getDate();
    const dailyAvg = dayOfMonth > 0 ? (totalSpent / dayOfMonth) : 0;
    animateNumber(document.getElementById('analytics-daily-average'), dailyAvg);

    let topCategory = 'None';
    let topAmount = 0;
    Object.keys(sums).forEach(cat => {
        if (sums[cat] > topAmount) { topAmount = sums[cat]; topCategory = cat; }
    });
    document.getElementById('analytics-top-category').innerText = topCategory;

    const riskEl = document.getElementById('analytics-risk-indicator');
    if (spentPercent >= 90) { riskEl.innerText = 'Critical'; riskEl.className = 'text-2xl font-extrabold text-rose-500 mt-2'; }
    else if (spentPercent >= 75) { riskEl.innerText = 'Caution'; riskEl.className = 'text-2xl font-extrabold text-amber-500 mt-2'; }
    else { riskEl.innerText = 'Perfect'; riskEl.className = 'text-2xl font-extrabold text-[#00C48C] mt-2'; }

    // Conservation analysis — updated before the chart below so these values
    // always reflect the latest data even if the chart step below fails.
    animateNumber(document.getElementById('analysis-allowance-val'), budget);
    animateNumber(document.getElementById('analysis-consumed-val'), totalSpent);
    animateNumber(document.getElementById('analysis-reserves-val'), remaining);
    document.getElementById('analysis-consumed-bar').style.width = `${spentPercent}%`;
    document.getElementById('analysis-reserves-bar').style.width = `${Math.max(0, 100 - spentPercent)}%`;

    const insightEl = document.getElementById('analysis-insight-message');
    if (spentPercent >= 90) insightEl.innerText = 'Spending is close to your full allowance. Consider pausing non-essential purchases for the rest of the cycle.';
    else if (spentPercent >= 75) insightEl.innerText = 'You are approaching your monthly limit. Review discretionary categories like Entertainment.';
    else insightEl.innerText = 'Maintain a solid budget limit. Regularly audit your category targets for maximum monthly returns.';

    // Bar chart (Chart.js)
    const barCanvas = document.getElementById('analytics-bar-chart');
    const barFallback = document.getElementById('analytics-bar-chart-fallback');
    if (barCanvas) {
        const showChartFallback = (msg) => {
            barCanvas.classList.add('hidden');
            if (barFallback) {
                barFallback.innerText = msg;
                barFallback.classList.remove('hidden');
            }
        };
        const hideChartFallback = () => {
            barCanvas.classList.remove('hidden');
            if (barFallback) barFallback.classList.add('hidden');
        };

        if (typeof Chart === 'undefined') {
            // The Chart.js library itself never loaded (e.g. the CDN script
            // was blocked). Say so on-page instead of leaving a silent blank card.
            console.error('Chart.js did not load — window.Chart is undefined.');
            showChartFallback('Chart library failed to load. Check your internet connection and refresh the page.');
        } else {
            hideChartFallback();
            const labels = Object.keys(sums);
            const dataVals = labels.map(cat => sums[cat]);
            const colors = labels.map(cat => CATEGORY_COLORS[cat] || '#64748B');

            // Destroy any previous chart bound to this canvas first. This is
            // wrapped in its own try/catch, kept separate from chart creation
            // below, so that a teardown hiccup can never end up skipping the
            // actual chart render.
            try {
                const existingChart = (typeof Chart.getChart === 'function' && Chart.getChart(barCanvas)) || categoryBarChart;
                if (existingChart) existingChart.destroy();
            } catch (err) {
                console.error('Chart teardown failed (continuing to render anyway):', err);
            }
            categoryBarChart = null;

            const buildChart = () => {
                try {
                    const dark = isDarkMode();
                    const gridColor = dark ? '#1E2A42' : '#F1F5F9';
                    const tickColor = dark ? '#8695AC' : '#94A3B8';

                    categoryBarChart = new Chart(barCanvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Spend (₹)',
                                data: dataVals,
                                backgroundColor: colors,
                                borderRadius: 8,
                                maxBarThickness: 56
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: { duration: 500 },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#0B1220',
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: (ctx) => `₹${Number(ctx.parsed.y).toLocaleString()}`
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: gridColor },
                                    ticks: { callback: (v) => `₹${Number(v).toLocaleString()}`, color: tickColor, font: { size: 10 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: tickColor, font: { size: 10, weight: 'bold' } }
                                }
                            }
                        }
                    });
                } catch (err) {
                    console.error('Category expenditure chart failed to render:', err);
                    showChartFallback('Could not draw the chart. See the browser console for details.');
                }
            };

            // A canvas that still measures 0x0 (e.g. its parent hasn't been
            // laid out yet right after the tab became visible) makes Chart.js
            // draw nothing, with no error thrown — the classic cause of a
            // silently blank chart. If that happens, wait one more frame and
            // try again instead of giving up.
            if (barCanvas.clientWidth === 0 || barCanvas.clientHeight === 0) {
                requestAnimationFrame(() => requestAnimationFrame(buildChart));
            } else {
                buildChart();
            }
        }
    }
}

async function handleAddExpense(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const amountInput = document.getElementById('form-amount');
    const descInput = document.getElementById('form-description');

    const payload = {
        amount: parseFloat(amountInput.value),
        category: document.getElementById('form-category').value,
        description: descInput.value,
        date_created: document.getElementById('form-date').value
    };

    setButtonLoading(btn, true, 'Saving…');
    const data = await api('api/expenses.php', { method: 'POST', body: JSON.stringify(payload) });
    setButtonLoading(btn, false);

    if (data.success) {
        amountInput.value = '';
        descInput.value = '';
        await refreshExpenses();
        showToast('Success', 'Expense recorded!', 'success');
        const card = document.getElementById('expense-record-card');
        if (card && !prefersReducedMotion()) {
            card.classList.remove('animate-success-flash');
            void card.offsetWidth;
            card.classList.add('animate-success-flash');
        }
        checkBudgetExceeded();
    } else {
        showToast('Error', data.message || 'Could not save expense.', 'error');
        flashFormError(e.target);
    }
}

async function handleDeleteExpense(id) {
    const data = await api(`api/expenses.php?id=${id}`, { method: 'DELETE' });
    if (data.success) {
        await refreshExpenses();
        showToast('Trash', 'Selected entry removed.', 'warning');
    } else {
        showToast('Error', data.message || 'Could not delete expense.', 'error');
    }
}

async function clearAllExpenses() {
    if (!confirm('Purge all transactions? This cannot be undone.')) return;
    const data = await api('api/expenses.php', { method: 'POST', body: JSON.stringify({ action: 'clear_all' }) });
    if (data.success) {
        await refreshExpenses();
        showToast('Purged', 'All registered transactions wiped out.', 'warning');
    }
}

async function refreshExpenses() {
    const data = await api('api/expenses.php');
    if (data.success) {
        state.expenses = data.expenses;
        renderApp();
        if (!document.getElementById('tab-analytics').classList.contains('hidden')) {
            renderAnalyticsCharts();
        }
    }
}

function openSetBudgetModal() {
    document.getElementById('modal-overall-budget').value = state.budget.total_budget;
    document.getElementById('modal-cat-food').value = state.budget.cat_food;
    document.getElementById('modal-cat-books').value = state.budget.cat_books;
    document.getElementById('modal-cat-ent').value = state.budget.cat_entertainment;
    document.getElementById('modal-cat-rent').value = state.budget.cat_rent;
    document.getElementById('modal-cat-others').value = state.budget.cat_others;
    calculateTotalFromCategories();
    document.getElementById('budget-modal').classList.remove('hidden');
}

function closeSetBudgetModal() {
    document.getElementById('budget-modal').classList.add('hidden');
}

function handleOverallBudgetInput() {
    const overall = parseFloat(document.getElementById('modal-overall-budget').value) || 0;
    const food = Math.round(overall * 0.20);
    const books = Math.round(overall * 0.10);
    const ent = Math.round(overall * 0.10);
    const rent = Math.round(overall * 0.50);
    const others = Math.round(overall * 0.10);

    document.getElementById('modal-cat-food').value = food;
    document.getElementById('modal-cat-books').value = books;
    document.getElementById('modal-cat-ent').value = ent;
    document.getElementById('modal-cat-rent').value = rent;
    document.getElementById('modal-cat-others').value = others;
    document.getElementById('modal-calculated-total').innerText = `₹${overall.toLocaleString()}`;
}

function calculateTotalFromCategories() {
    const food = parseFloat(document.getElementById('modal-cat-food').value) || 0;
    const books = parseFloat(document.getElementById('modal-cat-books').value) || 0;
    const ent = parseFloat(document.getElementById('modal-cat-ent').value) || 0;
    const rent = parseFloat(document.getElementById('modal-cat-rent').value) || 0;
    const others = parseFloat(document.getElementById('modal-cat-others').value) || 0;
    const total = food + books + ent + rent + others;
    document.getElementById('modal-overall-budget').value = total;
    document.getElementById('modal-calculated-total').innerText = `₹${total.toLocaleString()}`;
    return total;
}

async function handleSetBudget() {
    const payload = {
        food: parseFloat(document.getElementById('modal-cat-food').value) || 0,
        books: parseFloat(document.getElementById('modal-cat-books').value) || 0,
        entertainment: parseFloat(document.getElementById('modal-cat-ent').value) || 0,
        rent: parseFloat(document.getElementById('modal-cat-rent').value) || 0,
        others: parseFloat(document.getElementById('modal-cat-others').value) || 0
    };
    const total = payload.food + payload.books + payload.entertainment + payload.rent + payload.others;
    if (total <= 0) {
        showToast('Error', 'Total budget must be greater than ₹0.', 'error');
        return;
    }

    const saveBtn = document.getElementById('save-budgets-btn');
    setButtonLoading(saveBtn, true, 'Saving…');
    const data = await api('api/budget.php', { method: 'POST', body: JSON.stringify(payload) });
    setButtonLoading(saveBtn, false);
    if (data.success) {
        state.budget = {
            total_budget: total,
            cat_food: payload.food,
            cat_books: payload.books,
            cat_entertainment: payload.entertainment,
            cat_rent: payload.rent,
            cat_others: payload.others
        };
        closeSetBudgetModal();
        renderApp();
        if (!document.getElementById('tab-analytics').classList.contains('hidden')) {
            renderAnalyticsCharts();
        }
        showToast('Updated', `Category budgets updated! Total is ₹${total.toLocaleString()}`, 'success');
        checkBudgetExceeded();
    } else {
        showToast('Error', data.message || 'Could not update budget.', 'error');
    }
}

function checkBudgetExceeded() {
    const budget = parseFloat(state.budget.total_budget) || 0;
    if (budget <= 0) return;
    const sums = categorySums();
    let totalSpent = 0;
    Object.values(sums).forEach(v => totalSpent += v);
    if (totalSpent > budget) {
        const over = totalSpent - budget;
        showBudgetExceededModal(`You've spent ₹${totalSpent.toLocaleString()} out of your ₹${budget.toLocaleString()} monthly budget — that's ₹${over.toLocaleString()} over the limit.`);
    }
}

function showBudgetExceededModal(message) {
    const msgEl = document.getElementById('budget-exceeded-message');
    if (msgEl) msgEl.innerText = message;
    const modal = document.getElementById('budget-exceeded-modal');
    if (modal) modal.classList.remove('hidden');
}

function closeBudgetExceededModal() {
    const modal = document.getElementById('budget-exceeded-modal');
    if (modal) modal.classList.add('hidden');
}

function escapeHTML(str) {
    return String(str).replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag));
}

function showToast(title, msg, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');

    let accentColor = 'border-indigo-500 text-indigo-200 bg-slate-950';
    let iconSvg = `<i class="fa-solid fa-circle-info text-indigo-400 text-base"></i>`;
    let barColor = 'bg-indigo-400';

    if (type === 'success') {
        accentColor = 'border-emerald-500 text-emerald-200 bg-[#0B1220]';
        iconSvg = `<i class="fa-solid fa-circle-check text-[#00C48C] text-base"></i>`;
        barColor = 'bg-[#00C48C]';
    } else if (type === 'warning') {
        accentColor = 'border-amber-500 text-amber-200 bg-[#0B1220]';
        iconSvg = `<i class="fa-solid fa-circle-exclamation text-amber-500 text-base"></i>`;
        barColor = 'bg-amber-500';
    } else if (type === 'error') {
        accentColor = 'border-rose-500 text-rose-200 bg-[#0B1220]';
        iconSvg = `<i class="fa-solid fa-circle-xmark text-rose-500 text-base"></i>`;
        barColor = 'bg-rose-500';
    }

    const duration = 4000;
    toast.className = `relative overflow-hidden flex items-start gap-3 p-4 pb-4 border rounded-xl shadow-xl toast-in ${accentColor}`;
    toast.innerHTML = `
        <div class="shrink-0 mt-0.5">${iconSvg}</div>
        <div class="flex-1">
            <h4 class="font-bold text-xs text-slate-100 uppercase tracking-wide">${title}</h4>
            <p class="text-xs text-slate-300 mt-1">${msg}</p>
        </div>
        <div class="absolute bottom-0 left-0 h-0.5 ${barColor}" style="width:100%; animation: toastTimerShrink ${duration}ms linear forwards;"></div>
    `;

    container.appendChild(toast);

    const dismiss = () => {
        if (prefersReducedMotion()) { toast.remove(); return; }
        toast.style.transition = 'opacity .25s ease, transform .25s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(24px) scale(.96)';
        setTimeout(() => toast.remove(), 250);
    };
    setTimeout(dismiss, duration);
}
