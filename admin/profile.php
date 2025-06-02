<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$password_success = '';
$password_error = '';
$image_success = '';
$image_error = '';

// Fetch farmer's profile
$profile_query = "SELECT * FROM users WHERE user_id = $farmer_id";
$profile_result = mysqli_query($dbconn, $profile_query);
$profile = mysqli_fetch_assoc($profile_result);



// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($dbconn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($dbconn, $_POST['last_name']);
    $email = mysqli_real_escape_string($dbconn, $_POST['email']);
    $phone = mysqli_real_escape_string($dbconn, $_POST['phone']);
    $address = mysqli_real_escape_string($dbconn, $_POST['address']);
    $bio = mysqli_real_escape_string($dbconn, $_POST['bio']);
    
    // Check if email already exists for another user
    $email_check = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $farmer_id";
    $email_result = mysqli_query($dbconn, $email_check);
    
    if (mysqli_num_rows($email_result) > 0) {
        $error_message = "Email already in use by another account.";
    } else {
        // Update user profile
        $update_query = "UPDATE users SET 
                        first_name = '$first_name', 
                        last_name = '$last_name', 
                        email = '$email', 
                        phone = '$phone', 
                        address = '$address', 
                        bio = '$bio' 
                        WHERE user_id = $farmer_id";
        
        if (mysqli_query($dbconn, $update_query)) {
            $success_message = "Profile updated successfully!";
            
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            
            // Refresh profile data
            $profile_result = mysqli_query($dbconn, $profile_query);
            $profile = mysqli_fetch_assoc($profile_result);
        } else {
            $error_message = "Error updating profile: " . mysqli_error($dbconn);
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
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
    
    <div class="container-fluid">
        <div class="row">
         <!-- Main Content -->
            <div class="col-md-10 ms-auto content-wrapper p-4">
                <h2 class="mb-4">My Profile</h2>
                
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
                
                <?php if($password_success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $password_success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($password_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $password_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($image_success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $image_success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($image_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $image_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Profile Stats -->
                    <div class="col-md-4 mb-4">
                        <div class="card profile-header text-center mb-4">
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
                                
                                <!-- Profile Image Upload Form -->
                                <form action="" method="POST" enctype="multipart/form-data" class="mt-3">
                                    <div class="mb-3">
                                        <label for="profile_image" class="form-label">Update Profile Image</label>
                                        <input type="file" class="form-control" id="profile_image" name="profile_image" required>
                                    </div>
                                    <button type="submit" class="btn btn-outline-primary">Upload Image</button>
                                </form>
                            </div>
                        </div>
                        
                        
                    </div>
                    
                    <!-- Profile Edit Form -->
                    <div class="col-md-8 mb-4">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Edit Profile</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($profile['address']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell people about yourself..."><?php echo htmlspecialchars($profile['bio']); ?></textarea>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Change Password Form -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>