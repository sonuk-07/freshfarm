<?php
session_start(); // Start the session ONCE, at the very beginning
include '../config/db.php';
// Redirect if not logged in or not a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit();
}

// Include the database connection file (if needed here)
// include '../config/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Dashboard - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .dashboard-header {
            padding: 1.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
            color: #28a745;
        }
        .stats-card p {
            margin: 0.5rem 0 0;
            color: #6c757d;
        }
        .orders-section {
            margin-top: 2rem;
        }
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-card .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            background-color: #cce5ff;
            color: #004085;
        }
        .recommended-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .product-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        .farmer-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .farmer-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .farmer-item img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 1rem;
        }
        .btn-add {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }
        .btn-add:hover {
            background-color: #218838;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>  <div class="container py-4">
        <div class="dashboard-header">
            <h1>Your Dashboard</h1>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3>1</h3>
                    <p>Active Orders</p>
                    <small>Orders in progress</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3>4</h3>
                    <p>Favorite Farmers</p>
                    <small>Farms you regularly buy from</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3>7</h3>
                    <p>Saved Items</p>
                    <small>Products in your wishlist</small>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="orders-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Your Orders</h2>
                        <div class="nav nav-tabs">
                            <button class="nav-link active">Active Orders</button>
                            <button class="nav-link">Order History</button>
                        </div>
                    </div>
                    
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <h5>Order #3002</h5>
                                <small class="text-muted">6/12/2023</small>
                            </div>
                            <span class="status-badge">Shipped</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="mb-0">$29.45</p>
                            <button class="btn btn-outline-primary btn-sm">Track Order</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="recommended-section">
                    <h2>Recommended For You</h2>
                    <p class="text-muted">Based on your previous purchases</p>
                    
                    <div class="product-item">
                        <img src="assets/img/products/apples.jpg" alt="Organic Apples">
                        <div>
                            <h5>Organic Apples</h5>
                            <p class="mb-0">$3.99 / lb</p>
                        </div>
                        <button class="btn btn-add ms-auto">Add</button>
                    </div>

                    <div class="product-item">
                        <img src="assets/img/products/eggs.jpg" alt="Farm Fresh Eggs">
                        <div>
                            <h5>Farm Fresh Eggs</h5>
                            <p class="mb-0">$5.99 / dozen</p>
                        </div>
                        <button class="btn btn-add ms-auto">Add</button>
                    </div>

                    <div class="product-item">
                        <img src="assets/img/products/honey.jpg" alt="Local Honey">
                        <div>
                            <h5>Local Honey</h5>
                            <p class="mb-0">$8.50 / jar</p>
                        </div>
                        <button class="btn btn-add ms-auto">Add</button>
                    </div>

                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-link">View More Products</a>
                    </div>
                </div>

                <div class="farmer-section mt-4">
                    <h2>Your Favorite Farmers</h2>
                    <p class="text-muted">Farms you regularly buy from</p>
                    
                    <div class="farmer-item">
                        <img src="assets/img/farmers/farmer1.jpg" alt="Green Meadows Farm">
                        <div>
                            <h5>Green Meadows Farm</h5>
                            <p class="mb-0">Organic vegetables & fruits</p>
                        </div>
                        <a href="#" class="btn btn-outline-primary btn-sm ms-auto">Visit</a>
                    </div>

                    <div class="farmer-item">
                        <img src="assets/img/farmers/farmer2.jpg" alt="Sunrise Farm">
                        <div>
                            <h5>Sunrise Farm</h5>
                            <p class="mb-0">Free range eggs & poultry</p>
                        </div>
                        <a href="#" class="btn btn-outline-primary btn-sm ms-auto">Visit</a>
                    </div>

                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-link">Explore All Farmers</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
