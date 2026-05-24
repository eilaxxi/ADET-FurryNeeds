// js/settings-db.js
// Handles all API calls for the Settings admin page
// Talks to api/settings.php on the backend

/**
 * Fetch the logged-in admin's profile.
 */
function fetchAdminProfile(onSuccess, onError) {
    fetch('api/settings.php?action=getProfile')
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(data => { if (data.success) onSuccess(data.admin); else onError(data.message); })
        .catch(err => { console.error('fetchAdminProfile:', err); onError(err); });
}

/**
 * Update the admin's profile info (name, email, phone).
 */
function updateAdminProfile(payload, onSuccess, onError) {
    fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'updateProfile', ...payload })
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(data => { if (data.success) onSuccess(); else onError(data.message); })
    .catch(err => { console.error('updateAdminProfile:', err); onError(err); });
}

/**
 * Change admin password.
 * @param {{ currentPassword, newPassword }} payload
 */
function updateAdminPassword(payload, onSuccess, onError) {
    fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'changePassword', ...payload })
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(data => { if (data.success) onSuccess(); else onError(data.message); })
    .catch(err => { console.error('updateAdminPassword:', err); onError(err); });
}

/**
 * Fetch store-wide settings.
 */
function fetchStoreSettings(onSuccess, onError) {
    fetch('api/settings.php?action=getStoreSettings')
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(data => { if (data.success) onSuccess(data.settings); else onError(data.message); })
        .catch(err => { console.error('fetchStoreSettings:', err); onError(err); });
}

/**
 * Save store settings.
 */
function updateStoreSettings(payload, onSuccess, onError) {
    fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'updateStoreSettings', ...payload })
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(data => { if (data.success) onSuccess(); else onError(data.message); })
    .catch(err => { console.error('updateStoreSettings:', err); onError(err); });
}

/**
 * Update which payment methods are enabled.
 * @param {{ cod, gcash, bank }} payload
 */
function updatePaymentMethods(payload, onSuccess, onError) {
    fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'updatePaymentMethods', ...payload })
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(data => { if (data.success) onSuccess(); else onError(data.message); })
    .catch(err => { console.error('updatePaymentMethods:', err); onError(err); });
}

/**
 * Save GCash / bank account details.
 */
function updatePaymentDetails(payload, onSuccess, onError) {
    fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'updatePaymentDetails', ...payload })
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(data => { if (data.success) onSuccess(); else onError(data.message); })
    .catch(err => { console.error('updatePaymentDetails:', err); onError(err); });
}

/**
 * Fetch notification preferences.
 */
function fetchNotificationPrefs(onSuccess, onError) {
    fetch('api/settings.php?action=getNotificationPrefs')
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(data => { if (data.success) onSuccess(data.prefs); else onError(data.message); })
        .catch(err => { console.error('fetchNotificationPrefs:', err); onError(err); });
}

/**
 * Save notification preferences.
 */
function updateNotificationPrefs(payload, onSuccess, onError) {
    fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'updateNotificationPrefs', ...payload })
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(data => { if (data.success) onSuccess(); else onError(data.message); })
    .catch(err => { console.error('updateNotificationPrefs:', err); onError(err); });
}

/**
 * Execute a danger zone action (clearCancelled, resetInventory).
 */
function executeDangerZoneAction(action, onSuccess, onError) {
    fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'dangerZone', task: action })
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(data => { if (data.success) onSuccess(data.message); else onError(data.message); })
    .catch(err => { console.error('executeDangerZoneAction:', err); onError(err); });
}

/**
 * Trigger a full data backup export.
 * Redirects to the PHP endpoint which sends a CSV download.
 */
function triggerFullExport(onSuccess, onError) {
    try {
        window.open('api/settings.php?action=exportBackup', '_blank');
        onSuccess();
    } catch (err) {
        onError(err);
    }
}
