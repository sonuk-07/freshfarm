<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consumer_id = $_SESSION['user_id'];
    $product_id = (int) $_POST['product_id'];
    $rating = (int) $_POST['rating'];
    $review_text = mysqli_real_escape_string($dbconn, $_POST['review_text']);

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Invalid rating value";
        header("Location: product_details.php?id=$product_id");
        exit();
    }

    // Insert review
    $insert_query = "INSERT INTO product_reviews (product_id, consumer_id, rating, review_text) 
                     VALUES ($product_id, $consumer_id, $rating, '$review_text')";

    if (mysqli_query($dbconn, $insert_query)) {
        $_SESSION['success'] = "Review submitted successfully and pending approval";
    } else {
        $_SESSION['error'] = "Error submitting review: " . mysqli_error($dbconn);
    }

    header("Location: product_details.php?id=$product_id");
    exit();
}