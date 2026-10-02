<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// CORS headers
header("Access-Control-Allow-Origin: http://localhost:8888");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// MAMP MySQL configuration
$host = "localhost";
$db = "myshop";
$user = "root";
$pass = "root"; // MAMP default password
$port = 8889; // MAMP MySQL port is usually 8889
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock"; // MAMP MySQL socket

// Create connection
$conn = new mysqli($host, $user, $pass, $db, $port, $socket);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        "error" => "Database connection failed",
        "message" => $conn->connect_error,
        "details" => "Using MAMP settings: host=$host, user=$user, db=$db, port=$port"
    ]);
    exit;
}

// Get the raw POST data
$content = file_get_contents("php://input");
$data = json_decode($content, true);

// Log for debugging
file_put_contents('register_debug.log', date('Y-m-d H:i:s') . " - Received: " . print_r($data, true) . "\n", FILE_APPEND);

if (!$data) {
    echo json_encode(["message" => "No data received", "input" => $content]);
    exit;
}

if (!isset($data['username'], $data['email'], $data['password'])) {
    echo json_encode(["message" => "Missing fields", "received" => $data]);
    exit;
}

$username = $conn->real_escape_string(trim($data['username']));
$email = $conn->real_escape_string(trim($data['email']));
$password = password_hash($data['password'], PASSWORD_BCRYPT);

// Check if email already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$check) {
    echo json_encode(["message" => "Prepare failed: " . $conn->error]);
    exit;
}

$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["message" => "Email already registered"]);
    exit;
}

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(["message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    echo json_encode(["message" => "User registered successfully"]);
} else {
    echo json_encode(["message" => "Registration failed: " . $stmt->error]);
}

$conn->close();
?>