<?php
session_start();
include '../config/db.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['farmer', 'admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get product ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product details with conditional ownership for farmers
if ($role === 'admin') {
    $product_query = "SELECT p.*, c.name as category_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.category_id 
                      WHERE p.product_id = ?";
    $stmt = mysqli_prepare($dbconn, $product_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
} else {
    $product_query = "SELECT p.*, c.name as category_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.category_id 
                      WHERE p.product_id = ? AND p.seller_id = ?";
    $stmt = mysqli_prepare($dbconn, $product_query);
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: products.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// Fetch categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($dbconn, $categories_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($dbconn, $_POST['name']);
    $description = mysqli_real_escape_string($dbconn, $_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category_id = intval($_POST['category_id']);

    // Handle image upload
    $image_update = "";
    if (isset($_FILES['product_image']) && $_FILES['product_image']['size'] > 0) {
        $file = $_FILES['product_image'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . $product_id . '_' . time() . '.' . $ext;
        $target = "../uploads/products/" . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $image_update = ", product_image = '$filename'";
        }
    }

    // Build update query
    $update_query = "UPDATE products 
                     SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, updated_at = CURRENT_TIMESTAMP $image_update 
                     WHERE product_id = ?";
    
    if ($role === 'farmer') {
        $update_query .= " AND seller_id = ?";
        $update_stmt = mysqli_prepare($dbconn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssdiiii", $name, $description, $price, $stock, $category_id, $product_id, $user_id);
    } else {
        $update_stmt = mysqli_prepare($dbconn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssdiii", $name, $description, $price, $stock, $category_id, $product_id);
    }

    if (mysqli_stmt_execute($update_stmt)) {
        $success_message = "Product updated successfully!";
        // Refresh product data
        $refetch_query = ($role === 'admin') 
            ? "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = $product_id"
            : "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = $product_id AND p.seller_id = $user_id";
        $product = mysqli_fetch_assoc(mysqli_query($dbconn, $refetch_query));
    } else {
        $error_message = "Error updating product: " . mysqli_error($dbconn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .content-wrapper {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        .product-image {
            max-width: 200px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit Product</h2>
            <a href="products.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <?php if (!empty($product['product_image'])): ?>
                            <img src="../uploads/products/<?php echo $product['product_image']; ?>" class="img-fluid product-image" alt="Product Image">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $product['status'] === 'approved' ? 'success' : 
                                    ($product['status'] === 'rejected' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </p>
                        <p><strong>Added On:</strong> <?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
                    </div>
                </div>

                <form action="" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="category_id" required>
                                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $category['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price (Rs.)</label>
                            <input type="number" class="form-control" name="price" id="price" value="<?php echo $product['price']; ?>" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" id="stock" value="<?php echo $product['stock']; ?>" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="product_image" class="form-label">Update Product Image</label>
                            <input type="file" class="form-control" name="product_image" id="product_image" accept="image/*">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Product
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
