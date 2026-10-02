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
$conn->set_charset('utf8mb4');

function ensure_products_discount_column(mysqli $conn) {
    $r = $conn->query("SHOW COLUMNS FROM products LIKE 'discount_price'");
    if ($r && $r->num_rows === 0) {
        $conn->query("ALTER TABLE products ADD COLUMN discount_price DECIMAL(10,2) NULL DEFAULT NULL AFTER price");
    }
}

ensure_products_discount_column($conn);

function resolve_category_from_post(mysqli $conn) {
    $sel = isset($_POST['category_select']) ? trim((string) $_POST['category_select']) : '';
    if ($sel === '__new__') {
        return isset($_POST['category_new']) ? trim((string) $_POST['category_new']) : '';
    }
    return $sel;
}

function resolve_edit_category_from_post(mysqli $conn) {
    $sel = isset($_POST['edit_category_select']) ? trim((string) $_POST['edit_category_select']) : '';
    if ($sel === '__new__') {
        return isset($_POST['edit_category_new']) ? trim((string) $_POST['edit_category_new']) : '';
    }
    return $sel;
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

// Delete order
if (isset($_GET['delete_order'])) {
    $oid = intval($_GET['delete_order']);
    if ($oid > 0) {
        $st = $conn->prepare("DELETE FROM orders WHERE id = ?");
        if ($st) {
            $st->bind_param("i", $oid);
            $st->execute();
            $st->close();
        }
    }
    header("Location: admin_dashboard.php");
    exit;
}

// Edit order payment status (prepared)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_order_status'])) {
    $oid = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $status = isset($_POST['payment_status']) ? trim((string) $_POST['payment_status']) : '';
    $allowed = ['Pending', 'Completed', 'Cancelled'];
    if ($oid > 0 && in_array($status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $oid);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: admin_dashboard.php");
    exit;
}

// Add product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
    
    $image_url = trim($_POST['image_url'] ?? '');
    $dpRaw = trim($_POST['discount_price'] ?? '');
    $discount_price = $dpRaw === '' ? null : (float) $dpRaw;

    header('Content-Type: application/json');
    if ($name !== '' && $category_id > 0 && $price > 0) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, discount_price, category_id, image_url) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sddis", $name, $price, $discount_price, $category_id, $image_url);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
            }
            $stmt->close();
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Edit product (prepared statement)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $name = isset($_POST['edit_name']) ? trim($_POST['edit_name']) : '';
    $price = isset($_POST['edit_price']) ? (float) $_POST['edit_price'] : 0;
    $category_id = isset($_POST['edit_category_id']) ? (int) $_POST['edit_category_id'] : 0;
    
    $image_url = trim($_POST['edit_image_url'] ?? '');
    $dpRaw = trim($_POST['edit_discount_price'] ?? '');
    $discount_price = $dpRaw === '' ? null : (float) $dpRaw;

    header('Content-Type: application/json');
    if ($id > 0 && $name !== '' && $category_id > 0 && $price > 0) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, discount_price = ?, category_id = ?, image_url = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sddisi", $name, $price, $discount_price, $category_id, $image_url, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
            }
            $stmt->close();
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
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
$orders_result = $conn->query("SELECT * FROM orders ORDER BY id DESC");
$products_result = $conn->query("SELECT p.id, p.name, p.price, p.discount_price, p.image_url, p.category_id, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC");

