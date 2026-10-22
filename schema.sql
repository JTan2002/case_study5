-- JavaJam Coffee House - Case Study 05 schema (MySQL / MariaDB)
-- Import with:  mysql -u root < schema.sql      (or via phpMyAdmin "Import")

CREATE DATABASE IF NOT EXISTS javajam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE javajam;

-- Remove old tables first (children before parents) so this file can be re-imported to reset everything
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_options;
DROP TABLE IF EXISTS products;

CREATE TABLE users (
  user_id       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(40)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB;

-- One row per coffee blend
CREATE TABLE products (
    product_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(60)  NOT NULL,
    description  VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- One row per sellable option (category) of a product.
-- option_name is 'Null' for products without options (Just Java), else 'Single' / 'Double'.
CREATE TABLE product_options (
    option_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id   INT UNSIGNED NOT NULL,
    option_name  ENUM('Null','Single','Double') NOT NULL,
    option_label VARCHAR(40)  NOT NULL,          -- text shown on the menu page
    price        DECIMAL(6,2) NOT NULL,
    UNIQUE KEY uq_product_option (product_id, option_name),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

-- A checked-out customer order
CREATE TABLE orders (
    order_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total       DECIMAL(8,2) NOT NULL,
    KEY idx_order_date (order_date)
) ENGINE=InnoDB;

-- Lines of an order. unit_price is copied at sale time so that later
-- price updates do not change historical sales reports.
CREATE TABLE order_items (
    item_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    option_id   INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL,
    unit_price  DECIMAL(6,2) NOT NULL,
    line_total  DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (option_id)  REFERENCES product_options(option_id)
) ENGINE=InnoDB;

-- Starting data: the three coffee blends
INSERT INTO products (product_id, name, description) VALUES
 (1, 'Just Java',        'Regular house blend, decaffeinated coffee, or flavor of the day.'),
 (2, 'Cafe au Lait',     'House blended coffee infused into a smooth steamed milk.'),
 (3, 'Iced Cappuccino',  'Sweetened espresso blended with icy-cold milk and served in a chilled glass.');

-- Starting data: ORIGINAL prices (Just Java has one option 'Null'; the others have Single and Double)
INSERT INTO product_options (product_id, option_name, option_label, price) VALUES
 (1, 'Null',   'Endless Cup', 2.00),
 (2, 'Single', 'Single',      2.00),
 (2, 'Double', 'Double',      3.00),
 (3, 'Single', 'Single',      4.75),
 (3, 'Double', 'Double',      5.75);

-- Required starting data: quantity 1 of every product/category already ordered
-- (same rows the Check-Out button creates; Single and Double of one coffee need separate orders).
-- Two orders (order headers with their totals)
INSERT INTO orders (order_id, order_date, total) VALUES (1, NOW(), 8.75), (2, NOW(), 8.75);

-- Order lines: order 1 = Just Java + both Single options, order 2 = both Double options; quantity 1 each,
-- price copied from product_options
INSERT INTO order_items (order_id, product_id, option_id, quantity, unit_price, line_total)
SELECT 1, product_id, option_id, 1, price, price FROM product_options WHERE option_name IN ('Null','Single')
UNION ALL
SELECT 2, product_id, option_id, 1, price, price FROM product_options WHERE option_name = 'Double';
