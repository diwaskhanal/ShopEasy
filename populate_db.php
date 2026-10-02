<?php
$host = 'localhost';
$db = 'myshop';
$user = 'root';
$pass = 'root';
$port = 8889;
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

$conn = new mysqli($host, $user, $pass, $db, $port, $socket);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = file_get_contents('setup_db.sql');
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!$conn->multi_query($query)) {
            echo "Error: " . $conn->error . "\n";
        }
        // Consume results
        while ($conn->more_results()) {
            $conn->next_result();
        }
    }
}
echo "Database populated successfully.";
$conn->close();
?>