$category_options = [];
$cr = $conn->query("SELECT DISTINCT name AS category FROM categories ORDER BY name ASC");
if ($cr) {
    while ($crow = $cr->fetch_assoc()) {
        $category_options[] = $crow['category'];
    }
}
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
            <table class="orders-table">
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Summary</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $orders_result->fetch_assoc()):
                    $shipLine = trim(
                        ($row['shipping_name'] ?? '') . ', ' .
                        ($row['shipping_address'] ?? '') . ', ' .
                        ($row['shipping_city'] ?? '') . ' ' .
                        ($row['shipping_zip'] ?? '') . ', ' .
                        ($row['shipping_country'] ?? '')
                    );
                    $rawStatus = (string) ($row['payment_status'] ?? 'Pending');
                    $statusEsc = htmlspecialchars($rawStatus);
                    $oid = (int) $row['id'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['id']) ?></td>
                        <td><?= htmlspecialchars((string) $row['email']) ?></td>
                        <td>
                            <details class="order-ship-details">
                                <summary>Items &amp; shipping</summary>
                                <div class="order-detail-body">
                                    <p><strong>Items (JSON):</strong> <?= htmlspecialchars((string) $row['items']) ?></p>
                                    <p><strong>Shipping:</strong> <?= htmlspecialchars($shipLine) ?></p>
                                    <p><strong>Payment method:</strong> <?= htmlspecialchars((string) ($row['payment_method'] ?? '')) ?></p>
                                    <p><strong>Transaction id:</strong> <?= htmlspecialchars((string) ($row['transaction_id'] ?? '')) ?></p>
                                </div>
                            </details>
                        </td>
                        <td>रू <?= number_format((float) $row['total_price'], 2) ?></td>
                        <td><?= $statusEsc ?></td>
                        <td>
                            <form method="POST" class="inline-order-form">
                                <input type="hidden" name="order_id" value="<?= $oid ?>">
                                <select name="payment_status" class="order-status-select">
                                    <option value="Pending" <?= strcasecmp($rawStatus, 'Pending') === 0 ? 'selected' : '' ?>>Pending</option>
                                    <option value="Completed" <?= strcasecmp($rawStatus, 'Completed') === 0 ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= strcasecmp($rawStatus, 'Cancelled') === 0 ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="edit_order_status" class="admin-btn green">Save</button>
                            </form>
                            <a href="?delete_order=<?= $oid ?>" class="admin-btn red" onclick="return confirm('Delete this order?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>

        <section>
            <h2>Manage Products</h2>
            <form method="POST" class="product-form-block" id="add-product-form">
                <h3>Add New Product</h3>
                <input type="text" name="name" placeholder="Product Name" required>
                <input type="text" name="price" placeholder="List price (रू)" required>
                <input type="text" name="discount_price" placeholder="Discount price (रू, optional)">
                <label for="category_id_add">Category</label>
                <select name="category_id" id="category_id_add" required>
                    <?php 
                    $cat_query = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                    while ($cat = $cat_query->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="image_url" placeholder="Image URL" required>
                <input type="submit" name="add_product" value="Add Product" class="admin-btn green">
            </form>

            <table>
                <tr><th>ID</th><th>Title</th><th>List</th><th>Sale</th><th>Category</th><th>Image</th><th>Actions</th></tr>
                <?php while ($row = $products_result->fetch_assoc()):
                    $dp = isset($row['discount_price']) && $row['discount_price'] !== null ? (float) $row['discount_price'] : null;
                    $productJson = htmlspecialchars(
                        json_encode([
                            'id' => (int) $row['id'],
                            'name' => $row['name'],
                            'price' => (float) $row['price'],
                            'discount_price' => $dp,
                            'category_id' => $row['category_id'],
                            'category' => $row['category_name'],
                            'image_url' => $row['image_url'],
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td>रू <?= number_format((float) $row['price'], 2) ?></td>
                        <td><?= $dp !== null && $dp > 0 ? 'रू ' . number_format($dp, 2) : '—' ?></td>
                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                        <td><img src="<?= htmlspecialchars($row['image_url'] ?? 'https://via.placeholder.com/150') ?>" alt="" style="width: 50px; height: auto; border-radius: 4px;"></td>
                        <td>
                            <button type="button" class="admin-btn blue edit-product-btn" data-product="<?= $productJson ?>">Edit</button>
                            <a href="?delete_product=<?= (int) $row['id'] ?>" class="admin-btn red" onclick="return confirm('Delete this product?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>
    </div>

    <div id="edit-product-modal" class="admin-modal" style="display: none;" aria-hidden="true">
        <div class="admin-modal-backdrop" id="edit-product-modal-backdrop"></div>
        <div class="admin-modal-dialog">
            <button type="button" class="admin-modal-close" id="edit-product-modal-close" aria-label="Close">&times;</button>
            <h3>Edit Product</h3>
            <form method="POST" class="product-form-block" id="edit-product-form">
                <input type="hidden" name="product_id" id="edit_product_id" value="">
                <input type="text" name="edit_name" id="edit_name" placeholder="Product Name" required>
                <input type="text" name="edit_price" id="edit_price" placeholder="List price (रू)" required>
                <input type="text" name="edit_discount_price" id="edit_discount_price" placeholder="Discount price (रू, optional)">
                <label for="edit_category_id">Category</label>
                <select name="edit_category_id" id="edit_category_id" required>
                    <?php 
                    $cat_query = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                    while ($cat = $cat_query->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="edit_image_url" id="edit_image_url" placeholder="Image URL" required>
                <div class="admin-modal-actions">
                    <button type="button" class="admin-btn" id="edit-product-cancel">Cancel</button>
                    <input type="submit" name="edit_product" value="Save Changes" class="admin-btn green">
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            // Helper to show notifications
            function showNotification(message, type) {
                alert(message); // Simple for now
            }

            // AJAX form submission handler
            function setupAjaxForm(formId, successCallback) {
                var form = document.getElementById(formId);
                if (!form) return;
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(form);
                    // Add the submit button name/value to formData because it's not included by default
                    var submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
                    if (submitBtn && submitBtn.name) {
                        formData.append(submitBtn.name, submitBtn.value);
                    }
                    
                    fetch('admin_dashboard.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            successCallback(data);
                        } else {
                            showNotification(data.message || 'Action failed', 'error');
                        }
                    })
                    .catch(e => {
                        console.error(e);
                        showNotification('Action failed', 'error');
                    });
                });
            }

            // Setup AJAX for Add and Edit forms
            setupAjaxForm('add-product-form', function() { location.reload(); });
            setupAjaxForm('edit-product-form', function() { location.reload(); });

            // ... (rest of the script)

            var modal = document.getElementById('edit-product-modal');
            function openModal() {
                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');
            }
            function closeModal() {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }
            document.querySelectorAll('.edit-product-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    try {
                        var p = JSON.parse(btn.getAttribute('data-product'));
                        document.getElementById('edit_product_id').value = p.id;
                        document.getElementById('edit_name').value = p.name || '';
                        document.getElementById('edit_price').value = p.price != null ? p.price : '';
                        document.getElementById('edit_discount_price').value = (p.discount_price != null && p.discount_price > 0) ? p.discount_price : '';
                        
                        var sel = document.getElementById('edit_category_id');
                        var catId = p.category_id || '';
                        sel.value = catId;
                        
                        document.getElementById('edit_image_url').value = p.image_url || '';
                        openModal();
                    } catch (e) {
                        console.error(e);
                        alert('Could not load product data for editing.');
                    }
                });
            });
            document.getElementById('edit-product-modal-close').addEventListener('click', closeModal);
            document.getElementById('edit-product-cancel').addEventListener('click', closeModal);
            document.getElementById('edit-product-modal-backdrop').addEventListener('click', closeModal);
        })();
    </script>
</body>
</html>
<?php $conn->close(); ?>
