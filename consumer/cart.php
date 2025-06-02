<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $cart_id => $quantity) {
            $cart_id = (int) $cart_id;
            $quantity = (int) $quantity;

            if ($quantity > 0) {
                // Check product stock before updating
                $stock_check = "SELECT p.stock FROM cart c 
                              JOIN products p ON c.product_id = p.product_id 
                              WHERE c.cart_id = $cart_id AND c.consumer_id = $consumer_id";
                $stock_result = mysqli_query($dbconn, $stock_check);
                $stock_data = mysqli_fetch_assoc($stock_result);

                if ($quantity > $stock_data['stock']) {
                    $quantity = $stock_data['stock'];
                }

                $update_query = "UPDATE cart SET quantity = $quantity WHERE cart_id = $cart_id AND consumer_id = $consumer_id";
                mysqli_query($dbconn, $update_query);
            } else {
                // Remove item if quantity is 0
                $delete_query = "DELETE FROM cart WHERE cart_id = $cart_id AND consumer_id = $consumer_id";
                mysqli_query($dbconn, $delete_query);
            }
        }

        $_SESSION['cart_message'] = "Cart updated successfully!";
        header("Location: cart.php");
        exit();
    }

    if (isset($_POST['remove_item']) && isset($_POST['cart_id'])) {
        $cart_id = (int) $_POST['cart_id'];
        $delete_query = "DELETE FROM cart WHERE cart_id = $cart_id AND consumer_id = $consumer_id";

        if (mysqli_query($dbconn, $delete_query)) {
            $_SESSION['cart_message'] = "Item removed from cart!";
        } else {
            $_SESSION['cart_error'] = "Error removing item: " . mysqli_error($dbconn);
        }

        header("Location: cart.php");
        exit();
    }
}

// Fetch cart items
$cart_query = "SELECT c.*, p.name, p.price, p.product_image, p.stock, p.seller_id, 
              u.first_name, u.last_name
              FROM cart c
              JOIN products p ON c.product_id = p.product_id
              JOIN users u ON p.seller_id = u.user_id
              WHERE c.consumer_id = $consumer_id";
$cart_result = mysqli_query($dbconn, $cart_query);

// Calculate cart total
$cart_total = 0;
$cart_items = [];
while ($item = mysqli_fetch_assoc($cart_result)) {
    $cart_items[] = $item;
    $cart_total += $item['price'] * $item['quantity'];
}

