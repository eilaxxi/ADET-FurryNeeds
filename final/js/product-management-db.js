/**
 * product-management-db.js
 * Replaces static `products` array with live API data.
 * Only loaded on product-management.html
 */

let dbProducts = [];
let categories = [];

async function loadCategories() {
    // Hardcoded from DB seed - could also fetch via API
    categories = [
        { id: 1, name: 'Dog Food' },
        { id: 2, name: 'Cat Food' },
        { id: 3, name: 'Accessories' },
        { id: 4, name: 'Grooming' },
        { id: 5, name: 'Health Care' },
    ];
    // Populate category selects in the modal
    const selects = document.querySelectorAll('#productModal select');
    if (selects[0]) {
        selects[0].innerHTML = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    }
}

async function loadDbProducts() {
    const res = await apiGet(API.products);
    if (!res.success) { showToast(res.error || 'Failed to load products.', 'error'); return; }
    dbProducts = res.products.map(p => ({
        id:         p.product_id,
        name:       p.product_name,
        category:   p.category_name,
        category_id: null, // will be resolved
        price:      parseFloat(p.price),
        stock:      parseInt(p.stock_quantity),
        status:     p.stock_status,
        sku:        p.sku || '',
        description: p.description || '',
        image:      p.image || '',
        ingredients: p.ingredients || '',
        low_stock_level: parseInt(p.low_stock_level),
        icon:       '📦',
    }));
    renderTable(dbProducts);
    updateSummaryCards();
}

function updateSummaryCards() {
    const total   = dbProducts.length;
    const inStock = dbProducts.filter(p => p.status === 'In Stock').length;
    const low     = dbProducts.filter(p => p.status === 'Low Stock').length;
    const out     = dbProducts.filter(p => p.status === 'Out of Stock').length;

    const cards = document.querySelectorAll('.stat-number');
    if (cards[0]) cards[0].textContent = total;
    if (cards[1]) cards[1].textContent = inStock;
    if (cards[2]) cards[2].textContent = low;
    if (cards[3]) cards[3].textContent = out;
}

// Override the original renderTable to use dbProducts
function renderTable(filteredProducts = dbProducts) {
    const tbody = document.querySelector('.data-table tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    filteredProducts.forEach(product => {
        const statusClass = product.status === 'In Stock' ? 'stock-in' : product.status === 'Low Stock' ? 'stock-low' : 'stock-out';
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="product-info">
                    <div class="product-thumb">${product.icon}</div>
                    <div>
                        <div style="font-weight: 600;">${product.name}</div>
                        <div style="font-size: 12px; color: #999;">SKU: ${product.sku}</div>
                    </div>
                </div>
            </td>
            <td>${product.category}</td>
            <td>₱${product.price.toFixed(2)}</td>
            <td>${product.stock} units</td>
            <td><span class="stock-status ${statusClass}">${product.status}</span></td>
            <td>
                <div class="action-btns">
                    <button class="btn btn-primary btn-sm" onclick="openModal('edit', ${product.id})">Edit</button>
                    <button class="btn btn-secondary btn-sm" onclick="openStockModal(${product.id})">Stock</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteProduct(${product.id})">Delete</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function filterProducts() {
    const searchValue   = document.querySelector('.search-box')?.value.toLowerCase() || '';
    const categoryValue = document.querySelectorAll('.filter-select')[0]?.value || 'All Categories';
    const stockValue    = document.querySelectorAll('.filter-select')[1]?.value || 'All Stock Status';

    const filtered = dbProducts.filter(p => {
        const matchSearch   = p.name.toLowerCase().includes(searchValue) || p.sku.toLowerCase().includes(searchValue);
        const matchCategory = categoryValue === 'All Categories' || p.category === categoryValue;
        const matchStock    = stockValue === 'All Stock Status' || p.status === stockValue;
        return matchSearch && matchCategory && matchStock;
    });
    renderTable(filtered);
}

function openModal(mode, id = null) {
    const modal = document.getElementById('productModal');
    if (!modal) return;
    modal.classList.add('show');
    window._editingId = id;

    if (mode === 'edit' && id) {
        const p = dbProducts.find(x => x.id === id);
        if (p) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            const form = document.querySelector('#productModal form');
            if (form) {
                form.querySelector('input[placeholder="Enter product name"]').value = p.name;
                form.querySelector('textarea').value = p.description || '';
                form.querySelectorAll('select')[0].value = p.category;
                form.querySelector('input[type="number"][placeholder="0.00"]').value = p.price;
                form.querySelector('input[placeholder="0"][type="number"]').value = p.stock;
                form.querySelector('input[placeholder="e.g., DGF-001"]').value = p.sku;
            }
        }
    } else {
        document.getElementById('modalTitle').textContent = 'Add New Product';
        document.querySelector('#productModal form')?.reset();
    }
}

