<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

if ($method === 'GET') {
    $period = $_GET['period'] ?? 'monthly';
    $start  = $_GET['start_date'] ?? date('Y-m-01');
    $end    = $_GET['end_date']   ?? date('Y-m-d');

    // Total revenue & orders in period
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total_orders,
               COALESCE(SUM(final_amount),0) AS total_revenue,
               COALESCE(AVG(final_amount),0) AS avg_order_value
        FROM orders
        WHERE order_status NOT IN ('Cancelled')
          AND DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$start, $end]);
    $summary = $stmt->fetch();

    // New customers in period
    $cu_stmt = $pdo->prepare("SELECT COUNT(*) AS new_customers FROM users WHERE role_type='Customer' AND DATE(date_created) BETWEEN ? AND ?");
    $cu_stmt->execute([$start, $end]);
    $summary['new_customers'] = (int)$cu_stmt->fetchColumn();

    // Revenue by period (daily/weekly/monthly)
    $group_format = match($period) {
        'daily'   => '%Y-%m-%d',
        'weekly'  => '%Y-%u',
        'yearly'  => '%Y',
        default   => '%Y-%m',
    };
    $rev_stmt = $pdo->prepare("
        SELECT DATE_FORMAT(order_date, ?) AS label,
               COALESCE(SUM(final_amount),0) AS revenue,
               COUNT(*) AS orders
        FROM orders
        WHERE order_status NOT IN ('Cancelled')
          AND DATE(order_date) BETWEEN ? AND ?
        GROUP BY label ORDER BY label
    ");
    $rev_stmt->execute([$group_format, $start, $end]);
    $chart_data = $rev_stmt->fetchAll();

    // Top products by revenue
    $top_stmt = $pdo->prepare("
        SELECT p.product_name, SUM(oi.quantity) AS units_sold, SUM(oi.subtotal) AS revenue
        FROM order_items oi
        JOIN products p ON p.product_id=oi.product_id
        JOIN orders o ON o.order_id=oi.order_id
        WHERE o.order_status NOT IN ('Cancelled') AND DATE(o.order_date) BETWEEN ? AND ?
        GROUP BY oi.product_id ORDER BY revenue DESC LIMIT 10
    ");
    $top_stmt->execute([$start, $end]);
    $top_products = $top_stmt->fetchAll();

    // Revenue by category
    $cat_stmt = $pdo->prepare("
        SELECT c.category_name, COALESCE(SUM(oi.subtotal),0) AS revenue
        FROM order_items oi
        JOIN products p ON p.product_id=oi.product_id
        JOIN categories c ON c.category_id=p.category_id
        JOIN orders o ON o.order_id=oi.order_id
        WHERE o.order_status NOT IN ('Cancelled') AND DATE(o.order_date) BETWEEN ? AND ?
        GROUP BY c.category_id ORDER BY revenue DESC
    ");
    $cat_stmt->execute([$start, $end]);
    $by_category = $cat_stmt->fetchAll();

    // Order status breakdown
    $status_stmt = $pdo->prepare("
        SELECT order_status, COUNT(*) AS count
        FROM orders WHERE DATE(order_date) BETWEEN ? AND ?
        GROUP BY order_status
    ");
    $status_stmt->execute([$start, $end]);
    $by_status = $status_stmt->fetchAll();

    // Low stock alert
    $low_stmt = $pdo->query("
        SELECT product_name, sku, stock_quantity, low_stock_level
        FROM products WHERE is_active=1 AND stock_quantity <= low_stock_level
        ORDER BY stock_quantity ASC LIMIT 10
    ");
    $low_stock = $low_stmt->fetchAll();

    respond([
        'success'       => true,
        'summary'       => $summary,
        'chart_data'    => $chart_data,
        'top_products'  => $top_products,
        'by_category'   => $by_category,
        'by_status'     => $by_status,
        'low_stock'     => $low_stock,
    ]);
}

respond(['success'=>false,'error'=>'Method not allowed.'], 405);
