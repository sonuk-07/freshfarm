<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch consumer details
$consumer_query = "SELECT * FROM users WHERE user_id = $consumer_id";
$consumer_result = mysqli_query($dbconn, $consumer_query);
$consumer = mysqli_fetch_assoc($consumer_result);

// Fetch cart items
$cart_query = "SELECT c.*, p.name, p.price, p.product_image as product_image, p.stock as stock,
              p.seller_id, u.first_name, u.last_name
              FROM cart c
              JOIN products p ON c.product_id = p.product_id
              JOIN users u ON p.seller_id = u.user_id
              WHERE c.consumer_id = $consumer_id";
$cart_result = mysqli_query($dbconn, $cart_query);

if (mysqli_num_rows($cart_result) == 0) {
    // If cart is empty, redirect to cart page (or products page)
    $_SESSION['cart_error'] = "Your cart is empty. Please add items before checking out.";
    header("Location: cart.php"); // Or products.php
    exit();
}

$cart_items = [];
$sellers = [];
$cart_total = 0;

while ($item = mysqli_fetch_assoc($cart_result)) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $cart_total += $item['subtotal'];
    $cart_items[] = $item;

    if (!isset($sellers[$item['seller_id']])) {
        $sellers[$item['seller_id']] = [
            'name' => $item['first_name'] . ' ' . $item['last_name'],
            'items' => [],
            'seller_subtotal' => 0 // To calculate subtotal per seller
        ];
    }
    $sellers[$item['seller_id']]['items'][] = $item;
    $sellers[$item['seller_id']]['seller_subtotal'] += $item['subtotal'];
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_address = mysqli_real_escape_string($dbconn, $_POST['shipping_address']);
    $payment_method = mysqli_real_escape_string($dbconn, $_POST['payment_method']);

    mysqli_begin_transaction($dbconn);
    try {
        $order_query = "INSERT INTO orders (consumer_id, total_amount, shipping_address, payment_method, status, order_date)
                        VALUES ($consumer_id, $cart_total, '$shipping_address', '$payment_method', 'pending', NOW())";
        if (mysqli_query($dbconn, $order_query)) {
            $order_id = mysqli_insert_id($dbconn);
            $_SESSION['pending_order_id'] = $order_id;

            foreach ($cart_items as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $subtotal = $item['subtotal'];
                $seller_id = $item['seller_id'];

                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price_per_unit, subtotal)
                               VALUES ($order_id, $product_id, $quantity, $price, $subtotal)";
                mysqli_query($dbconn, $item_query);

                // Update product stock
                $stock_update = "UPDATE products SET stock = stock - $quantity WHERE product_id = $product_id";
                mysqli_query($dbconn, $stock_update);

                // Add notification for seller
                $notification_query = "INSERT INTO notifications (user_id, type, message, reference_id, created_at)
                                         VALUES ($seller_id, 'new_order', 'You have received a new order for product: " . mysqli_real_escape_string($dbconn, $item['name']) . " (Qty: " . $quantity . ")', $order_id, NOW())";
                mysqli_query($dbconn, $notification_query);
            }

            // Add order status history
            $status_query = "INSERT INTO order_status_history (order_id, status, notes, created_at)
                             VALUES ($order_id, 'pending', 'Order placed successfully', NOW())";
            mysqli_query($dbconn, $status_query);

            // Clear cart
            $clear_cart = "DELETE FROM cart WHERE consumer_id = $consumer_id";
            mysqli_query($dbconn, $clear_cart);

            mysqli_commit($dbconn);

            if ($payment_method === 'esewa') {
                include '../config/esewa_config.php'; // Ensure this file exists and has the necessary constants

                $transaction_uuid = uniqid(); // Unique transaction ID
                $total_amount_esewa = $cart_total; // Use a distinct variable for eSewa total
                $amount_esewa = $cart_total;
                $tax_amount = 0; // Assuming no tax for simplicity, adjust as needed
                $service_charge = 0; // Assuming no service charge
                $delivery_charge = 0; // Assuming no delivery charge

                $message = "total_amount=$total_amount_esewa,transaction_uuid=$transaction_uuid,product_code=" . ESEWA_PRODUCT_CODE;
                $signature = base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET, true));

                // Store eSewa transaction details in session for verification on callback
                $_SESSION['esewa_transaction'] = [
                    'order_id' => $order_id,
                    'transaction_uuid' => $transaction_uuid,
                    'total_amount' => $total_amount_esewa,
                ];

                echo "<form id='esewaForm' action='" . ESEWA_API_URL . "' method='POST'>
                        <input type='hidden' name='amount' value='$amount_esewa'>
                        <input type='hidden' name='tax_amount' value='$tax_amount'>
                        <input type='hidden' name='total_amount' value='$total_amount_esewa'>
                        <input type='hidden' name='transaction_uuid' value='$transaction_uuid'>
                        <input type='hidden' name='product_code' value='" . ESEWA_PRODUCT_CODE . "'>
                        <input type='hidden' name='product_service_charge' value='$service_charge'>
                        <input type='hidden' name='product_delivery_charge' value='$delivery_charge'>
                        <input type='hidden' name='success_url' value='" . ESEWA_SUCCESS_URL . "'>
                        <input type='hidden' name='failure_url' value='" . ESEWA_FAILURE_URL . "'>
                        <input type='hidden' name='signed_field_names' value='total_amount,transaction_uuid,product_code'>
                        <input type='hidden' name='signature' value='$signature'>
                      </form>
                      <script>document.getElementById('esewaForm').submit();</script>";
                exit();
            } else {
                $_SESSION['order_success_message'] = "Your order has been placed successfully!";
                header("Location: order_confirmation.php?id=$order_id");
                exit();
            }
        } else {
            throw new Exception("Failed to create order: " . mysqli_error($dbconn));
        }
    } catch (Exception $e) {
        mysqli_rollback($dbconn);
        $error_message = "Order failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General styles, consistent with products.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }

        .main-header {
            background: white;
            padding: 2.5rem 0 2rem;
            border-bottom: 1px solid #eee;
            margin-bottom: 2rem; /* Add margin to separate from content */
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0; /* No bottom margin, if only title */
        }

        .container.py-4 {
            padding-top: 2rem !important;
            padding-bottom: 4rem !important;
        }

        /* Card styles */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            padding: 1.5rem; /* Consistent padding */
            margin-bottom: 1.5rem; /* Space between cards */
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
        }

        /* Form elements */
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #555;
        }

        .form-control,
        .form-select,
        .form-check-input {
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: none;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #52c41a;
            box-shadow: 0 0 0 3px rgba(82, 196, 26, 0.1);
        }

        textarea.form-control {
            min-height: 100px; /* Make textarea taller */
        }

        .form-check {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
            margin-top: 0.25rem;
            margin-right: 0.75rem;
            border-radius: 50%; /* Make radio buttons circular */
        }

        .form-check-input:checked {
            background-color: #52c41a;
            border-color: #52c41a;
        }

        .form-check-label {
            display: flex;
            align-items: center;
            font-size: 1rem;
            color: #555;
            cursor: pointer;
        }

        .form-check-label img {
            margin-left: 0.5rem; /* Space for eSewa logo */
            vertical-align: middle;
        }

        /* Buttons */
        .btn-success {
            background: #52c41a;
            border: none;
            border-radius: 8px;
            padding: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            margin-top: 1rem; /* Space above button */
        }

        .btn-success:hover {
            background: #389e0d;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(82, 196, 26, 0.2);
        }

        /* Order Summary List */
        .list-group-item {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            padding: 1rem 1.25rem;
            background-color: #fcfcfc; /* Slightly off-white for list items */
        }

        .list-group-item:last-child {
            margin-bottom: 0;
        }

        .list-group-item h6.my-0 {
            font-size: 1rem;
            font-weight: 500;
            color: #333;
        }

        .list-group-item small.text-muted {
            font-size: 0.85rem;
            color: #888 !important;
        }

        .list-group-item span {
            font-weight: 600;
            color: #2d5016; /* Green for price */
            font-size: 1rem;
        }

        .order-total-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            margin-top: 1rem;
            border-top: 1px solid #eee;
        }

        .order-total-section h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d5016;
        }

        /* Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fff2f0;
            color: #a8071a;
            border: 1px solid #ffccc7;
        }

        /* Seller Grouping Header */
        .seller-group-header {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #52c41a; /* Green underline */
            display: inline-block; /* To make underline fit content */
            padding-bottom: 0.5rem;
        }

        .seller-subtotal {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d5016;
            text-align: right;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px dashed #eee;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            .card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="main-header">
        <div class="container">
            <h1 class="page-title">Checkout</h1>
        </div>
    </div>

    <div class="container py-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-7 mb-4">
                <div class="card">
                    <div class="card-header">Order Summary</div>
                    <div class="card-body p-0">
                        <?php foreach ($sellers as $seller_id => $seller): ?>
                            <h5 class="seller-group-header mt-3 mb-2 px-3">From: <?php echo htmlspecialchars($seller['name']); ?></h5>
                            <ul class="list-group list-group-flush mb-2">
                                <?php foreach ($seller['items'] as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                           
                                            <div>
                                                <h6 class="my-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?> x रू<?php echo number_format($item['price'], 2); ?>/unit</small>
                                            </div>
                                        </div>
                                        <span class="text-nowrap">रू<?php echo number_format($item['subtotal'], 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="seller-subtotal px-3">
                                Seller Subtotal: रू<?php echo number_format($seller['seller_subtotal'], 2); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-total-section px-3">
                        <h4>Grand Total:</h4>
                        <h4>रू<?php echo number_format($cart_total, 2); ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">Shipping & Payment</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address</label>
                                <textarea name="shipping_address" id="shipping_address" class="form-control" rows="4" required><?php echo htmlspecialchars($consumer['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Select Payment Method</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" value="cod" id="cod" checked>
                                    <label class="form-check-label" for="cod">
                                        <i class="fas fa-money-bill-wave me-2"></i> Cash on Delivery
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="radio" name="payment_method" value="esewa" id="payment_esewa">
                                    <label class="form-check-label" for="payment_esewa">
                                        <img src="../assets/images/esewa.png" alt="eSewa" height="25" class="me-2"> eSewa
                                    </label>
                                </div>
                            </div>

                            <button type="submit" name="place_order" class="btn btn-success w-100">Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>