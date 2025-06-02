<?php
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Optional: You can add custom styles here if needed */
        body, html {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .navbar {
            margin-top: 0;
            margin-bottom: 0;
        }

        .logout-btn:hover {
            background-color: #dc2626;
            color: white;
        }
        .navbar-brand img {
            max-height: 40px; /* Adjust as needed */
            margin-right: 10px; /* Add some space between logo and text */
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: #22c55e !important;
            font-size: 1.5rem;
        }

        .navbar-nav .nav-link {
            color: #64748b !important;
            font-weight: 500;
            margin: 0 0.5rem;
        }

        /* Notification styles */
        .notification-icon {
            position: relative;
            font-size: 1.25rem;
            color: #64748b;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-dropdown {
            width: 320px;
            max-height: 400px;
            overflow-y: auto;
            padding: 0;
        }

        .notification-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            white-space: normal;
        }

        .notification-item:hover {
            background-color: #f3f4f6;
        }

        .notification-item.unread {
            background-color: #f0fdf4;
        }

        .notification-content {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .notification-empty {
            padding: 1rem;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../uploads/logo.avif" alt="Logo">
                FarmFresh
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])) { 
                        // Get unread notifications count
                        $user_id = $_SESSION['user_id'];
                        $count_query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
                        $stmt = mysqli_prepare($dbconn, $count_query);
                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $unread_count = mysqli_fetch_assoc($result)['unread'];
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="notification-icon">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                            <?php
                            $notifications_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                            $stmt = mysqli_prepare($dbconn, $notifications_query);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $notifications = mysqli_stmt_get_result($stmt);

                            if (mysqli_num_rows($notifications) > 0) {
                                while ($notification = mysqli_fetch_assoc($notifications)) {
                                    $is_read_class = $notification['is_read'] ? '' : 'unread';
                                    echo "<div class='notification-item {$is_read_class}' data-id='{$notification['notification_id']}'>
                                            <div class='notification-content'>{$notification['message']}</div>
                                            <div class='notification-time'>" . date('M d, Y', strtotime($notification['created_at'])) . "</div>
                                          </div>";
                                }
                            } else {
                                echo "<div class='notification-empty'>No notifications</div>";
                            }
                            ?>
                        </div>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Welcome <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                    <?php } else { ?>
                        <li class="nav-item"><a class="nav-link" href="index.php">Sign In</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Sign Up</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mark notification as read when clicked
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                fetch('../includes/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notification_id=' + notificationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove unread class
                        this.classList.remove('unread');
                        
                        // Update badge count
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent);
                            if (currentCount > 1) {
                                badge.textContent = currentCount - 1;
                            } else {
                                badge.remove();
                            }
                        }
                    }
                });
            });
        });
    });
    </script>
</body>
</html>
