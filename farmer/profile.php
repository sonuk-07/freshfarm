<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

// Fetch farmer's profile
$profile_query = "SELECT * FROM users WHERE user_id = $farmer_id";
$profile_result = mysqli_query($dbconn, $profile_query);
$profile = mysqli_fetch_assoc($profile_result);

// Handle profile update
if (isset($_POST['update_profile'])) {
    $username = mysqli_real_escape_string($dbconn, $_POST['username']);
    $email = mysqli_real_escape_string($dbconn, $_POST['email']);
    $phone = mysqli_real_escape_string($dbconn, $_POST['phone']);
    $address = mysqli_real_escape_string($dbconn, $_POST['address']);
    $bio = mysqli_real_escape_string($dbconn, $_POST['bio']);
    
    // Update profile
    $update_query = "UPDATE users SET 
                    username = '$username',
                    email = '$email',
                    phone = '$phone',
                    address = '$address',
                    bio = '$bio'
                    WHERE user_id = $farmer_id";
    
    if (mysqli_query($dbconn, $update_query)) {
        $success_message = "Profile updated successfully!";
        // Refresh profile data
        $profile_result = mysqli_query($dbconn, $profile_query);
        $profile = mysqli_fetch_assoc($profile_result);
    } else {
        $error_message = "Error updating profile: " . mysqli_error($dbconn);
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($current_password, $profile['password'])) {
        // Check if new passwords match
        if ($new_password === $confirm_password) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $password_query = "UPDATE users SET password = '$hashed_password' WHERE user_id = $farmer_id";
            
            if (mysqli_query($dbconn, $password_query)) {
                $password_success = "Password changed successfully!";
            } else {
                $password_error = "Error changing password: " . mysqli_error($dbconn);
            }
        } else {
            $password_error = "New passwords do not match!";
        }
    } else {
        $password_error = "Current password is incorrect!";
    }
}

// Handle profile image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_image']['name'];
    $filetype = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Check if file type is allowed
    if (in_array(strtolower($filetype), $allowed)) {
        // Create uploads directory if it doesn't exist
        $upload_dir = "../uploads/profiles/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $new_filename = "farmer_" . $farmer_id . "_" . time() . "." . $filetype;
        $upload_path = $upload_dir . $new_filename;
        
        // Upload file
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            // Update profile image in database
            $image_query = "UPDATE users SET profile_image = '$new_filename' WHERE user_id = $farmer_id";
            
            if (mysqli_query($dbconn, $image_query)) {
                $image_success = "Profile image updated successfully!";
                // Refresh profile data
                $profile_result = mysqli_query($dbconn, $profile_query);
                $profile = mysqli_fetch_assoc($profile_result);
            } else {
                $image_error = "Error updating profile image: " . mysqli_error($dbconn);
            }
        } else {
            $image_error = "Error uploading image!";
        }
    } else {
        $image_error = "Invalid file type. Allowed types: jpg, jpeg, png, gif";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
        }
        .sidebar a {
            color: rgba(255,255,255,.75);
            padding: 10px 15px;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background-color: rgba(255,255,255,.1);
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .content-wrapper {
            min-height: calc(100vh - 56px);
        }
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #f8f9fa;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-none d-md-block sidebar py-4">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-carrot"></i> My Products</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 ms-auto content-wrapper p-4">
                <h2 class="mb-4">My Profile</h2>
                
                <!-- Profile Header -->
                <div class="profile-header d-flex flex-column flex-md-row align-items-center">
                    <div class="text-center mb-3 mb-md-0 me-md-4">
                        <?php if (!empty($profile['profile_image'])): ?>
                            <img src="../uploads/profiles/<?php echo $profile['profile_image']; ?>" class="profile-image" alt="Profile Image">
                        <?php else: ?>
                            <img src="../assets/images/profile-placeholder.jpg" class="profile-image" alt="Profile Image">
                        <?php endif; ?>
                        <img src="../uploads/profiles/<?php echo $profile['profile_image']; ?>" class="profile-image" alt="Profile Image">
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>