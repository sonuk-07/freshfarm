<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = (int) $_POST['order_id'];
    $status = mysqli_real_escape_string($dbconn, $_POST['status']);
    $notes = mysqli_real_escape_string($dbconn, $_POST['notes'] ?? '');

    // Update order status
    $update_query = "UPDATE orders SET status = '$status' WHERE order_id = $order_id";
    
    if (mysqli_query($dbconn, $update_query)) {
        // Add to status history
        $history_query = "INSERT INTO order_status_history (order_id, status, notes) VALUES ($order_id, '$status', '$notes')";
        mysqli_query($dbconn, $history_query);
        $_SESSION['success'] = "Order status updated successfully";
    } else {
        $_SESSION['error'] = "Error updating order status";
    }
}

// Fetch all orders with related information
$orders_query = "SELECT o.*, 
                        u.username as consumer_name,
                        COUNT(oi.order_item_id) as total_items,
                        GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items_list
                 FROM orders o
                 JOIN users u ON o.consumer_id = u.user_id
                 LEFT JOIN order_items oi ON o.order_id = oi.order_id
                 LEFT JOIN products p ON oi.product_id = p.product_id
                 GROUP BY o.order_id
                 ORDER BY o.order_date DESC";
$orders_result = mysqli_query($dbconn, $orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin Dashboard</title>
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

        .header {
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #64748b;
        }

        .orders-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }

        .orders-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .orders-table tbody tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background: #fef3c7; color: #d97706; }
        .status-processing { background: #dbeafe; color: #2563eb; }
        .status-shipped { background: #e0e7ff; color: #4f46e5; }
        .status-delivered { background: #dcfce7; color: #16a34a; }
        .status-canceled { background: #fee2e2; color: #dc2626; }

        .btn-status {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-status:hover {
            opacity: 0.9;
        }

        .items-list {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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


        @media (max-width: 1024px) {
            .orders-table {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="header">
            <h1>Order Management</h1>
            <p>Monitor and manage all orders across the platform</p>
        </div>
        <!-- Navigation Tabs -->
        <div class="navigation-tabs">
            <a href="dashboard.php" class="nav-tab" >Overview</a>
            <a href="users.php" class="nav-tab">User Management</a>
            <a href="products.php" class="nav-tab">Product Management</a>
            <a href="orders.php" class="nav-tab active">Order Management</a>
            <a href="reviews.php" class="nav-tab">Review Moderation</a>
        </div>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Consumer</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['consumer_name']); ?></td>
                            <td class="items-list" title="<?php echo htmlspecialchars($order['items_list']); ?>">
                                <?php echo htmlspecialchars($order['items_list']); ?>
                            </td>
                            <td>Rs.<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <form action="" method="POST" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="btn-status">
                                        <option value="">Update Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="canceled">Canceled</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>