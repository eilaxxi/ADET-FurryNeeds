// js/orders-db.js
// Handles all API calls for the Orders admin page
// Talks to api/orders.php on the backend

/**
 * Fetch all orders from the database.
 * @param {Function} onSuccess - called with array of order objects
 * @param {Function} onError   - called on failure
 */
function fetchOrders(onSuccess, onError) {
    fetch('api/orders.php?action=getAll')
        .then(function(response) {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                onSuccess(data.orders);
            } else {
                console.error('fetchOrders error:', data.message);
                onError(data.message);
            }
        })
        .catch(function(err) {
            console.error('fetchOrders failed:', err);
            onError(err);
        });
}

/**
 * Fetch one order's full details including order items.
 * @param {number} orderId
 * @param {Function} onSuccess - called with { order, items }
 * @param {Function} onError
 */
function fetchOrderDetail(orderId, onSuccess, onError) {
    fetch('api/orders.php?action=getDetail&id=' + orderId)
        .then(function(response) {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                onSuccess({ order: data.order, items: data.items });
            } else {
                console.error('fetchOrderDetail error:', data.message);
                onError(data.message);
            }
        })
        .catch(function(err) {
            console.error('fetchOrderDetail failed:', err);
            onError(err);
        });
}

/**
 * Update the status of an order.
 * @param {number} orderId
 * @param {string} newStatus - e.g. 'Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'
 * @param {Function} onSuccess
 * @param {Function} onError
 */
function updateOrderStatus(orderId, newStatus, onSuccess, onError) {
    fetch('api/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'updateStatus',
            order_id: orderId,
            status: newStatus
        })
    })
    .then(function(response) {
        if (!response.ok) throw new Error('Network error: ' + response.status);
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            onSuccess();
        } else {
            console.error('updateOrderStatus error:', data.message);
            onError(data.message);
        }
    })
    .catch(function(err) {
        console.error('updateOrderStatus failed:', err);
        onError(err);
    });
}

/**
 * Export orders array as a downloadable CSV file.
 * @param {Array} orders
 */
function exportOrdersCSV(orders) {
    if (!orders || orders.length === 0) {
        alert('No orders to export.');
        return;
    }

    const headers = ['OrderID', 'CustomerName', 'OrderDate', 'ItemCount', 'TotalAmount', 'DeliveryFee', 'DiscountAmount', 'FinalAmount', 'PaymentMethod', 'PaymentStatus', 'OrderStatus'];
    const rows = orders.map(function(o) {
        return [
            o.OrderID,
            '"' + (o.CustomerName || '') + '"',
            o.OrderDate || '',
            o.ItemCount || '',
            o.TotalAmount || '',
            o.DeliveryFee || '',
            o.DiscountAmount || '',
            o.FinalAmount || '',
            o.PaymentMethod || '',
            o.PaymentStatus || '',
            o.OrderStatus || ''
        ].join(',');
    });

    const csvContent = [headers.join(',')].concat(rows).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'orders_export_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}
