<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/index.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Fetch consumer's orders
$orders_query = "SELECT COUNT(*) as total_orders FROM orders WHERE consumer_id = $consumer_id AND status != 'delivered' AND status != 'cancelled'";
$orders_result = mysqli_query($dbconn, $orders_query);
$orders_data = mysqli_fetch_assoc($orders_result);
$active_orders = $orders_data['total_orders'] ?? 0;

// Count favorite farmers - Check if table exists first
$favorite_farmers = 0;
$table_check = mysqli_query($dbconn, "SHOW TABLES LIKE 'favorite_farmers'");
if (mysqli_num_rows($table_check) > 0) {
    $favorites_query = "SELECT COUNT(DISTINCT seller_id) as favorite_farmers 
                       FROM favorite_farmers 
                       WHERE consumer_id = $consumer_id";
    $favorites_result = mysqli_query($dbconn, $favorites_query);
    $favorites_data = mysqli_fetch_assoc($favorites_result);
    $favorite_farmers = $favorites_data['favorite_farmers'] ?? 0;
}

// Count saved items - Check if table exists first
$saved_items = 0;
$table_check = mysqli_query($dbconn, "SHOW TABLES LIKE 'saved_items'");
if (mysqli_num_rows($table_check) > 0) {
    $saved_items_query = "SELECT COUNT(*) as saved_items FROM saved_items WHERE consumer_id = $consumer_id";
    $saved_items_result = mysqli_query($dbconn, $saved_items_query);
    $saved_items_data = mysqli_fetch_assoc($saved_items_result);
    $saved_items = $saved_items_data['saved_items'] ?? 0;
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
$recommended_query = "SELECT p.*, c.name as category_name 
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.category_id
                     WHERE p.stock > 0
                     ORDER BY RAND() LIMIT 3";
$recommended_result = mysqli_query($dbconn, $recommended_query);

// Get favorite farmers - Check if table exists first
$favorite_farmers_result = false;
$table_check = mysqli_query($dbconn, "SHOW TABLES LIKE 'favorite_farmers'");
if (mysqli_num_rows($table_check) > 0) {
    $favorite_farmers_query = "SELECT u.user_id, u.username, u.profile_image, 
                              (SELECT GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') 
                               FROM products p 
                               JOIN categories c ON p.category_id = c.category_id 
                               WHERE p.seller_id = u.user_id) as specialties
                              FROM users u
                              JOIN favorite_farmers ff ON u.user_id = ff.seller_id
                              WHERE ff.consumer_id = $consumer_id
                              LIMIT 5";
    $favorite_farmers_result = mysqli_query($dbconn, $favorite_farmers_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .product-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .farmer-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .farmer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #495057;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Your Dashboard</h1>
            <a href="./marketplace.php" class="btn btn-success">Shop Now</a>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted mb-2">Active Orders</h5>
                        <h2 class="display-4 fw-bold mb-2"><?php echo $active_orders; ?></h2>
                        <p class="card-text text-muted">Orders in progress</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted mb-2">Favorite Farmers</h5>
                        <h2 class="display-4 fw-bold mb-2"><?php echo $favorite_farmers; ?></h2>
                        <p class="card-text text-muted">Farms you regularly buy from</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted mb-2">Saved Items</h5>
                        <h2 class="display-4 fw-bold mb-2"><?php echo $saved_items; ?></h2>
                        <p class="card-text text-muted">Products in your wishlist</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Orders Section -->
            <div class="col-lg-8 mb-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Your Orders</h2>
                        <p class="text-muted mb-4">Track and manage your current and past orders</p>
                        
                        <ul class="nav nav-tabs mb-3" id="orderTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">Active Orders</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Order History</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="orderTabsContent">
                            <div class="tab-pane fade show active" id="active" role="tabpanel">
                                <?php if(mysqli_num_rows($recent_orders_result) > 0): ?>
                                    <?php 
                                    // Reset pointer to beginning
                                    mysqli_data_seek($recent_orders_result, 0);
                                    ?>
                                    <?php while($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                                        <?php if($order['status'] != 'delivered' && $order['status'] != 'cancelled'): ?>
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1">Order #<?php echo $order['order_id']; ?></h6>
                                                            <p class="text-muted mb-0 small">
                                                                <?php echo date('m/d/Y', strtotime($order['order_date'])); ?> | 
                                                                $<?php echo number_format($order['total_amount'], 2); ?> | 
                                                                <?php echo $order['item_count']; ?> items
                                                            </p>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-primary me-3"><?php echo ucfirst($order['status']); ?></span>
                                                            <a href="track_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">Track Order</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <h5>No active orders</h5>
                                        <p class="text-muted">You don't have any active orders at the moment.</p>
                                        <a href="../marketplace.php" class="btn btn-primary">Start Shopping</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="tab-pane fade" id="history" role="tabpanel">
                                <?php if(mysqli_num_rows($recent_orders_result) > 0): ?>
                                    <?php 
                                    // Reset pointer to beginning
                                    mysqli_data_seek($recent_orders_result, 0);
                                    ?>
                                    <?php while($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                                        <?php if($order['status'] == 'delivered' || $order['status'] == 'cancelled'): ?>
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1">Order #<?php echo $order['order_id']; ?></h6>
                                                            <p class="text-muted mb-0 small">
                                                                <?php echo date('m/d/Y', strtotime($order['order_date'])); ?> | 
                                                                $<?php echo number_format($order['total_amount'], 2); ?> | 
                                                                <?php echo $order['item_count']; ?> items
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <span class="badge bg-<?php echo $order['status'] == 'delivered' ? 'success' : 'secondary'; ?> me-2">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                            <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-secondary">Details</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                        <h5>No order history</h5>
                                        <p class="text-muted">You haven't placed any orders yet.</p>
                                        <a href="../marketplace.php" class="btn btn-primary">Start Shopping</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recommended Products -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Recommended For You</h2>
                        <p class="text-muted mb-4">Based on your previous purchases</p>
                        
                        <?php if(mysqli_num_rows($recommended_result) > 0): ?>
                            <?php while($product = mysqli_fetch_assoc($recommended_result)): ?>
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0 me-3">
                                        <?php if(!empty($product['image_url'])): ?>
                                            <img src="../uploads/products/<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="50" height="50" class="rounded">
                                        <?php else: ?>
                                            <div class="bg-light rounded" style="width: 50px; height: 50px;"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="text-muted mb-0">$<?php echo number_format($product['price'], 2); ?> / <?php echo $product['unit'] ?? 'item'; ?></p>
                                    </div>
                                    <a href="../product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-success">Add</a>
                                </div>
                            <?php endwhile; ?>
                            <div class="text-center mt-3">
                                <a href="../marketplace.php" class="btn btn-link">View More Products</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-leaf fa-3x text-muted mb-3"></i>
                                <h5>No recommendations yet</h5>
                                <p class="text-muted">Start shopping to get personalized recommendations.</p>
                                <a href="../marketplace.php" class="btn btn-success">Explore Products</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Favorite Farmers -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h5 mb-1">Your Favorite Farmers</h2>
                        <p class="text-muted mb-0">Farms you regularly buy from</p>
                    </div>
                    <a href="../farmers.php" class="btn btn-link">Explore All Farmers</a>
                </div>
                
                <div class="row">
                    <?php if(mysqli_num_rows($favorite_farmers_result) > 0): ?>
                        <?php while($farmer = mysqli_fetch_assoc($favorite_farmers_result)): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card farmer-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="farmer-avatar me-3">
                                                <?php if(!empty($farmer['profile_image'])): ?>
                                                    <img src="../uploads/profiles/<?php echo $farmer['profile_image']; ?>" alt="<?php echo htmlspecialchars($farmer['username']); ?>" width="40" height="40" class="rounded-circle">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($farmer['username'], 0, 2)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($farmer['username']); ?></h6>
                                                <p class="text-muted mb-0 small"><?php echo htmlspecialchars($farmer['specialties'] ?? 'Organic produce'); ?></p>
                                            </div>
                                            <a href="../farmer_profile.php?id=<?php echo $farmer['user_id']; ?>" class="btn btn-sm btn-outline-primary">Visit</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-4">
                                <i class="fas fa-tractor fa-3x text-muted mb-3"></i>
                                <h5>No favorite farmers yet</h5>
                                <p class="text-muted">Explore our marketplace and find farmers you love.</p>
                                <a href="../farmers.php" class="btn btn-primary">Discover Farmers</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
