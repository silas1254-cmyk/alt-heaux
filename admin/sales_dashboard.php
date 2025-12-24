<?php
require '../includes/config.php';

requireAdmin();

// Get time period filter
$period = $_GET['period'] ?? 'month';
$valid_periods = ['day', 'week', 'month', 'year'];
if (!in_array($period, $valid_periods)) {
    $period = 'month';
}

// Get sales statistics
$stats = [];

// Total revenue
$query = "SELECT SUM(total_amount) as total_revenue, COUNT(id) as total_orders FROM orders";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$stats['total_revenue'] = $row['total_revenue'] ?? 0;
$stats['total_orders'] = $row['total_orders'] ?? 0;

// Revenue data based on period
$chart_data = [];
$chart_labels = [];

if ($period === 'day') {
    // Last 30 days
    $query = "SELECT DATE(order_date) as day, SUM(total_amount) as revenue, COUNT(id) as orders FROM orders 
              WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY DATE(order_date) ORDER BY day ASC";
    $label_format = 'M d';
} elseif ($period === 'week') {
    // Last 12 weeks
    $query = "SELECT WEEK(order_date) as week, YEAR(order_date) as year, SUM(total_amount) as revenue, COUNT(id) as orders FROM orders 
              WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
              GROUP BY YEAR(order_date), WEEK(order_date) ORDER BY year, week ASC";
    $label_format = 'Week';
} elseif ($period === 'year') {
    // Last 5 years
    $query = "SELECT YEAR(order_date) as year, SUM(total_amount) as revenue, COUNT(id) as orders FROM orders 
              WHERE order_date >= DATE_SUB(NOW(), INTERVAL 5 YEAR)
              GROUP BY YEAR(order_date) ORDER BY year ASC";
    $label_format = 'Year';
} else {
    // Default: last 12 months
    $query = "SELECT DATE_FORMAT(order_date, '%Y-%m') as month, SUM(total_amount) as revenue, COUNT(id) as orders FROM orders 
              WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(order_date, '%Y-%m') ORDER BY month ASC";
    $label_format = 'Month';
}

$result = $conn->query($query);
$chart_data = [];
while ($row = $result->fetch_assoc()) {
    if ($period === 'day') {
        $chart_labels[] = date($label_format, strtotime($row['day']));
    } elseif ($period === 'week') {
        $chart_labels[] = 'W' . str_pad($row['week'], 2, '0', STR_PAD_LEFT) . ' ' . $row['year'];
    } elseif ($period === 'year') {
        $chart_labels[] = $row['year'];
    } else {
        $chart_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    }
    $chart_data[] = $row['revenue'];
}

// Order status breakdown
$query = "SELECT status, COUNT(id) as count FROM orders GROUP BY status";
$result = $conn->query($query);
$status_breakdown = [];
while ($row = $result->fetch_assoc()) {
    $status_breakdown[] = $row;
}

// Top products
$query = "SELECT p.id, p.name, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as revenue FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY p.id ORDER BY total_sold DESC LIMIT 10";
$result = $conn->query($query);
$top_products = [];
while ($row = $result->fetch_assoc()) {
    $top_products[] = $row;
}

// Recent orders
$query = "SELECT o.id, o.order_date, o.total_amount, o.status, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 10";
$result = $conn->query($query);
$recent_orders = [];
while ($row = $result->fetch_assoc()) {
    $recent_orders[] = $row;
}

$avg_order_value = $stats['total_orders'] > 0 ? $stats['total_revenue'] / $stats['total_orders'] : 0;

// Prepare chart labels and data as JSON
$chart_labels_json = json_encode($chart_labels);
$chart_data_json = json_encode($chart_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include('_sidebar.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Sales Dashboard</h1>
                    <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </div>

                <!-- Time Period Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="mb-3">View Sales By:</h6>
                        <div class="btn-group" role="group">
                            <a href="?period=day" class="btn btn-sm <?php echo $period === 'day' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-calendar-day"></i> Daily
                            </a>
                            <a href="?period=week" class="btn btn-sm <?php echo $period === 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-calendar-week"></i> Weekly
                            </a>
                            <a href="?period=month" class="btn btn-sm <?php echo $period === 'month' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-calendar"></i> Monthly
                            </a>
                            <a href="?period=year" class="btn btn-sm <?php echo $period === 'year' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-chart-line"></i> Yearly
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card revenue">
                            <div class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card orders">
                            <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card average">
                            <div class="stat-value">$<?php echo number_format($avg_order_value, 2); ?></div>
                            <div class="stat-label">Average Order Value</div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-bar-chart"></i> 
                                    <?php 
                                        $titles = [
                                            'day' => 'Daily Revenue (Last 30 Days)',
                                            'week' => 'Weekly Revenue (Last 12 Weeks)',
                                            'month' => 'Monthly Revenue (Last 12 Months)',
                                            'year' => 'Yearly Revenue (Last 5 Years)'
                                        ];
                                        echo $titles[$period];
                                    ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Order Status</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Top 10 Best Selling Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Units Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo $product['total_sold']; ?></td>
                                            <td>$<?php echo number_format($product['revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin.js"></script>
    <script>
        // Revenue Chart with dynamic data based on period
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const chartLabels = <?php echo $chart_labels_json; ?>;
        const chartData = <?php echo $chart_data_json; ?>;
        
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Revenue',
                    data: chartData,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?php echo json_encode($status_breakdown); ?>;
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.status),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: [
                        '#28a745',
                        '#007bff',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
