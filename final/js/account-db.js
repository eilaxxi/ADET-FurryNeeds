/**
 * account-db.js – loaded only on account.html
 * Handles profile update and password change via API.
 * Order loading is handled by the inline script in account.html.
 */

async function loadProfile(userId) {
    const res = await apiGet(API.account, { action: 'profile', user_id: userId });
    if (!res.success) return;
    const u = res.user;

    const fillEl = (selector, value) => {
        const el = document.querySelector(selector);
        if (el) el.textContent = value;
    };
    fillEl('.user-name, .profile-name, h2.name', `${u.first_name} ${u.last_name}`);
    fillEl('.user-email, .profile-email',         u.email);
    fillEl('.user-phone, .profile-phone',          u.phone_num || '—');

    const setVal = (selector, value) => { const el = document.querySelector(selector); if (el) el.value = value; };
    setVal('input[id*="firstName"], input[name="first_name"]', u.first_name);
    setVal('input[id*="lastName"],  input[name="last_name"]',  u.last_name);
    setVal('input[id*="email"],     input[name="email"]',      u.email);
    setVal('input[id*="phone"],     input[name="phone"]',      u.phone_num || '');

    loadAddresses(u.addresses || [], userId);
}

function loadAddresses(addresses, userId) {
    const container = document.querySelector('.addresses-list, #addresses-list, [data-section="addresses"]');
    if (!container) return;

    if (addresses.length === 0) {
        container.innerHTML = '<p style="color:#999;">No saved addresses.</p>';
        return;
    }

    container.innerHTML = addresses.map(a => `
        <div class="address-card" style="border:1px solid #e0e0e0;border-radius:10px;padding:16px;margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    ${a.is_default ? '<span style="background:#FDCA5D;color:white;font-size:11px;padding:2px 8px;border-radius:4px;margin-bottom:6px;display:inline-block;">Default</span>' : ''}
                    <div>${a.street}${a.barangay ? ', ' + a.barangay : ''}</div>
                    <div>${a.city}, ${a.province} ${a.zip_code || ''}</div>
                    ${a.phone ? `<div style="color:#666;font-size:13px;">${a.phone}</div>` : ''}
                </div>
                <button class="btn btn-danger btn-sm" onclick="deleteAddress(${a.address_id}, ${userId})">Remove</button>
            </div>
        </div>
    `).join('');
}

// Profile update
document.addEventListener('DOMContentLoaded', () => {
    const saveBtn = document.querySelector('#saveProfileBtn, button[onclick*="saveProfile"], .save-profile-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            const userId = getUserId();
            const first_name = document.querySelector('input[id*="firstName"], input[name="first_name"]')?.value || '';
            const last_name  = document.querySelector('input[id*="lastName"],  input[name="last_name"]')?.value  || '';
            const phone_num  = document.querySelector('input[id*="phone"],     input[name="phone"]')?.value     || '';
            const res = await apiPost(API.account, 'update_profile', { user_id: userId, first_name, last_name, phone_num });
            showToast(res.success ? 'Profile updated!' : res.error || 'Update failed.', res.success ? 'success' : 'error');
            if (res.success) {
                const session = getSession();
                if (session) { session.name = `${first_name} ${last_name}`; setSession(session); }
            }
        });
    }

    // Change password
    const changePwBtn = document.querySelector('#changePasswordBtn, button[onclick*="changePassword"]');
    if (changePwBtn) {
        changePwBtn.addEventListener('click', async () => {
            const userId   = getUserId();
            const old_pass = document.querySelector('input[id*="currentPw"], input[type="password"]:nth-of-type(1)')?.value || '';
            const new_pass = document.querySelector('input[id*="newPw"],     input[type="password"]:nth-of-type(2)')?.value || '';
            const confirm  = document.querySelector('input[id*="confirmPw"],  input[type="password"]:nth-of-type(3)')?.value || '';
            if (new_pass !== confirm) { showToast("Passwords don't match.", 'error'); return; }
            const res = await apiPost(API.account, 'change_password', { user_id: userId, old_password: old_pass, new_password: new_pass });
            showToast(res.success ? 'Password changed!' : res.error || 'Failed.', res.success ? 'success' : 'error');
        });
    }
});
