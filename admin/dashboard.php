<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch dashboard statistics from database
// Total Users
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = mysqli_query($dbconn, $users_query);
$users_data = mysqli_fetch_assoc($users_result);
$total_users = $users_data['total_users'];

// Total Farmers
$farmers_query = "SELECT COUNT(*) as total_farmers FROM users WHERE role = 'farmer'";
$farmers_result = mysqli_query($dbconn, $farmers_query);
$farmers_data = mysqli_fetch_assoc($farmers_result);
$total_farmers = $farmers_data['total_farmers'];

// Total Consumers
$consumers_query = "SELECT COUNT(*) as total_consumers FROM users WHERE role = 'consumer'";
$consumers_result = mysqli_query($dbconn, $consumers_query);
$consumers_data = mysqli_fetch_assoc($consumers_result);
$total_consumers = $consumers_data['total_consumers'];

// Total Products
$products_query = "SELECT COUNT(*) as total_products FROM products";
$products_result = mysqli_query($dbconn, $products_query);
$products_data = mysqli_fetch_assoc($products_result);
$total_products = $products_data['total_products'] ?? 0;

// Total Orders
$orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$orders_result = mysqli_query($dbconn, $orders_query);
$orders_data = mysqli_fetch_assoc($orders_result);
$total_orders = $orders_data['total_orders'] ?? 0;

// Total Revenue
$revenue_query = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'delivered'";
$revenue_result = mysqli_query($dbconn, $revenue_query);
$revenue_data = mysqli_fetch_assoc($revenue_result);
$total_revenue = $revenue_data['total_revenue'] ?? 0;

// Recent Users
$recent_users_query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
$recent_users_result = mysqli_query($dbconn, $recent_users_query);

// Recent Products - Modified to match your schema
$recent_products_query = "SELECT p.*, u.username as farmer_name, c.name as category_name 
                         FROM products p 
                         JOIN users u ON p.seller_id = u.user_id 
                         LEFT JOIN categories c ON p.category_id = c.category_id
                         ORDER BY p.created_at DESC LIMIT 5";
$recent_products_result = mysqli_query($dbconn, $recent_products_query);

// Recent Orders - Modified to match your schema
$recent_orders_query = "SELECT o.*, u.username FROM orders o 
                       JOIN users u ON o.consumer_id = u.user_id 
                       ORDER BY o.order_date DESC LIMIT 5";
