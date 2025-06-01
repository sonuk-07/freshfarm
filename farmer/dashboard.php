<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

// Fetch farmer's products
$products_query = "SELECT COUNT(*) as total_products FROM products WHERE seller_id = $farmer_id";
$products_result = mysqli_query($dbconn, $products_query);
$products_data = mysqli_fetch_assoc($products_result);
$total_products = $products_data['total_products'] ?? 0;

// Fetch farmer's orders
$orders_query = "SELECT COUNT(*) as total_orders FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                JOIN orders o ON oi.order_id = o.order_id 
                WHERE p.seller_id = $farmer_id";
$orders_result = mysqli_query($dbconn, $orders_query);
$orders_data = mysqli_fetch_assoc($orders_result);
$total_orders = $orders_data['total_orders'] ?? 0;

// Calculate farmer's revenue
$revenue_query = "SELECT SUM(oi.quantity * oi.price_per_unit) as total_revenue 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.product_id 
                 JOIN orders o ON oi.order_id = o.order_id 
                 WHERE p.seller_id = $farmer_id AND o.status != 'canceled'";
$revenue_result = mysqli_query($dbconn, $revenue_query);
$revenue_data = mysqli_fetch_assoc($revenue_result);
$total_revenue = $revenue_data['total_revenue'] ?? 0;

// Recent products
$recent_products_query = "SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.category_id
                         WHERE p.seller_id = $farmer_id
                         ORDER BY p.created_at DESC LIMIT 5";
$recent_products_result = mysqli_query($dbconn, $recent_products_query);

// Recent orders
$recent_orders_query = "SELECT o.order_id, o.order_date, o.total_amount, o.status, 
                       u.username, oi.quantity, oi.price_per_unit, p.name as product_name
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.product_id 
                       JOIN orders o ON oi.order_id = o.order_id 
                       JOIN users u ON o.consumer_id = u.user_id
                       WHERE p.seller_id = $farmer_id
                       ORDER BY o.order_date DESC LIMIT 5";
$recent_orders_result = mysqli_query($dbconn, $recent_orders_query);

// Get farmer's name from session or database
$farmer_name = $_SESSION['username'] ?? 'Farmer';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .stat-change {
            font-size: 0.875rem;
            color: #16a34a;
            font-weight: 500;
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

        .view-all-link {
            color: #16a34a;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .view-all-link:hover {
            color: #15803d;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info h6 {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .order-details {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .order-date {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .order-amount {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-badge.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge.shipped {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-badge.delivered {
            background-color: #dcfce7;
            color: #166534;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-card {
            background: white;
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #64748b;
        }

        .action-card:hover {
            border-color: #16a34a;
            color: #16a34a;
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($farmer_name); ?>!</h1>
            <p class="welcome-subtitle">Manage your farm products and orders</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
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
                <div class="stat-label">This Month</div>
                <div class="stat-value stat-change">+12%</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-tabs-custom">
            <li class="nav-item">
                <a class="nav-link active" href="#overview">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">My Products</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="analytics.php">Analytics</a>
            </li>
        </ul>

        <!-- Recent Orders Section -->
        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">Recent Orders</h3>
                <a href="orders.php" class="view-all-link">View all</a>
            </div>

            <div class="orders-list">
                <?php if (mysqli_num_rows($recent_orders_result) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h6><?php echo htmlspecialchars($order['username']); ?></h6>
                                <div class="order-details">
                                    <?php echo htmlspecialchars($order['product_name']); ?> × <?php echo $order['quantity']; ?>
                                </div>
                                <div class="order-date">
                                    <?php echo date('Y-m-d', strtotime($order['order_date'])); ?>
                                </div>
                            </div>
                            <div class="order-summary">
                                <div class="order-amount">
                                    $<?php echo number_format($order['quantity'] * $order['price_per_unit'], 2); ?>
                                </div>
                                <span class="status-badge <?php
                                if ($order['status'] == 'delivered')
                                    echo 'delivered';
                                elseif ($order['status'] == 'processing')
                                    echo 'pending';
                                elseif ($order['status'] == 'shipped')
                                    echo 'shipped';
                                else
                                    echo 'pending';
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="order-item">
                        <div class="order-info">
                            <h6>No recent orders</h6>
                            <div class="order-details">Your orders will appear here once customers start purchasing your
                                products.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="content-section">
            <h3 class="section-title">Quick Actions</h3>
            <div class="quick-actions">
                <a href="add_products.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-title">Add New Product</div>
                </a>

                <a href="orders.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="action-title">Manage Orders</div>
                </a>

                <a href="analytics.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="action-title">View Analytics</div>
                </a>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>