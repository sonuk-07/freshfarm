<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = $_POST['notification_id'];

$update_query = "UPDATE notifications SET is_read = 1 
                 WHERE notification_id = ? AND user_id = ?";
$stmt = mysqli_prepare($dbconn, $update_query);
mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
}