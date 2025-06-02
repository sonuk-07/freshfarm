<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
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
$items_query = "SELECT oi.*, p.name, p.product_image ,p.price
               FROM order_items oi
               JOIN products p ON oi.product_id = p.product_id
               WHERE oi.order_id = $order_id";
$items_result = mysqli_query($dbconn, $items_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }

        .confirmation-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #d1e7dd;
            color: #0f5132;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card confirmation-card mb-4">
                    <div class="card-body text-center py-5">
                        <div class="success-icon mb-4">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </div>
                        <h2 class="mb-3">Order Placed Successfully!</h2>
                        <p class="text-muted mb-4">Thank you for your order. Your order has been received and is now
                            being processed.</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                                <i class="fas fa-info-circle me-2"></i>View Order Details
                            </a>
                            <a href="products.php" class="btn btn-outline-success">
                                <i class="fas fa-shopping-basket me-2"></i>Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card confirmation-card mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6 class="text-muted mb-2">Order Number</h6>
                                <p class="mb-0 fw-bold">#<?php echo str_pad($order_id, 8, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Order Date</h6>
                                <p class="mb-0"><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?>
                                </p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6 class="text-muted mb-2">Payment Method</h6>
                                <p class="mb-0"><?php echo ucfirst($order['payment_method']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Order Status</h6>
                                <span class="badge bg-warning text-dark"><?php echo ucfirst($order['status']); ?></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Shipping Address</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>

                        <h6 class="mb-3">Order Items</h6>
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Product</th>
                                        <th scope="col" class="text-center">Quantity</th>
                                        <th scope="col" class="text-end">Price</th>
                                        <th scope="col" class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $subtotal = 0;
                                    while ($item = mysqli_fetch_assoc($items_result)):
                                        $item_total = $item['price'] * $item['quantity'];
                                        $subtotal += $item_total;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['product_image'])): ?>
                                                        <img src="../uploads/products/<?php echo $item['product_image']; ?>"
                                                            class="product-img me-3"
                                                            alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <?php else: ?>
                                                        <img src="../assets/images/product-placeholder.jpg"
                                                            class="product-img me-3" alt="Product Image">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">रू<?php echo number_format($item['price'], 2); ?></td>
                                            <td class="text-end">Rs.<?php echo number_format($item_total, 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total</strong></td>
                                        <td class="text-end">
                                            <strong>Rs.<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-4">
                    <p class="mb-3">Have questions about your order?</p>
                    <a href="#" class="btn btn-outline-secondary">
                        <i class="fas fa-question-circle me-2"></i>Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>