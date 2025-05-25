<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/index.php");
    exit();
}

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // Check if category has products
    $check_query = "SELECT COUNT(*) as product_count FROM products WHERE category_id = $category_id";
    $check_result = mysqli_query($dbconn, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['product_count'] > 0) {
        $error_message = "Cannot delete category: It has associated products. Remove the products first.";
    } else {
        $delete_query = "DELETE FROM categories WHERE category_id = $category_id";
        if (mysqli_query($dbconn, $delete_query)) {
            $success_message = "Category deleted successfully!";
        } else {
            $error_message = "Error deleting category: " . mysqli_error($dbconn);
        }
    }
}

// Handle category creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($dbconn, $_POST['name']);
    $description = mysqli_real_escape_string($dbconn, $_POST['description']);
    
    if (isset($_POST['category_id']) && is_numeric($_POST['category_id'])) {
        // Update existing category
        $category_id = $_POST['category_id'];
        $update_query = "UPDATE categories SET name = '$name', description = '$description' WHERE category_id = $category_id";
        
        if (mysqli_query($dbconn, $update_query)) {
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = 'category_' . $category_id . '_' . time() . '.' . $filetype;
                    $upload_dir = '../uploads/categories/';               
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        // Update image URL in database
                        $image_query = "UPDATE categories SET image_url = '$new_filename' WHERE category_id = $category_id";
                        mysqli_query($dbconn, $image_query);
                    } else {
                        $error_message = "Failed to upload image.";
                    }
                } else {
                    $error_message = "Invalid file type. Allowed types: jpg, jpeg, png, gif";
                }
            }
            
            $success_message = "Category updated successfully!";
        } else {
            $error_message = "Error updating category: " . mysqli_error($dbconn);
        }
    } else {
        // Create new category
        $insert_query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
        
        if (mysqli_query($dbconn, $insert_query)) {
            $category_id = mysqli_insert_id($dbconn);
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = 'category_' . $category_id . '_' . time() . '.' . $filetype;
                    $upload_dir = '../uploads/categories/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        // Update image URL in database
                        $image_query = "UPDATE categories SET image_url = '$new_filename' WHERE category_id = $category_id";
                        mysqli_query($dbconn, $image_query);
                    } else {
                        $error_message = "Failed to upload image.";
                    }
                } else {
                    $error_message = "Invalid file type. Allowed types: jpg, jpeg, png, gif";
                }
            }
            
            $success_message = "Category created successfully!";
        } else {
            $error_message = "Error creating category: " . mysqli_error($dbconn);
        }
    }
}

// Fetch category for editing if ID is provided
$edit_mode = false;
$category_data = [];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $category_id = $_GET['edit'];
    $edit_query = "SELECT * FROM categories WHERE category_id = $category_id";
    $edit_result = mysqli_query($dbconn, $edit_query);
    
    if (mysqli_num_rows($edit_result) > 0) {
        $category_data = mysqli_fetch_assoc($edit_result);
        $edit_mode = true;
    }
}

// Fetch all categories with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($dbconn, $_GET['search']) : '';

// Build the query with search filter
$query = "SELECT * FROM categories WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}

// Count total records for pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$count_result = mysqli_query($dbconn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $limit);

// Add pagination to the main query
$query .= " ORDER BY name ASC LIMIT $offset, $limit";
$categories_result = mysqli_query($dbconn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Dashboard</title>
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
        .category-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .category-placeholder {
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
                <a href="products.php"><i class="fas fa-box me-2"></i> Products</a>
                <a href="categories.php" class="active"><i class="fas fa-tags me-2"></i> Categories</a>
                <a href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
                <a href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2 class="mb-4"><?php echo $edit_mode ? 'Edit Category' : 'Manage Categories'; ?></h2>
                
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
                
                <!-- Category Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" action="categories.php" enctype="multipart/form-data">
                            <?php if($edit_mode): ?>
                                <input type="hidden" name="category_id" value="<?php echo $category_data['category_id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Category Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $edit_mode ? htmlspecialchars($category_data['name']) : ''; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="image" class="form-label">Category Image</label>
                                    <input type="file" class="form-control" id="image" name="image">
                                    <?php if($edit_mode && !empty($category_data['image_url'])): ?>
                                        <div class="mt-2">
                                            <small>Current image:</small>
                                            <img src="../uploads/categories/<?php echo $category_data['image_url']; ?>" alt="Category Image" class="mt-1 category-image">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_mode ? htmlspecialchars($category_data['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Update Category' : 'Add Category'; ?></button>
                                <?php if($edit_mode): ?>
                                    <a href="categories.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="categories.php" class="row g-3">
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="search" placeholder="Search categories..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Categories Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <?php 
                                        // Count products in this category
                                        $product_count_query = "SELECT COUNT(*) as count FROM products WHERE category_id = {$category['category_id']}";
                                        $product_count_result = mysqli_query($dbconn, $product_count_query);
                                        $product_count_data = mysqli_fetch_assoc($product_count_result);
                                        $product_count = $product_count_data['count'];
                                    ?>
                                    <tr>
                                        <td><?php echo $category['category_id']; ?></td>
                                        <td>
                                            <?php if(!empty($category['image_url'])): ?>
                                                <img src="../uploads/categories/<?php echo $category['image_url']; ?>" alt="Category" class="category-image">
                                            <?php else: ?>
                                                <div class="category-placeholder">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td>
                                            <?php 
                                                $desc = htmlspecialchars($category['description']);
                                                echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc; 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $product_count; ?> products</span>
                                        </td>
                                        <td>
                                            <a href="categories.php?edit=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                            <?php if($product_count == 0): ?>
                                            <a href="categories.php?delete=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-danger" disabled title="Cannot delete: Category has products"><i class="fas fa-trash"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if(mysqli_num_rows($categories_result) == 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No categories found</td>
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
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
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