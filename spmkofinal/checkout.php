<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers - UPDATE CORS FOR MAMP
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:8888");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log the raw request for debugging
$raw_input = file_get_contents("php://input");
file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Raw Input: " . $raw_input . "\n", FILE_APPEND);

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["message" => "Invalid request method"]);
    exit;
}
function generateEsewaPaymentData($order_id, $amount, $email) {
    // eSewa merchant credentials (you'll get these from eSewa)
    $merchant_id = "YOUR_ESEWA_MERCHANT_ID"; // Replace with actual merchant ID
    $secret_key = "YOUR_ESEWA_SECRET_KEY";   // Replace with actual secret key
    
    $transaction_uuid = "ORDER_" . $order_id;
    $product_code = "EPAYTEST"; // Use "EPAYTEST" for testing, actual code for production
    
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
    
    // Generate signature (you'll need to implement this based on eSewa's documentation)
    $data['signature'] = generateEsewaSignature($data, $secret_key);
    
    return $data;
}

function generateEsewaSignature($data, $secret_key) {
    // This is a placeholder - implement based on eSewa's documentation
    $signature_data = $data['total_amount'] . ',' . $data['transaction_uuid'] . ',' . $data['product_code'];
    return base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));
}


// MAMP Database configuration - UPDATED FOR MAMP
$host = "localhost";
$db = "myshop";
$user = "root";
$pass = "root";
$port = 8889;
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

// Connect to database
try {
    $conn = new mysqli($host, $user, $pass, $db, $port, $socket);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Parse input data
    $data = json_decode($raw_input, true);
    
    // Log parsed data
    file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Parsed Data: " . json_encode($data) . "\n", FILE_APPEND);
    
    // Validate input data
    if (!$data || !isset($data['email'], $data['items'], $data['shipping'], $data['total'])) {
        throw new Exception("Missing required order data: " . print_r($data, true));
    }
    
    // Sanitize input
    $email = $conn->real_escape_string($data['email']);
    $items = $conn->real_escape_string(json_encode($data['items']));
    $total_price = (float) $data['total'];
    
    $shipping = $data['shipping'];
    $name = $conn->real_escape_string($shipping['name']);
    $address = $conn->real_escape_string($shipping['address']);
    $city = $conn->real_escape_string($shipping['city']);
    $zip = $conn->real_escape_string($shipping['zip']);
    $country = $conn->real_escape_string($shipping['country']);
    // In your checkout.php, after the shipping information, add:
$payment_method = $conn->real_escape_string($data['payment_method'] ?? 'credit');
$transaction_id = $conn->real_escape_string($data['transaction_id'] ?? '');
$payment_status = $conn->real_escape_string($data['payment_status'] ?? 'pending');

// Update your SQL to include payment fields
$stmt = $conn->prepare("INSERT INTO orders (user_email, items, total_price, shipping_name, shipping_address, shipping_city, shipping_zip, shipping_country, payment_method, transaction_id, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssdssssssss", $email, $items, $total_price, $name, $address, $city, $zip, $country, $payment_method, $transaction_id, $payment_status);
    
    // Make sure the orders table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'orders'";
    $tableExists = $conn->query($tableCheckQuery)->num_rows > 0;
    
    if (!$tableExists) {
        // Create the orders table if it doesn't exist
        $createTableQuery = "CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_email VARCHAR(100) NOT NULL,
            items TEXT NOT NULL,
            total_price DECIMAL(10, 2) NOT NULL,
            shipping_name VARCHAR(100) NOT NULL,
            shipping_address VARCHAR(255) NOT NULL,
            shipping_city VARCHAR(100) NOT NULL,
            shipping_zip VARCHAR(20) NOT NULL,
            shipping_country VARCHAR(50) NOT NULL,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($createTableQuery)) {
            throw new Exception("Failed to create orders table: " . $conn->error);
        }
        
        file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Created orders table\n", FILE_APPEND);
    }
    
    // Prepare and execute statement
    $stmt = $conn->prepare("INSERT INTO orders (user_email, items, total_price, shipping_name, shipping_address, shipping_city, shipping_zip, shipping_country)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssdsssss", $email, $items, $total_price, $name, $address, $city, $zip, $country);
    
    $success = $stmt->execute();
    
    if (!$success) {
        throw new Exception("Execute statement failed: " . $stmt->error);
    }
    
    // Success response
    echo json_encode(["message" => "Order stored successfully"]);
    file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Order stored successfully\n", FILE_APPEND);
    
} catch (Exception $e) {
    // Error response
    echo json_encode([
        "message" => "Order failed",
        "error" => $e->getMessage()
    ]);
    
    file_put_contents('checkout_debug.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
}
?>