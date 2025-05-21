<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/index.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$orders_query = "SELECT o.*, 
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o 
                WHERE o.consumer_id = $consumer_id 
                ORDER BY o.order_date DESC
                LIMIT $offset, $items_per_page";
$orders_result = mysqli_query($dbconn, $orders_query);

// Get total orders for pagination
$count_query = "SELECT COUNT(*) as total FROM orders WHERE consumer_id = $consumer_id";
$count_result = mysqli_query($dbconn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_orders = $count_data['total'];
$total_pages = ceil($total_orders / $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .order-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">My Orders</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if(mysqli_num_rows($orders_result) > 0): ?>
            <div class="row">
                <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card order-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Order #<?php echo $order['order_id']; ?></h5>
                                    <span class="status-badge bg-<?php 
                                        echo match($order['status']) {
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'shipped' => 'primary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?> text-white">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="text-muted mb-1">
                                    <i class="far fa-calendar-alt me-2"></i>
                                    <?php echo date('F j, Y', strtotime($order['order_date'])); ?>
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-shopping-basket me-2"></i>
                                    <?php echo $order['item_count']; ?> items
                                </p>
                                <p class="text-muted mb-3">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-outline-primary">
                                        View Details
                                    </a>
                                    <?php if($order['status'] == 'delivered'): ?>
                                        <a href="review_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-outline-success">
                                            Leave Review
                                        </a>
                                    <?php elseif($order['status'] == 'pending'): ?>
                                        <form action="cancel_order.php" method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger">Cancel Order</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                <h3>No orders yet</h3>
                <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="marketplace.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>