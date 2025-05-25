<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/index.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($dbconn, $_POST['username']);
    $email = mysqli_real_escape_string($dbconn, $_POST['email']);
    $first_name = mysqli_real_escape_string($dbconn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($dbconn, $_POST['last_name']);
    $phone = mysqli_real_escape_string($dbconn, $_POST['phone']);
    $address = mysqli_real_escape_string($dbconn, $_POST['address']);
    $city = mysqli_real_escape_string($dbconn, $_POST['city']);
    $state = mysqli_real_escape_string($dbconn, $_POST['state']);
    $zipcode = mysqli_real_escape_string($dbconn, $_POST['zipcode']);
    $role = mysqli_real_escape_string($dbconn, $_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if email already exists for another user
    $email_check = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id";
    $email_result = mysqli_query($dbconn, $email_check);
    
    if (mysqli_num_rows($email_result) > 0) {
        $error_message = "Email already exists for another user!";
    } else {
        // Update user
        $update_query = "UPDATE users SET 
                        username = '$username',
                        email = '$email',
                        first_name = '$first_name',
                        last_name = '$last_name',
                        phone = '$phone',
                        address = '$address',
                        city = '$city',
                        state = '$state',
                        zipcode = '$zipcode',
                        role = '$role',
                        is_active = $is_active
                        WHERE user_id = $user_id";
        
        if (mysqli_query($dbconn, $update_query)) {
            // Handle password update if provided
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $password_query = "UPDATE users SET password = '$password' WHERE user_id = $user_id";
                mysqli_query($dbconn, $password_query);
            }
            
            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
                    $upload_dir = '../uploads/profiles/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_query = "UPDATE users SET profile_image = '$new_filename' WHERE user_id = $user_id";
                        mysqli_query($dbconn, $image_query);
                    }
                }
            }
            
            $success_message = "User updated successfully!";
        } else {
            $error_message = "Error updating user: " . mysqli_error($dbconn);
        }
    }
}

// Fetch user data
$user_query = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($dbconn, $user_query);

if (mysqli_num_rows($user_result) == 0) {
    header("Location: users.php");
    exit();
}

$user = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar a {
            color: #f8f9fa;
            padding: 10px 15px;
            display: block;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar a.active {
            background-color: #0d6efd;
        }
        .content {
            padding: 20px;
        }
        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <h2 class="text-center text-white py-3">Admin Panel</h2>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="users.php" class="active"><i class="fas fa-users me-2"></i> Users</a>
                <a href="products.php"><i class="fas fa-box me-2"></i> Products</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> Categories</a>
                <a href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
                <a href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Edit User</h2>
                    <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Users</a>
                </div>
                
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="edit_user.php?id=<?php echo $user_id; ?>" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <div class="profile-image-preview">
                                        <?php if(!empty($user['profile_image'])): ?>
                                            <img src="../uploads/profiles/<?php echo $user['profile_image']; ?>" alt="Profile" class="w-100 h-100 rounded-circle">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="profile_image" class="form-label">Profile Image</label>
                                        <input type="file" class="form-control" id="profile_image" name="profile_image">
                                        <small class="text-muted">Leave empty to keep current image</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-9">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="role" class="form-label">Role</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                <option value="farmer" <?php echo $user['role'] === 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                                                <option value="consumer" <?php echo $user['role'] === 'consumer' ? 'selected' : ''; ?>>Consumer</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="state" class="form-label">State</label>
                                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="zipcode" class="form-label">Zipcode</label>
                                            <input type="text" class="form-control" id="zipcode" name="zipcode" value="<?php echo htmlspecialchars($user['zipcode']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <small class="text-muted">Leave empty to keep current password</small>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary">Update User</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>