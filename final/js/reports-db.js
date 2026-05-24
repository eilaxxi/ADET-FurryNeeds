/**
 * reports-db.js – loaded only on sales-reports.html
 */
document.addEventListener('DOMContentLoaded', async () => {
    await loadReports('monthly');

    // Wire the period selector / generate button
    const rangeSelect = document.querySelector('select[id*="range"], .range-select, select');
    const generateBtn = document.querySelector('#generateBtn, button[id*="generate"], .generate-btn');
    const startInput  = document.querySelector('input[type="date"]:first-of-type, #startDate');
    const endInput    = document.querySelector('input[type="date"]:last-of-type, #endDate');

    if (generateBtn) {
        generateBtn.addEventListener('click', async () => {
            const period = rangeSelect?.value || 'monthly';
            const start  = startInput?.value || '';
            const end    = endInput?.value   || '';
            await loadReports(period, start, end);
        });
    }
});

async function loadReports(period = 'monthly', startDate = '', endDate = '') {
    const params = { period };
    if (startDate) params.start_date = startDate;
    if (endDate)   params.end_date   = endDate;

    const res = await apiGet(API.reports, params);
    if (!res.success) { showToast(res.error || 'Failed to load reports.', 'error'); return; }

    const { summary, chart_data, top_products, by_category, by_status, low_stock } = res;

    // Update summary stat cards
    const fmt = (n) => parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    const statNums = document.querySelectorAll('.stat-number, .metric-value');
    if (statNums.length >= 4) {
        statNums[0].textContent = '₱' + fmt(summary?.total_revenue);
        statNums[1].textContent = summary?.total_orders || 0;
        statNums[2].textContent = summary?.new_customers || 0;
        statNums[3].textContent = '₱' + fmt(summary?.avg_order_value);
    }

    // Update revenue chart if Chart.js is loaded
    updateRevenueChart(chart_data || []);

    // Top products table
    const topBody = document.querySelector('#topProductsTable tbody, .top-products tbody, table:last-of-type tbody');
    if (topBody && top_products) {
        topBody.innerHTML = top_products.map((p, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${p.product_name}</td>
                <td>${p.units_sold}</td>
                <td>₱${fmt(p.revenue)}</td>
            </tr>
        `).join('') || '<tr><td colspan="4" style="text-align:center;color:#999;">No data</td></tr>';
    }

    // Low stock alerts
    const lowBody = document.querySelector('.low-stock tbody, #lowStockTable tbody');
    if (lowBody && low_stock) {
        lowBody.innerHTML = low_stock.map(p => `
            <tr>
                <td>${p.product_name}</td>
                <td>${p.sku || 'N/A'}</td>
                <td style="color:${p.stock_quantity === 0 ? '#e53935' : '#ff9800'};font-weight:600;">${p.stock_quantity}</td>
                <td>${p.low_stock_level}</td>
                <td><button class="btn btn-primary btn-sm" onclick="window.location.href='inventory.html'">Restock</button></td>
            </tr>
        `).join('') || '<tr><td colspan="5" style="text-align:center;color:#4caf50;">All products well-stocked ✓</td></tr>';
    }
}

function updateRevenueChart(chartData) {
    const canvas = document.querySelector('#revenueChart, canvas');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels  = chartData.map(d => d.label);
    const revenue = chartData.map(d => parseFloat(d.revenue));

    if (canvas._chartInstance) { canvas._chartInstance.destroy(); }

    canvas._chartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Revenue (₱)',
                data: revenue,
                backgroundColor: 'rgba(253, 202, 93, 0.7)',
                borderColor: '#FDCA5D',
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } }
            }
        }
    });
}
