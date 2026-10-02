<?php
/**
 * Local product API — serves products from MySQL in FakeStore-compatible JSON for script.js.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$db   = 'myshop';
$user = 'root';
$pass = 'root';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$conn->set_charset('utf8mb4');

$r = $conn->query("SHOW COLUMNS FROM products LIKE 'discount_price'");
if ($r && $r->num_rows === 0) {
    $conn->query("ALTER TABLE products ADD COLUMN discount_price DECIMAL(10,2) NULL DEFAULT NULL AFTER price");
}

$sql = 'SELECT p.id, p.name, p.price, p.description, p.image_url, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC';
$result = $conn->query($sql);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed', 'detail' => $conn->error]);
    $conn->close();
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'id'              => (int) $row['id'],
        'name'           => (string) $row['name'],
        'price'           => (float) $row['price'],
        'discount_price'  => null, // Discount logic needs re-implementation if column exists in SQL
        'description'     => (string) $row['description'],
        'category'        => (string) ($row['category_name'] ?? 'Uncategorized'),
        'image_url'       => !empty($row['image_url']) ? (string) $row['image_url'] : 'placeholder.jpg',
        'rating'          => ['rate' => 0.0, 'count' => 0],
    ];
}

$result->free();
$conn->close();

echo json_encode($rows);
