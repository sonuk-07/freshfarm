<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/index.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: marketplace.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch product details
$product_query = "SELECT p.*, c.name as category_name, 
                 u.first_name, u.last_name, u.profile_image, u.user_id as farmer_id
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN users u ON p.seller_id = u.user_id
                 WHERE p.product_id = $product_id";
$product_result = mysqli_query($dbconn, $product_query);

if (mysqli_num_rows($product_result) == 0) {
    header("Location: marketplace.php");
    exit();
}

$product = mysqli_fetch_assoc($product_result);

// Fetch related products (same category)
$related_query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 WHERE p.category_id = {$product['category_id']} 
                 AND p.product_id != $product_id
                 AND p.stock > 0
                 ORDER BY RAND() 
                 LIMIT 4";
$related_result = mysqli_query($dbconn, $related_query);

// Success/error messages for add to cart
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
    <title><?php echo htmlspecialchars($product['name']); ?> - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .product-img {
            max-height: 400px;
            object-fit: cover;
            border-radius: 10px;
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
        .related-img {
            height: 150px;
            object-fit: cover;
        }
        .farmer-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .farmer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #495057;
        }
        .category-badge {
            background-color: #e9ecef;
            color: #495057;
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="marketplace.php">Marketplace</a></li>
                <li class="breadcrumb-item"><a href="marketplace.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
        
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
            <!-- Product Image -->
            <div class="col-md-5 mb-4">
                <?php if(!empty($product['product_image'])): ?>
                    <img src="../uploads/products/<?php echo $product['product_image']; ?>" class="img-fluid product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <img src="../assets/images/product-placeholder.jpg" class="img-fluid product-img" alt="Product Image">
                <?php endif; ?>
            </div>
            
            <!-- Product Details -->
            <div class="col-md-7 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                    <span class="text-muted">Product ID: <?php echo $product['product_id']; ?></span>
                </div>
                
                <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="d-flex align-items-center mb-3">
                    <h3 class="text-primary mb-0 me-3">$<?php echo number_format($product['price'], 2); ?></h3>
                    <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                        <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                </div>
                
                <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="mb-4">
                    <p class="mb-1"><strong>Availability:</strong> <?php echo $product['stock']; ?> units available</p>
                    <p class="mb-1"><strong>Added on:</strong> <?php echo date('F j, Y', strtotime($product['created_at'])); ?></p>
                </div>
                
                <form action="add_to_cart.php" method="post" class="mb-4">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="quantity" class="col-form-label">Quantity:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-success" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus me-2"></i>Add to Cart
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Farmer Info -->
                <div class="card farmer-card mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Sold by</h5>
                        <div class="d-flex align-items-center">
                            <div class="farmer-avatar me-3">
                                <?php if(!empty($product['profile_image'])): ?>
                                    <img src="../uploads/profiles/<?php echo $product['profile_image']; ?>" alt="Farmer" class="w-100 h-100 rounded-circle">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($product['first_name'], 0, 1) . substr($product['last_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></h6>
                                <a href="farmer_profile.php?id=<?php echo $product['farmer_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">View Profile</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if(mysqli_num_rows($related_result) > 0): ?>
            <div class="mt-5">
                <h3 class="mb-4">Related Products</h3>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <?php while($related = mysqli_fetch_assoc($related_result)): ?>
                        <div class="col">
                            <div class="card product-card h-100">
                                <?php if(!empty($related['product_image'])): ?>
                                    <img src="../uploads/products/<?php echo $related['product_image']; ?>" class="card-img-top related-img" alt="<?php echo htmlspecialchars($related['name']); ?>">
                                <?php else: ?>
                                    <img src="../assets/images/product-placeholder.jpg" class="card-img-top related-img" alt="Product Image">
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h5>
                                    <p class="card-text text-primary fw-bold mb-2">$<?php echo number_format($related['price'], 2); ?></p>
                                    
                                    <div class="mt-auto">
                                        <a href="product_details.php?id=<?php echo $related['product_id']; ?>" class="btn btn-outline-primary btn-sm w-100">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>