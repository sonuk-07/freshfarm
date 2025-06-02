<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

// Get farmer's name from session or database
$farmer_name = $_SESSION['username'] ?? 'Farmer';

// Calculate total revenue
$revenue_query = "SELECT SUM(oi.quantity * oi.price_per_unit) as total_revenue 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.product_id 
                 JOIN orders o ON oi.order_id = o.order_id 
                 WHERE p.seller_id = $farmer_id AND o.status != 'canceled'";
$revenue_result = mysqli_query($dbconn, $revenue_query);
$revenue_data = mysqli_fetch_assoc($revenue_result);
$total_revenue = $revenue_data['total_revenue'] ?? 0;

// Calculate total orders
$orders_query = "SELECT COUNT(*) as total_orders FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                JOIN orders o ON oi.order_id = o.order_id 
                WHERE p.seller_id = $farmer_id";
$orders_result = mysqli_query($dbconn, $orders_query);
$orders_data = mysqli_fetch_assoc($orders_result);
$total_orders = $orders_data['total_orders'] ?? 0;

// Calculate total products
$products_query = "SELECT COUNT(*) as total_products FROM products WHERE seller_id = $farmer_id";
$products_result = mysqli_query($dbconn, $products_query);
$products_data = mysqli_fetch_assoc($products_result);
$total_products = $products_data['total_products'] ?? 0;

// Get monthly sales data for the last 6 months
$monthly_sales_query = "SELECT 
                        DATE_FORMAT(o.order_date, '%Y-%m') as month,
                        SUM(oi.quantity * oi.price_per_unit) as monthly_revenue,
                        COUNT(DISTINCT o.order_id) as order_count
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.product_id 
                        JOIN orders o ON oi.order_id = o.order_id 
                        WHERE p.seller_id = $farmer_id 
                        AND o.status != 'canceled'
                        AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
                        ORDER BY month ASC";
$monthly_sales_result = mysqli_query($dbconn, $monthly_sales_query);

// Prepare data for monthly sales chart
$months = [];
$sales_data = [];
$orders_data = [];

while ($row = mysqli_fetch_assoc($monthly_sales_result)) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $sales_data[] = $row['monthly_revenue'];
    $orders_data[] = $row['order_count'];
}

// Get top selling products
$top_products_query = "SELECT 
                      p.name as product_name,
                      SUM(oi.quantity) as total_quantity,
                      SUM(oi.quantity * oi.price_per_unit) as total_revenue
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.product_id 
                      JOIN orders o ON oi.order_id = o.order_id 
                      WHERE p.seller_id = $farmer_id 
                      AND o.status != 'canceled'
                      GROUP BY p.product_id
                      ORDER BY total_revenue DESC
                      LIMIT 5";
$top_products_result = mysqli_query($dbconn, $top_products_query);

// Get order status distribution
$status_query = "SELECT 
                o.status,
                COUNT(*) as status_count
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                JOIN orders o ON oi.order_id = o.order_id 
                WHERE p.seller_id = $farmer_id 
                GROUP BY o.status";
$status_result = mysqli_query($dbconn, $status_query);

// Prepare data for status chart
$statuses = [];
$status_counts = [];

while ($row = mysqli_fetch_assoc($status_result)) {
    $statuses[] = ucfirst($row['status']);
    $status_counts[] = $row['status_count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #334155;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .welcome-section {
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: #64748b;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .stat-icon.green {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .stat-icon.blue {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .stat-icon.yellow {
            background-color: #fef3c7;
            color: #d97706;
        }

        .stat-icon.purple {
            background-color: #f3e8ff;
            color: #9333ea;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .nav-tabs-custom {
            border: none;
            margin-bottom: 2rem;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #64748b;
            font-weight: 500;
            padding: 0.75rem 0;
            margin-right: 2rem;
            background: none;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs-custom .nav-link.active {
            color: #16a34a;
            border-bottom-color: #16a34a;
            background: none;
        }

        .content-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .top-products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-products-table th {
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-weight: 600;
        }

        .top-products-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .top-products-table tr:last-child td {
            border-bottom: none;
        }

        .product-name {
            font-weight: 500;
            color: #1e293b;
        }

        .product-revenue {
            font-weight: 600;
            color: #16a34a;
        }

        .progress-bar-container {
            width: 100%;
            background-color: #f1f5f9;
            border-radius: 20px;
            height: 8px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 20px;
            background-color: #16a34a;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }

            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Sales Analytics</h1>
            <p class="welcome-subtitle">Track your sales performance and product statistics</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-value">Rs.<?php echo number_format($total_revenue, 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-label">Active Products</div>
                <div class="stat-value"><?php echo $total_products; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-label">Avg. Order Value</div>
                <div class="stat-value">
                    Rs.<?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-tabs-custom">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">My Products</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="analytics.php">Analytics</a>
            </li>
        </ul>

        <!-- Monthly Sales Chart -->
        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">Monthly Sales Performance</h3>
            </div>
            <div class="chart-container">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>

        <!-- Two Column Layout for Charts -->
        <div class="row">
            <!-- Top Products -->
            <div class="col-md-6">
                <div class="content-section">
                    <div class="section-header">
                        <h3 class="section-title">Top Selling Products</h3>
                    </div>
                    <?php if (mysqli_num_rows($top_products_result) > 0): ?>
                        <table class="top-products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max_revenue = 0;
                                $top_products = [];
                                
                                // First pass to find max revenue for progress bar
                                mysqli_data_seek($top_products_result, 0);
                                while ($product = mysqli_fetch_assoc($top_products_result)) {
                                    if ($product['total_revenue'] > $max_revenue) {
                                        $max_revenue = $product['total_revenue'];
                                    }
                                    $top_products[] = $product;
                                }
                                
                                // Second pass to display products
                                foreach ($top_products as $product):
                                    $percentage = ($max_revenue > 0) ? ($product['total_revenue'] / $max_revenue) * 100 : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                    </td>
                                    <td><?php echo $product['total_quantity']; ?></td>
                                    <td>
                                        <div class="product-revenue">Rs.<?php echo number_format($product['total_revenue'], 2); ?></div>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No sales data available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Status Distribution -->
            <div class="col-md-6">
                <div class="content-section">
                    <div class="section-header">
                        <h3 class="section-title">Order Status Distribution</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Sales Chart
        const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
        const monthlySalesChart = new Chart(monthlySalesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: <?php echo json_encode($sales_data); ?>,
                    backgroundColor: 'rgba(22, 163, 74, 0.2)',
                    borderColor: 'rgba(22, 163, 74, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode($orders_data); ?>,
                    type: 'line',
                    fill: false,
                    borderColor: 'rgba(37, 99, 235, 1)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (Rs.)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        }
                    }
                }
            }
        });

        // Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusChart = new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($statuses); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: [
                        'rgba(22, 163, 74, 0.7)',  // delivered - green
                        'rgba(245, 158, 11, 0.7)', // processing - yellow
                        'rgba(37, 99, 235, 0.7)',  // shipped - blue
                        'rgba(239, 68, 68, 0.7)',  // canceled - red
                        'rgba(107, 114, 128, 0.7)' // other - gray
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
</body>

</html>