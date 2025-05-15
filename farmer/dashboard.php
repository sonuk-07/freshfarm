<?php
session_start(); // Start the session ONCE, at the very beginning of the file
include '../config/db.php';
// Redirect if not logged in or not a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') { // Corrected to use $_SESSION['role']
    header("Location: ../auth/login.php");
    exit();
}

// Include the database connection file (if needed here)
// include '../config/db.php';  // Include this if you need database access in this file.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }

        .dashboard-container {
            padding: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid #28a745;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .stat-card i {
            font-size: 2rem;
            color: #28a745;
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .stat-card p {
            color: #666;
            margin-bottom: 0;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: 350px;
        }

        .product-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .product-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
        }

        .btn-add-product {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-add-product:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
        }

        .action-buttons .btn {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            margin: 0 0.2rem;
        }

        .section-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #28a745;
            display: inline-block;
        }

        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container dashboard-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Farmer Dashboard</h1>
            <button class="btn btn-add-product" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Add New Product
            </button>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>$15,231.89</h3>
                    <p>Total Sales</p>
                    <small class="text-success">+30.1% from last month</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>192</h3>
                    <p>Total Orders</p>
                    <small class="text-success">+12.5% from last month</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-box"></i>
                    <h3>3</h3>
                    <p>Active Products</p>
                    <small>3 organic products</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-star"></i>
                    <h3>4.8/5.0</h3>
                    <p>Customer Rating</p>
                    <small>Based on 56 reviews</small>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h2 class="section-title">Sales Overview</h2>
                    <p class="text-muted">Your product sales over the last 6 months</p>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h2 class="section-title">Order Status</h2>
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="product-table">
                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                        <h2 class="section-title mb-0">Your Products</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="path/to/tomatoes.jpg" alt="Tomatoes" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 10px;">
                                            <span>Organic Heirloom Tomatoes</span>
                                        </div>
                                    </td>
                                    <td>$4.99/lb</td>
                                    <td>30 in stock</td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Unit</label>
                            <input type="number" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success">Add Product</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Sales ($)',
                data: [1200, 1800, 3000, 2800, 4500, 3200],
                backgroundColor: '#28a745',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Order Status Chart
    const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Processing', 'Shipped', 'Delivered'],
            datasets: [{
                data: [15, 11, 28, 44],
                backgroundColor: ['#007bff', '#17a2b8', '#ffc107', '#28a745']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    </script>

<?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
