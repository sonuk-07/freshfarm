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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .dashboard-header {
            margin-bottom: 32px;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .dashboard-subtitle {
            color: #64748b;
            font-size: 1rem;
        }

        .navigation-tabs {
            display: flex;
            gap: 32px;
            margin-bottom: 32px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
        }

        .nav-tab {
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-tab.active {
            color: #10b981;
            border-bottom-color: #10b981;
        }

        .nav-tab:hover {
            color: #10b981;
        }

        .logout-btn {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #ef4444;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #dc2626;
            color: white;
            text-decoration: none;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .alert-success {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .card-header {
            padding: 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-body {
            padding: 24px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 16px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input, .form-select {
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.3s ease;
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #10b981;
            color: white;
        }

        .btn-primary:hover {
            background: #059669;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.75rem;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-toggle {
            background: #f59e0b;
            color: white;
        }

        .btn-toggle:hover {
            background: #d97706;
        }

        .btn-toggle.activate {
            background: #10b981;
        }

        .btn-toggle.activate:hover {
            background: #059669;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .table-container {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .users-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
        }

        .users-table tbody tr:hover {
            background: #f9fafb;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #6b7280;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 500;
            color: #1f2937;
        }

        .user-username {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-admin { background: #fef2f2; color: #dc2626; }
        .badge-farmer { background: #f0fdf4; color: #16a34a; }
        .badge-consumer { background: #eff6ff; color: #2563eb; }
        .badge-active { background: #f0fdf4; color: #16a34a; }
        .badge-inactive { background: #f3f4f6; color: #6b7280; }

        .actions {
            display: flex;
            gap: 8px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
        }

        .page-btn {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .page-btn:hover {
            background: #f9fafb;
            text-decoration: none;
            color: #374151;
        }

        .page-btn.active {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        .page-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 16px;
            }
            
            .navigation-tabs {
                flex-wrap: wrap;
                gap: 16px;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <a href="../auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">User Management</h1>
            <p class="dashboard-subtitle">Manage users, roles, and permissions</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="navigation-tabs">
            <a href="dashboard.php" class="nav-tab">Overview</a>
            <a href="users.php" class="nav-tab active">User Management</a>
            <a href="products.php" class="nav-tab">Product Management</a>
            <a href="orders.php" class="nav-tab">Order Management</a>
            <a href="#" class="nav-tab">Review Moderation</a>
            <a href="#" class="nav-tab">Analytics</a>
        </div>

        <!-- Alert Messages -->
        <?php if(isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #1f2937;">Filter Users</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="users.php" class="filter-form">
                    <div class="form-group">
                        <label class="form-label">Search Users</label>
                        <input type="text" 
                               class="form-input" 
                               name="search" 
                               placeholder="Search by name, username, or email..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role Filter</label>
                        <select class="form-select" name="role">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="farmer" <?php echo $role_filter === 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                            <option value="consumer" <?php echo $role_filter === 'consumer' ? 'selected' : ''; ?>>Consumer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-container">
                    <table class="users-table">
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
                            <?php if(mysqli_num_rows($users_result) > 0): ?>
                                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td><span style="font-weight: 500; color: #6b7280;">#<?php echo $user['user_id']; ?></span></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php if(!empty($user['profile_image'])): ?>
                                                    <img src="../uploads/profiles/<?php echo $user['profile_image']; ?>" alt="User">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-details">
                                                <div class="user-name"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></div>
                                                <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-edit" 
                                               title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="users.php?toggle_status=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-toggle <?php echo $user['is_active'] ? '' : 'activate'; ?>" 
                                               onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')"
                                               title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> User">
                                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?delete=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"
                                               title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-users"></i>
                                            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 8px;">No users found</h3>
                                            <p>Try adjusting your search criteria or filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page-1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                       class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if($start_page > 1): ?>
                        <a href="?page=1&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" class="page-btn">1</a>
                        <?php if($start_page > 2): ?>
                            <span class="page-btn disabled">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                           class="page-btn <?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if($end_page < $total_pages): ?>
                        <?php if($end_page < $total_pages - 1): ?>
                            <span class="page-btn disabled">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" class="page-btn"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <a href="?page=<?php echo min($total_pages, $page+1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                       class="page-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add some smooth interactions
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-1px)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>