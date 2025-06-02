<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle product approval
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $product_id = intval($_GET['approve']);
    
    // Use prepared statement for security
    $approve_query = "UPDATE products SET status = 'approved' WHERE product_id = ?";
    $stmt = mysqli_prepare($dbconn, $approve_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Product approved successfully!";
    } else {
        $error_message = "Error approving product: " . mysqli_error($dbconn);
    }
    mysqli_stmt_close($stmt);
}

// Handle product rejection
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $product_id = intval($_GET['reject']);
    
    $reject_query = "UPDATE products SET status = 'rejected' WHERE product_id = ?";
    $stmt = mysqli_prepare($dbconn, $reject_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Product rejected successfully!";
    } else {
        $error_message = "Error rejecting product: " . mysqli_error($dbconn);
    }
    mysqli_stmt_close($stmt);
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // Check if product exists using prepared statement
    $check_query = "SELECT * FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($dbconn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Start transaction
        mysqli_begin_transaction($dbconn);
        
        try {
            // Delete related cart items
            $delete_cart = "DELETE FROM cart WHERE product_id = ?";
            $cart_stmt = mysqli_prepare($dbconn, $delete_cart);
            mysqli_stmt_bind_param($cart_stmt, "i", $product_id);
            mysqli_stmt_execute($cart_stmt);
            mysqli_stmt_close($cart_stmt);
            
            // Delete related reviews
            $delete_reviews = "DELETE FROM product_reviews WHERE product_id = ?";
            $reviews_stmt = mysqli_prepare($dbconn, $delete_reviews);
            mysqli_stmt_bind_param($reviews_stmt, "i", $product_id);
            mysqli_stmt_execute($reviews_stmt);
            mysqli_stmt_close($reviews_stmt);
            
            // Delete related order items
            $delete_order_items = "DELETE FROM order_items WHERE product_id = ?";
            $order_items_stmt = mysqli_prepare($dbconn, $delete_order_items);
            mysqli_stmt_bind_param($order_items_stmt, "i", $product_id);
            mysqli_stmt_execute($order_items_stmt);
            mysqli_stmt_close($order_items_stmt);
            
            // Finally delete the product
            $delete_query = "DELETE FROM products WHERE product_id = ?";
            $delete_stmt = mysqli_prepare($dbconn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $product_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
            
            // If everything is successful, commit the transaction
            mysqli_commit($dbconn);
            $success_message = "Product deleted successfully!";
            
        } catch (Exception $e) {
            // If there's an error, rollback the changes
            mysqli_rollback($dbconn);
            $error_message = "Error deleting product: " . $e->getMessage();
        }
    } else {
        $error_message = "Product not found!";
    }
    mysqli_stmt_close($stmt);
}

// Fetch all products with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($dbconn, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($dbconn, $_GET['category']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($dbconn, $_GET['status']) : '';

// Build the query with filters using prepared statements
$query = "SELECT p.*, u.username as seller_name, c.name as category_name 
         FROM products p 
         LEFT JOIN users u ON p.seller_id = u.user_id 
         LEFT JOIN categories c ON p.category_id = c.category_id 
         WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if (!empty($category_filter)) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if (!empty($status_filter)) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Count total records for pagination
$count_query = str_replace("SELECT p.*, u.username as seller_name, c.name as category_name", "SELECT COUNT(*) as total", $query);
$count_stmt = mysqli_prepare($dbconn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_data = mysqli_fetch_assoc($count_result);
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $limit);
mysqli_stmt_close($count_stmt);

// Add pagination to the main query
$query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$products_stmt = mysqli_prepare($dbconn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($products_stmt, $types, ...$params);
}
mysqli_stmt_execute($products_stmt);
$products_result = mysqli_stmt_get_result($products_stmt);

