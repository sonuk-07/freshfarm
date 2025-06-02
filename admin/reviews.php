<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle review status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id']) && isset($_POST['status'])) {
    $review_id = (int) $_POST['review_id'];
    $status = mysqli_real_escape_string($dbconn, $_POST['status']);
    
    if (in_array($status, ['approved', 'rejected'])) {
        $update_query = "UPDATE product_reviews SET status = '$status' WHERE review_id = $review_id";
        if (mysqli_query($dbconn, $update_query)) {
            $_SESSION['success'] = "Review status updated successfully";
        } else {
            $_SESSION['error'] = "Error updating review status";
        }
    }
}

// Fetch all reviews
$reviews_query = "SELECT r.*, p.name as product_name, u.username as consumer_name, 
                         s.username as seller_name 
                  FROM product_reviews r 
                  JOIN products p ON r.product_id = p.product_id 
                  JOIN users u ON r.consumer_id = u.user_id 
                  JOIN users s ON p.seller_id = s.user_id 
                  ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($dbconn, $reviews_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
                * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }
        
        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #374151;
            font-size: 1.875rem;
            font-weight: 700;
            margin: 0;
        }
        
        .header p {
            color: #6b7280;
            margin: 0.5rem 0 0;
        }

        .main-container {
            padding: 0 2rem 2rem;
        }

        .table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background: #f8f9fa;
            color: #4b5563;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .navigation-tabs {
            display: flex;
            gap: 32px;
            margin-bottom: 32px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
        }

        .nav-tab {
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-tab.active {
            color: #10b981;
            border-bottom-color: #10b981;
        }

        .nav-tab:hover {
            color: #10b981;
        }


        .table td {
            padding: 1rem;
            color: #374151;
            vertical-align: middle;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            font-size: 0.75rem;
            border-radius: 9999px;
        }

        .badge.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge.approved {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge.rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
            font-weight: 500;
        }

        .star-rating {
            color: #fbbf24;
        }

        .review-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 1024px) {
            .main-container {
                padding: 1rem;
            }

            .header {
                padding: 1rem;
            }

            .table-responsive {
                margin: 0 -1rem;
                padding: 0 1rem;
                overflow-x: auto;
            }

            .review-text {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>


    <div class="header">
        <h1>Review Management</h1>
        <p>Manage and moderate product reviews</p>
    </div>

    <div class="main-container">
        <!-- Navigation Tabs -->
        <div class="navigation-tabs">
            <a href="dashboard.php" class="nav-tab">Overview</a>
            <a href="users.php" class="nav-tab">User Management</a>
            <a href="products.php" class="nav-tab">Product Management</a>
            <a href="orders.php" class="nav-tab">Order Management</a>
            <a href="reviews.php" class="nav-tab active">Review Moderation</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" role="alert">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Consumer</th>
                        <th>Seller</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                        <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['consumer_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['seller_name']); ?></td>
                                <td class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </td>
                                <td class="review-text">
                                    <?php echo htmlspecialchars($review['review_text']); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $review['status']; ?>">
                                        <?php echo ucfirst($review['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($review['status'] === 'pending'): ?>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form action="" method="POST" class="d-inline ms-1">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-comment-slash fa-3x mb-3"></i>
                                    <h5>No Reviews Found</h5>
                                    <p class="mb-0">There are no product reviews to display.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
