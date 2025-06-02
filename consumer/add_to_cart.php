<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int) $_POST['product_id'];
    $quantity = (int) $_POST['quantity'];

    // Validate quantity
    if ($quantity <= 0) {
        $_SESSION['cart_error'] = "Please select a valid quantity.";
        header("Location: product_details.php?id=$product_id");
        exit();
    }

    // Check if product exists and has enough stock
    $product_query = "SELECT * FROM products WHERE product_id = $product_id AND stock > 0";
    $product_result = mysqli_query($dbconn, $product_query);

    if (mysqli_num_rows($product_result) == 0) {
        $_SESSION['cart_error'] = "Product is not available.";
        header("Location: products.php");
        exit();
    }

    $product = mysqli_fetch_assoc($product_result);

    // Check if quantity is available
    if ($quantity > $product['stock']) {
        $_SESSION['cart_error'] = "Sorry, only {$product['stock']} units available.";
        header("Location: product_details.php?id=$product_id");
        exit();
    }

    // Check if product is already in cart
    $cart_check = "SELECT * FROM cart WHERE consumer_id = $consumer_id AND product_id = $product_id";
    $cart_result = mysqli_query($dbconn, $cart_check);

    if (mysqli_num_rows($cart_result) > 0) {
        // Update quantity
        $cart_item = mysqli_fetch_assoc($cart_result);
        $new_quantity = $cart_item['quantity'] + $quantity;

        // Check if new quantity exceeds stock
        if ($new_quantity > $product['stock']) {
            $_SESSION['cart_error'] = "Cannot add more. You already have {$cart_item['quantity']} in your cart and only {$product['stock']} units are available.";
            header("Location: product_details.php?id=$product_id");
            exit();
        }

        $update_query = "UPDATE cart SET quantity = $new_quantity WHERE cart_id = {$cart_item['cart_id']}";
        if (mysqli_query($dbconn, $update_query)) {
            $_SESSION['cart_message'] = "Cart updated successfully!";
        } else {
            $_SESSION['cart_error'] = "Error updating cart: " . mysqli_error($dbconn);
        }
    } else {
        // Add new item to cart
        $insert_query = "INSERT INTO cart (consumer_id, product_id, quantity, price, added_at) 
                        VALUES ($consumer_id, $product_id, $quantity, {$product['price']}, NOW())";

        if (mysqli_query($dbconn, $insert_query)) {
            $_SESSION['cart_message'] = "Product added to cart successfully!";
        } else {
            $_SESSION['cart_error'] = "Error adding to cart: " . mysqli_error($dbconn);
        }
    }

    // Redirect back to product page
    header("Location: product_details.php?id=$product_id");
    exit();
} else {
    // Invalid request
    header("Location: products.php");
    exit();
}
?>