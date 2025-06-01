<?php
session_start();
include '../config/db.php';

// Verify the payment response
$transaction_uuid = $_GET['transaction_uuid'] ?? '';
$status = $_GET['status'] ?? '';
$order_id = $_SESSION['pending_order_id'] ?? 0;

if ($status === 'COMPLETE' && $order_id) {
    // Update order status
    $update_query = "UPDATE orders SET status = 'processing', transaction_id = '$transaction_uuid' WHERE order_id = $order_id";
    mysqli_query($dbconn, $update_query);
    
    // Add status history
    $status_query = "INSERT INTO order_status_history (order_id, status, notes, created_at) 
                     VALUES ($order_id, 'processing', 'Payment completed via eSewa', NOW())";
    mysqli_query($dbconn, $status_query);
    
    unset($_SESSION['pending_order_id']);
    header("Location: order_confirmation.php?id=$order_id");
} else {
    header("Location: checkout.php?error=payment_failed");
}
?>