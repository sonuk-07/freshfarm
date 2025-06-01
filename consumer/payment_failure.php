<?php
session_start();
include '../config/db.php';

$order_id = $_SESSION['pending_order_id'] ?? 0;
if ($order_id) {
    // Update order status to failed
    $update_query = "UPDATE orders SET status = 'canceled' WHERE order_id = $order_id";
    mysqli_query($dbconn, $update_query);
    
    // Add status history
    $status_query = "INSERT INTO order_status_history (order_id, status, notes, created_at) 
                     VALUES ($order_id, 'canceled', 'Payment failed', NOW())";
    mysqli_query($dbconn, $status_query);
    
    unset($_SESSION['pending_order_id']);
}

header("Location: checkout.php?error=payment_failed");
?>