<!-- <?php
session_start(); // Start the session at the beginning of the file
?> -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
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
        .navbar-brand img {
            max-height: 40px; /* Adjust as needed */
            margin-right: 10px; /* Add some space between logo and text */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="https://via.placeholder.com/40" alt="Logo">
                FarmFresh Connect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto" method="GET" action="products.php">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search products..." aria-label="Search">
                    <button class="btn btn-outline-success" type="submit">Search</button>
                </form>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">About Us</a></li>
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        echo '<li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Welcome  ' . htmlspecialchars($_SESSION['first_name']) . '
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                                </ul>
                            </li>';
                    } else {
                        echo '<li class="nav-item"><a class="nav-link" href="index.php">Sign In</a></li>
                              <li class="nav-item"><a class="nav-link" href="register.php">Sign Up</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
