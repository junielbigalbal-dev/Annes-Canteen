-- Create the database
CREATE DATABASE IF NOT EXISTS school_canteen;
USE school_canteen;

-- Users table (for both customers and admins)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    is_admin BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(255),
    remember_token_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu items table
CREATE TABLE IF NOT EXISTS menu_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    is_vegetarian BOOLEAN DEFAULT FALSE,
    is_vegan BOOLEAN DEFAULT FALSE,
    is_gluten_free BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    delivery_address TEXT,
    contact_number VARCHAR(20),
    special_instructions TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    item_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    special_instructions TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_id INT,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotions table
CREATE TABLE IF NOT EXISTS promotions (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    min_order_amount DECIMAL(10, 2) DEFAULT 0,
    max_discount DECIMAL(10, 2),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User addresses table
CREATE TABLE IF NOT EXISTS user_addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    address_type ENUM('home', 'work', 'other') DEFAULT 'home',
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User payment methods table
CREATE TABLE IF NOT EXISTS user_payment_methods (
    payment_method_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    payment_type ENUM('credit_card', 'debit_card', 'paypal', 'other') NOT NULL,
    provider VARCHAR(50) NOT NULL,
    account_number VARCHAR(100),
    expiry_date DATE,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active) VALUES 
('admin', 'admin@schoolcanteen.com', '$2y$10$YQ6Z8K2xL9pN3mR5sT7vQOaXQ3Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5', 'Admin', 'User', TRUE, TRUE);

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Breakfast', 'Delicious breakfast items to start your day'),
('Lunch', 'Hearty meals for lunch'),
('Dinner', 'Satisfying dinner options'),
('Sides', 'Tasty side dishes'),
('Beverages', 'Refreshing drinks'),
('Desserts', 'Sweet treats');

-- Insert sample menu items
INSERT INTO menu_items (category_id, name, description, price, is_vegetarian, is_featured, image_url) VALUES
(1, 'Pancake Stack', 'Fluffy pancakes with maple syrup and butter', 8.99, TRUE, TRUE, 'pancakes.jpg'),
(1, 'Vegetable Omelette', 'Three-egg omelette with fresh vegetables', 7.99, TRUE, FALSE, 'omelette.jpg'),
(2, 'Grilled Chicken Sandwich', 'Grilled chicken with lettuce, tomato, and special sauce', 9.99, FALSE, TRUE, 'chicken-sandwich.jpg'),
(2, 'Caesar Salad', 'Fresh romaine lettuce with Caesar dressing and croutons', 8.49, FALSE, FALSE, 'caesar-salad.jpg'),
(3, 'Spaghetti Carbonara', 'Classic Italian pasta with creamy sauce and bacon', 12.99, FALSE, TRUE, 'carbonara.jpg'),
(4, 'French Fries', 'Crispy golden fries with a pinch of salt', 3.99, TRUE, TRUE, 'fries.jpg'),
(5, 'Iced Tea', 'Refreshing iced tea with lemon', 2.49, TRUE, FALSE, 'iced-tea.jpg'),
(6, 'Chocolate Brownie', 'Warm chocolate brownie with vanilla ice cream', 5.99, TRUE, TRUE, 'brownie.jpg');

-- Insert sample promotion
INSERT INTO promotions (code, description, discount_type, discount_value, min_order_amount, max_discount, start_date, end_date) VALUES
('WELCOME10', 'Welcome discount for new customers', 'percentage', 10.00, 20.00, 10.00, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY));

-- Insert sample address for admin
INSERT INTO user_addresses (user_id, address_type, address_line1, city, state, postal_code, country, is_default) VALUES
(1, 'work', '123 School Street', 'Education City', 'Metro', '1001', 'Philippines', TRUE);
