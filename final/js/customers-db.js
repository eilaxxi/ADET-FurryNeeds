// js/customers-db.js
// Handles all API calls for the Customers admin page
// Talks to api/customers.php on the backend

/**
 * Fetch all customers with summary stats (total orders, total spent).
 * @param {Function} onSuccess - called with array of customer objects
 * @param {Function} onError
 */
function fetchCustomers(onSuccess, onError) {
    fetch('api/customers.php?action=getAll')
        .then(function(response) {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                onSuccess(data.customers);
            } else {
                console.error('fetchCustomers error:', data.message);
                onError(data.message);
            }
        })
        .catch(function(err) {
            console.error('fetchCustomers failed:', err);
            onError(err);
        });
}

/**
 * Fetch one customer's full profile including addresses and order history.
 * @param {number} userId
 * @param {Function} onSuccess - called with { customer, orders, addresses }
 * @param {Function} onError
 */
function fetchCustomerDetail(userId, onSuccess, onError) {
    fetch('api/customers.php?action=getDetail&id=' + userId)
        .then(function(response) {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                onSuccess({
                    customer: data.customer,
                    orders: data.orders,
                    addresses: data.addresses
                });
            } else {
                console.error('fetchCustomerDetail error:', data.message);
                onError(data.message);
            }
        })
        .catch(function(err) {
            console.error('fetchCustomerDetail failed:', err);
            onError(err);
        });
}

/**
 * Export customers array as a downloadable CSV file.
 * @param {Array} customers
 */
function exportCustomersCSV(customers) {
    if (!customers || customers.length === 0) {
        alert('No customers to export.');
        return;
    }

    const headers = ['UserID', 'FirstName', 'LastName', 'Email', 'PhoneNum', 'TotalOrders', 'TotalSpent', 'DateCreated'];
    const rows = customers.map(function(c) {
        return [
            c.UserID,
            '"' + (c.FirstName || '') + '"',
            '"' + (c.LastName || '') + '"',
            '"' + (c.Email || '') + '"',
            c.PhoneNum || '',
            c.TotalOrders || 0,
            parseFloat(c.TotalSpent || 0).toFixed(2),
            c.DateCreated || ''
        ].join(',');
    });

    const csvContent = [headers.join(',')].concat(rows).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'customers_export_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}
