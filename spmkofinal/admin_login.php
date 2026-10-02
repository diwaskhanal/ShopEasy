<?php
session_start();

$admin_username = "admin";
$admin_password = "admin123"; // Change this to something secure

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';

    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION["admin_logged_in"] = true;
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Invalid admin credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <h1 class="admin-logo">ShopEasy Admin</h1>
        <div class="admin-box">
            <h2>Admin Login</h2>
            <?php if (!empty($error)) echo "<p class='admin-error'>$error</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="submit" value="Login" class="admin-btn">
            </form>
        </div>
    </div>
</body>
</html>