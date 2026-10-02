-- Setup script for categories, products, and variations

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Create product_variations table
CREATE TABLE IF NOT EXISTS product_variations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    attribute_name VARCHAR(50), -- e.g., 'Size' or 'Color'
    attribute_value VARCHAR(50), -- e.g., 'M' or 'Red'
    stock_quantity INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert Categories
INSERT INTO categories (name, description) VALUES
('Electronics', 'Gadgets and electronic devices'),
('Clothing', 'Apparel and accessories'),
('Home', 'Home decor and kitchenware'),
('Books', 'Various genres of books'),
('Sports', 'Sports equipment and gear'),
('Beauty', 'Personal care and beauty products');

-- Insert Products
INSERT INTO products (category_id, name, description, price, image_url) VALUES
((SELECT id FROM categories WHERE name = 'Electronics'), 'Smartphone X', 'High-end smartphone', 799.99, 'https://images.unsplash.com/photo-1598327105666-5b8935149301'),
((SELECT id FROM categories WHERE name = 'Electronics'), 'Wireless Headphones', 'Noise cancelling headphones', 199.99, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e'),
((SELECT id FROM categories WHERE name = 'Electronics'), 'Smart Watch', 'Fitness tracker and watch', 149.99, 'https://images.unsplash.com/photo-1557185243-7a91632731c3'),
((SELECT id FROM categories WHERE name = 'Clothing'), 'T-Shirt', 'Cotton crew neck t-shirt', 19.99, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab'),
((SELECT id FROM categories WHERE name = 'Clothing'), 'Jeans', 'Slim fit blue jeans', 49.99, 'https://images.unsplash.com/photo-1542272604-787c3835535d'),
((SELECT id FROM categories WHERE name = 'Clothing'), 'Hoodie', 'Warm fleece hoodie', 39.99, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7'),
((SELECT id FROM categories WHERE name = 'Home'), 'Coffee Mug', 'Ceramic coffee mug', 9.99, 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d'),
((SELECT id FROM categories WHERE name = 'Home'), 'Desk Lamp', 'LED adjustable desk lamp', 29.99, 'https://images.unsplash.com/photo-1534073828943-f801091abb18'),
((SELECT id FROM categories WHERE name = 'Home'), 'Throw Blanket', 'Soft cozy throw blanket', 24.99, 'https://images.unsplash.com/photo-1583847681096-02102047609a'),
((SELECT id FROM categories WHERE name = 'Books'), 'Fiction Novel', 'Best selling mystery novel', 15.99, 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d'),
((SELECT id FROM categories WHERE name = 'Books'), 'Cookbook', 'Healthy recipes for home', 25.99, 'https://images.unsplash.com/photo-1556761175-b413da4b1625'),
((SELECT id FROM categories WHERE name = 'Books'), 'Science Magazine', 'Monthly science insights', 8.99, 'https://images.unsplash.com/photo-1532012193837-0f86235471d8'),
((SELECT id FROM categories WHERE name = 'Sports'), 'Yoga Mat', 'Non-slip yoga mat', 29.99, 'https://images.unsplash.com/photo-1593810391683-50950b730598'),
((SELECT id FROM categories WHERE name = 'Sports'), 'Dumbbell Set', 'Adjustable weight dumbbells', 89.99, 'https://images.unsplash.com/photo-1599058917212-d750089bc07e'),
((SELECT id FROM categories WHERE name = 'Sports'), 'Running Shoes', 'Lightweight running shoes', 79.99, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff'),
((SELECT id FROM categories WHERE name = 'Beauty'), 'Face Cream', 'Moisturizing face cream', 35.99, 'https://images.unsplash.com/photo-1616683693504-3323b16752d5'),
((SELECT id FROM categories WHERE name = 'Beauty'), 'Lipstick', 'Long-lasting matte lipstick', 15.99, 'https://images.unsplash.com/photo-1586444248902-2f64ed7c3df6'),
((SELECT id FROM categories WHERE name = 'Beauty'), 'Shampoo', 'Natural herbal shampoo', 12.99, 'https://images.unsplash.com/photo-1626232766866-963d39571120'),
((SELECT id FROM categories WHERE name = 'Electronics'), 'Bluetooth Speaker', 'Portable waterproof speaker', 59.99, 'https://images.unsplash.com/photo-1589256469067-ea88167bb1d8'),
((SELECT id FROM categories WHERE name = 'Electronics'), 'Laptop Sleeve', 'Protective laptop cover', 25.99, 'https://images.unsplash.com/photo-1603302576837-37561b2e2e02'),
((SELECT id FROM categories WHERE name = 'Clothing'), 'Socks', 'Comfortable cotton socks', 5.99, 'https://images.unsplash.com/photo-1586350977771-b3b0abd50c82'),
((SELECT id FROM categories WHERE name = 'Home'), 'Plant Pot', 'Modern ceramic plant pot', 18.99, 'https://images.unsplash.com/photo-1536640712-4d4c36ff0e4e'),
((SELECT id FROM categories WHERE name = 'Books'), 'Notebook', 'Hardcover ruled notebook', 12.99, 'https://images.unsplash.com/photo-1531346878377-a5be20888e57'),
((SELECT id FROM categories WHERE name = 'Sports'), 'Water Bottle', 'Stainless steel water bottle', 15.99, 'https://images.unsplash.com/photo-1602143407151-7111542de6e8'),
((SELECT id FROM categories WHERE name = 'Beauty'), 'Hand Sanitizer', 'Alcohol-based sanitizer', 4.99, 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a');

-- Insert Variations
INSERT INTO product_variations (product_id, attribute_name, attribute_value, stock_quantity) VALUES
(1, 'Color', 'Black', 50), (1, 'Color', 'Silver', 30),
(2, 'Color', 'Black', 100), (2, 'Color', 'White', 20),
(3, 'Color', 'Black', 60),
(4, 'Size', 'S', 100), (4, 'Size', 'M', 150), (4, 'Size', 'L', 120),
(5, 'Size', '30', 40), (5, 'Size', '32', 50), (5, 'Size', '34', 30),
(6, 'Size', 'M', 80), (6, 'Size', 'L', 70),
(7, 'Color', 'Blue', 200), (7, 'Color', 'Red', 150),
(8, 'Color', 'Black', 90), (8, 'Color', 'White', 40),
(9, 'Color', 'Gray', 110),
(10, 'Format', 'Hardcover', 30), (10, 'Format', 'Paperback', 200),
(11, 'Format', 'Hardcover', 50),
(12, 'Format', 'Paperback', 500),
(13, 'Color', 'Blue', 100), (13, 'Color', 'Green', 80),
(14, 'Weight', '5kg', 20), (14, 'Weight', '10kg', 15),
(15, 'Size', '8', 50), (15, 'Size', '9', 60), (15, 'Size', '10', 40),
(16, 'Size', '50ml', 100),
(17, 'Color', 'Red', 80), (17, 'Color', 'Pink', 90),
(18, 'Size', '250ml', 200),
(19, 'Color', 'Blue', 120),
(20, 'Size', '13-inch', 60), (20, 'Size', '15-inch', 50),
(21, 'Color', 'Black', 300),
(22, 'Color', 'White', 70),
(23, 'Color', 'Black', 150),
(24, 'Color', 'Silver', 200),
(25, 'Size', '100ml', 500);
