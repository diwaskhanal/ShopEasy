<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?next=' . urlencode('settings.php'));
    exit;
}

$host = 'localhost';
$db = 'myshop';
$user = 'root';
$pass = 'root';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Database connection failed.');
}
$conn->set_charset('utf8mb4');

$uid = (int) $_SESSION['user_id'];
$message = '';
$error = '';

$stmt = $conn->prepare('SELECT id, username, email FROM users WHERE id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) {
    session_destroy();
    header('Location: login.php?next=' . urlencode('settings.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if ($name === '' || $email === '') {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($pass !== '' && $pass !== $pass2) {
        $error = 'Passwords do not match.';
    } elseif ($pass !== '' && strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $chk = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $chk->bind_param('si', $email, $uid);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'That email is already in use.';
        }
        $chk->close();
    }

    if ($error === '') {
        if ($pass !== '') {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $u = $conn->prepare('UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?');
            $u->bind_param('sssi', $name, $email, $hash, $uid);
        } else {
            $u = $conn->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
            $u->bind_param('ssi', $name, $email, $uid);
        }
        if ($u && $u->execute()) {
            $_SESSION['user_email'] = $email;
            $_SESSION['user_username'] = $name;
            $message = 'Profile updated successfully.';
            $profile['username'] = $name;
            $profile['email'] = $email;
        } else {
            $error = 'Could not save changes.';
        }
        if (!empty($u)) {
            $u->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings — ShopEasy</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.html" style="text-decoration:none;color:inherit;"><h1>ShopEasy</h1></a>
            </div>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="my_orders.php">My Orders</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="settings-page">
        <h1>Account Settings</h1>
        <p class="settings-lead">Update your name and email. Your current password cannot be shown; enter a new password below only if you want to change it.</p>
        <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <label for="username">Name</label>
            <input type="text" id="username" name="username" required value="<?= htmlspecialchars($profile['username']) ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($profile['email']) ?>">

            <label for="password">New password</label>
            <input type="password" id="password" name="password" placeholder="Leave blank to keep current password" autocomplete="new-password">

            <label for="password_confirm">Confirm new password</label>
            <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password">

            <button type="submit" name="update_profile" value="1" class="btn-primary settings-submit-btn">Update Profile</button>
        </form>
        <p class="settings-footer-links">
            <a href="index.html">← Back to shop</a>
            <a href="my_orders.php">My orders</a>
        </p>
    </main>
    <?php if ($message && $error === ''): ?>
    <script>
        try {
            var _u = <?= json_encode(['username' => $profile['username'], 'email' => $profile['email']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            localStorage.setItem('user', JSON.stringify(_u));
        } catch (e) {}
    </script>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
