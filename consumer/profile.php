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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($dbconn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($dbconn, $_POST['last_name']);
    $email = mysqli_real_escape_string($dbconn, $_POST['email']);
    $phone = mysqli_real_escape_string($dbconn, $_POST['phone']);
    $address = mysqli_real_escape_string($dbconn, $_POST['address']);
    
    // Check if email already exists for another user
    $email_check = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $consumer_id";
    $email_result = mysqli_query($dbconn, $email_check);
    
    if (mysqli_num_rows($email_result) > 0) {
        $error_message = "Email already in use by another account.";
    } else {
        // Handle profile image upload
        $profile_image = '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                // Create upload directory if it doesn't exist
                $upload_dir = "../uploads/profiles/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $new_filename = uniqid('profile_') . '.' . $filetype;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $profile_image = $new_filename;
                    
                    // Update profile image in database
                    if (!empty($profile_image)) {
                        $image_update = "UPDATE users SET profile_image = '$profile_image' WHERE user_id = $consumer_id";
                        mysqli_query($dbconn, $image_update);
                    }
                } else {
                    $error_message = "Failed to upload profile image.";
                }
            } else {
                $error_message = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF.";
            }
        }
        
        if (empty($error_message)) {
            // Update user profile
            $update_query = "UPDATE users SET 
                            first_name = '$first_name', 
                            last_name = '$last_name', 
                            email = '$email', 
                            phone = '$phone', 
                            address = '$address' 
                            WHERE user_id = $consumer_id";
            
            if (mysqli_query($dbconn, $update_query)) {
                $success_message = "Profile updated successfully!";
                
                // Update session variables
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
            } else {
                $error_message = "Error updating profile: " . mysqli_error($dbconn);
            }
        }
    }
}

// Get user profile data
$profile_query = "SELECT * FROM users WHERE user_id = $consumer_id";
$profile_result = mysqli_query($dbconn, $profile_query);
$profile = mysqli_fetch_assoc($profile_result);

// Get order statistics
$orders_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(total_amount) as total_spent
                FROM orders 
                WHERE consumer_id = $consumer_id";
$orders_result = mysqli_query($dbconn, $orders_query);
$orders_stats = mysqli_fetch_assoc($orders_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">My Profile</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
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
        
        <div class="row">
            <!-- Profile Stats -->
            <div class="col-md-4 mb-4">
                <div class="card profile-card text-center mb-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <?php if(!empty($profile['profile_image'])): ?>
                                <img src="../uploads/profiles/<?php echo $profile['profile_image']; ?>" alt="Profile Image" class="profile-image">
                            <?php else: ?>
                                <div class="profile-image d-flex align-items-center justify-content-center bg-light">
                                    <span class="display-4"><?php echo strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($profile['email']); ?></p>
                        <p class="text-muted">Member since <?php echo date('F Y', strtotime($profile['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">