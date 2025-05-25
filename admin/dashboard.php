<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/index.php");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar a {
            color: #f8f9fa;
            padding: 10px 15px;
            display: block;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar a.active {
            background-color: #0d6efd;
        }
        .content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <h2 class="text-center text-white py-3">Admin Panel</h2>
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="users.php"><i class="fas fa-users me-2"></i> Users</a>
                <a href="products.php"><i class="fas fa-box me-2"></i> Products</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> Categories</a>
                <a href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
                <a href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2 class="mb-4">Admin Dashboard</h2>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="dashboard-card bg-primary text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Users</h6>
                                    <h2 class="mb-0"><?php echo $total_users; ?></h2>
                                </div>
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <div class="mt-2">
                                <small>Farmers: <?php echo $total_farmers; ?> | Consumers: <?php echo $total_consumers; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="dashboard-card bg-success text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Products</h6>
                                    <h2 class="mb-0"><?php echo $total_products; ?></h2>
                                </div>
                                <i class="fas fa-carrot fa-2x"></i>
                            </div>
                            <div class="mt-2">
                                <small>Active listings in marketplace</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="dashboard-card bg-info text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Orders</h6>
                                    <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                                </div>
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                            <div class="mt-2">
                                <small>Revenue: $<?php echo number_format($total_revenue, 2); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Recent Users</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if(mysqli_num_rows($recent_users_result) > 0): ?>
                                        <?php while($user = mysqli_fetch_assoc($recent_users_result)): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                </div>
                                                <span class="badge bg-<?php echo $user['role'] == 'farmer' ? 'success' : ($user['role'] == 'admin' ? 'danger' : 'primary'); ?> rounded-pill">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item">No users found</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Products -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Recent Products</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if(mysqli_num_rows($recent_products_result) > 0): ?>
                                        <?php while($product = mysqli_fetch_assoc($recent_products_result)): ?>
                                            <li class="list-group-item">
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">By: <?php echo htmlspecialchars($product['farmer_name']); ?></small>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                                    </span>
                                                    <span class="text-success">
                                                        $<?php echo number_format($product['price'], 2); ?>
                                                    </span>
                                                </div>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item">No products found</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Recent Orders</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if(mysqli_num_rows($recent_orders_result) > 0): ?>
                                        <?php while($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                                            <li class="list-group-item">
                                                <strong>Order #<?php echo $order['order_id']; ?></strong>
                                                <br>
                                                <small class="text-muted">By: <?php echo htmlspecialchars($order['username']); ?></small>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <span class="badge bg-<?php 
                                                        if($order['status'] == 'delivered') echo 'success';
                                                        elseif($order['status'] == 'processing') echo 'warning';
                                                        elseif($order['status'] == 'canceled') echo 'danger';
                                                        else echo 'secondary';
                                                    ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                    <span class="text-success">
                                                        $<?php echo number_format($order['total_amount'], 2); ?>
                                                    </span>
                                                </div>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item">No orders found</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
