<?php
/**
 * Dual-mode login:
 * - GET: HTML login page (for redirects from my_orders.php / settings.php).
 * - POST (application/json): JSON API for index.html fetch().
 * - POST (form): Same credentials, sets PHP session, redirects to ?next=
 */
session_set_cookie_params([
    'lifetime' => 86400 * 7,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function allowed_next(?string $n): string
{
    $allowed = ['index.html', 'my_orders.php', 'settings.php'];
    $b = $n ? basename($n) : '';
    return in_array($b, $allowed, true) ? $b : 'index.html';
}

$host = 'localhost';
$db = 'myshop';
$dbUser = 'root';
$dbPass = 'root';

// CORS preflight (JSON API)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . (!empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
    http_response_code(204);
    exit;
}

// HTML login page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_SESSION['user_email'])) {
        header('Location: ' . allowed_next($_GET['next'] ?? 'index.html'));
        exit;
    }
    $next = allowed_next($_GET['next'] ?? 'index.html');
    $err = isset($_GET['error']);
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — ShopEasy</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-body">
    <div class="login-page-shell">
        <div class="login-page-card">
            <h1>ShopEasy</h1>
            <p class="login-page-lead">Sign in to view orders and account settings.</p>
            <?php if ($err): ?>
                <p class="login-page-error">Invalid username or password. Please try again.</p>
            <?php endif; ?>
            <form method="post" action="login.php" class="login-page-form">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">

                <button type="submit" name="form_login" value="1" class="btn-primary login-page-submit">Sign in</button>
            </form>
            <p class="login-page-footer"><a href="index.html">← Back to shop</a></p>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}

// Form POST login (same-origin session cookie)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['form_login'])
    && (empty($_SERVER['CONTENT_TYPE']) || stripos((string) $_SERVER['CONTENT_TYPE'], 'application/json') === false)
) {
    $next = allowed_next($_POST['next'] ?? 'index.html');
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $conn = new mysqli($host, $dbUser, $dbPass, $db);
    if ($conn->connect_error) {
        header('Location: login.php?error=1&next=' . urlencode($next));
        exit;
    }
    $stmt = $conn->prepare('SELECT id, username, email, password FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $stmt->close();
        $conn->close();
        header('Location: login.php?error=1&next=' . urlencode($next));
        exit;
    }
    $u = $res->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!password_verify($password, $u['password'])) {
        header('Location: login.php?error=1&next=' . urlencode($next));
        exit;
    }

    $_SESSION['user_id'] = (int) $u['id'];
    $_SESSION['user_email'] = $u['email'];
    $_SESSION['user_username'] = $u['username'];
    header('Location: ' . $next);
    exit;
}

// JSON API (SPA)
header('Content-Type: application/json');
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');

$conn = new mysqli($host, $dbUser, $dbPass, $db);
if ($conn->connect_error) {
    echo json_encode(['message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username'], $data['password'])) {
    echo json_encode(['message' => 'Missing username or password']);
    exit;
}

$username = trim((string) ($data['username'] ?? ''));
$password = $data['password'];

$stmt = $conn->prepare('SELECT id, username, email, password FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $u = $result->fetch_assoc();
    if (password_verify($password, $u['password'])) {
        $_SESSION['user_id'] = (int) $u['id'];
        $_SESSION['user_email'] = $u['email'];
        $_SESSION['user_username'] = $u['username'];
        echo json_encode([
            'message' => 'Login successful',
            'user' => [
                'username' => $u['username'],
                'email' => $u['email'],
            ],
        ]);
    } else {
        echo json_encode(['message' => 'Invalid username or password']);
    }
} else {
    echo json_encode(['message' => 'Invalid username or password']);
}
$stmt->close();
$conn->close();
