<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/index.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];

// Fetch order details
$order_query = "SELECT o.* FROM orders o 
               WHERE o.order_id = $order_id AND o.consumer_id = $consumer_id";
$order_result = mysqli_query($dbconn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: orders.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Fetch order items
$items_query = "SELECT oi.*, p.name, p.product_image, p.seller_id, 
               u.first_name, u.last_name
               FROM order_items oi
               JOIN products p ON oi.product_id = p.product_id
               JOIN users u ON p.seller_id = u.user_id
               WHERE oi.order_id = $order_id";
$items_result = mysqli_query($dbconn, $items_query);

// Group items by seller
$sellers = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    if (!isset($sellers[$item['seller_id']])) {
        $sellers[$item['seller_id']] = [
            'name' => $item['first_name'] . ' ' . $item['last_name'],
            'items' => []
        ];
    }
    
    $sellers[$item['seller_id']]['items'][] = $item;
}

// Get order status history
$status_query = "SELECT * FROM order_status_history 
                WHERE order_id = $order_id 
                ORDER BY created_at ASC";
$status_result = mysqli_query($dbconn, $status_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - FarmFresh Connect</title>
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
        }
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-item::before {
            content: "";
            position: absolute;
            left: -30px;
            top: 0;
            width: 2px;
            height: 100%;
            background-color: #dee2e6;
        }
        .timeline-item:last-child::before {
            height: 0;
        }
        .timeline-badge {
            position: absolute;
            left: -38px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #6c757d;
        }
        .timeline-badge.active {
            background-color: #28a745;
            border-color: #28a745;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Order Details</h1>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>
        
        <!-- Order Summary -->
        <div class="card order-card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title">Order #<?php echo $order['order_id']; ?></h5>
                        <p class="text-muted mb-1">Placed on: <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                        <p class="mb-0">
                            <span class="badge bg-<?php 
                                switch($order['status']) {
                                    case 'pending': echo 'warning'; break;
                                    case 'processing': echo 'info'; break;
                                    case 'shipped': echo 'primary'; break;
                                    case 'delivered': echo 'success'; break;
                                    case 'cancelled': echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?> status-badge">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="card-title">Total: $<?php echo number_format($order['total_amount'], 2); ?></h5>
                        <p class="text-muted mb-1">Payment Method: <?php echo ucfirst($order['payment_method']); ?></p>
                        <?php if($order['status'] == 'pending'): ?>
                            <form action="cancel_order.php" method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel Order</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Shipping Information -->
        <div class="card order-card mb-4">
            <div class="card-body">
                <h5 class="card-title">Shipping Information</h5>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>
        </div>
        
        <!-- Order Items -->
        <h5 class="mb-3">Order Items</h5>
        <?php foreach($sellers as $seller_id => $seller): ?>
            <div class="card order-card mb-3">
                <div class="card-header bg-light">
                    <strong>Seller: <?php echo htmlspecialchars($seller['name']); ?></strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col" class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($seller['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if(!empty($item['product_image'])): ?>
                                                    <img src="../uploads/products/<?php echo $item['product_image']; ?>" class="product-img me-3" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php else: ?>
                                                    <div class="product-img bg-light me-3"></div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none">View Product</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Order Status Timeline -->
        <div class="card order-card mt-4">
            <div class="card-body">
                <h5 class="card-title mb-4">Order Status Timeline</h5>
                
                <div class="timeline">
                    <?php 
                    $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                    $current_status_index = array_search($order['status'], $statuses);
                    
                    if (mysqli_num_rows($status_result) > 0) {
                        // Display actual status history
                        while ($status = mysqli_fetch_assoc($status_result)) {
                            $is_active = ($status['status'] == $order['status']);
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-badge <?php echo $is_active ? 'active' : ''; ?>"></div>
                                <h6 class="mb-1"><?php echo ucfirst($status['status']); ?></h6>
                                <p class="text-muted small mb-0"><?php echo date('F j, Y, g:i a', strtotime($status['created_at'])); ?></p>
                                <?php if (!empty($status['notes'])): ?>
                                    <p class="mb-0 mt-2"><?php echo htmlspecialchars($status['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    } else {
                        // Display default timeline based on current status
                        foreach ($statuses as $index => $status) {
                            $is_active = ($index <= $current_status_index);
                            $is_current = ($status == $order['status']);
                            
                            if ($is_active || $index <= $current_status_index + 1) {
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-badge <?php echo $is_active ? 'active' : ''; ?>"></div>
                                    <h6 class="mb-1"><?php echo ucfirst($status); ?></h6>
                                    <?php if ($is_current): ?>
                                        <p class="text-muted small mb-0">Current Status</p>
                                    <?php elseif ($is_active): ?>
                                        <p class="text-muted small mb-0">Completed</p>
                                    <?php else: ?>
                                        <p class="text-muted small mb-0">Upcoming</p>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>