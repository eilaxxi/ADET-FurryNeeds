/**
 * checkout-payment-db.js
 * Loaded on both checkout.html and payment.html
 */

// ── Checkout page: populate cart summary from sessionStorage ─────────────────
if (document.getElementById('proceedBtn')) {
    // Load cart items into the checkout order summary panel
    const cartItems  = JSON.parse(sessionStorage.getItem('fn_cart_items')  || '[]');
    const cartTotals = JSON.parse(sessionStorage.getItem('fn_cart_totals') || '{}');

    const summaryList = document.querySelector('.order-items, .checkout-items, .summary-items');
    if (summaryList && cartItems.length > 0) {
        summaryList.innerHTML = cartItems.map(item => {
            const finalUnit = parseFloat(item.unit_price) - parseFloat(item.discount_amount || 0);
            return `<div class="order-item" style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;">
                <span>${item.product_name} × ${item.quantity}</span>
                <span>₱${(finalUnit * item.quantity).toFixed(2)}</span>
            </div>`;
        }).join('');
    }

    // Patch the proceed button to also save address context
    const originalProceed = document.getElementById('proceedBtn').onclick;
    document.getElementById('proceedBtn').addEventListener('click', function() {
        // address data will already be saved to sessionStorage by the existing script
        // we just need to forward the cart data too
        sessionStorage.setItem('fn_checkout_ready', '1');
    }, true);
}

// ── Payment page: submit order to DB ─────────────────────────────────────────
if (window.location.pathname.includes('payment')) {
    // Pre-fill order summary from sessionStorage
    const checkoutData = JSON.parse(localStorage.getItem('checkoutData') || '{}');
    const cartItems    = JSON.parse(sessionStorage.getItem('fn_cart_items') || '[]');
    const cartTotals   = JSON.parse(sessionStorage.getItem('fn_cart_totals') || '{}');

    // Override the "Place Order" / confirm payment button
    document.addEventListener('DOMContentLoaded', () => {
        const confirmBtn = document.querySelector('#confirmPaymentBtn, #confirmBtn, .btn-confirm, button[onclick*="order-confirm"]');
        if (!confirmBtn) return;

        confirmBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();

            const userId = getUserId();
            if (!userId) { showToast('Please log in to place an order.', 'warning'); window.location.href = 'login.html'; return; }

            // Build address from checkoutData (saved by checkout.html)
            const cd = checkoutData;
            if (!cd.firstName) { window.location.href = 'order-confirm.html'; return; } // guest fallback

            const address = {
                phone:    cd.phone    || '',
                street:   cd.address  || '',
                barangay: '',
                city:     cd.city     || '',
                province: cd.state    || '',
                zip_code: cd.zip      || '',
            };

            // Build items array (use original unit_price, discount applied at order level)
            const items = cartItems.map(item => ({
                product_id: item.product_id,
                quantity:   item.quantity,
                unit_price: parseFloat(item.unit_price),  // Original price (not discounted)
            }));

            if (items.length === 0) {
                // Fallback: use checkout items if cart is empty
                const fallback = cd.orderSummary?.items || [];
                fallback.forEach(i => items.push({ product_id: 1, quantity: i.qty, unit_price: i.price }));
            }

            const paymentMethod = document.querySelector('input[name="payment"]:checked')?.value || 'cod';
            const methodMap = { cod: 'Cash on Delivery', online: 'Card', gcash: 'GCash', maya: 'Maya', card: 'Card', bank: 'Bank Transfer' };

            const body = {
                user_id:         userId,
                address,
                items,
                payment_method:  methodMap[paymentMethod] || 'Cash on Delivery',
                discount_amount: cartTotals.discount || cd.orderSummary?.discount || 0,
                reference_number: document.querySelector('input[placeholder*="reference"], #referenceNum')?.value || '',
                account_name:    document.querySelector('input[placeholder*="account"], #accountName')?.value    || '',
                mobile_number:   document.querySelector('input[placeholder*="mobile"], #mobileNum')?.value      || '',
            };

            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Placing order...';

            const res = await apiFetch(API.orders + '?action=place', { method: 'POST', body: JSON.stringify(body) });

            if (res.success) {
                // Store order details for confirmation page
                const orderData = {
                    order_id: res.order_id,
                    final_amount: res.final_amount,
                    items: items,
                    payment_method: body.payment_method,
                    address: body.address,
                    totals: cartTotals
                };
                localStorage.setItem('fn_last_order', JSON.stringify(orderData));
                sessionStorage.setItem('fn_last_order', JSON.stringify(orderData));
                
                // Clear all cart/checkout data from storage immediately
                sessionStorage.removeItem('fn_cart_items');
                sessionStorage.removeItem('fn_cart_totals');
                sessionStorage.removeItem('fn_checkout_ready');
                localStorage.removeItem('checkoutData');
                
                // Refresh cart badge to show 0
                refreshCartBadge();
                
                showToast('Order placed successfully!', 'success');
                setTimeout(() => window.location.href = 'order-confirm.html', 900);
            } else {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Payment';
                showToast(res.error || 'Order failed. Please try again.', 'error');
            }
        }, true);
    });
}
