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
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000000;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest';
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($dbconn, $_GET['search']) : '';

// Build query based on filters
$query = "SELECT p.*, c.name as category_name, u.first_name, u.last_name, u.username 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN users u ON p.seller_id = u.user_id
          WHERE p.stock > 0";

// Add category filter if selected
if ($category_filter > 0) {
    $query .= " AND p.category_id = $category_filter";
}

// Add price range filter
$query .= " AND p.price BETWEEN $min_price AND $max_price";

// Add search term if provided
if (!empty($search_term)) {
    $query .= " AND (p.name LIKE '%$search_term%' OR p.description LIKE '%$search_term%')";
}

// Add sorting
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

// Get all categories for filter dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($dbconn, $categories_query);

// Success message for add to cart
$success_message = '';
$error_message = '';

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Check if product exists and has enough stock
    $product_check = "SELECT * FROM products WHERE product_id = $product_id AND stock >= $quantity";
    $product_result = mysqli_query($dbconn, $product_check);
    
    if (mysqli_num_rows($product_result) > 0) {
        $product = mysqli_fetch_assoc($product_result);
        
        // Check if cart table exists, if not create it
        $cart_table_check = mysqli_query($dbconn, "SHOW TABLES LIKE 'cart'");
        if (mysqli_num_rows($cart_table_check) == 0) {
            $create_cart_table = "CREATE TABLE cart (
                cart_id INT(11) AUTO_INCREMENT PRIMARY KEY,
                consumer_id INT(11) NOT NULL,
                product_id INT(11) NOT NULL,
                quantity INT(11) NOT NULL,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (consumer_id) REFERENCES users(user_id),
                FOREIGN KEY (product_id) REFERENCES products(product_id)
            )";
            mysqli_query($dbconn, $create_cart_table);
        }
        
        // Check if product already in cart
        $cart_check = "SELECT * FROM cart WHERE consumer_id = $consumer_id AND product_id = $product_id";
        $cart_result = mysqli_query($dbconn, $cart_check);
        
        if (mysqli_num_rows($cart_result) > 0) {
            // Update quantity
            $cart_item = mysqli_fetch_assoc($cart_result);
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            // Check if new quantity exceeds stock
            if ($new_quantity <= $product['stock']) {
                $update_cart = "UPDATE cart SET quantity = $new_quantity WHERE cart_id = {$cart_item['cart_id']}";
                if (mysqli_query($dbconn, $update_cart)) {
                    $success_message = "Cart updated successfully!";
                } else {
                    $error_message = "Error updating cart: " . mysqli_error($dbconn);
                }
            } else {
                $error_message = "Cannot add more of this product. Exceeds available stock.";
            }
        } else {
            // Add new item to cart
            $add_to_cart = "INSERT INTO cart (consumer_id, product_id, quantity) VALUES ($consumer_id, $product_id, $quantity)";
            if (mysqli_query($dbconn, $add_to_cart)) {
                $success_message = "Product added to cart!";
            } else {
                $error_message = "Error adding to cart: " . mysqli_error($dbconn);
            }
        }
    } else {
        $error_message = "Product not available in the requested quantity.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .product-card {
            height: 100%;
            transition: transform 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-img {
            height: 200px;
            object-fit: cover;
        }
        .farmer-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255,255,255,0.8);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .filter-card {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Browse Fresh Products</h1>
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
            <div class="col-md-3">
                <div class="card filter-card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="products.php">
                            <!-- Category Filter -->
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select name="category" id="category" class="form-select">
                                    <option value="0">All Categories</option>
                                    <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_filter == $category['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="mb-3">
                                <label class="form-label">Price Range</label>
                                <div class="row g-2">
                                    <div class="col">
                                        <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $min_price; ?>">
                                    </div>
                                    <div class="col">
                                        <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $max_price == 1000000 ? '' : $max_price; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sort By -->
                            <div class="mb-3">
                                <label for="sort_by" class="form-label">Sort By</label>
                                <select name="sort_by" id="sort_by" class="form-select">
                                    <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="price_low" <?php echo ($sort_by == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo ($sort_by == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="name" <?php echo ($sort_by == 'name') ? 'selected' : ''; ?>>Name (A-Z)</option>
                                </select>
                            </div>
                            
                            <!-- Search Term (hidden field to preserve search) -->
                            <?php if(!empty($search_term)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </form>
                        
                        <?php if(!empty($_GET)): ?>
                            <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-md-9">
                <?php if(mysqli_num_rows($products_result) > 0): ?>
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                            <div class="col">
                                <div class="card product-card h-100">
                                    <div class="position-relative">
                                        <?php if(!empty($product['product_image'])): ?>
                                            <img src="../uploads/products/<?php echo $product['product_image']; ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top product-img" alt="No Image">
                                        <?php endif; ?>
                                        <div class="farmer-badge">
                                            <i class="fas fa-user-circle me-1"></i>
                                            <?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?>
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <span class="badge bg-success">$<?php echo number_format($product['price'], 2); ?></span>
                                        </div>
                                        <p class="card-text text-muted small mb-2">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </p>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></p>
                                        <p class="card-text text-muted mt-auto mb-2">
                                            <small><i class="fas fa-box me-1"></i> <?php echo $product['stock']; ?> in stock</small>
                                        </p>
                                        <form method="POST" class="mt-auto">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <div class="input-group mb-3">
                                                <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                                <button type="submit" name="add_to_cart" class="btn btn-primary">
                                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No products found matching your criteria. Try adjusting your filters.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>