/**
 * cart-page.js  – loaded only on cart.html
 * Replaces the static cart with live DB data when the user is logged in.
 * Falls back to localStorage cart for guests.
 */

document.addEventListener('DOMContentLoaded', async () => {
    const userId = getUserId();
    if (userId) {
        await loadDbCart(userId);
    } else {
        // Guest: keep existing static display but show login prompt
        const banner = document.createElement('div');
        banner.style.cssText = 'background:#fff8e1;border:1px solid #FDCA5D;border-radius:8px;padding:12px 18px;margin-bottom:18px;font-size:14px;';
        banner.innerHTML = '🔐 <strong>Log in</strong> to save your cart and access your orders. <a href="login.html" style="color:#FDCA5D;font-weight:600;">Login / Register →</a>';
        document.querySelector('.cart-items')?.prepend(banner);
    }
});

async function loadDbCart(userId) {
    const res = await apiGet(API.cart, { user_id: userId });
    if (!res.success) { showToast(res.error || 'Could not load cart.', 'error'); return; }

    const items    = res.items || [];
    const cartList = document.querySelector('.cart-items');
    if (!cartList) return;

    // Remove static items, keep the header if any
    cartList.querySelectorAll('.cart-item').forEach(el => el.remove());

    if (items.length === 0) {
        cartList.innerHTML += `<div style="text-align:center;padding:60px 20px;color:#999;">
            <div style="font-size:60px;margin-bottom:16px;">🛒</div>
            <h3>Your cart is empty</h3>
            <a href="dogs.html" style="color:#FDCA5D;font-weight:600;">Shop now →</a>
        </div>`;
        updateSummary([], 0);
        return;
    }

    items.forEach(item => cartList.insertAdjacentHTML('beforeend', buildCartItemHtml(item)));
    updateSummaryFromItems(items);
}

function buildCartItemHtml(item) {
    const unitPrice = parseFloat(item.unit_price);
    const discount  = parseFloat(item.discount_amount || 0);
    const finalUnit = unitPrice - discount;
    const imgSrc    = item.image ? item.image : '';
    const imgTag    = imgSrc
        ? `<img src="${imgSrc}" alt="${item.product_name}" style="max-width:80px;max-height:80px;object-fit:contain;">`
        : `<span style="font-size:40px;">🐾</span>`;

    return `
    <div class="cart-item" id="cart-item-${item.cart_item_id}">
        <div class="item-image">${imgTag}</div>
        <div class="item-details">
            <div class="item-title">${item.product_name}</div>
            <div class="item-stock" style="color:#4caf50;">✓ In Stock (${item.stock_quantity} left)</div>
            <div class="item-actions">
                <a class="action-link" onclick="dbRemoveItem(${item.cart_item_id})" style="cursor:pointer;">Remove</a>
            </div>
        </div>
        <div class="item-right">
            <div class="item-price">
                ${discount > 0 ? `<div class="item-price-original" style="text-decoration:line-through;color:#999;">₱${unitPrice.toFixed(2)}</div>` : ''}
                <div class="item-price-current">₱${(finalUnit * item.quantity).toFixed(2)}</div>
                ${discount > 0 ? `<div class="item-promo" style="color:#4caf50;font-size:12px;">-₱${(discount * item.quantity).toFixed(2)} discount</div>` : ''}
            </div>
            <div class="qty-controls">
                <button class="qty-btn" onclick="dbUpdateQty(${item.cart_item_id}, ${item.quantity - 1}, ${finalUnit})">−</button>
                <div class="qty-display" id="qty-${item.cart_item_id}">${item.quantity}</div>
                <button class="qty-btn" onclick="dbUpdateQty(${item.cart_item_id}, ${item.quantity + 1}, ${finalUnit})">+</button>
            </div>
        </div>
    </div>`;
}

async function dbUpdateQty(cartItemId, newQty, finalUnit) {
    if (newQty < 1) { dbRemoveItem(cartItemId); return; }
    const res = await apiPost(API.cart, 'update', { user_id: getUserId(), cart_item_id: cartItemId, quantity: newQty });
    if (res.success) {
        document.getElementById(`qty-${cartItemId}`).textContent = newQty;
        // Update price display
        const itemEl = document.getElementById(`cart-item-${cartItemId}`);
        if (itemEl) {
            const priceEl = itemEl.querySelector('.item-price-current');
            if (priceEl) priceEl.textContent = `₱${(finalUnit * newQty).toFixed(2)}`;
            // Update qty buttons
            itemEl.querySelectorAll('.qty-btn')[0].onclick = () => dbUpdateQty(cartItemId, newQty - 1, finalUnit);
            itemEl.querySelectorAll('.qty-btn')[1].onclick = () => dbUpdateQty(cartItemId, newQty + 1, finalUnit);
        }
        await recalcSummary();
    } else {
        showToast(res.error || 'Could not update cart.', 'error');
    }
}

async function dbRemoveItem(cartItemId) {
    if (!confirm('Remove this item from your cart?')) return;
    const res = await apiPost(API.cart, 'remove', { user_id: getUserId(), cart_item_id: cartItemId });
    if (res.success) {
        document.getElementById(`cart-item-${cartItemId}`)?.remove();
        showToast('Item removed.', 'info');
        await recalcSummary();
        refreshCartBadge();
    } else {
        showToast(res.error || 'Could not remove item.', 'error');
    }
}

async function recalcSummary() {
    const userId = getUserId();
    if (!userId) return;
    const res = await apiGet(API.cart, { user_id: userId });
    if (res.success) updateSummaryFromItems(res.items || []);
}

function updateSummaryFromItems(items) {
    let subtotal  = 0;
    let discount  = 0;
    let itemCount = 0;
    items.forEach(item => {
        const unit   = parseFloat(item.unit_price);
        const disc   = parseFloat(item.discount_amount || 0);
        const qty    = parseInt(item.quantity);
        subtotal    += unit * qty;
        discount    += disc * qty;
        itemCount   += qty;
    });
    const shipping = subtotal - discount >= 2900 ? 0 : 99;
    const total    = subtotal - discount + shipping;

    const el = id => document.getElementById(id);
    if (el('subtotal'))  el('subtotal').textContent  = `₱${subtotal.toFixed(2)}`;
    if (el('discount'))  el('discount').textContent  = `-₱${discount.toFixed(2)}`;
    if (el('shipping'))  el('shipping').textContent  = shipping === 0 ? 'FREE' : `₱${shipping.toFixed(2)}`;
    if (el('total'))     el('total').textContent     = `₱${total.toFixed(2)}`;
    document.querySelectorAll('.item-count').forEach(el => el.textContent = `${itemCount} item${itemCount !== 1 ? 's' : ''}`);

    // Save checkout snapshot to sessionStorage for checkout.html
    sessionStorage.setItem('fn_cart_items', JSON.stringify(items));
    sessionStorage.setItem('fn_cart_totals', JSON.stringify({ subtotal, discount, shipping, total, itemCount }));
}
