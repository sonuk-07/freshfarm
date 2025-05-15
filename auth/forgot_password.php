<?php
session_start();
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists in database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($dbconn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        
        // Set timezone to Nepal
        date_default_timezone_set('Asia/Kathmandu');
        
        // Generate expiry time in Nepal timezone
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($dbconn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $email, $token, $expires);

        if (mysqli_stmt_execute($stmt)) {
            // Send email with reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/freshfarm/auth/reset_password.php?token=" . $token;


            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();                                            // Send using SMTP 
                $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   // Enable SMTP authentication 
                $mail->Username   = 'jaiswalsonukr7@gmail.com';                     // SMTP username
                $mail->Password   = 'aqby upcs byyr sngz';                               // SMTP password (app password)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
                $mail->Port       = 587;                                    // TCP port to connect to
                
                // Recipients
                $mail->setFrom('jaiswalsonukr7@gmail.com', 'FarmFresh Connect'); // Use the same email as Username
                $mail->addAddress($email);   // Receiver
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Reset Your FarmFresh Connect Password';
                $mail->Body = '
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .button { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h2>Reset Your Password</h2>
                            <p>Hello,</p>
                            <p>We received a request to reset your password for your FarmFresh Connect account. Click the button below to reset your password:</p>
                            <p><a href="' . $reset_link . '" class="button">Reset Password</a></p>
                            <p>Or copy and paste this link into your browser:</p>
                            <p>' . $reset_link . '</p>
                            <p>This link will expire in 1 hour.</p>
                            <p>If you did not request a password reset, please ignore this email.</p>
                            <p>Thank you,<br>FarmFresh Connect Team</p>
                        </div>
                    </body>
                    </html>
                ';
                $mail->AltBody = 'Reset your password by visiting this link: ' . $reset_link;

                $mail->send();
                $message = "A password reset link has been sent to your email address.";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "Error sending email: " . $mail->ErrorInfo;
                $message_type = "danger";
            }
        } else {
            $message = "Error: " . mysqli_error($dbconn);
            $message_type = "danger";
        }
    } else {
        // Don't reveal if email exists or not for security
        $message = "If your email exists in our system, you will receive a password reset link.";
        $message_type = "info";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - FarmFresh Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .forgot-container {
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
        <div class="forgot-container">
            <h2 class="text-center mb-4">Forgot Password</h2>
            <?php if($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <p class="mb-4">Enter your email address and we'll send you a link to reset your password.</p>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Send Reset Link</button>
                <div class="text-center mt-3">
                    <a href="login.php" class="text-muted">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>