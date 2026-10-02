<?php
session_start();

function format_order_items(?string $json): string
{
    $d = json_decode($json ?? '[]', true);
    if (!is_array($d) || $d === []) {
        return '—';
    }
    $parts = [];
    foreach ($d as $it) {
        if (!is_array($it)) {
            continue;
        }
        $title = htmlspecialchars((string) ($it['title'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
        $q = (int) ($it['quantity'] ?? 1);
        $parts[] = $title . ' × ' . $q;
    }
    return $parts === [] ? '—' : implode(', ', $parts);
}

if (empty($_SESSION['user_email'])) {
    header('Location: login.php?next=' . urlencode('my_orders.php'));
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

$userEmail = $_SESSION['user_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $oid = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    if ($oid > 0) {
        $stmt = $conn->prepare(
            "UPDATE orders SET payment_status = 'Cancelled' WHERE id = ? AND email = ? AND LOWER(TRIM(payment_status)) = 'pending'"
        );
        if ($stmt) {
            $stmt->bind_param('is', $oid, $userEmail);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: my_orders.php');
    exit;
}

$ordersLoadError = false;
$orders = null;
$stmt = null;
$ordersSqlDiagnostics = '';

$priceCol = 'total_price';
$pc = $conn->query("SHOW COLUMNS FROM orders LIKE 'total_price'");
if (!$pc || $pc->num_rows === 0) {
    $pc2 = $conn->query("SHOW COLUMNS FROM orders LIKE 'amount'");
    if ($pc2 && $pc2->num_rows > 0) {
        $priceCol = 'amount';
    }
}
if ($priceCol !== 'total_price' && $priceCol !== 'amount') {
    $priceCol = 'total_price';
}

$sql = 'SELECT id, email, `' . $priceCol . '` AS order_line_total, payment_status, order_date, items FROM orders WHERE email = ? ORDER BY id DESC';
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $ordersLoadError = true;
    $ordersSqlDiagnostics = $conn->error;
    error_log('my_orders.php prepare failed: ' . $conn->error);
} else {
    $stmt->bind_param('s', $userEmail);
    if (!$stmt->execute()) {
        $ordersLoadError = true;
        $ordersSqlDiagnostics = $stmt->error;
        error_log('my_orders.php execute failed: ' . $stmt->error);
        $stmt->close();
        $stmt = null;
    } else {
        $orders = $stmt->get_result();
        if ($orders === false) {
            $ordersLoadError = true;
            $ordersSqlDiagnostics = $conn->error;
            error_log('my_orders.php get_result failed: ' . $conn->error);
            $stmt->close();
            $stmt = null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — ShopEasy</title>
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
                    <li><a href="settings.php">Settings</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="orders-page">
        <h1>My Orders</h1>
        <p class="orders-meta">Signed in as <?= htmlspecialchars($userEmail) ?></p>
        <?php if ($ordersLoadError): ?>
            <p class="orders-system-msg">System Update in Progress - Please try again later.</p>
            <?php if ($ordersSqlDiagnostics !== ''): ?>
                <p class="orders-sql-debug">Error: <?= htmlspecialchars($ordersSqlDiagnostics) ?></p>
            <?php endif; ?>
        <?php else: ?>
        <div class="orders-table-wrap">
            <table class="orders-data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items purchased</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tracking</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders->num_rows === 0): ?>
                        <tr>
                            <td colspan="7" class="orders-empty-cell">You have no previous orders.</td>
                        </tr>
                    <?php endif; ?>
                    <?php while ($row = $orders->fetch_assoc()):
                        try {
                            $dt = new DateTime($row['order_date'] ?? 'now');
                        } catch (Throwable $e) {
                            $dt = new DateTime();
                        }
                        $delivery = (clone $dt)->modify('+5 days');
                        $status = (string) ($row['payment_status'] ?? 'Pending');
                        $canCancel = strcasecmp(trim($status), 'Pending') === 0;
                        $itemsHtml = format_order_items($row['items'] ?? '');
                        ?>
                        <tr>
                            <td><?= (int) $row['id'] ?></td>
                            <td><?= htmlspecialchars($dt->format('Y-m-d H:i')) ?></td>
                            <td class="orders-items-cell"><?= $itemsHtml ?></td>
                            <td>रू <?= number_format((float) ($row['order_line_total'] ?? 0), 2) ?></td>
                            <td><?= htmlspecialchars($status) ?></td>
                            <td class="tracking-cell">Expected Delivery: <?= htmlspecialchars($delivery->format('Y-m-d')) ?></td>
                            <td>
                                <?php if ($canCancel): ?>
                                    <form method="POST" class="cancel-form" onsubmit="return confirm('Cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" name="cancel_order" value="1" class="cancel-btn">Cancel Order</button>
                                    </form>
                                <?php else: ?>
                                    <span class="orders-action-dash">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <p class="orders-footer-links">
            <a class="btn-link-site" href="index.html">← Continue shopping</a>
            <a class="btn-link-site" href="settings.php">Account settings</a>
        </p>
    </main>
</body>
</html>
<?php
if ($stmt) {
    $stmt->close();
}
$conn->close();
