<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
</head>
<body>
    <?php
    $pageTitle = "Home"; // Set the page title
    include 'includes/header.php';
    include 'includes/navbar.php';
    ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Farm Fresh to Your Table</h1>
            <p class="lead">Support local farmers and enjoy fresh, sustainable produce delivered directly to you.</p>
            <div class="mt-4">
                <button class="btn btn-success btn-lg me-3">Shop Now</button>
                <button class="btn btn-outline-light btn-lg">Become a Seller</button>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="steps">
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <h4>Browse & Select</h4>
                        <p>Explore products from local farmers and add your favorites to cart</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <h4>Place Your Order</h4>
                        <p>Complete your purchase securely and choose delivery preferences</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <h4>Enjoy Farm Fresh</h4>
                        <p>Receive your farm-fresh products and support local community</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Featured Products</h2>
                <a href="#" class="text-success">See all</a>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1567306226416-28f0efdc88ce" alt="Tomatoes">
                        <div class="product-info">
                            <span class="badge bg-success">Organic</span>
                            <h5 class="mt-2">Organic Heirloom Tomatoes</h5>
                            <p class="text-muted">Green Valley Farms</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0">$4.99/lb</span>
                                <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- More product cards here -->
            </div>
        </div>
    </section>

    <!-- Browse Categories -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="mb-4">Browse Categories</h2>
            <div class="row">
                <div class="col-md-2">
                    <div class="category-card">
                        <img src="https://images.unsplash.com/photo-1601004890684-d8cbf643f5f2" alt="Vegetables">
                        <div class="category-overlay">
                            <h5 class="mb-0">Vegetables</h5>
                        </div>
                    </div>
                </div>
                <!-- More category cards here -->
            </div>
        </div>
    </section>

    <!-- Meet Our Farmers -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Meet Our Farmers</h2>
                <a href="#" class="text-success">See all</a>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="farmer-profile">
                        <img src="https://via.placeholder.com/80" alt="Farmer">
                        <h5>John Appleseed</h5>
                        <p class="text-muted">Green Valley Farms</p>
                        <div class="rating">★★★★★</div>
                    </div>
                </div>
                <!-- More farmer profiles here -->
            </div>
        </div>
    </section>

    <!-- Newsletter & Farmer CTA -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="bg-success text-white p-4 rounded">
                        <h3>Are You a Farmer?</h3>
                        <p>Join our platform to sell your products directly to consumers and grow your business.</p>
                        <button class="btn btn-light">Join Now</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bg-white p-4 rounded">
                        <h3>Subscribe to Our Newsletter</h3>
                        <p>Get updates on new products and special offers right to your inbox.</p>
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Enter your email">
                            <button class="btn btn-success">Subscribe</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
