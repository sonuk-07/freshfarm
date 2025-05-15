<?php
// 1.  Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freshfarm";

$dbconn = mysqli_connect($servername, $username, $password, $dbname);
if (!$dbconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 2.  Admin User Details
$admin_username = "admin";
$admin_email = "admin@gmail.com";
$admin_password = "admin123";
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
$admin_role = "admin";

// 3. Check if the admin user already exists.  Check by email.
$check_sql = "SELECT user_id FROM users WHERE email = '$admin_email'";
$check_result = mysqli_query($dbconn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
} else {
    // 4.  Insert the Admin User
    $insert_sql = "INSERT INTO users (username, email, password, role) VALUES ('$admin_username', '$admin_email', '$hashed_password', '$admin_role')";

    if (mysqli_query($dbconn, $insert_sql)) {
    } else {
        echo "Error creating admin user: " . mysqli_error($conn);
    }
}

?>