// Fetch categories for filter dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($dbconn, $categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
                * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #374151;
            font-size: 1.875rem;
            font-weight: 700;
            margin: 0;
        }
        
        .header p {
            color: #6b7280;
            margin: 0.25rem 0 0 0;
            font-size: 0.875rem;
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

        
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .filter-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .filter-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1.5rem;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: 1fr 200px 200px 120px;
            gap: 1rem;
            align-items: end;
        }
        
        .filter-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-control-custom {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .form-control-custom:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .btn-filter {
            background-color: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            height: fit-content;
        }
        
        .btn-filter:hover {
            background-color: #059669;
            color: white;
        }
        
        .data-table-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table-custom {
            margin: 0;
        }
        
        .table-custom thead th {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem;
        }
        
        .table-custom tbody td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        
        .table-custom tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .product-avatar {
            width: 40px;
            height: 40px;
            border-radius: 0.375rem;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
            overflow: hidden;
        }
        
        .product-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info h6 {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }
        
        .product-info p {
            margin: 0;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .badge-custom {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-admin { background-color: #fef3c7; color: #d97706; }
        .badge-consumer { background-color: #dbeafe; color: #2563eb; }
        .badge-farmer { background-color: #d1fae5; color: #065f46; }
        
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #d97706; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 0.375rem;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }
        
        .btn-edit { background-color: #3b82f6; color: white; }
        .btn-edit:hover { background-color: #2563eb; color: white; }
        
        .btn-view { background-color: #f59e0b; color: white; }
        .btn-view:hover { background-color: #d97706; color: white; }
        
        .btn-delete { background-color: #ef4444; color: white; }
        .btn-delete:hover { background-color: #dc2626; color: white; }
        
        .btn-approve { background-color: #10b981; color: white; }
        .btn-approve:hover { background-color: #059669; color: white; }
        
        .btn-reject { background-color: #f59e0b; color: white; }
        .btn-reject:hover { background-color: #d97706; color: white; }
        
        .pagination-custom {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .pagination-custom .pagination {
            margin: 0;
            justify-content: center;
        }
        
        .pagination-custom .page-link {
            color: #6b7280;
            border: 1px solid #d1d5db;
            margin: 0 0.125rem;
        }
        
        .pagination-custom .page-item.active .page-link {
            background-color: #10b981;
            border-color: #10b981;
        }
        
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            border-radius: 0.375rem;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 0.375rem;
        }
        
        .price-cell {
            font-weight: 600;
            color: #059669;
        }
        
        .stock-cell {
            font-weight: 500;
            color: #374151;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .container-custom {
                padding: 0 1rem;
            }
            .navigation-tabs {
                flex-wrap: wrap;
                gap: 16px;
            }
            
            .header {
                padding: 1rem;
            }
            
            .nav-tabs {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Product Management</h1>
            <p class="dashboard-subtitle">Manage products, inventory, and listings</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="navigation-tabs">
            <a href="dashboard.php" class="nav-tab">Overview</a>
            <a href="users.php" class="nav-tab">User Management</a>
            <a href="products.php" class="nav-tab active" >Product Management</a>
            <a href="orders.php" class="nav-tab">Order Management</a>
            <a href="reviews.php" class="nav-tab">Review Moderation</a>
        </div>

    <div class="container-custom">
        <?php if(isset($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter Products Card -->
        <div class="filter-card">
            <h3 class="filter-title">Filter Products</h3>
            <form method="GET" action="products.php">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search Products</label>
                        <input type="text" id="search" name="search" class="form-control form-control-custom" 
                               placeholder="Search by name, description, or seller..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="category">Category Filter</label>
                        <select id="category" name="category" class="form-control form-control-custom">
                            <option value="">All Categories</option>
                            <?php mysqli_data_seek($categories_result, 0); ?>
                            <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?php echo $category['category_id']; ?>" 
                                        <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status">Status Filter</label>
                        <select id="status" name="status" class="form-control form-control-custom">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-filter w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Products Table -->
        <div class="data-table-card">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                    <tr>
                        <td>#<?php echo $product['product_id']; ?></td>
                        <td>
                            <div class="product-cell">
                                <div class="product-avatar">
                                    <?php if(!empty($product['product_image'])): ?>
                                        <img src="../uploads/products/<?php echo htmlspecialchars($product['product_image']); ?>" alt="Product">
                                    <?php else: ?>
                                        <i class="fas fa-box"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <p><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 30)) . (strlen($product['description'] ?? '') > 30 ? '...' : ''); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="product-info">
                                <h6><?php echo htmlspecialchars($product['seller_name']); ?></h6>
                                <p>ID: <?php echo $product['seller_id']; ?></p>
                            </div>
                        </td>
                        <td>
                            <span class="badge-custom badge-farmer">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                        </td>
                        <td class="price-cell"Rs.?php echo number_format($product['price'], 2); ?></td>
                        <td class="stock-cell"><?php echo $product['stock']; ?></td>
                        <td>
                            <?php 
                            $status = $product['status'] ?? 'pending';
                            $status_class = 'status-' . $status;
                            $status_text = ucfirst($status);
                            ?>
                            <span class="badge-custom <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <!-- Edit Product -->
                                <a href="../farmer/edit_product.php?id=<?php echo $product['product_id']; ?>"
                                   class="btn btn-action btn-edit" title="Edit Product">
                                    <i class="fas fa-edit"></i>
                                </a>
                                                                
                                <?php if($status === 'pending'): ?>
                                    <!-- Approve Product -->
                                    <button class="btn btn-action btn-approve" title="Approve Product"
                                            onclick="if(confirm('Are you sure you want to approve this product?')) { window.location.href='products.php?approve=<?php echo $product['product_id']; ?>'; }">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    
                                    <!-- Reject Product -->
                                    <button class="btn btn-action btn-reject" title="Reject Product"
                                            onclick="if(confirm('Are you sure you want to reject this product?')) { window.location.href='products.php?reject=<?php echo $product['product_id']; ?>'; }">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php elseif($status === 'rejected'): ?>
                                    <!-- Approve Product -->
                                    <button class="btn btn-action btn-approve" title="Approve Product"
                                            onclick="if(confirm('Are you sure you want to approve this product?')) { window.location.href='products.php?approve=<?php echo $product['product_id']; ?>'; }">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <!-- Delete Product -->
                                <button class="btn btn-action btn-delete" title="Delete Product"
                                        onclick="if(confirm('Are you sure you want to delete this product? This action cannot be undone.')) { window.location.href='products.php?delete=<?php echo $product['product_id']; ?>'; }">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if(mysqli_num_rows($products_result) == 0): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-box text-muted mb-2 d-block" style="font-size: 2rem;"></i>
                            <span class="text-muted">No products found</span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="pagination-custom">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        ?>
                        
                        <?php if($start > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>">1</a>
                            </li>
                            <?php if($start > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if($end < $total_pages): ?>
                            <?php if($end < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>

<?php
// Close prepared statement
if (isset($products_stmt)) {
    mysqli_stmt_close($products_stmt);
}
?>