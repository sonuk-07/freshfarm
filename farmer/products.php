<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit();
}
$farmer_id = $_SESSION['user_id'];

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    $delete_query = "DELETE FROM products WHERE product_id = $product_id AND seller_id = $farmer_id";
    if (mysqli_query($dbconn, $delete_query)) {
        $success_message = "Product deleted successfully!";
    } else {
        $error_message = "Error deleting product: " . mysqli_error($dbconn);
    }
}

// Fetch all products by this farmer
$products_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.seller_id = $farmer_id
                  ORDER BY p.created_at DESC";
$products_result = mysqli_query($dbconn, $products_query);

// Fetch categories for the add product form
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($dbconn, $categories_query);

// Get farmer name for welcome message - try different possible column names
$farmer_query = "SELECT * FROM users WHERE user_id = $farmer_id LIMIT 1";
$farmer_result = mysqli_query($dbconn, $farmer_query);
$farmer_data = mysqli_fetch_assoc($farmer_result);

// Try different possible column names for the farmer's name
$farmer_name = 'Farmer'; // Default fallback
if (isset($farmer_data['name'])) {
    $farmer_name = explode(' ', $farmer_data['name'])[0];
} elseif (isset($farmer_data['username'])) {
    $farmer_name = $farmer_data['username'];
} elseif (isset($farmer_data['first_name'])) {
    $farmer_name = $farmer_data['first_name'];
} elseif (isset($farmer_data['full_name'])) {
    $farmer_name = explode(' ', $farmer_data['full_name'])[0];
}

// Calculate dashboard stats
$total_products = mysqli_num_rows($products_result);

// Calculate sales data by joining through order_items and products
$total_sales = 0;
$total_orders = 0;

// Query to get sales data for this farmer's products
$sales_query = "SELECT SUM(oi.quantity * oi.price) as total_sales, COUNT(DISTINCT o.order_id) as total_orders 
                FROM orders o 
                JOIN order_items oi ON o.order_id = oi.order_id 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE p.seller_id = $farmer_id";
$sales_result = mysqli_query($dbconn, $sales_query);

if ($sales_result) {
    $sales_data = mysqli_fetch_assoc($sales_result);
    $total_sales = $sales_data['total_sales'] ?? 0;
    $total_orders = $sales_data['total_orders'] ?? 0;
}

// Reset products result for display
mysqli_data_seek($products_result, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #22c55e;
            --secondary-color: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-dark);
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-section {
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .stat-icon.sales { background: #dcfce7; color: var(--success-color); }
        .stat-icon.orders { background: #dbeafe; color: #3b82f6; }
        .stat-icon.products { background: #fef3c7; color: var(--warning-color); }
        .stat-icon.growth { background: #f3e8ff; color: #8b5cf6; }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .add-product-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .add-product-btn:hover {
            background: #16a34a;
            color: white;
            transform: translateY(-1px);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .product-content {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .product-description {
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .product-stock {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .product-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .status-active {
            background: #dcfce7;
            color: var(--success-color);
        }

        .status-pending {
            background: #fef3c7;
            color: var(--warning-color);
        }

        .status-rejected {
            background: #fecaca;
            color: var(--danger-color);
        }

        .status-unknown {
            background: #e2e8f0;
            color: var(--text-muted);
        }
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
        }

        .btn-edit {
            background: #fef3c7;
            color: var(--warning-color);
        }

        .btn-edit:hover {
            background: #fde68a;
            color: var(--warning-color);
        }

        .btn-delete {
            background: #fecaca;
            color: var(--danger-color);
        }

        .btn-delete:hover {
            background: #fca5a5;
            color: var(--danger-color);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .empty-description {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: none;
        }

        .alert-success {
            background: #dcfce7;
            color: var(--success-color);
        }

        .alert-danger {
            background: #fecaca;
            color: var(--danger-color);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($farmer_name); ?>!</h1>
            <p class="welcome-subtitle">Manage your farm products and orders</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>



        <!-- Products Section -->
        <div class="section-header">
            <h2 class="section-title">My Products</h2>
            <a href="add_products.php" class="add-product-btn">
                <i class="fas fa-plus"></i>
                Add Product
            </a>
        </div>

        <?php if (mysqli_num_rows($products_result) > 0): ?>
            <div class="products-grid">
                <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                    <div class="product-card">
                        <?php if (!empty($product['product_image'])): ?>
                            <img src="../uploads/products/<?php echo $product['product_image']; ?>" 
                                 class="product-image" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <div class="product-image" style="display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-content">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description">
                                <?php echo htmlspecialchars(mb_strimwidth($product['description'], 0, 100, "...")); ?>
                            </p>
                            
                            <div class="product-meta">
                                <div class="product-price">रू<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-stock">Stock: <?php echo $product['stock']; ?></div>
                            </div>
                            
                            <?php 
                            $status_class = '';
                            $status_text = 'Unknown';
                            
                            if (isset($product['status'])) {
                                switch($product['status']) {
                                    case 'approved':
                                        $status_class = 'status-active';
                                        $status_text = 'Approved';
                                        break;
                                    case 'pending':
                                        $status_class = 'status-pending';
                                        $status_text = 'Pending';
                                        break;
                                    case 'rejected':
                                        $status_class = 'status-rejected';
                                        $status_text = 'Rejected';
                                        break;
                                    default:
                                        $status_class = 'status-unknown';
                                        $status_text = 'Unknown';
                            }
                            } else {
                                $status_class = 'status-unknown';
                                $status_text = 'Status Unknown';
                            }
                            ?>
                            
                            <div class="product-status <?php echo $status_class; ?>"><?php echo $status_text; ?></div>
                            <div class="product-actions">
                                <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="action-btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="products.php?delete=<?php echo $product['product_id']; ?>" 
                                   class="action-btn btn-delete"
                                   onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h3 class="empty-title">No products yet</h3>
                <p class="empty-description">Start adding your farm products to sell them in the marketplace.</p>
                <a href="add_products.php" class="add-product-btn">
                    <i class="fas fa-plus"></i>
                    Add Your First Product
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add Product Modal (keeping original functionality) -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_product.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php
                                    mysqli_data_seek($categories_result, 0);
                                    while ($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Price (Rs.)</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <small class="text-muted">Recommended size: 800x600 pixels</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>