// Success/error messages
$success_message = '';
$error_message = '';
if (isset($_SESSION['cart_message'])) {
    $success_message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
if (isset($_SESSION['cart_error'])) {
    $error_message = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }



        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .cart-header {
            margin-bottom: 2rem;
        }

        .cart-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0;
        }

        .cart-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            align-items: start;
        }

        .cart-items {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .cart-items-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .cart-items-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .cart-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .product-seller {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1rem;
            font-weight: 600;
            color: #22c55e;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 1rem;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quantity-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .quantity-display {
            min-width: 40px;
            text-align: center;
            font-weight: 500;
        }

        .remove-btn {
            color: #dc2626;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .remove-btn:hover {
            background: #fef2f2;
        }

        .order-summary {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .order-summary h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .summary-row:last-of-type {
            margin-bottom: 1.5rem;
        }

        .summary-label {
            color: #64748b;
            font-size: 0.875rem;
        }

        .summary-value {
            font-weight: 500;
            color: #1e293b;
        }

        .total-row {
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .total-label {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }

        .total-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1e293b;
        }

        .checkout-btn {
            width: 100%;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }

        .checkout-btn:hover {
            background: #16a34a;
            transform: translateY(-1px);
        }

        .continue-shopping {
            width: 100%;
            background: none;
            color: #22c55e;
            border: 1px solid #22c55e;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .continue-shopping:hover {
            background: #f0fdf4;
            color: #16a34a;
            text-decoration: none;
        }

        .empty-cart {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 4rem 2rem;
            text-align: center;
            grid-column: 1 / -1;
        }

        .empty-cart-icon {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-cart h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .empty-cart p {
            color: #64748b;
            margin-bottom: 2rem;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-left: 4px solid #22c55e;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }

        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .product-image {
                width: 100%;
                height: 200px;
            }

            .quantity-controls {
                margin-right: 0;
            }
        }
    </style>
</head>

<body>

<?php include '../includes/navbar.php'; ?>
    <div class="cart-container">
        <div class="cart-header">
            <h1>Shopping Cart</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
            <div class="cart-content">
                <!-- Cart Items -->
                <div class="cart-items">
                    <div class="cart-items-header">
                        <h3>Cart Items (<?php echo count($cart_items); ?>)</h3>
                    </div>
                    
                    <form action="cart.php" method="post" id="cartForm">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <?php if (!empty($item['product_image'])): ?>
                                    <img src="../uploads/products/<?php echo $item['product_image']; ?>"
                                         class="product-image"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="product-image"></div>
                                <?php endif; ?>
                                
                                <div class="product-details">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-seller">by <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></div>
                                    <div class="product-price">रू<?php echo number_format($item['price'], 2); ?></div>
                                </div>
                                
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1, <?php echo $item['stock']; ?>)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <div class="quantity-display" id="qty-<?php echo $item['cart_id']; ?>"><?php echo $item['quantity']; ?></div>
                                    <input type="hidden" name="quantity[<?php echo $item['cart_id']; ?>]" 
                                           id="qty-input-<?php echo $item['cart_id']; ?>" 
                                           value="<?php echo $item['quantity']; ?>">
                                    <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1, <?php echo $item['stock']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                <button type="button" class="remove-btn" onclick="removeItem(<?php echo $item['cart_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                        
                        <input type="hidden" name="update_cart" value="1">
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value" id="subtotal">रू<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Delivery Fee</span>
                        <span class="summary-value">Free</span>
                    </div>
                    
                    <div class="total-row">
                        <span class="total-label">Total</span>
                        <span class="total-value" id="total">रू<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <button type="button" class="checkout-btn" onclick="proceedToCheckout()">
                        Proceed to Checkout
                    </button>
                    
                    <a href="products.php" class="continue-shopping">Continue Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart empty-cart-icon"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any products to your cart yet.</p>
                <a href="products.php" class="checkout-btn" style="max-width: 200px; margin: 0 auto;">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hidden forms for individual actions -->
    <form id="removeForm" action="cart.php" method="post" style="display: none;">
        <input type="hidden" name="remove_item" value="1">
        <input type="hidden" name="cart_id" id="removeCartId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store original prices for calculation
        const itemPrices = {
            <?php foreach ($cart_items as $item): ?>
            <?php echo $item['cart_id']; ?>: <?php echo $item['price']; ?>,
            <?php endforeach; ?>
        };

        function updateQuantity(cartId, change, maxStock) {
            const qtyDisplay = document.getElementById('qty-' + cartId);
            const qtyInput = document.getElementById('qty-input-' + cartId);
            let currentQty = parseInt(qtyDisplay.textContent);
            let newQty = currentQty + change;
            
            if (newQty < 1) newQty = 1;
            if (newQty > maxStock) newQty = maxStock;
            
            qtyDisplay.textContent = newQty;
            qtyInput.value = newQty;
            
            updateTotals();
        }

        function updateTotals() {
            let subtotal = 0;
            
            <?php foreach ($cart_items as $item): ?>
            {
                const qty = parseInt(document.getElementById('qty-<?php echo $item['cart_id']; ?>').textContent);
                subtotal += qty * itemPrices[<?php echo $item['cart_id']; ?>];
            }
            <?php endforeach; ?>
            
            document.getElementById('subtotal').textContent = 'रू' + subtotal.toFixed(2);
            document.getElementById('total').textContent = 'रू' + subtotal.toFixed(2);
        }

        function removeItem(cartId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                document.getElementById('removeCartId').value = cartId;
                document.getElementById('removeForm').submit();
            }
        }

        function proceedToCheckout() {
            // Submit the cart form to update quantities first
            document.getElementById('cartForm').action = 'cart.php';
            document.getElementById('cartForm').submit();
            
            // Then redirect to checkout (this will happen after the form submission)
            setTimeout(() => {
                window.location.href = 'checkout.php';
            }, 100);
        }

        // Auto-update cart when quantities change
        let updateTimeout;
        document.addEventListener('change', function(e) {
            if (e.target.name && e.target.name.includes('quantity')) {
                clearTimeout(updateTimeout);
                updateTimeout = setTimeout(() => {
                    document.getElementById('cartForm').submit();
                }, 1000);
            }
        });
    </script>
</body>

</html>