function closeModal() {
    document.getElementById('productModal')?.classList.remove('show');
    window._editingId = null;
}

async function openStockModal(id) {
    const product = dbProducts.find(p => p.id === id);
    if (!product) return;
    const newStock = prompt(`Update stock for: ${product.name}\nCurrent: ${product.stock} units\n\nEnter quantity to ADD (use negative to subtract):`, '0');
    if (newStock === null) return;
    const qty = parseInt(newStock);
    if (isNaN(qty) || qty === 0) return;

    const type = qty > 0 ? 'Restock' : 'Adjustment';
    const res  = await apiPost(API.products, 'update_stock', {
        product_id:       id,
        quantity:         Math.abs(qty),
        transaction_type: type,
        notes:            `Manual ${type} from admin panel`,
    });
    if (res.success) {
        showToast('Stock updated!', 'success');
        loadDbProducts();
    } else {
        showToast(res.error || 'Stock update failed.', 'error');
    }
}

async function deleteProduct(id) {
    if (!confirm('Delete this product? It will be deactivated (not permanently removed).')) return;
    const res = await apiPost(API.products, 'delete', { product_id: id });
    if (res.success) {
        showToast('Product removed.', 'success');
        loadDbProducts();
    } else {
        showToast(res.error || 'Delete failed.', 'error');
    }
}

// Handle form submit (add / edit)
document.addEventListener('DOMContentLoaded', async () => {
    await loadCategories();
    await loadDbProducts();

    const searchBox     = document.querySelector('.search-box');
    const filterSelects = document.querySelectorAll('.filter-select');
    if (searchBox) searchBox.addEventListener('keyup', filterProducts);
    filterSelects.forEach(s => s.addEventListener('change', filterProducts));

    const form = document.querySelector('#productModal form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const name        = form.querySelector('input[placeholder="Enter product name"]').value.trim();
            const description = form.querySelector('textarea').value.trim();
            const categoryName = form.querySelectorAll('select')[0].value;
            const price       = parseFloat(form.querySelector('input[type="number"][placeholder="0.00"]').value);
            const stock       = parseInt(form.querySelector('input[placeholder="0"][type="number"]').value);
            const sku         = form.querySelector('input[placeholder="e.g., DGF-001"]').value.trim();

            const cat = categories.find(c => c.name === categoryName || String(c.id) === categoryName);
            const category_id = cat ? cat.id : 1;

            const payload = { product_name: name, description, category_id, price, stock_quantity: stock, sku };

            let res;
            if (window._editingId) {
                res = await apiPost(API.products, 'update', { ...payload, product_id: window._editingId });
            } else {
                res = await apiPost(API.products, 'add', payload);
            }
            if (res.success) {
                showToast(window._editingId ? 'Product updated!' : 'Product added!', 'success');
                closeModal();
                loadDbProducts();
            } else {
                showToast(res.error || 'Save failed.', 'error');
            }
        });
    }
});
