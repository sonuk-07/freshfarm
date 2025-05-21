<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

// Handle product submission
if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($dbconn, $_POST['name']);
    $description = mysqli_real_escape_string($dbconn, $_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $category_id = intval($_POST['category_id']);
    
    // Insert product
    $insert_query = "INSERT INTO products (name, description, price, stock, category_id, seller_id) 
                    VALUES ('$name', '$description', $price, $quantity, $category_id, $farmer_id)";
    
    if (mysqli_query($dbconn, $insert_query)) {
        $product_id = mysqli_insert_id($dbconn);
        $success_message = "Product added successfully!";
        
        // Handle product image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['product_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            // Check if file type is allowed
            if (in_array(strtolower($filetype), $allowed)) {
                // Create uploads directory if it doesn't exist
                $upload_dir = "../uploads/products/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $new_filename = "product_" . $product_id . "_" . time() . "." . $filetype;
                $upload_path = $upload_dir . $new_filename;
                
                // Upload file
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    // Update product image in database
                    $image_query = "UPDATE products SET product_image = '$new_filename' WHERE product_id = $product_id";
                    
                    if (!mysqli_query($dbconn, $image_query)) {
                        $image_error = "Error updating product image: " . mysqli_error($dbconn);
                    }
                } else {
                    $image_error = "Error uploading image!";
                }
            } else {
                $image_error = "Invalid file type. Allowed types: jpg, jpeg, png, gif";
            }
        }
        
        // Redirect to products page after successful addition
        if (!isset($image_error)) {
            header("Location: products.php?success=Product added successfully!");
            exit();
        }
    } else {
        $error_message = "Error adding product: " . mysqli_error($dbconn);
    }
}

// Fetch categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($dbconn, $categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
        }
        .sidebar a {
            color: rgba(255,255,255,.75);
            padding: 10px 15px;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background-color: rgba(255,255,255,.1);
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .content-wrapper {
            min-height: calc(100vh - 56px);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-none d-md-block sidebar py-4">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="products.php" class="active"><i class="fas fa-carrot"></i> My Products</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 ms-auto content-wrapper p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Add New Product</h2>
                    <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Products</a>
                </div>
                
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($image_error)): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?php echo $image_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="add_products.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price (per unit)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Quantity Available</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                                        <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product_image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="product_image" name="product_image">
                                <div class="form-text">Recommended size: 800x600 pixels. Max file size: 2MB.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="add_product" class="btn btn-primary btn-lg">Add Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>