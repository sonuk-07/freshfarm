<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Get consumer username
$user_query = "SELECT username FROM users WHERE user_id = $consumer_id";
$user_result = mysqli_query($dbconn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
$username = $user_data['username'] ?? 'User';

// Fetch consumer's orders
$orders_query = "SELECT COUNT(*) as total_orders FROM orders WHERE consumer_id = $consumer_id AND status != 'delivered' AND status != 'cancelled'";
$orders_result = mysqli_query($dbconn, $orders_query);
$orders_data = mysqli_fetch_assoc($orders_result);
$active_orders = $orders_data['total_orders'] ?? 0;

// Count total orders
$total_orders_query = "SELECT COUNT(*) as total_orders FROM orders WHERE consumer_id = $consumer_id";
$total_orders_result = mysqli_query($dbconn, $total_orders_query);
$total_orders_data = mysqli_fetch_assoc($total_orders_result);
$total_orders = $total_orders_data['total_orders'] ?? 0;


// Count saved items - Check if table exists first
$saved_items = 0;
$table_check = mysqli_query($dbconn, "SHOW TABLES LIKE 'saved_items'");
if (mysqli_num_rows($table_check) > 0) {
    $saved_items_query = "SELECT COUNT(*) as saved_items FROM saved_items WHERE consumer_id = $consumer_id";
    $saved_items_result = mysqli_query($dbconn, $saved_items_query);
    $saved_items_data = mysqli_fetch_assoc($saved_items_result);
    $saved_items = $saved_items_data['saved_items'] ?? 0;
}

// Count cart items
$cart_count = 0;
$cart_check = mysqli_query($dbconn, "SHOW TABLES LIKE 'cart'");
if (mysqli_num_rows($cart_check) > 0) {
    $cart_query = "SELECT COUNT(*) as count FROM cart WHERE consumer_id = $consumer_id";
    $cart_result = mysqli_query($dbconn, $cart_query);
    if ($cart_result && mysqli_num_rows($cart_result) > 0) {
        $cart_data = mysqli_fetch_assoc($cart_result);
        $cart_count = $cart_data['count'] ?? 0;
    }
}


// Recent orders
$recent_orders_query = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                       FROM orders o 
                       LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                       WHERE o.consumer_id = $consumer_id 
                       GROUP BY o.order_id
                       ORDER BY o.order_date DESC LIMIT 5";
$recent_orders_result = mysqli_query($dbconn, $recent_orders_query);

// Get recommended products based on previous purchases
$recommended_query = "SELECT p.*, c.name as category_name, u.username as seller_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.category_id
                     LEFT JOIN users u ON p.seller_id = u.user_id
                     WHERE p.stock > 0
                     ORDER BY RAND() LIMIT 3";
$recommended_result = mysqli_query($dbconn, $recommended_query);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #198754;
            --dark-green: #146c43;
            --light-green: #d1e7dd;
            --background-gray: #f8fafc;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-gray);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .welcome-section {
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .stat-icon.cart {
            background-color: var(--light-green);
            color: var(--dark-green);
        }

        .stat-icon.orders {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .stat-title {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info h6 {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .order-meta {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .order-price {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-delivered {
            background-color: var(--light-green);
            color: var(--dark-green);
        }

        .status-shipped {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .btn-primary-custom {
            background-color: var(--primary-green);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }

        .btn-primary-custom:hover {
            background-color: var(--dark-green);
            color: white;
        }

        .btn-outline-custom {
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-outline-custom:hover {
            border-color: var(--primary-green);
            color: var(--primary-green);
        }

        .btn-sm-custom {
            background-color: var(--primary-green);
            border: none;
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
            font-weight: 500;
            color: white;
            text-decoration: none;
            font-size: 0.75rem;
            transition: background-color 0.2s;
        }

        .btn-sm-custom:hover {
            background-color: var(--dark-green);
            color: white;
        }

        .product-grid {
            display: grid;
            gap: 1rem;
        }

        .product-item {
            display: flex;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .product-item:hover {
            border-color: var(--primary-green);
            box-shadow: 0 2px 8px rgba(25, 135, 84, 0.1);
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background-color: var(--background-gray);
        }

        .product-info h6 {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .product-meta {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .product-price {
            font-weight: 600;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }



        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-container {
                padding: 1rem;
            }
            
            .welcome-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="main-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
            <p class="welcome-subtitle">Discover fresh produce from local farmers</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon cart">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-title">Cart Items</div>
                <div class="stat-value"><?php echo $cart_count; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-title">Total Orders</div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Orders -->
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="section-title mb-0">Recent Orders</h2>
                </div>

                <?php if (mysqli_num_rows($recent_orders_result) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h6>Order #<?php echo $order['order_id']; ?></h6>
                                <div class="order-meta">
                                    <?php echo date('Y-m-d', strtotime($order['order_date'])); ?> • <?php echo $order['item_count']; ?> items
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="order-price">Rs.<?php echo number_format($order['total_amount'], 2); ?></div>
                                <span class="status-badge <?php echo $order['status'] == 'delivered' ? 'status-delivered' : 'status-shipped'; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No orders yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="section-card">
                <h3 class="section-title">Quick Actions</h3>
                <div class="d-grid gap-2">
                    <a href="products.php" class="btn-primary-custom text-center">Browse Products</a>
                    <a href="cart.php" class="btn-outline-custom text-center">View Cart (<?php echo $cart_count; ?>)</a>
                </div>
            </div>
        </div>

        <!-- Recommended Products -->
        <div class="section-card">
            <h2 class="section-title">Recommended for You</h2>
            
            <?php if (mysqli_num_rows($recommended_result) > 0): ?>
                <div class="product-grid">
                    <?php while ($product = mysqli_fetch_assoc($recommended_result)): ?>
                        <div class="product-item">
                            <div class="product-image">
                                <?php if (!empty($product['product_image'])): ?>
                                    <img src="../uploads/products/<?php echo $product['product_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-image d-flex align-items-center justify-content-center">
                                        <i class="fas fa-leaf" style="color: var(--primary-green); font-size: 1.5rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info flex-grow-1">
                                <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                <div class="product-meta">by <?php echo htmlspecialchars($product['seller_name'] ?? 'Local Farm'); ?></div>
                                <div class="product-price">Rs.<?php echo number_format($product['price'], 2); ?>/<?php echo htmlspecialchars($product['unit'] ?? 'lb'); ?></div>
                            </div>
                            
                            <div class="text-end">
                                <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn-sm-custom">
                                    View Product
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-leaf"></i>
                    <p>No recommendations available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>