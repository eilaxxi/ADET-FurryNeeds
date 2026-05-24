/**
 * FurryNeeds - Shared JS (db.js)

 */

const API = {
    auth:      'api/auth.php',
    products:  'api/products.php',
    cart:      'api/cart.php',
    orders:    'api/orders.php',
    inventory: 'api/inventory.php',
    reports:   'api/reports.php',
    account:   'api/account.php',
    productDetail: 'api/product-detail.php'
};

// ── Session helpers ────────────────────────────────────────────────────────────
function getSession() {
    try { return JSON.parse(localStorage.getItem('fn_session')) || null; } catch { return null; }
}
function setSession(data) { localStorage.setItem('fn_session', JSON.stringify(data)); }
function clearSession() { localStorage.removeItem('fn_session'); localStorage.removeItem('fn_cart_count'); }
function isLoggedIn()   { return !!getSession(); }
function isAdmin()      { const s = getSession(); return s && s.role_type === 'Admin'; }
function getUserId()    { const s = getSession(); return s ? s.user_id : null; }

// ── Fetch wrapper ──────────────────────────────────────────────────────────────
async function apiFetch(url, options = {}) {
    try {
        const res  = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...options });
        const data = await res.json();
        return data;
    } catch (e) {
        console.error('API error', e);
        return { success: false, error: e.message };
    }
}

async function apiGet(endpoint, params = {}) {
    const qs  = new URLSearchParams(params).toString();
    return apiFetch(qs ? `${endpoint}?${qs}` : endpoint);
}

async function apiPost(endpoint, action, body = {}) {
    return apiFetch(`${endpoint}?action=${action}`, { method: 'POST', body: JSON.stringify(body) });
}

// ── Cart badge ─────────────────────────────────────────────────────────────────
async function refreshCartBadge() {
    const badge = document.querySelector('.badge');
    if (!badge) return;
    const userId = getUserId();
    if (!userId) { badge.textContent = '0'; return; }

    const res = await apiGet(API.cart, { user_id: userId });
    if (res.success) {
        const count = res.count || 0;
        badge.textContent = count;
        localStorage.setItem('fn_cart_count', count);
    }
}

// ── Add to cart (used by product cards) ────────────────────────────────────────
async function addToCart(productId, quantity = 1) {
    const userId = getUserId();
    if (!userId) {
        showToast('Please log in to add items to your cart.', 'warning');
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }
    const res = await apiPost(API.cart, 'add', { user_id: userId, product_id: productId, quantity });
    if (res.success) {
        showToast('Added to cart! 🛒', 'success');
        refreshCartBadge();
    } else {
        showToast(res.error || 'Could not add to cart.', 'error');
    }
}

// ── Toast notification ─────────────────────────────────────────────────────────
function showToast(message, type = 'info') {
    let toast = document.getElementById('fn-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'fn-toast';
        toast.style.cssText = `
            position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 22px;
            border-radius:10px;font-size:15px;font-weight:600;color:#fff;
            box-shadow:0 4px 20px rgba(0,0,0,0.25);transition:opacity .3s;
            max-width:320px;pointer-events:none;
        `;
        document.body.appendChild(toast);
    }
    const colors = { success: '#4caf50', error: '#e53935', warning: '#ff9800', info: '#FDCA5D' };
    toast.style.background = colors[type] || colors.info;
    toast.textContent = message;
    toast.style.opacity = '1';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.opacity = '0'; }, 3000);
}

// ── Header auth button ─────────────────────────────────────────────────────────
function updateHeaderAuth() {
    const userBtn = document.querySelector('.icon-btn[onclick*="login"]');
    if (!userBtn) return;
    const session = getSession();
    if (session) {
        userBtn.textContent = '👤';
        userBtn.title = session.name || 'My Account';
        userBtn.onclick = () => window.location.href = isAdmin() ? 'admin-dashboard.html' : 'account.html';
    }
}

// ── Login handler (used in login.html) ────────────────────────────────────────
async function handleLogin() {
    const email    = document.getElementById('login-email')?.value.trim();
    const password = document.getElementById('login-password')?.value;
    if (!email || !password) { showToast('Please enter email and password.', 'warning'); return; }

    const btn = document.querySelector('.btn-primary');
    if (btn) { btn.disabled = true; btn.textContent = 'Logging in...'; }

    const res = await apiPost(API.auth, 'login', { email, password });

    if (btn) { btn.disabled = false; btn.textContent = 'Login'; }

    if (res.success) {
        setSession(res);
        showToast('Welcome back, ' + (res.name?.split(' ')[0] || 'there') + '! 🐾', 'success');
        setTimeout(() => {
            window.location.href = res.role_type === 'Admin' ? 'admin-dashboard.html' : 'index.html';
        }, 800);
    } else {
        showToast(res.error || 'Login failed.', 'error');
    }
}

// ── Phone validation ─────────────────────────────────────────────────────────────
function validatePhoneInput() {
    const phoneInput = document.getElementById('register-phone');
    const phoneError = document.getElementById('phone-error');
    if (!phoneInput || !phoneError) return;
    
    const value = phoneInput.value;
    // Only allow digits
    const digitsOnly = value.replace(/\D/g, '');
    phoneInput.value = digitsOnly;
    
    // Show/hide error based on length
    if (digitsOnly.length > 0 && digitsOnly.length !== 11) {
        phoneError.style.display = 'block';
    } else {
        phoneError.style.display = 'none';
    }
}

// ── Register handler (used in login.html) ─────────────────────────────────────
async function handleRegister(e) {
    if (e) e.preventDefault();
    const full_name        = document.getElementById('register-name')?.value.trim();
    const email            = document.getElementById('register-email')?.value.trim();
    const phone            = document.getElementById('register-phone')?.value.trim();
    const password         = document.getElementById('register-password')?.value;
    const confirm_password = document.getElementById('register-confirm')?.value;

    if (!full_name || !email || !password) { showToast('Please fill all required fields.', 'warning'); return; }
    if (password !== confirm_password)     { showToast('Passwords do not match.', 'error'); return; }
    if (password.length < 8)              { showToast('Password must be at least 8 characters.', 'warning'); return; }

    const res = await apiPost(API.auth, 'register', { full_name, email, phone, password, confirm_password });
    if (res.success) {
        showToast('Account created! Please log in.', 'success');
        // Switch to login tab
        document.querySelectorAll('.tab')[0]?.click();
    } else {
        showToast(res.error || 'Registration failed.', 'error');
    }
}

// ── Logout ─────────────────────────────────────────────────────────────────────
function handleLogout() {
    clearSession();
    showToast('Logged out. See you soon! 👋', 'info');
    setTimeout(() => window.location.href = 'login.html', 800);
}

// ── On page load ───────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    updateHeaderAuth();
    refreshCartBadge();

    // Attach register form submission
    const regForm = document.querySelector('#register-form form');
    if (regForm) regForm.addEventListener('submit', handleRegister);

    // Attach logout buttons
    document.querySelectorAll('[onclick*="login.html"]').forEach(btn => {
        if (btn.textContent.trim() === 'Logout') {
            btn.onclick = handleLogout;
        }
    });

    // Wire all "Add to Cart" buttons that have a data-product-id
    document.querySelectorAll('.btn-add-cart[data-product-id]').forEach(btn => {
        // Avoid double-adding when the HTML already has onclick="addToCart(...)".
        if (btn.getAttribute('onclick')) return;
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            addToCart(parseInt(btn.dataset.productId));
        });
    });
});
