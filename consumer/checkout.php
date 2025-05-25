<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
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
$cart_query = "SELECT c.*, p.name, p.price, p.product_image, p.stock, p.seller_id, 
              u.first_name, u.last_name
              FROM cart c
              JOIN products p ON c.product_id = p.product_id
              JOIN users u ON p.seller_id = u.user_id
              WHERE c.consumer_id = $consumer_id";
$cart_result = mysqli_query($dbconn, $cart_query);

// Check if cart is empty
if (mysqli_num_rows($cart_result) == 0) {
    header("Location: cart.php");
    exit();
}

// Calculate cart totals and group by seller
$cart_items = [];
$sellers = [];
$cart_total = 0;

while ($item = mysqli_fetch_assoc($cart_result)) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $cart_total += $item['subtotal'];
    $cart_items[] = $item;
    
    // Group by seller
    if (!isset($sellers[$item['seller_id']])) {
        $sellers[$item['seller_id']] = [
            'name' => $item['first_name'] . ' ' . $item['last_name'],
            'items' => []
        ];
    }
    
    $sellers[$item['seller_id']]['items'][] = $item;
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_address = mysqli_real_escape_string($dbconn, $_POST['shipping_address']);
    $payment_method = mysqli_real_escape_string($dbconn, $_POST['payment_method']);
    
    // Start transaction
    mysqli_begin_transaction($dbconn);
    
    try {
        // Create order
        $order_query = "INSERT INTO orders (consumer_id, total_amount, shipping_address, payment_method, status, order_date) 
                       VALUES ($consumer_id, $cart_total, '$shipping_address', '$payment_method', 'pending', NOW())";
        
        if (mysqli_query($dbconn, $order_query)) {
            $order_id = mysqli_insert_id($dbconn);
            
            // Add order items
            foreach ($cart_items as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $seller_id = $item['seller_id'];
                
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price_per_unit, subtotal) 
                              VALUES ($order_id, $product_id, $quantity, $price, $quantity * $price)";
                mysqli_query($dbconn, $item_query);
                
                // Update product stock
                $stock_update = "UPDATE products SET stock = stock - $quantity WHERE product_id = $product_id";
                mysqli_query($dbconn, $stock_update);
                
                // Create notification for seller
                $notification_query = "INSERT INTO notifications (user_id, type, message, reference_id, created_at) 
                                      VALUES ($seller_id, 'new_order', 'You have received a new order!', $order_id, NOW())";
                mysqli_query($dbconn, $notification_query);
            }
            
            // Add order status history
            $status_query = "INSERT INTO order_status_history (order_id, status, notes, created_at) 
                            VALUES ($order_id, 'pending', 'Order placed successfully', NOW())";
            mysqli_query($dbconn, $status_query);
            
            // Clear cart
            $clear_cart = "DELETE FROM cart WHERE consumer_id = $consumer_id";
            mysqli_query($dbconn, $clear_cart);
            
            // Commit transaction
            mysqli_commit($dbconn);
            
            // Redirect to order confirmation
            header("Location: order_confirmation.php?id=$order_id");
            exit();
        } else {
            throw new Exception("Error creating order: " . mysqli_error($dbconn));
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($dbconn);
        $error_message = $e->getMessage();
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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .checkout-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Checkout</h1>
            <a href="cart.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Cart
            </a>
        </div>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Order Summary -->
            <div class="col-lg-8 mb-4">
                <div class="card checkout-card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($sellers as $seller_id => $seller): ?>
                            <div class="mb-4">
                                <h6 class="mb-3">Items from <?php echo htmlspecialchars($seller['name']); ?></h6>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <?php foreach($seller['items'] as $item): ?>
                                                <tr>
                                                    <td width="70">
                                                        <?php if(!empty($item['product_image'])): ?>
                                                            <img src="../uploads/products/<?php echo $item['product_image']; ?>" class="product-img" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                        <?php else: ?>
                                                            <img src="../assets/images/product-placeholder.jpg" class="product-img" alt="Product Image">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                                    </td>
                                                    <td class="text-end">$<?php echo number_format($item['subtotal'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <h5>Total</h5>
                            <h5>रू<?php echo number_format($cart_total, 2); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="col-lg-4">
                <div class="card checkout-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="checkout.php" method="post">
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($consumer['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Payment Method</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_cod" value="cash_on_delivery" checked>
                                    <label class="form-check-label" for="payment_cod">
                                        Cash on Delivery
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_bank" value="bank_transfer">
                                    <label class="form-check-label" for="payment_bank">
                                        Bank Transfer
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-success w-100">
                                <i class="fas fa-check-circle me-2"></i>Place Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>