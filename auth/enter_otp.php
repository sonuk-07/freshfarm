
<!DOCTYPE html>
<html>
<head>
    <title>Enter OTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 400px;">
    <div class="bg-white p-4 rounded shadow">
        <h4 class="mb-3">Enter OTP</h4>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="otp" class="form-label">OTP Code</label>
                <input type="text" name="otp" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit</button>
        </form>
    </div>
</div>
</body>
</html>
