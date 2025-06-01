<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>FarmFresh Connect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
  <style>
    /* Hero Section */
    .hero {
      height: 700px;
      background-image: url('uploads/banner.png');
      background-size: cover;
      background-position: center;
      position: relative;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hero::before {
      content: "";
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.3));
      z-index: 0;
    }

    .hero .content {
      position: relative;
      z-index: 1;
      text-align: center;
      padding: 2rem;
      max-width: 800px;
    }

    .hero h1 {
      font-size: 3.5rem;
      font-weight: 800;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .hero p {
      font-size: 1.4rem;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    /* Navbar */
    .navbar {
      padding: 1rem 0;
      transition: all 0.3s ease;
    }

    .navbar-brand {
      font-size: 1.5rem;
    }

    .nav-link {
      font-weight: 500;
      padding: 0.5rem 1rem !important;
      transition: color 0.3s ease;
    }

    .nav-link:hover {
      color: var(--primary-green) !important;
    }

    /* Features Section */
    .feature-card {
      padding: 2rem;
      border-radius: 10px;
      background: white;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease;
    }

    .feature-card:hover {
      transform: translateY(-5px);
    }

    .feature-icon {
      font-size: 2.5rem;
      color: #38b000;
    }

    .footer {
      background-color: #111;
      color: #ccc;
      padding: 40px 20px;
    }

    .footer a {
      color: #ccc;
      text-decoration: none;
      transition: color 0.3s ease;
    }

    .footer a:hover {
      color: white;
    }

    .footer .logo {
      font-size: 1.5rem;
      color: white;
      font-weight: bold;
    }

    .footer-links li {
      margin-bottom: 10px;
    }

    /* Buttons */
    .btn {
      padding: 0.8rem 1.5rem;
      border-radius: 5px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-success {
      background-color: var(--primary-green);
      border-color: var(--primary-green);
    }

    .btn-success:hover {
      background-color: var(--dark-green);
      border-color: var(--dark-green);
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand text-success fw-bold" href="#">
      <i class="fas fa-leaf me-2"></i>FarmFresh Connect
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav me-3">
        <li class="nav-item"><a class="nav-link" href="#">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Our Farmers</a></li>
      </ul>
      <a href="auth/login.php" class="btn btn-outline-success me-2">Login</a>
      <a href="auth/signup.php" class="btn btn-success">Sign Up</a>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero">
  <div class="content">
    <h1 class="display-4 fw-bold mb-4">Fresh From Farm to Your Table</h1>
    <p class="lead mb-5">Connect directly with local farmers and enjoy the freshest produce while supporting your community</p>
    <div class="d-flex justify-content-center gap-3">
      <a href="consumer/marketplace.php" class="btn btn-light btn-lg">Shop Now</a>
      <a href="auth/signup.php" class="btn btn-outline-light btn-lg">Join as Farmer</a>
    </div>
  </div>
</section>

<!-- Why Choose Section -->
<section class="py-5 bg-light">
  <div class="container py-5">
    <h2 class="fw-bold text-center mb-3">Why Choose FarmFresh Connect?</h2>
    <p class="text-center mb-5 text-muted">Experience the difference of truly fresh, locally-sourced produce</p>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card text-center h-100">
          <div class="feature-icon">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <h5 class="fw-bold mb-3">Direct from Farmers</h5>
          <p class="text-muted mb-0">No middlemen. Support local farmers and get the best prices.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center h-100">
          <div class="feature-icon">
            <i class="fas fa-bolt"></i>
          </div>
          <h5 class="fw-bold mb-3">Fresh & Fast</h5>
          <p class="text-muted mb-0">From harvest to your doorstep in 24 hours or less.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center h-100">
          <div class="feature-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h5 class="fw-bold mb-3">Quality Guaranteed</h5>
          <p class="text-muted mb-0">Every product is verified for quality and freshness.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Call to Action -->
<section class="cta">
  <div class="container">
    <h2 class="fw-bold mb-4">Ready to Experience Farm Fresh?</h2>
    <p class="lead mb-5">Join thousands of satisfied customers who've made the switch to fresh, local produce</p>
    <a href="consumer/marketplace.php" class="btn btn-light btn-lg">Start Shopping</a>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-4">
        <div class="logo mb-3">
          <i class="fas fa-leaf me-2"></i>FarmFresh Connect
        </div>
        <p class="text-muted">Connecting farmers directly with consumers for the freshest produce.</p>
      </div>
      <div class="col-lg-2 col-md-4">
        <h6 class="text-white fw-bold mb-3">For Consumers</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="consumer/marketplace.php">Browse Products</a></li>
          <li><a href="#">Categories</a></li>
          <li><a href="consumer/cart.php">My Cart</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-md-4">
        <h6 class="text-white fw-bold mb-3">For Farmers</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="auth/signup.php">Join as Farmer</a></li>
          <li><a href="farmer/dashboard.php">Farmer Dashboard</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-md-4">
        <h6 class="text-white fw-bold mb-3">Support</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">Help Center</a></li>
          <li><a href="#">Contact Us</a></li>
          <li><a href="#">Terms of Service</a></li>
        </ul>
      </div>
    </div>
    <hr class="mt-5 mb-4 border-secondary">
    <div class="text-center text-muted">
      &copy; <?php echo date("Y"); ?> FarmFresh Connect. All rights reserved.
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
