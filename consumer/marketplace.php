<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/index.php");
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
          WHERE p.stock > 0";

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
    <title>Marketplace - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .product-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-img {
            height: 200px;
            object-fit: cover;
        }
        .filter-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .category-badge {
            background-color: #e9ecef;
            color: #495057;
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Marketplace</h1>
            <a href="cart.php" class="btn btn-success">
                <i class="fas fa-shopping-cart me-2"></i>View Cart
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
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card filter-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filters</h5>
                        <form action="marketplace.php" method="get">
                            <!-- Search -->
                            <?php if(!empty($search_term)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                            <?php endif; ?>
                            
                            <!-- Categories -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Categories</label>
                                <select class="form-select" name="category">
                                    <option value="0">All Categories</option>
                                    <?php mysqli_data_seek($categories_result, 0); ?>
                                    <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Price Range</label>
                                <div class="row g-2">
                                    <div class="col">
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="min_price" value="<?php echo $min_price; ?>" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="max_price" value="<?php echo $max_price; ?>" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sort By -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Sort By</label>
                                <select class="form-select" name="sort">
                                    <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </form>
                        
                        <?php if($category_filter > 0 || $min_price > 0 || $max_price < 1000 || !empty($search_term) || $sort_by != 'newest'): ?>
                            <a href="marketplace.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <?php if(mysqli_num_rows($products_result) > 0): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                            <div class="col">
                                <div class="card product-card h-100">
                                    <?php if(!empty($product['product_image'])): ?>
                                        <img src="../uploads/products/<?php echo $product['product_image']; ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <img src="../assets/images/product-placeholder.jpg" class="card-img-top product-img" alt="Product Image">
                                    <?php endif; ?>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                                        </div>
                                        
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text text-muted small mb-2">By <?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></p>
                                        <p class="card-text text-primary fw-bold mb-2">$<?php echo number_format($product['price'], 2); ?></p>
                                        <p class="card-text small mb-3"><?php echo mb_strimwidth(htmlspecialchars($product['description']), 0, 80, "..."); ?></p>
                                        
                                        <div class="mt-auto d-flex">
                                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary flex-grow-1 me-2">View Details</a>
                                            <form action="add_to_cart.php" method="post">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No products found</h4>
                        <p class="text-muted">Try adjusting your filters or search criteria.</p>
                        <a href="marketplace.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>