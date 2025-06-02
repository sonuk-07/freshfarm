<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['order_item_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $order_item_id = $_POST['order_item_id'];
    $status = $_POST['status'];
    
    // Update the order item status
    $update_query = "UPDATE orders SET status = '$status' WHERE order_id = $order_item_id";
    
    if (mysqli_query($dbconn, $update_query)) {
        // Check if all items in the order have the same status
        $check_query = "SELECT COUNT(DISTINCT status) as status_count FROM orders WHERE order_id = $order_id";
        $check_result = mysqli_query($dbconn, $check_query);
        $check_data = mysqli_fetch_assoc($check_result);
        
        // If all items have the same status, update the order status
        if ($check_data['status_count'] == 1) {
            $order_status_query = "SELECT status FROM orders WHERE order_id = $order_id LIMIT 1";
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
                p.name as product_name, p.product_image,
                u.username, u.email, u.phone
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                JOIN orders o ON oi.order_id = o.order_id
                JOIN users u ON o.consumer_id = u.user_id
                WHERE p.seller_id = $farmer_id
                ORDER BY o.order_date DESC";
$orders_result = mysqli_query($dbconn, $orders_query);

// Get farmer name for welcome message - try different possible column names
$farmer_query = "SELECT * FROM users WHERE user_id = $farmer_id LIMIT 1";
$farmer_result = mysqli_query($dbconn, $farmer_query);
$farmer_data = mysqli_fetch_assoc($farmer_result);

// Try different possible column names for the farmer's name
$farmer_name = 'Farmer'; // Default fallback
if (isset($farmer_data['name'])) {
    $farmer_name = explode(' ', $farmer_data['name'])[0];
} elseif (isset($farmer_data['username'])) {
    $farmer_name = $farmer_data['username'];
} elseif (isset($farmer_data['first_name'])) {
    $farmer_name = $farmer_data['first_name'];
} elseif (isset($farmer_data['full_name'])) {
    $farmer_name = explode(' ', $farmer_data['full_name'])[0];
}

// Calculate dashboard stats
$total_orders = mysqli_num_rows($orders_result);

// Calculate total sales
$total_sales = 0;
$orders_copy = $orders_result; // Create a copy to iterate through
while ($order = mysqli_fetch_assoc($orders_copy)) {
    $total_sales += $order['quantity'] * $order['price_per_unit'];
}

// Reset orders result for display
mysqli_data_seek($orders_result, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #22c55e;
            --secondary-color: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-dark);
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-section {
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: var(--text-muted);
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .stat-icon.sales { background: #dcfce7; color: var(--success-color); }
        .stat-icon.orders { background: #dbeafe; color: #3b82f6; }
        .stat-icon.products { background: #fef3c7; color: var(--warning-color); }
        .stat-icon.growth { background: #f3e8ff; color: #8b5cf6; }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .orders-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th {
            background-color: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-color);
        }

        .orders-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }

        .product-info {
            display: flex;
            align-items: center;
        }

        .product-name {
            font-weight: 500;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
        }

        .customer-name {
            font-weight: 500;
        }

        .customer-email {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .order-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-processing {
            background: #fef3c7;
            color: var(--warning-color);
        }

        .status-shipped {
            background: #dbeafe;
            color: #3b82f6;
        }

        .status-delivered {
            background: #dcfce7;
            color: var(--success-color);
        }

        .status-canceled {
            background: #fecaca;
            color: var(--danger-color);
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-update {
            background: #dbeafe;
            color: #3b82f6;
        }

        .btn-update:hover {
            background: #bfdbfe;
            color: #2563eb;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .empty-description {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: none;
        }

        .alert-success {
            background: #dcfce7;
            color: var(--success-color);
        }

        .alert-danger {
            background: #fecaca;
            color: var(--danger-color);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .orders-table {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($farmer_name); ?>!</h1>
            <p class="welcome-subtitle">Manage your orders and track your sales</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon sales">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-value">रू<?php echo number_format($total_sales, 2); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon products">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-label">Delivered Orders</div>
                <div class="stat-value">0</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon growth">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-label">This Month</div>
                <div class="stat-value">+12%</div>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="section-header">
            <h2 class="section-title">My Orders</h2>
        </div>

        <?php if (mysqli_num_rows($orders_result) > 0): ?>
            <div class="orders-table">
                <table>
                    <thead>
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
                                    <div class="product-info">
                                        <?php if(!empty($order['product_image'])): ?>
                                            <img src="../uploads/products/<?php echo $order['product_image']; ?>" class="product-img" alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                                        <?php else: ?>
                                            <div class="product-img" style="display: flex; align-items: center; justify-content: center; background-color: #f1f5f9; color: var(--text-muted);">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name"><?php echo htmlspecialchars($order['username']); ?></span>
                                        <span class="customer-email"><?php echo htmlspecialchars($order['email']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>रू<?php echo number_format($order['quantity'] * $order['price_per_unit'], 2); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($order['item_status']) {
                                        case 'processing':
                                            $status_class = 'status-processing';
                                            break;
                                        case 'shipped':
                                            $status_class = 'status-shipped';
                                            break;
                                        case 'delivered':
                                            $status_class = 'status-delivered';
                                            break;
                                        case 'canceled':
                                            $status_class = 'status-canceled';
                                            break;
                                        default:
                                            $status_class = 'status-processing';
                                    }
                                    ?>
                                    <span class="order-status <?php echo $status_class; ?>">
                                        <?php echo ucfirst($order['item_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="action-btn btn-update" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $order['order_item_id']; ?>">
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
                                                        <input type="hidden" name="order_item_id" value="<?php echo $order['order_item_id']; ?>">
                                                        
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
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="empty-title">No orders yet</h3>
                <p class="empty-description">You haven't received any orders for your products yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>