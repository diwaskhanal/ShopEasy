<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit;
}
$conn = new mysqli("localhost", "root", "root", "myshop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete user
if (isset($_GET['delete_user'])) {
    $userId = intval($_GET['delete_user']);
    $conn->query("DELETE FROM users WHERE id = $userId");
    header("Location: admin_dashboard.php");
    exit;
}

// Edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id = intval($_POST['user_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $conn->query("UPDATE users SET username='$username', email='$email' WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit;
}

// Add product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $price = floatval($_POST['price']);
    $category = $conn->real_escape_string($_POST['category']);
    $image = $conn->real_escape_string($_POST['image']);
    $conn->query("INSERT INTO products (title, price, category, image) VALUES ('$title', $price, '$category', '$image')");
    header("Location: admin_dashboard.php");
    exit;
}

// Delete product
if (isset($_GET['delete_product'])) {
    $productId = intval($_GET['delete_product']);
    $conn->query("DELETE FROM products WHERE id = $productId");
    header("Location: admin_dashboard.php");
    exit;
}

$users_result = $conn->query("SELECT id, username, email FROM users");
$orders_result = $conn->query("SELECT * FROM orders");
$products_result = $conn->query("SELECT * FROM products");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <header class="admin-header">
            <h1>Admin Dashboard</h1>
            <a href="admin_logout.php" class="admin-logout">Logout</a>
        </header>

        <section>
            <h2>Registered Users</h2>
            <table>
                <tr><th>ID</th><th>Username</th><th>Email</th><th>Actions</th></tr>
                <?php while ($row = $users_result->fetch_assoc()): ?>
                    <tr>
                        <form method="POST">
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" required></td>
                            <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required></td>
                            <td>
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="edit_user" class="admin-btn green">Save</button>
                                <a href="?delete_user=<?= $row['id'] ?>" class="admin-btn red" onclick="return confirm('Delete this user?');">Delete</a>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>

        <section>
            <h2>Orders</h2>
            <table>
                <tr><th>ID</th><th>User Email</th><th>Items</th><th>Total</th><th>Shipping Address</th><th>shipping Country</th></tr>
                <?php while ($row = $orders_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["id"]) ?></td>
                        <td><?= htmlspecialchars($row["user_email"]) ?></td>
                        <td><?= htmlspecialchars($row["items"]) ?></td>
                        <td>$<?= number_format($row["total_price"], 2) ?></td>
                        <td><?= htmlspecialchars($row["shipping_address"]) ?></td>
                        <td><?= htmlspecialchars($row["shipping_country"]) ?></td>
                        
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>
        <section>
            <h2>Manage Products</h2>
            <form method="POST" style="margin-bottom: 20px;">
                <h3>Add New Product</h3>
                <input type="text" name="title" placeholder="Product Title" required>
                <input type="text" name="price" placeholder="Price" required>
                <input type="text" name="category" placeholder="Category" required>
                <input type="text" name="image" placeholder="Image URL" required>
                <input type="submit" name="add_product" value="Add Product" class="admin-btn green">
            </form>

            <table>
                <tr><th>ID</th><th>Title</th><th>Price</th><th>Category</th><th>Image</th><th>Actions</th></tr>
                <?php while ($row = $products_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td>$<?= number_format($row['price'], 2) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><img src="<?= htmlspecialchars($row['image']) ?>" alt="Product Image" style="width: 50px; height: auto; border-radius: 4px;"></td>
                        <td>
                            <a href="?delete_product=<?= $row['id'] ?>" class="admin-btn red" onclick="return confirm('Delete this product?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>
    </div>
</body>
</html>
<?php $conn->close(); ?>
