<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Initialize filters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($dbconn, $_GET['search']) : '';

// Build query based on filters
$query = "SELECT p.*, c.name as category_name, u.first_name, u.last_name 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN users u ON p.seller_id = u.user_id
          WHERE p.stock > 0 AND p.status = 'approved'"; // Add status filter here

// Apply category filter
if ($category_filter > 0) {
    $query .= " AND p.category_id = $category_filter";
}

// Apply price filter
$query .= " AND p.price BETWEEN $min_price AND $max_price";

// Apply search filter
if (!empty($search_term)) {
    $query .= " AND (p.name LIKE '%$search_term%' OR p.description LIKE '%$search_term%')";
}

// Apply sorting
switch ($sort_by) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

$products_result = mysqli_query($dbconn, $query);

// Get all categories for filter
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = mysqli_query($dbconn, $categories_query);

// Success/error messages
$success_message = '';
$error_message = '';
if (isset($_SESSION['cart_message'])) {
    $success_message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
if (isset($_SESSION['cart_error'])) {
    $error_message = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fffe;
            color: #2d3748;
            line-height: 1.6;
        }
                
        /* Main Content Styles */
        .main-content {
            padding: 2rem 0;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .search-sort-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        
        .search-container {
            flex: 1;
            max-width: 400px;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background-color: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.875rem;
        }
        
        .sort-container {
            position: relative;
        }
        
        .sort-select {
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background: white;
            font-size: 0.875rem;
            color: #374151;
            min-width: 180px;
            cursor: pointer;
        }
        
        .sort-select:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .products-count {
            color: #6b7280;
            font-size: 0.875rem;
            margin-left: auto;
        }
        
        /* Category Filter Tabs */
        .category-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        
        .category-tab {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            background: white;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.2s;
        }
        
        .category-tab:hover {
            color: #22c55e;
            border-color: #22c55e;
            text-decoration: none;
        }
        
        .category-tab.active {
            background: #22c55e;
            color: white;
            border-color: #22c55e;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
            border: 1px solid #f3f4f6;
        }
        
        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f9fafb;
        }
        
        .product-info {
            padding: 1.25rem;
        }
        
        .product-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .product-description {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            line-height: 1.5;
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
            color: #1f2937;
        }
        
        .product-unit {
            color: #9ca3af;
            font-size: 0.75rem;
            margin-left: 0.25rem;
        }
        
        .organic-badge {
            background: #dcfce7;
            color: #166534;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .add-to-cart-btn {
            width: 100%;
            background: #22c55e;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .add-to-cart-btn:hover {
            background: #16a34a;
            transform: translateY(-1px);
        }
        
        /* Sidebar Filters */
        .filters-sidebar {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
            margin-bottom: 2rem;
        }
        
        .filters-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }
        
        .filter-group {
            margin-bottom: 1.5rem;
        }
        
        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control, .form-select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .btn-primary {
            background: #22c55e;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background: #16a34a;
        }
        
        .btn-outline-secondary {
            border: 1px solid #d1d5db;
            color: #6b7280;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-outline-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #374151;
        }
        
        /* No Products State */
        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }
        
        .no-products-icon {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-products h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        /* Alert Messages */
        .alert {
            border-radius: 0.5rem;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .search-sort-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .products-count {
                margin-left: 0;
                margin-top: 0.5rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .category-filters {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    <!-- Main Content -->
    <div class="container main-content">
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
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">All Products</h1>
            
            <!-- Search and Sort Bar -->
            <div class="search-sort-bar">
                <div class="search-container position-relative">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search_term); ?>" 
                           onkeypress="if(event.key==='Enter'){searchProducts(this.value)}">
                </div>
                
                <form method="get" class="sort-container">
                    <?php foreach($_GET as $key => $value): ?>
                        <?php if($key !== 'sort'): ?>
                            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                        <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Sort by Newest</option>
                        <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </form>
                
                <div class="products-count">
                    <?php echo mysqli_num_rows($products_result); ?> products found
                </div>
            </div>
        </div>
        
        <!-- Category Filter Tabs -->
        <div class="category-filters">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => 0])); ?>" 
               class="category-tab <?php echo $category_filter == 0 ? 'active' : ''; ?>">All Categories</a>
            <?php mysqli_data_seek($categories_result, 0); ?>
            <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => $category['category_id']])); ?>" 
                   class="category-tab <?php echo $category_filter == $category['category_id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endwhile; ?>
        </div>
        
        <div class="row">
          
                    
                        
            <!-- Products Grid -->
            <div class="col-lg-9">
                <?php if(mysqli_num_rows($products_result) > 0): ?>
                    <div class="products-grid">
                        <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                            <div class="product-card">
                                <?php if(!empty($product['product_image'])): ?>
                                    <img src="../uploads/products/<?php echo $product['product_image']; ?>" 
                                         class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <img src="../assets/images/product-placeholder.jpg" class="product-image" alt="Product Image">
                                <?php endif; ?>
                                
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-description">
                                        <?php echo mb_strimwidth(htmlspecialchars($product['description']), 0, 100, "..."); ?>
                                    </p>
                                    
                                    <div class="product-meta">
                                        <div>
                                            <span class="product-price">Rs.<?php echo number_format($product['price'], 2); ?></span>
                                            <span class="product-unit">per <?php echo htmlspecialchars($product['category_name'] ?? 'unit'); ?></span>
                                        </div>
                                        <span class="organic-badge">Organic</span>
                                    </div>
                                    
                                    <form action="add_to_cart.php" method="post">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">
                                            <i class="fas fa-plus"></i>
                                            Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-search no-products-icon"></i>
                        <h4>No products found</h4>
                        <p>Try adjusting your filters or search criteria.</p>
                        <a href="products.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function searchProducts(term) {
            const url = new URL(window.location);
            if (term.trim()) {
                url.searchParams.set('search', term);
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }
    </script>
</body>
</html>