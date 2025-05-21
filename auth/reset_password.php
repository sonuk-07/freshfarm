
<?php
session_start();
include '../config/db.php';

$message = '';
$valid_token = false;

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Set timezone to Nepal
    date_default_timezone_set('Asia/Kathmandu');
    
    // Verify token
    $sql = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()";
    $stmt = mysqli_prepare($dbconn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $valid_token = true;
        $reset_data = mysqli_fetch_assoc($result);
        $email = $reset_data['email'];
    } else {
        $message = "Invalid or expired token.";
    }
} else {
    $message = "No token provided.";
}

// Process password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password === $confirm_password) {
        // Update user password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = mysqli_prepare($dbconn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            // Delete used token
            $sql = "DELETE FROM password_resets WHERE email = ?";
            $stmt = mysqli_prepare($dbconn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            
            $message = "Password has been reset successfully. <a href='index.php'>Login now</a>";
        } else {
            $message = "Error updating password: " . mysqli_error($dbconn);
        }
    } else {
        $message = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .reset-container {
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
        <div class="reset-container">
            <h2 class="text-center mb-4">Reset Your Password</h2>
            <?php if($message): ?>
                <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($valid_token): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Reset Password</button>
                </form>
            <?php elseif(!$message): ?>
                <div class="alert alert-warning">
                    Invalid password reset request.
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="index.php" class="text-muted">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
