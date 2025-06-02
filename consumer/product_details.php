<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit();
}

$consumer_id = $_SESSION['user_id'];

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
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
    header("Location: products.php");
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
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="products.php">Marketplace</a></li>
            <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5 mb-4">
            <?php if (!empty($product['product_image'])): ?>
                <img src="../uploads/products/<?php echo $product['product_image']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php else: ?>
                <img src="../assets/images/product-placeholder.jpg" class="img-fluid rounded" alt="Product Image">
            <?php endif; ?>
        </div>
        <div class="col-md-7 mb-4">
            <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
            <h1 class="mb-3 fw-bold"><?php echo htmlspecialchars($product['name']); ?></h1>
            <h3 class="text-primary mb-2">Rs.<?php echo number_format($product['price'], 2); ?></h3>
            <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
            </span>
            <p class="mt-3 text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <form action="add_to_cart.php" method="post" class="my-4">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <div class="input-group">
                    <label class="input-group-text" for="quantity">Qty</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                    <button type="submit" class="btn btn-success" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                    </button>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <?php if (!empty($product['profile_image'])): ?>
                            <img src="../uploads/profiles/<?php echo $product['profile_image']; ?>" alt="Farmer" class="rounded-circle" width="60" height="60">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <?php echo strtoupper(substr($product['first_name'], 0, 1) . substr($product['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></h6>
                        <a href="farmer_profile.php?id=<?php echo $product['farmer_id']; ?>" class="btn btn-sm btn-outline-primary mt-1">View Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="mt-4">
        <h3>Write a Review</h3>
        <form action="submit_review.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <div class="mb-3">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-select" required>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Very Good</option>
                    <option value="3">3 - Good</option>
                    <option value="2">2 - Fair</option>
                    <option value="1">1 - Poor</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Your Review</label>
                <textarea name="review_text" class="form-control" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    </div>

    <div class="mt-5">
        <h3>Product Reviews</h3>
        <?php
        $reviews_query = "SELECT r.*, u.username 
                          FROM product_reviews r 
                          JOIN users u ON r.consumer_id = u.user_id 
                          WHERE r.product_id = $product_id AND r.status = 'approved' 
                          ORDER BY r.created_at DESC";
        $reviews_result = mysqli_query($dbconn, $reviews_query);
        if (mysqli_num_rows($reviews_result) > 0):
            while ($review = mysqli_fetch_assoc($reviews_result)):
        ?>
            <div class="border p-3 rounded mb-3">
                <div class="d-flex justify-content-between">
                    <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                    <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                </div>
                <div class="mt-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                    <?php endfor; ?>
                </div>
                <p class="mb-1"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                <?php if ($review['farmer_reply']): ?>
                    <div class="bg-light p-2 mt-2">
                        <strong>Farmer's Reply:</strong><br>
                        <?php echo nl2br(htmlspecialchars($review['farmer_reply'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; else: ?>
            <p class="text-muted">No reviews yet</p>
        <?php endif; ?>
    </div>

    <?php if (mysqli_num_rows($related_result) > 0): ?>
        <div class="mt-5">
            <h3>Related Products</h3>
            <div class="row g-3">
                <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                    <div class="col-md-3">
                        <div class="card h-100">
                            <?php if (!empty($related['product_image'])): ?>
                                <img src="../uploads/products/<?php echo $related['product_image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($related['name']); ?>">
                            <?php else: ?>
                                <img src="../assets/images/product-placeholder.jpg" class="card-img-top" alt="Product Image">
                            <?php endif; ?>
                            <div class="card-body">
                                <h6 class="card-title fw-bold"><?php echo htmlspecialchars($related['name']); ?></h6>
                                <p class="text-primary">Rs.<?php echo number_format($related['price'], 2); ?></p>
                                <a href="product_details.php?id=<?php echo $related['product_id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
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