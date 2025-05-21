<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['order_item_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $order_item_id = $_POST['order_item_id'];
    $status = $_POST['status'];
    
    // Update the order item status
    $update_query = "UPDATE order_items SET status = '$status' WHERE order_item_id = $order_item_id";
    
    if (mysqli_query($dbconn, $update_query)) {
        // Check if all items in the order have the same status
        $check_query = "SELECT COUNT(DISTINCT status) as status_count FROM order_items WHERE order_id = $order_id";
        $check_result = mysqli_query($dbconn, $check_query);
        $check_data = mysqli_fetch_assoc($check_result);
        
        // If all items have the same status, update the order status
        if ($check_data['status_count'] == 1) {
            $order_status_query = "SELECT status FROM order_items WHERE order_id = $order_id LIMIT 1";
            $order_status_result = mysqli_query($dbconn, $order_status_query);
            $order_status_data = mysqli_fetch_assoc($order_status_result);
            
            $order_update_query = "UPDATE orders SET status = '{$order_status_data['status']}' WHERE order_id = $order_id";
            mysqli_query($dbconn, $order_update_query);
        }
        
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Error updating order status: " . mysqli_error($dbconn);
    }
}

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Update the order status
    $update_query = "UPDATE orders SET status = '$status' WHERE order_id = $order_id";
    
    if (mysqli_query($dbconn, $update_query)) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Error updating order status: " . mysqli_error($dbconn);
    }
}

// Fetch orders for the farmer's products
$orders_query = "SELECT oi.order_item_id, oi.quantity, oi.price_per_unit,
                o.order_id, o.order_date, o.status as item_status, 
                p.name as product_name, 
                u.username, u.email, u.phone
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                JOIN orders o ON oi.order_id = o.order_id
                JOIN users u ON o.consumer_id = u.user_id
                WHERE p.seller_id = $farmer_id
                ORDER BY o.order_date DESC";
$orders_result = mysqli_query($dbconn, $orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
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
        .product-img {
            height: 50px;
            width: 50px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-none d-md-block sidebar py-4">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-carrot"></i> My Products</a>
                <a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 ms-auto content-wrapper p-4">
                <h2 class="mb-4">Manage Orders</h2>
                
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <?php if(mysqli_num_rows($orders_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Product</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if(!empty($order['product_image'])): ?>
                                                            <img src="../uploads/products/<?php echo $order['product_image']; ?>" class="product-img rounded me-2" alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                                                        <?php else: ?>
                                                            <img src="../assets/images/product-placeholder.jpg" class="product-img rounded me-2" alt="No image">
                                                        <?php endif; ?>
                                                        <div>
                                                            <?php echo htmlspecialchars($order['product_name']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['username']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                                <td><?php echo $order['quantity']; ?></td>
                                                <td>$<?php echo number_format($order['quantity'] * $order['price_per_unit'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        if($order['item_status'] == 'delivered') echo 'success';
                                                        elseif($order['item_status'] == 'processing') echo 'warning';
                                                        elseif($order['item_status'] == 'canceled') echo 'danger';
                                                        else echo 'secondary';
                                                    ?>">
                                                        <?php echo ucfirst($order['item_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $order['order_item_id']; ?>">
                                                        <i class="fas fa-edit"></i> Update
                                                    </button>
                                                    
                                                    <!-- Update Status Modal -->
                                                    <div class="modal fade" id="updateModal<?php echo $order['order_item_id']; ?>" tabindex="-1" aria-labelledby="updateModalLabel<?php echo $order['order_item_id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="updateModalLabel<?php echo $order['order_item_id']; ?>">Update Order Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="post" action="">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="status" class="form-label">Order Status</label>
                                                                            <select class="form-select" name="status" id="status" required>
                                                                                <option value="processing" <?php echo ($order['item_status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                                                                <option value="shipped" <?php echo ($order['item_status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                                                                <option value="delivered" <?php echo ($order['item_status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                                                <option value="canceled" <?php echo ($order['item_status'] == 'canceled') ? 'selected' : ''; ?>>Canceled</option>
                                                                            </select>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <p><strong>Order Details:</strong></p>
                                                                            <p>Product: <?php echo htmlspecialchars($order['product_name']); ?></p>
                                                                            <p>Quantity: <?php echo $order['quantity']; ?></p>
                                                                            <p>Customer: <?php echo htmlspecialchars($order['username']); ?></p>
                                                                            <p>Contact: <?php echo htmlspecialchars($order['phone']); ?></p>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                <h4>No orders yet</h4>
                                <p class="text-muted">You haven't received any orders for your products yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>