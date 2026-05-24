/**
 * admin-dashboard-db.js
 */
document.addEventListener('DOMContentLoaded', async () => {
    if (!isAdmin()) {
        showToast('Admin access required.', 'error');
        setTimeout(() => window.location.href = 'login.html', 1000);
        return;
    }
    await loadDashboardStats();
});

async function loadDashboardStats() {
    const today = new Date().toISOString().split('T')[0];
    const monthStart = today.substring(0, 7) + '-01';

    const [repRes, invRes] = await Promise.all([
        apiGet(API.reports, { period: 'monthly', start_date: monthStart, end_date: today }),
        apiGet(API.inventory, { action: 'list' }),
    ]);

    if (repRes.success) {
        const s = repRes.summary || {};
        const fmt = (n) => parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });

        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            const label = card.querySelector('.stat-label, p, small')?.textContent?.toLowerCase() || '';
            const val   = card.querySelector('.stat-number, h2, .value');
            if (!val) return;
            if (label.includes('revenue') || label.includes('sales'))    val.textContent = '₱' + fmt(s.total_revenue);
            if (label.includes('order'))   val.textContent = s.total_orders    || '0';
            if (label.includes('customer')) val.textContent = s.new_customers   || '0';
            if (label.includes('avg'))     val.textContent = '₱' + fmt(s.avg_order_value);
        });

        // Update chart
        if (typeof Chart !== 'undefined' && repRes.chart_data) {
            const canvas = document.querySelector('#revenueChart, canvas');
            if (canvas) {
                if (canvas._chartInst) canvas._chartInst.destroy();
                canvas._chartInst = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: repRes.chart_data.map(d => d.label),
                        datasets: [{
                            label: 'Revenue (₱)',
                            data: repRes.chart_data.map(d => parseFloat(d.revenue)),
                            borderColor: '#FDCA5D',
                            backgroundColor: 'rgba(253,202,93,0.15)',
                            tension: 0.4,
                            fill: true,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } } }
                    }
                });
            }
        }
    }

    if (invRes.success) {
        const low = invRes.products?.filter(p => p.stock_status !== 'In Stock') || [];
        const alertEl = document.querySelector('.alert-banner, .low-stock-alert, [class*="alert"]');
        if (alertEl && low.length > 0) {
            alertEl.innerHTML = `⚠️ <strong>${low.length} product${low.length > 1 ? 's are' : ' is'} running low on stock.</strong>
                <a href="inventory.html" style="color:#FDCA5D;font-weight:600;margin-left:8px;">View inventory →</a>`;
        }
    }
}
