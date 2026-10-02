<?php
session_start();

$headline = 'Payment Successful';
$detail = 'Thank you for your purchase.';
$dbOk = false;
$dbNote = '';

$oid = isset($_GET['oid']) ? (int) $_GET['oid'] : 0;

if ($oid > 0) {
    $host = "localhost";
    $db = "myshop";
    $user = "root";
    $pass = "root";
    $port = 8889;
    $socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

    $conn = @new mysqli($host, $user, $pass, $db, $port, $socket);
    if ($conn->connect_error) {
        $conn = new mysqli($host, $user, $pass, $db);
    }

    if ($conn->connect_error) {
        $dbNote = 'Could not connect to the database.';
    } else {
        $conn->set_charset('utf8mb4');
        $status = 'Completed';
        $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $oid);
            if ($stmt->execute()) {
                $dbOk = $stmt->affected_rows > 0;
                if (!$dbOk) {
                    $chk = $conn->prepare("SELECT payment_status FROM orders WHERE id = ? LIMIT 1");
                    if ($chk) {
                        $chk->bind_param("i", $oid);
                        $chk->execute();
                        $row = $chk->get_result()->fetch_assoc();
                        $chk->close();
                        if ($row && strcasecmp((string) $row['payment_status'], 'Completed') === 0) {
                            $dbOk = true;
                        }
                    }
                    if (!$dbOk) {
                        $dbNote = 'No matching pending order was found for this reference.';
                    }
                }
            } else {
                $dbNote = 'Could not update order status.';
            }
            $stmt->close();
        } else {
            $dbNote = 'Database error preparing update.';
        }
        $conn->close();
    }
} else {
    $dbNote = 'Missing or invalid order id (oid).';
}

if ($dbOk) {
    $headline = 'Payment Confirmed and Order Processed';
    $detail = 'Your payment was recorded and your order is complete.';
} elseif ($oid > 0 && $dbNote !== '') {
    $detail = $dbNote;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful - ShopEasy</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .success { color: #2e7d32; }
        .warn { color: #b45309; }
        .button { background: #ff4e50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 16px; }
    </style>
</head>
<body>
    <h1 class="<?= $dbOk ? 'success' : 'warn' ?>"><?= htmlspecialchars($headline) ?></h1>
    <p><?= htmlspecialchars($detail) ?></p>
    <a href="http://localhost:8888" class="button">Continue Shopping</a>

    <script>
        if (window.opener) {
            window.opener.postMessage({ type: 'esewa_payment_success' }, '*');
            setTimeout(function () { window.close(); }, 2000);
        }
    </script>
</body>
</html>
