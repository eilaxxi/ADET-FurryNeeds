/**
 * inventory-db.js – loaded only on inventory.html
 */
document.addEventListener('DOMContentLoaded', async () => {
    await loadInventory();
    setupInventoryEvents();
});

async function loadInventory(status = '', search = '') {
    const params = {};
    if (status) params.status = status;
    if (search) params.search = search;
    params.action = 'list';

    const res = await apiGet(API.inventory, params);
    if (!res.success) { showToast(res.error || 'Failed to load inventory.', 'error'); return; }

    const products = res.products || [];
    const summary  = res.summary  || {};

    // Update summary cards
    const cards = document.querySelectorAll('.stat-card .stat-number, .summary-card .stat-number');
    if (cards.length >= 4) {
        cards[0].textContent = summary.total      || products.length;
        cards[1].textContent = summary.in_stock   || 0;
        cards[2].textContent = summary.low_stock  || 0;
        cards[3].textContent = summary.out_of_stock || 0;
    }

    // Render table
    const tbody = document.querySelector('.data-table tbody, table tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#999;">No products found.</td></tr>';
        return;
    }

    products.forEach(p => {
        const statusClass = p.stock_status === 'In Stock' ? 'stock-in' : p.stock_status === 'Low Stock' ? 'stock-low' : 'stock-out';
        const needsAction = p.stock_status !== 'In Stock';
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td><strong>${p.product_name}</strong><br><span style="font-size:12px;color:#999;">SKU: ${p.sku || 'N/A'}</span></td>
                <td>${p.category_name}</td>
                <td>${p.stock_quantity}</td>
                <td>${p.low_stock_level}</td>
                <td><span class="stock-status ${statusClass}">${p.stock_status}</span></td>
                <td>
                    <div class="action-btns">
                        <button class="btn btn-primary btn-sm" onclick="openRestockModal('${p.product_name.replace(/'/g,"\\'")}', ${p.stock_quantity}, ${p.product_id})">
                            ${needsAction ? 'Restock' : 'Adjust'}
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="viewTransactions(${p.product_id}, '${p.product_name.replace(/'/g,"\\'")}')">History</button>
                    </div>
                </td>
            </tr>
        `);
    });
}

let _restockProductId = null;

function openRestockModal(name, stock, productId) {
    _restockProductId = productId;
    const modal = document.getElementById('restockModal');
    if (!modal) {
        // Modal might have different ID - try generic
        const qty = prompt(`Restock: ${name}\nCurrent stock: ${stock}\n\nQuantity to add:`, '');
        if (!qty || isNaN(parseInt(qty))) return;
        doRestock(productId, parseInt(qty), 'Restock', name);
        return;
    }
    modal.classList.add('show');
    const nameEl = modal.querySelector('[id*="product"], h3, .modal-product-name');
    if (nameEl) nameEl.textContent = name;
    const stockEl = modal.querySelector('[id*="stock"], .current-stock');
    if (stockEl) stockEl.textContent = `Current Stock: ${stock}`;
}

function closeRestockModal() {
    document.getElementById('restockModal')?.classList.remove('show');
    _restockProductId = null;
}

async function doRestock(productId, qty, type, notes) {
    const res = await apiPost(API.inventory, null, { product_id: productId, quantity: qty, transaction_type: type, notes: notes || '' });
    // inventory.php doesn't use action param for POST
    const url = `${API.inventory}`;
    const response = await apiFetch(url, { method: 'POST', body: JSON.stringify({ product_id: productId, quantity: qty, transaction_type: type, notes: notes || '' }) });
    if (response.success) {
        showToast(`Stock updated! New level: ${response.new_stock}`, 'success');
        closeRestockModal();
        loadInventory();
    } else {
        showToast(response.error || 'Restock failed.', 'error');
    }
}

async function viewTransactions(productId, name) {
    const res = await apiGet(API.inventory, { action: 'transactions', product_id: productId });
    if (!res.success) { showToast('Failed to load history.', 'error'); return; }
    const rows = (res.transactions || []).slice(0, 10).map(t =>
        `• ${t.transaction_date.substring(0,10)} | ${t.transaction_type} | Qty: ${t.quantity} | ${t.notes || '-'}`
    ).join('\n');
    alert(`Transaction history for: ${name}\n\n${rows || 'No transactions found.'}`);
}

function setupInventoryEvents() {
    // Filter
    const searchBox = document.querySelector('.search-box, input[type="search"]');
    if (searchBox) searchBox.addEventListener('keyup', () => {
        const status = document.querySelector('.filter-select, select')?.value || '';
        loadInventory(status === 'All' ? '' : status, searchBox.value);
    });

    // Restock modal form
    const form = document.querySelector('#restockModal form, form[id*="restock"]');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!_restockProductId) return;
            const qtyInput = form.querySelector('input[type="number"]');
            const typeInput = form.querySelector('select');
            const notesInput = form.querySelector('textarea, input[placeholder*="note"]');
            const qty  = parseInt(qtyInput?.value) || 0;
            const type = typeInput?.value || 'Restock';
            const note = notesInput?.value || '';
            if (qty <= 0) { showToast('Enter a valid quantity.', 'warning'); return; }
            await doRestock(_restockProductId, qty, type, note);
        });
    }

    // Export button
    document.getElementById('exportInventoryBtn')?.addEventListener('click', () => {
        showToast('Export feature requires server-side implementation.', 'info');
    });
}
