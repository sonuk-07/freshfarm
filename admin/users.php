<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/index.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Don't allow admin to delete themselves
    if ($user_id != $_SESSION['user_id']) {
        $delete_query = "DELETE FROM users WHERE user_id = $user_id";
        if (mysqli_query($dbconn, $delete_query)) {
            $success_message = "User deleted successfully!";
        } else {
            $error_message = "Error deleting user: " . mysqli_error($dbconn);
        }
    } else {
        $error_message = "You cannot delete your own account!";
    }
}

// Handle user status toggle (active/inactive)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = $_GET['toggle_status'];
    
    // Get current status
    $status_query = "SELECT is_active FROM users WHERE user_id = $user_id";
    $status_result = mysqli_query($dbconn, $status_query);
    $user_data = mysqli_fetch_assoc($status_result);
    
    // Toggle status
    $new_status = $user_data['is_active'] ? 0 : 1;
    $update_query = "UPDATE users SET is_active = $new_status WHERE user_id = $user_id";
    
    if (mysqli_query($dbconn, $update_query)) {
        $success_message = "User status updated successfully!";
    } else {
        $error_message = "Error updating user status: " . mysqli_error($dbconn);
    }
}

// Fetch all users with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($dbconn, $_GET['search']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($dbconn, $_GET['role']) : '';

// Build the query with filters
$query = "SELECT * FROM users WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (username LIKE '%$search%' OR email LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%')";
}

if (!empty($role_filter)) {
    $query .= " AND role = '$role_filter'";
}

// Count total records for pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$count_result = mysqli_query($dbconn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $limit);

// Add pagination to the main query
$query .= " ORDER BY created_at DESC LIMIT $offset, $limit";
$users_result = mysqli_query($dbconn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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
                <h2 class="mb-4">Manage Users</h2>
                
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
                
                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="users.php" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="role">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="farmer" <?php echo $role_filter === 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                                    <option value="consumer" <?php echo $role_filter === 'consumer' ? 'selected' : ''; ?>>Consumer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2">
                                                    <?php if(!empty($user['profile_image'])): ?>
                                                        <img src="../uploads/profiles/<?php echo $user['profile_image']; ?>" alt="User" class="w-100 h-100 rounded-circle">
                                                    <?php else: ?>
                                                        <?php echo strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                <small class="text-muted ms-2">(<?php echo htmlspecialchars($user['username']); ?>)</small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'farmer' ? 'success' : 'primary'); ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                            <a href="users.php?toggle_status=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>" onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?delete=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if(mysqli_num_rows($users_result) == 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No users found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>