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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
            min-height: calc(100vh - 56px);
            background-color: #343a40;
        }
        .sidebar a {
            color: rgba(255,255,255,.75);
            padding: 10px 15px;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background-color: rgba(255,255,255,.1);
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .content-wrapper {
            min-height: calc(100vh - 56px);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-none d-md-block sidebar py-4">
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-carrot"></i> My Products</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 ms-auto content-wrapper p-4">
                <h2 class="mb-4">Farmer Dashboard</h2>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="dashboard-card bg-success text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">My Products</h6>
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
                                <small>Orders for your products</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="dashboard-card bg-primary text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Revenue</h6>
                                    <h2 class="mb-0">$<?php echo number_format($total_revenue, 2); ?></h2>
                                </div>
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                            <div class="mt-2">
                                <small>From completed orders</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <!-- Recent Products -->
                    <div class="col-md-6 mb-4">
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
                    <div class="col-md-6 mb-4">
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
                                                <small class="text-muted">
                                                    By: <?php echo htmlspecialchars($order['username']); ?> | 
                                                    Product: <?php echo htmlspecialchars($order['product_name']); ?> x 
                                                    <?php echo $order['quantity']; ?>
                                                </small>
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
                                                        $<?php echo number_format($order['quantity'] * $order['price_per_unit'], 2); ?>
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
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
