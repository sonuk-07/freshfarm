<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/index.php");
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
$cart_query = "SELECT c.*, p.name, p.price, p.image_url as product_image, p.quantity_available as stock, 
              p.seller_id, u.first_name, u.last_name
              FROM cart c
              JOIN products p ON c.product_id = p.product_id
              JOIN users u ON p.seller_id = u.user_id
              WHERE c.consumer_id = $consumer_id";
$cart_result = mysqli_query($dbconn, $cart_query);

if (mysqli_num_rows($cart_result) == 0) {
    header("Location: cart.php");
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
            'items' => []
        ];
    }
    $sellers[$item['seller_id']]['items'][] = $item;
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

                $stock_update = "UPDATE products SET quantity_available = quantity_available - $quantity WHERE product_id = $product_id";
                mysqli_query($dbconn, $stock_update);

                $notification_query = "INSERT INTO notifications (user_id, type, message, reference_id, created_at) 
                                       VALUES ($seller_id, 'new_order', 'You have received a new order!', $order_id, NOW())";
                mysqli_query($dbconn, $notification_query);
            }

            $status_query = "INSERT INTO order_status_history (order_id, status, notes, created_at) 
                             VALUES ($order_id, 'pending', 'Order placed successfully', NOW())";
            mysqli_query($dbconn, $status_query);

            $clear_cart = "DELETE FROM cart WHERE consumer_id = $consumer_id";
            mysqli_query($dbconn, $clear_cart);

            mysqli_commit($dbconn);

            if ($payment_method === 'esewa') {
                include '../config/esewa_config.php';

                $transaction_uuid = uniqid();
                $total_amount = $cart_total;
                $amount = $cart_total;
                $tax_amount = 0;

                $message = "total_amount=$total_amount,transaction_uuid=$transaction_uuid,product_code=" . ESEWA_PRODUCT_CODE;
                $signature = base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET, true));

                echo "<form id='esewaForm' action='" . ESEWA_API_URL . "' method='POST'>
                    <input type='hidden' name='amount' value='$amount'>
                    <input type='hidden' name='tax_amount' value='$tax_amount'>
                    <input type='hidden' name='total_amount' value='$total_amount'>
                    <input type='hidden' name='transaction_uuid' value='$transaction_uuid'>
                    <input type='hidden' name='product_code' value='" . ESEWA_PRODUCT_CODE . "'>
                    <input type='hidden' name='product_service_charge' value='0'>
                    <input type='hidden' name='product_delivery_charge' value='0'>
                    <input type='hidden' name='success_url' value='" . ESEWA_SUCCESS_URL . "'>
                    <input type='hidden' name='failure_url' value='" . ESEWA_FAILURE_URL . "'>
                    <input type='hidden' name='signed_field_names' value='total_amount,transaction_uuid,product_code'>
                    <input type='hidden' name='signature' value='$signature'>
                </form>
                <script>document.getElementById('esewaForm').submit();</script>";
                exit();
            } else {
                header("Location: order_confirmation.php?id=$order_id");
                exit();
            }
        }
    } catch (Exception $e) {
        mysqli_rollback($dbconn);
        $error_message = "Order failed: " . $e->getMessage();
    }
}
?>

<!-- HTML Part Starts -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-4">
    <h1 class="mb-4">Checkout</h1>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Order Summary -->
            <?php foreach ($sellers as $seller): ?>
                <h5>From: <?php echo htmlspecialchars($seller['name']); ?></h5>
                <ul class="list-group mb-3">
                    <?php foreach ($seller['items'] as $item): ?>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <div>
                                <h6 class="my-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                            </div>
                            <span>रू<?php echo number_format($item['subtotal'], 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
            <h4>Total: रू<?php echo number_format($cart_total, 2); ?></h4>
        </div>

        <div class="col-md-4">
            <!-- Payment Form -->
            <form method="POST">
                <div class="mb-3">
                    <label for="shipping_address" class="form-label">Shipping Address</label>
                    <textarea name="shipping_address" class="form-control" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Select Payment Method</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" value="cod" id="cod" checked>
                        <label class="form-check-label" for="cod">Cash on Delivery</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" value="esewa" id="payment_esewa">
                        <label class="form-check-label" for="payment_esewa">
                            <img src="../assets/images/esewa-logo.png" alt="eSewa" height="25"> eSewa
                        </label>
                    </div>
                </div>

                <button type="submit" name="place_order" class="btn btn-success w-100">Place Order</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
