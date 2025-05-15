<?php
session_start();

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_otp = $_POST['otp'];
    if ($user_otp == $_SESSION['reset_otp']) {
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid OTP code";
    }
}
?>

<!-- HTML Form -->
<!DOCTYPE html>
<html>
<head><title>Verify OTP</title></head>
<body>
<div class="container">
    <h2>Verify OTP</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit">Verify</button>
    </form>
</div>
</body>
</html>
