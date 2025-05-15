<?php
session_start(); // Start the session ONCE, at the very beginning
include '../config/db.php';
// Redirect if not logged in or not a consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .dashboard-header {
            padding: 1rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .stats-container {
            margin: 2rem 0;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 100%;
        }
        .stat-card .icon {
            color: #28a745;
            margin-bottom: 1rem;
        }
        .stat-card .trend {
            font-size: 0.875rem;
            color: #28a745;
        }
        .data-table {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .table th {
            font-weight: 600;
            color: #495057;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-shipped {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        .btn-add {
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
        }
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        .role-farmer {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .role-consumer {
            background-color: #fff3e0;
            color: #ef6c00;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="dashboard-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Admin Dashboard</h1>
                <p class="text-muted mb-0">Manage your marketplace and monitor activity</p>
            </div>
            <button class="btn btn-success" type="button">System Settings</button>
        </div>

        <div class="stats-container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="icon mb-3">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <h3 class="h2 mb-2">4</h3>
                        <p class="text-muted mb-1">Total Users</p>
                        <p class="trend mb-0">+12% from last month</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="icon mb-3">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                        <h3 class="h2 mb-2">4</h3>
                        <p class="text-muted mb-1">Total Orders</p>
                        <p class="trend mb-0">+23% from last month</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="icon mb-3">
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                        <h3 class="h2 mb-2">4</h3>
                        <p class="text-muted mb-1">Products</p>
                        <p class="trend mb-0">+7% from last month</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="icon mb-3">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                        <h3 class="h2 mb-2">$12,345</h3>
                        <p class="text-muted mb-1">Revenue</p>
                        <p class="trend mb-0">+18% from last month</p>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="#">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Products</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Orders</a>
            </li>
        </ul>

        <div class="row">
            <div class="col-md-6">
                <div class="data-table">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Recent Users</h5>
                        <button class="btn btn-outline-success btn-add">Add User</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>John Doe</td>
                                    <td><span class="role-badge role-consumer">Consumer</span></td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                                <tr>
                                    <td>Jane Smith</td>
                                    <td><span class="role-badge role-farmer">Farmer</span></td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                                <tr>
                                    <td>Mark Wilson</td>
                                    <td><span class="role-badge role-consumer">Consumer</span></td>
                                    <td><span class="status-badge status-pending">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Sarah Brown</td>
                                    <td><span class="role-badge role-farmer">Farmer</span></td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="#" class="text-success">View all users</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="data-table">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Recent Orders</h5>
                        <button class="btn btn-outline-success btn-add">Add Order</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#ORD-001</td>
                                    <td><span class="status-badge status-delivered">Delivered</span></td>
                                    <td>$45.50</td>
                                </tr>
                                <tr>
                                    <td>#ORD-002</td>
                                    <td><span class="status-badge status-processing">Processing</span></td>
                                    <td>$32.75</td>
                                </tr>
                                <tr>
                                    <td>#ORD-003</td>
                                    <td><span class="status-badge status-shipped">Shipped</span></td>
                                    <td>$78.20</td>
                                </tr>
                                <tr>
                                    <td>#ORD-004</td>
                                    <td><span class="status-badge status-pending">Pending</span></td>
                                    <td>$21.30</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="#" class="text-success">View all orders</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="data-table mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Recent Products</h5>
                <button class="btn btn-outline-success btn-add">Add Product</button>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Organic Apples</td>
                            <td>Green Farms</td>
                            <td>Fruits</td>
                            <td>$3.99/lb</td>
                            <td>45</td>
                        </tr>
                        <tr>
                            <td>Fresh Tomatoes</td>
                            <td>Sunny Fields</td>
                            <td>Vegetables</td>
                            <td>$2.49/lb</td>
                            <td>78</td>
                        </tr>
                        <tr>
                            <td>Free Range Eggs</td>
                            <td>Happy Hens</td>
                            <td>Dairy & Eggs</td>
                            <td>$5.99/dozen</td>
                            <td>24</td>
                        </tr>
                        <tr>
                            <td>Raw Honey</td>
                            <td>Busy Bees</td>
                            <td>Other</td>
                            <td>$8.50/jar</td>
                            <td>15</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <a href="#" class="text-success">View all products</a>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
