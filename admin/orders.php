<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/index.php");
    exit();
}

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Update the order status
    $update_query = "UPDATE orders SET status = '$status' WHERE order_id = $order_id";
    
    if (mysqli_query($dbconn, $update_query)) {
        // Also update all order items with the same status
        $update_items_query = "UPDATE order_items SET status = '$status' WHERE order_id = $order_id";
        mysqli_query($dbconn, $update_items_query);
        
        // Add to order status history if the table exists
        $check_table_query = "SHOW TABLES LIKE 'order_status_history'";
        $check_table_result = mysqli_query($dbconn, $check_table_query);
        
        if (mysqli_num_rows($check_table_result) > 0) {
            $history_query = "INSERT INTO order_status_history (order_id, status, notes) 
                              VALUES ($order_id, '$status', 'Status updated by admin')";
            mysqli_query($dbconn, $history_query);
        }
        
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Error updating order status: " . mysqli_error($dbconn);
    }
}

// Fetch all orders with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($dbconn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($dbconn, $_GET['status']) : '';

// Build the query with filters
$query = "SELECT o.*, u.username, u.email 
         FROM orders o 
         JOIN users u ON o.consumer_id = u.user_id 
         WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (o.order_id LIKE '%$search%' OR u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $query .= " AND o.status = '$status_filter'";
}

// Count total records for pagination
$count_query = str_replace("SELECT o.*, u.username, u.email", "SELECT COUNT(*) as total", $query);
$count_result = mysqli_query($dbconn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $limit);

// Add pagination to the main query
$query .= " ORDER BY o.order_date DESC LIMIT $offset, $limit";
$orders_result = mysqli_query($dbconn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .product-placeholder {
            width: 60px;
            height: 60px;
            background-color: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <h2 class="text-center text-white py-3">Admin Panel</h2>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="users.php"><i class="fas fa-users me-2"></i> Users</a>
                <a href="products.php"><i class="fas fa-box me-2"></i> Products</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> Categories</a>
                <a href="orders.php" class="active"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
                <a href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2 class="mb-4">Manage Orders</h2>
                
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="orders.php" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" placeholder="Search by order ID or customer..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="canceled" <?php echo $status_filter == 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($orders_result) > 0): ?>
                                        <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($order['username']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        if($order['status'] == 'delivered') echo 'success';
                                                        elseif($order['status'] == 'processing') echo 'warning';
                                                        elseif($order['status'] == 'shipped') echo 'primary';
                                                        elseif($order['status'] == 'canceled') echo 'danger';
                                                        else echo 'secondary';
                                                    ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $order['order_id']; ?>">
                                                        <i class="fas fa-edit"></i> Update
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $order['order_id']; ?>">
                                                        <i class="fas fa-eye"></i> Details
                                                    </button>
                                                    
                                                    <!-- Update Status Modal -->
                                                    <div class="modal fade" id="updateModal<?php echo $order['order_id']; ?>" tabindex="-1" aria-labelledby="updateModalLabel<?php echo $order['order_id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="updateModalLabel<?php echo $order['order_id']; ?>">Update Order Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="post" action="">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="status" class="form-label">Order Status</label>
                                                                            <select class="form-select" name="status" id="status" required>
                                                                                <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                                <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                                                                <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                                                                <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                                                <option value="canceled" <?php echo ($order['status'] == 'canceled') ? 'selected' : ''; ?>>Canceled</option>
                                                                            </select>
                                                                        </div>
                                                                        
                                                                        <div class="alert alert-warning">
                                                                            <small><i class="fas fa-info-circle me-1"></i> Changing the status will update all items in this order.</small>
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
                                                    
                                                    <!-- Order Details Modal -->
                                                    <div class="modal fade" id="detailsModal<?php echo $order['order_id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $order['order_id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="detailsModalLabel<?php echo $order['order_id']; ?>">Order #<?php echo $order['order_id']; ?> Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <h6>Order Information</h6>
                                                                            <p class="mb-1">Date: <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                                                                            <p class="mb-1">Status: 
                                                                                <span class="badge bg-<?php 
                                                                                    if($order['status'] == 'delivered') echo 'success';
                                                                                    elseif($order['status'] == 'processing') echo 'warning';
                                                                                    elseif($order['status'] == 'shipped') echo 'primary';
                                                                                    elseif($order['status'] == 'canceled') echo 'danger';
                                                                                    else echo 'secondary';
                                                                                ?>">
                                                                                    <?php echo ucfirst($order['status']); ?>
                                                                                </span>
                                                                            </p>
                                                                            <p class="mb-1">Total: $<?php echo number_format($order['total_amount'], 2); ?></p>
                                                                            <p class="mb-1">Payment Method: <?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></p>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <h6>Customer Information</h6>
                                                                            <p class="mb-1">Name: <?php echo htmlspecialchars($order['username']); ?></p>
                                                                            <p class="mb-1">Email: <?php echo htmlspecialchars($order['email']); ?></p>
                                                                            <p class="mb-1">Shipping Address:</p>
                                                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? 'N/A')); ?></p>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <h6>Order Items</h6>
                                                                    <?php
                                                                    // Fetch order items
                                                                    $items_query = "SELECT oi.*, p.name, p.product_image, u.username as seller_name 
                                                                                   FROM order_items oi
                                                                                   JOIN products p ON oi.product_id = p.product_id
                                                                                   JOIN users u ON p.seller_id = u.user_id
                                                                                   WHERE oi.order_id = {$order['order_id']}";
                                                                    $items_result = mysqli_query($dbconn, $items_query);
                                                                    ?>
                                                                    
                                                                    <?php if(mysqli_num_rows($items_result) > 0): ?>
                                                                        <div class="table-responsive">
                                                                            <table class="table table-bordered table-sm">
                                                                                <thead class="table-light">
                                                                                    <tr>
                                                                                        <th>Product</th>
                                                                                        <th>Seller</th>
                                                                                        <th>Price</th>
                                                                                        <th>Quantity</th>
                                                                                        <th>Subtotal</th>
                                                                                        <th>Status</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <div class="d-flex align-items-center">
                                                                                                    <?php if(!empty($item['product_image'])): ?>
                                                                                                        <img src="../uploads/products/<?php echo $item['product_image']; ?>" class="product-image me-2" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                                                                    <?php else: ?>
                                                                                                        <div class="product-placeholder me-2">
                                                                                                            <i class="fas fa-image text-muted"></i>
                                                                                                        </div>
                                                                                                    <?php endif; ?>
                                                                                                    <div>
                                                                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </td>
                                                                                            <td><?php echo htmlspecialchars($item['seller_name']); ?></td>
                                                                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                                                            <td><?php echo $item['quantity']; ?></td>
                                                                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                                                            <td>
                                                                                                <span class="badge bg-<?php 
                                                                                                    if($item['status'] == 'delivered') echo 'success';
                                                                                                    elseif($item['status'] == 'processing') echo 'warning';
                                                                                                    elseif($item['status'] == 'shipped') echo 'primary';
                                                                                                    elseif($item['status'] == 'canceled') echo 'danger';
                                                                                                    else echo 'secondary';
                                                                                                ?>">
                                                                                                    <?php echo ucfirst($item['status']); ?>
                                                                                                </span>
                                                                                            </td>
                                                                                        </tr>
                                                                                    <?php endwhile; ?>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="alert alert-info">No items found for this order.</div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No orders found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>