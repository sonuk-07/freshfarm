<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

// Handle review reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['reply'])) {
    $review_id = (int) $_POST['review_id'];
    $reply = mysqli_real_escape_string($dbconn, trim($_POST['reply']));

    $update_query = "UPDATE product_reviews SET farmer_reply = '$reply' 
                     WHERE review_id = $review_id 
                     AND product_id IN (SELECT product_id FROM products WHERE seller_id = $farmer_id)";

    if (mysqli_query($dbconn, $update_query)) {
        $_SESSION['success'] = "Reply added successfully.";
    } else {
        $_SESSION['error'] = "Error adding reply. Please try again.";
    }

    header("Location: farmer_reviews.php");
    exit();
}

// Fetch reviews
$reviews_query = "SELECT r.*, p.name AS product_name, u.username 
                  FROM product_reviews r 
                  JOIN products p ON r.product_id = p.product_id 
                  JOIN users u ON r.consumer_id = u.user_id 
                  WHERE p.seller_id = $farmer_id 
                  ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($dbconn, $reviews_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Reviews - FarmFresh Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for star icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <style>
        .card-title {
            font-weight: bold;
        }
        .rating i {
            font-size: 1.2rem;
        }
        .reply-form textarea {
            resize: vertical;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">Product Reviews</h2>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($review['product_name']); ?></h5>
                    <h6 class="card-subtitle text-muted mb-2">
                        By <?php echo htmlspecialchars($review['username']); ?> on <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                    </h6>

                    <div class="rating mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-secondary'; ?>"></i>
                        <?php endfor; ?>
                    </div>

                    <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>

                    <!-- Reply Section -->
                    <?php if ($review['farmer_reply']): ?>
                        <div class="alert alert-info mt-3">
                            <strong>Your Reply:</strong><br>
                            <?php echo nl2br(htmlspecialchars($review['farmer_reply'])); ?>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="reply-form mt-3">
                            <input type="hidden" name="review_id" value="<?= $review['review_id']; ?>">
                            <div class="mb-3">
                                <label for="reply-<?= $review['review_id']; ?>" class="form-label">Reply to this review:</label>
                                <textarea name="reply" id="reply-<?= $review['review_id']; ?>" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Reply</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
