<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include '../config/db.php';
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Prepared statement to prevent SQL injection
    $sql_user = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($dbconn, $sql_user);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);

    // Check if user exists and verify password
    if ($user_data && password_verify($password, $user_data['password'])) {
        // Set session variables
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user_data['user_id']; // Changed from user_id to id
        $_SESSION['first_name'] = $user_data['first_name'];
        $_SESSION['role'] = $user_data['role'];
        $_SESSION['email'] = $email;

        // Redirect to the appropriate dashboard based on user role
        header("Location: ../{$_SESSION['role']}/dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
    mysqli_close($dbconn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Welcome Back</h2>
            <?php if(isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn btn-success w-100">Login</button>
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="text-muted">Forgot password?</a>
                </div>
                <hr>
                <p class="text-center">Don't have an account? <a href="register.php">Register</a></p>
            </form>
        </div>
    </div>
</body>
</html>
