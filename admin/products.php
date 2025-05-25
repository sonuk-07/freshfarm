<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/index.php");
    exit();
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Check if product exists
    $check_query = "SELECT * FROM products WHERE product_id = $product_id";
    $check_result = mysqli_query($dbconn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Delete the product
        $delete_query = "DELETE FROM products WHERE product_id = $product_id";
        if (mysqli_query($dbconn, $delete_query)) {
            $success_message = "Product deleted successfully!";
        } else {
            $error_message = "Error deleting product: " . mysqli_error($dbconn);
        }
    } else {
        $error_message = "Product not found!";
    }
}

// Fetch all products with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($dbconn, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($dbconn, $_GET['category']) : '';

// Build the query with filters
$query = "SELECT p.*, u.username as seller_name, c.name as category_name 
         FROM products p 
         LEFT JOIN users u ON p.seller_id = u.user_id 
         LEFT JOIN categories c ON p.category_id = c.category_id 
         WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%' OR u.username LIKE '%$search%')";
}

if (!empty($category_filter)) {
    $query .= " AND p.category_id = '$category_filter'";
}

// Count total records for pagination
$count_query = str_replace("SELECT p.*, u.username as seller_name, c.name as category_name", "SELECT COUNT(*) as total", $query);
$count_result = mysqli_query($dbconn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $limit);

// Add pagination to the main query
$query .= " ORDER BY p.created_at DESC LIMIT $offset, $limit";
$products_result = mysqli_query($dbconn, $query);

// Fetch categories for filter dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($dbconn, $categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Dashboard</title>
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
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .product-placeholder {
            width: 60px;
            height: 60px;
            background-color: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
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
                <a href="users.php"><i class="fas fa-users me-2"></i> Users</a>
                <a href="products.php" class="active"><i class="fas fa-box me-2"></i> Products</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> Categories</a>
                <a href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
                <a href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2 class="mb-4">Manage Products</h2>
                
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
                        <form method="GET" action="products.php" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php mysqli_data_seek($categories_result, 0); ?>
                                    <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Products Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Seller</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                                    <tr>
                                        <td><?php echo $product['product_id']; ?></td>
                                        <td>
                                            <?php if(!empty($product['product_image'])): ?>
                                                <img src="../uploads/products/<?php echo $product['product_image']; ?>" alt="Product" class="product-image">
                                            <?php else: ?>
                                                <div class="product-placeholder">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['stock']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                        <td>
                                            <a href="products.php?delete=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if(mysqli_num_rows($products_result) == 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No products found</td>
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
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">Next</a>
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