$recent_orders_result = mysqli_query($dbconn, $recent_orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FarmFresh Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .dashboard-header {
            margin-bottom: 32px;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .dashboard-subtitle {
            color: #64748b;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-icon.users { background: #dbeafe; color: #2563eb; }
        .stat-icon.products { background: #dcfce7; color: #16a34a; }
        .stat-icon.orders { background: #fce7f3; color: #db2777; }
        .stat-icon.revenue { background: #fef3c7; color: #d97706; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .stat-description {
            font-size: 0.875rem;
            color: #64748b;
        }

        .navigation-tabs {
            display: flex;
            gap: 32px;
            margin-bottom: 32px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
        }

        .nav-tab {
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-tab.active {
            color: #10b981;
            border-bottom-color: #10b981;
        }

        .nav-tab:hover {
            color: #10b981;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 24px;
        }

        .activity-list {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 0.875rem;
        }

        .activity-icon.user { background: #dbeafe; color: #2563eb; }
        .activity-icon.order { background: #dcfce7; color: #16a34a; }
        .activity-icon.product { background: #fce7f3; color: #db2777; }
        .activity-icon.warning { background: #fef3c7; color: #d97706; }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #64748b;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .action-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            text-decoration: none;
            color: inherit;
        }

        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.25rem;
        }

        .action-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1e293b;
        }

        .settings-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 24px;
        }

        .settings-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .settings-item:last-child {
            border-bottom: none;
        }

        .settings-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1e293b;
        }

        .toggle-switch {
            width: 48px;
            height: 24px;
            background: #e2e8f0;
            border-radius: 12px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .toggle-switch.active {
            background: #10b981;
        }

        .toggle-switch::after {
            content: '';
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            left: 2px;
            transition: transform 0.3s ease;
        }

        .toggle-switch.active::after {
            transform: translateX(24px);
        }

        .dropdown-select {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #1e293b;
            font-size: 0.875rem;
        }

        .configure-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .logout-btn {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #ef4444;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #dc2626;
            color: white;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 16px;
            }
            
            .navigation-tabs {
                flex-wrap: wrap;
                gap: 16px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Admin Dashboard</h1>
            <p class="dashboard-subtitle">Manage the FarmFresh Connect platform</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-title">Total Users</div>
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-description"><?php echo $total_farmers; ?> farmers, <?php echo $total_consumers; ?> consumers</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-title">Total Products</div>
                    <div class="stat-icon products">
                        <i class="fas fa-seedling"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $total_products; ?></div>
                <div class="stat-description">Active listings in marketplace</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-title">Total Orders</div>
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div class="stat-description">Orders processed</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-title">Revenue</div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-value">Rs.<?php echo number_format($total_revenue, 0); ?></div>
                <div class="stat-description">+15.3% this month</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="navigation-tabs">
            <a href="#" class="nav-tab active">Overview</a>
            <a href="users.php" class="nav-tab">User Management</a>
            <a href="products.php" class="nav-tab">Product Management</a>
            <a href="orders.php" class="nav-tab">Order Management</a>
            <a href="reviews.php" class="nav-tab">Review Moderation</a>
        </div>

        <!-- Recent Activity -->
        <div class="section">
            <h2 class="section-title">Recent Activity</h2>
            <div class="activity-list">
                <?php 
                // Get most recent user
                mysqli_data_seek($recent_users_result, 0);
                $recent_user = mysqli_fetch_assoc($recent_users_result);
                if($recent_user): ?>
                <div class="activity-item">
                    <div class="activity-icon user">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title"><?php echo $recent_user['role']; ?> logged in <?php echo htmlspecialchars($recent_user['username']); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php 
                // Get most recent order
                mysqli_data_seek($recent_orders_result, 0);
                $recent_order = mysqli_fetch_assoc($recent_orders_result);
                if($recent_order): ?>
                <div class="activity-item">
                    <div class="activity-icon order">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Order #<?php echo $recent_order['order_id']; ?> completed successfully</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php 
                // Get most recent product
                mysqli_data_seek($recent_products_result, 0);
                $recent_product = mysqli_fetch_assoc($recent_products_result);
                if($recent_product): ?>
                <div class="activity-item">
                    <div class="activity-icon product">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">New product added: <?php echo htmlspecialchars($recent_product['name']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <h2 class="section-title">Quick Actions</h2>
            <div class="quick-actions">
                <a href="users.php" class="action-card">
                    <div class="action-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="action-title">Manage Users</div>
                </a>

                <a href="products.php" class="action-card">
                    <div class="action-icon products">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="action-title">Review Products</div>
                </a>

                <a href="#" class="action-card">
                    <div class="action-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="action-title">Moderate Reviews</div>
                </a>
            </div>
        </div>



    <script>
        // Add some interactivity
        document.querySelectorAll('.toggle-switch').forEach(toggle => {
            toggle.addEventListener('click', function() {
                this.classList.toggle('active');
            });
        });

        // Simulate real-time updates (optional)
        setInterval(() => {
            const timeElements = document.querySelectorAll('.activity-time');
            timeElements.forEach(el => {
                if (el.textContent.includes('minutes ago')) {
                    let minutes = parseInt(el.textContent);
                    if (!isNaN(minutes)) {
                        el.textContent = `${minutes + 1} minutes ago`;
                    }
                }
            });
        }, 60000);
    </script>
</body>

</html>