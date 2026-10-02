<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);

header("Content-Type: application/json");
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw_input = file_get_contents("php://input");
file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Raw Input: " . $raw_input . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method", "order_id" => null]);
    exit;
}

function generateEsewaPaymentData($order_id, $amount, $email) {
    $merchant_id = "YOUR_ESEWA_MERCHANT_ID";
    $secret_key = "YOUR_ESEWA_SECRET_KEY";
    $transaction_uuid = "ORDER_" . $order_id;
    $product_code = "EPAYTEST";
    $data = [
        'amount' => $amount,
        'tax_amount' => 0,
        'total_amount' => $amount,
        'transaction_uuid' => $transaction_uuid,
        'product_code' => $product_code,
        'product_service_charge' => 0,
        'product_delivery_charge' => 0,
        'success_url' => "http://localhost:8888/SAD/esewa_success.php",
        'failure_url' => "http://localhost:8888/SAD/esewa_failure.php",
        'signed_field_names' => 'total_amount,transaction_uuid,product_code',
        'customer_name' => $email,
    ];
    $data['signature'] = generateEsewaSignature($data, $secret_key);
    return $data;
}

function generateEsewaSignature($data, $secret_key) {
    $signature_data = $data['total_amount'] . ',' . $data['transaction_uuid'] . ',' . $data['product_code'];
    return base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));
}

function checkout_connect() {
    $host = "localhost";
    $db = "myshop";
    $user = "root";
    $pass = "root";
    $port = 8889;
    $socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

    $conn = @new mysqli($host, $user, $pass, $db, $port, $socket);
    if (!$conn->connect_error) {
        return $conn;
    }
    $conn = new mysqli($host, $user, $pass, $db);
    return $conn;
}

function ensure_orders_schema($conn) {
    $r = $conn->query("SHOW TABLES LIKE 'orders'");
    if (!$r || $r->num_rows === 0) {
        $sql = "CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            items TEXT NOT NULL,
            total_price DECIMAL(10, 2) NOT NULL,
            shipping_name VARCHAR(100) NOT NULL,
            shipping_address VARCHAR(255) NOT NULL,
            shipping_city VARCHAR(100) NOT NULL,
            shipping_zip VARCHAR(20) NOT NULL,
            shipping_country VARCHAR(50) NOT NULL,
            payment_method VARCHAR(50) NOT NULL DEFAULT 'credit',
            transaction_id VARCHAR(255) NOT NULL DEFAULT '',
            payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        if (!$conn->query($sql)) {
            throw new Exception("Failed to create orders table: " . $conn->error);
        }
        file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Created orders table\n", FILE_APPEND);
        return;
    }

    $cols = [];
    $cr = $conn->query("SHOW COLUMNS FROM orders");
    if ($cr) {
        while ($row = $cr->fetch_assoc()) {
            $cols[$row['Field']] = true;
        }
    }

    // Rename user_email to email
    if (!empty($cols['user_email']) && empty($cols['email'])) {
        if (!$conn->query("ALTER TABLE orders CHANGE user_email email VARCHAR(100) NOT NULL")) {
            throw new Exception("Failed to rename user_email to email: " . $conn->error);
        }
        file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Renamed user_email to email\n", FILE_APPEND);
    }
    
    // Refresh cols
    $cols = [];
    $cr = $conn->query("SHOW COLUMNS FROM orders");
    if ($cr) {
        while ($row = $cr->fetch_assoc()) {
            $cols[$row['Field']] = true;
        }
    }

    $add = [
        'payment_method' => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'credit'",
        'transaction_id' => "ALTER TABLE orders ADD COLUMN transaction_id VARCHAR(255) NOT NULL DEFAULT ''",
        'payment_status' => "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending'",
        'shipping_name' => "ALTER TABLE orders ADD COLUMN shipping_name VARCHAR(100) NOT NULL DEFAULT ''",
        'shipping_address' => "ALTER TABLE orders ADD COLUMN shipping_address VARCHAR(255) NOT NULL DEFAULT ''",
        'shipping_city' => "ALTER TABLE orders ADD COLUMN shipping_city VARCHAR(100) NOT NULL DEFAULT ''",
        'shipping_zip' => "ALTER TABLE orders ADD COLUMN shipping_zip VARCHAR(20) NOT NULL DEFAULT ''",
        'shipping_country' => "ALTER TABLE orders ADD COLUMN shipping_country VARCHAR(50) NOT NULL DEFAULT ''",
    ];
    foreach ($add as $field => $alterSql) {
        if (empty($cols[$field])) {
            if (!$conn->query($alterSql)) {
                throw new Exception("Failed to add column $field: " . $conn->error);
            }
            file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Added column $field\n", FILE_APPEND);
        }
    }

    // Align legacy `amount` column with `total_price` used by checkout / my_orders
    $cols2 = [];
    $cr2 = $conn->query("SHOW COLUMNS FROM orders");
    if ($cr2) {
        while ($row = $cr2->fetch_assoc()) {
            $cols2[$row['Field']] = true;
        }
    }
    if (empty($cols2['total_price']) && !empty($cols2['amount'])) {
        if (!$conn->query("ALTER TABLE orders ADD COLUMN total_price DECIMAL(10, 2) NOT NULL DEFAULT 0")) {
            throw new Exception("Failed to add total_price: " . $conn->error);
        }
        if (!$conn->query("UPDATE orders SET total_price = amount")) {
            throw new Exception("Failed to sync total_price from amount: " . $conn->error);
        }
        file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Added total_price and copied from amount\n", FILE_APPEND);
    } elseif (empty($cols2['total_price'])) {
        if (!$conn->query("ALTER TABLE orders ADD COLUMN total_price DECIMAL(10, 2) NOT NULL DEFAULT 0")) {
            throw new Exception("Failed to add total_price: " . $conn->error);
        }
        file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Added column total_price\n", FILE_APPEND);
    }
}

