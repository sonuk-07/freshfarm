-- Create database and use it
CREATE DATABASE IF NOT EXISTS freshfarm;
USE freshfarm;

-- USERS table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(100),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    zipcode VARCHAR(20),
    role ENUM('farmer', 'consumer', 'admin') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE
);

-- CATEGORIES table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    image_url VARCHAR(255)
);

-- PRODUCTS table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT,
    category_id INT,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2),
    quantity_available INT,
    unit VARCHAR(50),
    organic BOOLEAN DEFAULT FALSE,
    image_url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (seller_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- ORDERS table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_id INT,
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'canceled') DEFAULT 'pending',
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    shipping_address VARCHAR(255),
    shipping_city VARCHAR(100),
    shipping_state VARCHAR(100),
    shipping_zipcode VARCHAR(20),
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consumer_id) REFERENCES users(user_id)
);

-- ORDER_ITEMS table
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    price_per_unit DECIMAL(10,2),
    subtotal DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- REVIEWS table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    user_id INT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);






-- Insert users



INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zipcode, role)
VALUES
('farmer_john', 'john@example.com', 'hashedpassword', 'John', 'Doe', '1234567890', '123 Green Rd', 'Farmville', 'CA', '90001', 'farmer'),
('consumer_amy', 'amy@example.com', 'hashedpassword', 'Amy', 'Smith', '9876543210', '456 City St', 'Metro', 'NY', '10001', 'consumer'),
;

-- Insert categories
INSERT INTO categories (name, description, image_url)
VALUES 
('Vegetables', 'Fresh and organic vegetables', 'vegetables.jpg'),
('Fruits', 'Seasonal and local fruits', 'fruits.jpg');

-- Insert products
INSERT INTO products (seller_id, category_id, name, description, price, quantity_available, unit, organic, image_url)
VALUES
(1, 1, 'Organic Carrots', 'Sweet and fresh organic carrots', 2.50, 100, 'kg', TRUE, 'carrots.jpg'),
(1, 2, 'Fresh Apples', 'Crisp red apples from the farm', 3.00, 80, 'kg', TRUE, 'apples.jpg');

-- Insert orders
INSERT INTO orders (consumer_id, total_amount, status, shipping_address, shipping_city, shipping_state, shipping_zipcode, payment_method, transaction_id)
VALUES
(2, 10.00, 'processing', '456 City St', 'Metro', 'NY', '10001', 'COD', 'TXN123456');

-- Insert order items
INSERT INTO order_items (order_id, product_id, quantity, price_per_unit, subtotal)
VALUES
(1, 1, 2, 2.50, 5.00),
(1, 2, 1, 5.00, 5.00);

-- Insert reviews
INSERT INTO reviews (product_id, user_id, rating, comment, is_verified_purchase)
VALUES
(1, 2, 5, 'Very fresh carrots, loved them!', TRUE),
(2, 2, 4, 'Great apples, a bit small but tasty.', TRUE);
