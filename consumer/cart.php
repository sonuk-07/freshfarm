<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/index.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $cart_id => $quantity) {
            $cart_id = (int)$cart_id;
            $quantity = (int)$quantity;
            
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
        $cart_id = (int)$_POST['cart_id'];
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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .cart-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .quantity-input {
            width: 70px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Shopping Cart</h1>
            <a href="marketplace.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
            </a>
        </div>
        
        <?php if($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if(mysqli_num_rows($cart_result) > 0): ?>
            <form action="cart.php" method="post">
                <div class="card cart-card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Product</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col" class="text-end">Subtotal</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item = mysqli_fetch_assoc($cart_result)): ?>
                                        <?php 
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $cart_total += $subtotal;
                                        ?>
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
                                                        <small class="text-muted">Seller: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td>
                                                <input type="number" name="quantity[<?php echo $item['cart_id']; ?>]" class="form-control quantity-input" value="<?php echo $item['quantity']; ?>" min="0" max="<?php echo $item['stock']; ?>">
                                                <small class="text-muted"><?php echo $item['stock']; ?> available</small>
                                            </td>
                                            <td class="text-end">$<?php echo number_format($subtotal, 2); ?></td>
                                            <td>
                                                <form action="cart.php" method="post" class="d-inline">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                                    <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold">$<?php echo number_format($cart_total, 2); ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" name="update_cart" class="btn btn-outline-primary">
                        <i class="fas fa-sync-alt me-2"></i>Update Cart
                    </button>
                    <a href="checkout.php" class="btn btn-success">
                        <i class="fas fa-shopping-cart me-2"></i>Proceed to Checkout
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="card cart-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h3>Your cart is empty</h3>
                    <p class="text-muted mb-4">Looks like you haven't added any products to your cart yet.</p>
                    <a href="marketplace.php" class="btn btn-primary">Start Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>