$conn = null;
try {
    $conn = checkout_connect();
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $data = json_decode($raw_input, true);
    file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Parsed Data: " . json_encode($data) . "\n", FILE_APPEND);

    if (!$data || !isset($data['items'], $data['shipping'], $data['total'])) {
        throw new Exception("Missing required order data");
    }

    ensure_orders_schema($conn);

    $email = trim((string) ($data['email'] ?? ''));
    if ($email === '') {
        throw new Exception("Missing email");
    }
    $itemsRaw = $data['items'];
    if (is_array($itemsRaw)) {
        $items = json_encode($itemsRaw, JSON_UNESCAPED_UNICODE);
    } elseif (is_string($itemsRaw) && $itemsRaw !== '') {
        $decoded = json_decode($itemsRaw, true);
        $items = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : json_encode([['title' => 'Order items', 'quantity' => 1]]);
    } else {
        $items = '[]';
    }
    if ($items === false || $items === '') {
        $items = '[]';
    }
    $total_price = round((float) $data['total'], 2);
    $shipping = $data['shipping'];
    if (!is_array($shipping)) {
        throw new Exception("Invalid shipping data");
    }
    $shipKeys = ['name', 'address', 'city', 'zip', 'country'];
    foreach ($shipKeys as $k) {
        if (!isset($shipping[$k]) || trim((string) $shipping[$k]) === '') {
            throw new Exception("Missing shipping field: " . $k);
        }
    }
    $name = trim((string) $shipping['name']);
    $address = trim((string) $shipping['address']);
    $city = trim((string) $shipping['city']);
    $zip = trim((string) $shipping['zip']);
    $country = trim((string) $shipping['country']);

    $methodRaw = isset($data['payment_method']) ? strtolower(trim((string) $data['payment_method'])) : 'credit';
    $payment_method = $methodRaw === 'esewa' ? 'esewa' : 'credit';

    if ($payment_method === 'esewa') {
        $payment_status = 'Pending';
    } else {
        $payment_status = isset($data['payment_status']) ? trim((string) $data['payment_status']) : 'Completed';
        if (strcasecmp($payment_status, 'pending') === 0) {
            $payment_status = 'Completed';
        }
    }

    $sql = "INSERT INTO orders (email, items, total_price, shipping_name, shipping_address, shipping_city, shipping_zip, shipping_country, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("checkout.php prepare failed: " . $conn->error . " | SQL: " . $sql);
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "ssdsssssss",
        $email,
        $items,
        $total_price,
        $name,
        $address,
        $city,
        $zip,
        $country,
        $payment_method,
        $payment_status
    );

    if (!$stmt->execute()) {
        error_log("checkout.php INSERT execute failed: " . $stmt->error . " | errno=" . $stmt->errno);
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $order_id = (int) $conn->insert_id;
    $stmt->close();
    $conn->close();

    echo json_encode([
        "success" => true,
        "message" => "Order stored successfully",
        "order_id" => $order_id,
    ], JSON_UNESCAPED_UNICODE);
    file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Order stored id=$order_id status=$payment_status\n", FILE_APPEND);
} catch (Exception $e) {
    if ($conn instanceof mysqli) {
        @$conn->close();
    }
    error_log("checkout.php order error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Order failed",
        "error" => $e->getMessage(),
        "order_id" => null,
    ], JSON_UNESCAPED_UNICODE);
    file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
}
