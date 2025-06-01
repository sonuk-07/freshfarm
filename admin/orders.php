<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .top-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .page-subtitle {
            color: #6c757d;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout-btn:hover {
            background: #c82333;
            color: white;
        }
        
        .nav-tabs-custom {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 2rem;
        }
        
        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 0;
            margin-right: 2rem;
            background: none;
        }
        
        .nav-tabs-custom .nav-link.active {
            color: #20c997;
            border-bottom: 2px solid #20c997;
            background: none;
        }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
        }
        
        .data-table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .modern-table {
            margin: 0;
        }
        
        .modern-table thead th {
            background: #f8f9fa;
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .modern-table tbody td {
            padding: 1rem;
            border-top: 1px solid #f1f3f4;
            vertical-align: middle;
        }
        
        .modern-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: 600;
            margin-right: 0.75rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-details h6 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }
        
        .user-details small {
            color: #6c757d;
        }
        
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #e7f3ff; color: #0c5460; }
        .status-shipped { background: #cce7ff; color: #004085; }
        .status-delivered { background: #d1e7dd; color: #0a3622; }
        .status-canceled { background: #f8d7da; color: #721c24; }
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            border: none;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-edit {
            background: #e7f3ff;
            color: #0969da;
        }
        
        .btn-edit:hover {
            background: #dbeafe;
            color: #0969da;
        }
        
        .btn-view {
            background: #fff8e1;
            color: #f57c00;
        }
        
        .btn-view:hover {
            background: #ffecb3;
            color: #f57c00;
        }
        
        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .btn-delete:hover {
            background: #ffcdd2;
            color: #d32f2f;
        }
        
        .filter-btn {
            background: #20c997;
            color: white;
            border: none;
            padding: 0.5rem 2rem;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .filter-btn:hover {
            background: #1aa085;
            color: white;
        }
        
        .form-control, .form-select {
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #20c997;
            box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.15);
        }
        
        .pagination {
            margin-top: 1.5rem;
        }
        
        .page-link {
            border: 1px solid #e1e5e9;
            color: #6c757d;
            padding: 0.5rem 0.75rem;
        }
        
        .page-link:hover {
            background: #f8f9fa;
            border-color: #e1e5e9;
            color: #333;
        }
        
        .page-item.active .page-link {
            background: #20c997;
            border-color: #20c997;
            color: white;
        }
        
        .alert-custom {
            border: none;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .product-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .product-placeholder {
            width: 40px;
            height: 40px;
            background-color: #f8f9fa;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Order Management</h1>
            <p class="page-subtitle">Manage orders, status updates, and tracking</p>
        </div>
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="container-fluid px-4">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-tabs-custom">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">User Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">Product Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="orders.php">Order Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reviews.php">Review Moderation</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="analytics.php">Analytics</a>
            </li>
        </ul>
        
        <!-- Success/Error Messages -->
        <div class="alert alert-success alert-custom" style="display: none;">
            <i class="fas fa-check-circle me-2"></i>
            Order status updated successfully!
        </div>
        
        <!-- Filter Orders -->
        <div class="filter-card">
            <h5 class="filter-title">Filter Orders</h5>
            <form method="GET" action="orders.php" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Search Orders</label>
                    <input type="text" class="form-control" name="search" placeholder="Search by order ID, customer name, or email..." value="">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status Filter</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn filter-btn w-100">
                        <i class="fas fa-search me-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Orders Table -->
        <div class="data-table-card">
            <div class="table-container">
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#1001</td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">J</div>
                                    <div class="user-details">
                                        <h6>John Smith</h6>
                                        <small>@johnsmith</small>
                                    </div>
                                </div>
                            </td>
                            <td>john@example.com</td>
                            <td>May 30, 2025</td>
                            <td>$129.99</td>
                            <td><span class="status-badge status-processing">Processing</span></td>
                            <td>
                                <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#updateModal1">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn btn-view" data-bs-toggle="modal" data-bs-target="#detailsModal1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>#1002</td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">S</div>
                                    <div class="user-details">
                                        <h6>Sarah Johnson</h6>
                                        <small>@sarahj</small>
                                    </div>
                                </div>
                            </td>
                            <td>sarah@example.com</td>
                            <td>May 29, 2025</td>
                            <td>$89.50</td>
                            <td><span class="status-badge status-shipped">Shipped</span></td>
                            <td>
                                <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#updateModal2">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn btn-view" data-bs-toggle="modal" data-bs-target="#detailsModal2">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>#1003</td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">M</div>
                                    <div class="user-details">
                                        <h6>Mike Davis</h6>
                                        <small>@miked</small>
                                    </div>
                                </div>
                            </td>
                            <td>mike@example.com</td>
                            <td>May 28, 2025</td>
                            <td>$199.99</td>
                            <td><span class="status-badge status-delivered">Delivered</span></td>
                            <td>
                                <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#updateModal3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn btn-view" data-bs-toggle="modal" data-bs-target="#detailsModal3">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>#1004</td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">E</div>
                                    <div class="user-details">
                                        <h6>Emily Brown</h6>
                                        <small>@emilyb</small>
                                    </div>
                                </div>
                            </td>
                            <td>emily@example.com</td>
                            <td>May 27, 2025</td>
                            <td>$45.75</td>
                            <td><span class="status-badge status-pending">Pending</span></td>
                            <td>
                                <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#updateModal4">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn btn-view" data-bs-toggle="modal" data-bs-target="#detailsModal4">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>#1005</td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">D</div>
                                    <div class="user-details">
                                        <h6>David Wilson</h6>
                                        <small>@davidw</small>
                                    </div>
                                </div>
                            </td>
                            <td>david@example.com</td>
                            <td>May 26, 2025</td>
                            <td>$310.25</td>
                            <td><span class="status-badge status-canceled">Canceled</span></td>
                            <td>
                                <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#updateModal5">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn btn-view" data-bs-toggle="modal" data-bs-target="#detailsModal5">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-4 pb-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#">Previous</a>
                        </li>
                        <li class="page-item active">
                            <a class="page-link" href="#">1</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">3</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="updateModal1" tabindex="-1" aria-labelledby="updateModalLabel1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel1">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="1001">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Order Status</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="pending">Pending</option>
                                <option value="processing" selected>Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small><i class="fas fa-info-circle me-1"></i> Changing the status will update all items in this order.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal fade" id="detailsModal1" tabindex="-1" aria-labelledby="detailsModalLabel1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel1">Order #1001 Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="mb-3">Order Information</h6>
                            <p class="mb-2"><strong>Date:</strong> May 30, 2025, 2:30 PM</p>
                            <p class="mb-2"><strong>Status:</strong> <span class="status-badge status-processing">Processing</span></p>
                            <p class="mb-2"><strong>Total:</strong> $129.99</p>
                            <p class="mb-2"><strong>Payment Method:</strong> Credit Card</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Customer Information</h6>
                            <p class="mb-2"><strong>Name:</strong> John Smith</p>
                            <p class="mb-2"><strong>Email:</strong> john@example.com</p>
                            <p class="mb-2"><strong>Shipping Address:</strong></p>
                            <p class="mb-0">123 Main Street<br>Anytown, ST 12345</p>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="product-placeholder me-2">
                                                <i class="fas fa-image"></i>
                                            </div>
                                            <div>Wireless Headphones</div>
                                        </div>
                                    </td>
                                    <td>TechStore</td>
                                    <td>$89.99</td>
                                    <td>1</td>
                                    <td>$89.99</td>
                                    <td><span class="status-badge status-processing">Processing</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="product-placeholder me-2">
                                                <i class="fas fa-image"></i>
                                            </div>
                                            <div>Phone Case</div>
                                        </div>
                                    </td>
                                    <td>AccessoryHub</td>
                                    <td>$19.99</td>
                                    <td>2</td>
                                    <td>$39.98</td>
                                    <td><span class="status-badge status-processing">Processing</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>