<?php
$host = 'localhost';
$db = 'myshop';
$user = 'root';
$pass = 'root';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Database connection failed.');
}
$conn->set_charset('utf8mb4');

$r = $conn->query("SHOW COLUMNS FROM products LIKE 'description'");
if ($r && $r->num_rows === 0) {
    $conn->query('ALTER TABLE products ADD COLUMN description TEXT NULL AFTER image');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $conn->prepare(
    'SELECT p.id, p.name, p.price, p.description, p.image_url, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

$variations = [];
$related_products = [];
if ($p) {
    $v_stmt = $conn->prepare('SELECT attribute_name, attribute_value, stock_quantity FROM product_variations WHERE product_id = ?');
    $v_stmt->bind_param('i', $id);
    $v_stmt->execute();
    $variations = $v_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $v_stmt->close();
    
    $r_stmt = $conn->prepare('SELECT p.id, p.name, p.price, p.image_url FROM products p JOIN categories c ON p.category_id = c.id WHERE c.name = ? AND p.id != ? LIMIT 4');
    $r_stmt->bind_param('si', $p['category_name'], $id);
    $r_stmt->execute();
    $related_products = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $r_stmt->close();
}

$conn->close();

if (!$p) {
    http_response_code(404);
    $notFound = true;
} else {
    $notFound = false;
    $list = (float) $p['price'];
    $hasSale = false; // Need to implement discount logic if needed, currently not in schema
    $unit = $list;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $notFound ? 'Product' : htmlspecialchars($p['name']) ?> — ShopEasy</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .pd-page { max-width: 1100px; margin: 0 auto; padding: 24px 20px 60px; }
        .pd-back { display: inline-block; margin-bottom: 20px; color: #ff4e50; font-weight: 600; }
        .pd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; }
        @media (max-width: 768px) { .pd-grid { grid-template-columns: 1fr; } }
        .pd-image-box { background: #f8f9fa; border-radius: 10px; padding: 24px; text-align: center; }
        .pd-image-box img { max-width: 100%; max-height: 480px; object-fit: contain; }
        .pd-cat { color: #777; text-transform: capitalize; margin-bottom: 8px; }
        .pd-title { font-size: 2rem; margin: 0 0 16px; line-height: 1.2; }
        .pd-prices { margin-bottom: 20px; }
        .pd-prices .sale { font-size: 1.75rem; font-weight: 700; color: #ff4e50; }
        .pd-desc { color: #444; line-height: 1.7; margin-bottom: 24px; white-space: pre-wrap; }
        .pd-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .pd-actions .btn-primary { padding: 12px 28px; }
    </style>
</head>
<body>
    <div class="pd-page">
        <a class="pd-back" href="index.html#featured">← Back to shop</a>
        <?php if ($notFound): ?>
            <h1>Product not found</h1>
            <p>This product does not exist or was removed.</p>
        <?php else: ?>
            <div class="pd-grid">
                <div class="pd-image-box">
                    <img src="<?= htmlspecialchars($p['image_url'] ?? 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div>
                    <p class="pd-cat"><?= htmlspecialchars($p['category_name']) ?></p>
                    <h1 class="pd-title"><?= htmlspecialchars($p['name']) ?></h1>
                    <div class="pd-prices">
                        <span class="sale">रू <?= number_format($unit, 2) ?></span>
                    </div>
                    <div class="pd-desc"><?= nl2br(htmlspecialchars($p['description'] !== '' ? $p['description'] : 'No description available for this product.')) ?></div>
                    
                    <div class="pd-variations" style="margin-bottom: 20px;">
                        <?php if (!empty($variations)): ?>
                            <label for="variation-select" style="display: block; margin-bottom: 5px;">Select Option:</label>
                            <select id="variation-select" style="padding: 8px; width: 100%; max-width: 200px;">
                                <?php foreach ($variations as $v): ?>
                                    <option value="<?= htmlspecialchars($v['attribute_value']) ?>" data-stock="<?= $v['stock_quantity'] ?>">
                                        <?= htmlspecialchars($v['attribute_name']) ?>: <?= htmlspecialchars($v['attribute_value']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p id="stock-status" style="margin-top: 10px; font-weight: 600;">Stock: <?= !empty($variations) && $variations[0]['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock' ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="pd-actions">
                        <button type="button" class="btn-primary" id="pd-add-cart">Add to Cart</button>
                    </div>
                </div>
            </div>

            <?php if (!empty($related_products)): ?>
            <div style="margin-top: 60px;">
                <h3>Related Products</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($related_products as $rel): ?>
                        <div class="product-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                            <img src="<?= htmlspecialchars($rel['image_url'] ?? 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($rel['name']) ?>" style="width: 100%; height: 150px; object-fit: contain;">
                            <h4 style="margin: 10px 0;"><?= htmlspecialchars($rel['name']) ?></h4>
                            <p>रू <?= number_format($rel['price'], 2) ?></p>
                            <a href="product_details.php?id=<?= $rel['id'] ?>" class="btn-primary" style="display: block; margin-top: 10px; text-align: center; padding: 8px; border-radius: 4px; color: white;">View</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <script>
                (function () {
                    var product = <?= json_encode([
                        'id' => (int) $p['id'],
                        'name' => $p['name'],
                        'price' => $list,
                        'image_url' => $p['image_url'] ?? 'placeholder.jpg',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

                    function unitPrice() {
                        var list = Number(product.price);
                        var d = product.discount_price != null ? Number(product.discount_price) : NaN;
                        if (!isNaN(d) && d > 0 && d < list) return Math.round(d * 100) / 100;
                        return Math.round(list * 100) / 100;
                    }

                    document.getElementById('variation-select')?.addEventListener('change', function() {
                        const selected = this.options[this.selectedIndex];
                        const stock = selected.getAttribute('data-stock');
                        document.getElementById('stock-status').textContent = 'Stock: ' + (stock > 0 ? 'In Stock' : 'Out of Stock');
                    });

                    document.getElementById('pd-add-cart').addEventListener('click', function () {
                        var user = null;
                        try { user = JSON.parse(localStorage.getItem('user') || 'null'); } catch (e) {}
                        if (!user || !user.email) {
                            alert('Please log in on the shop home page to add items to your cart.');
                            window.location.href = 'index.html';
                            return;
                        }

                        const stock = document.getElementById('variation-select') ? document.getElementById('variation-select').options[document.getElementById('variation-select').selectedIndex].getAttribute('data-stock') : 1;
                        if (stock <= 0) {
                            alert('This item is out of stock.');
                            return;
                        }

                        var cart = [];
                        try { cart = JSON.parse(localStorage.getItem('cart') || '[]'); } catch (e) {}
                        if (!Array.isArray(cart)) cart = [];
                        var price = unitPrice();
                        var variation = document.getElementById('variation-select') ? document.getElementById('variation-select').value : null;
                        
                        var found = cart.find(function (x) { return Number(x.id) === product.id && x.variation === variation; });
                        
                        if (found) {
                            found.quantity += 1;
                        } else {
                            cart.push({
                                id: product.id,
                                name: product.name,
                                price: price,
                                image: product.image_url,
                                quantity: 1,
                                variation: variation
                            });
                        }
                        localStorage.setItem('cart', JSON.stringify(cart));
                        alert('Added to cart! Continue shopping or open your cart on the home page.');
                    });
                })();
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
