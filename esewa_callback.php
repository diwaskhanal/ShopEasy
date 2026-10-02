<?php
// esewa_callback.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:8888");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log the callback for debugging
file_put_contents('esewa_callback.log', date('Y-m-d H:i:s') . " - Callback: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Database configuration (MAMP settings)
$host = "localhost";
$db = "myshop";
$user = "root";
$pass = "root";
$port = 8889;
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

$conn = new mysqli($host, $user, $pass, $db, $port, $socket);

if ($conn->connect_error) {
    file_put_contents('esewa_callback.log', date('Y-m-d H:i:s') . " - DB Connection Failed: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Process eSewa callback data
if (isset($_POST['transaction_uuid'], $_POST['transaction_code'], $_POST['status'])) {
    $transaction_uuid = $conn->real_escape_string($_POST['transaction_uuid']);
    $transaction_code = $conn->real_escape_string($_POST['transaction_code']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Update order payment status in database
    $stmt = $conn->prepare("UPDATE orders SET transaction_id = ?, payment_status = ? WHERE id = ?");
    $order_id = explode('_', $transaction_uuid)[1]; // Assuming format: ORDER_123
    
    if ($status === 'COMPLETE') {
        $payment_status = 'completed';
        $stmt->bind_param("ssi", $transaction_code, $payment_status, $order_id);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Payment completed successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update order status"]);
        }
    } else {
        $payment_status = 'failed';
        $stmt->bind_param("ssi", $transaction_code, $payment_status, $order_id);
        $stmt->execute();
        echo json_encode(["status" => "error", "message" => "Payment failed"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid callback data"]);
}

$conn->close();
?>