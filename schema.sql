-- ============================================================
-- Product Inventory DataGrid — schema + seed data
-- Run: mysql -u root < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS datagrid_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE datagrid_db;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    status ENUM('IN_STOCK','LOW_STOCK','OUT_OF_STOCK') NOT NULL DEFAULT 'IN_STOCK',
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sku (sku),
    INDEX idx_name (name),
    INDEX idx_is_deleted (is_deleted),
    INDEX idx_active_id (is_deleted, id)   -- matches the pagination WHERE + ORDER BY
) ENGINE=InnoDB;

-- Seed ~2,000 demo rows so pagination/search actually has volume to chew on.
DELIMITER $$
CREATE PROCEDURE seed_products()
BEGIN
    DECLARE i INT DEFAULT 1;
    WHILE i <= 2000 DO
        INSERT INTO products (sku, name, price, stock, status)
        VALUES (
            CONCAT('SKU-', LPAD(i, 6, '0')),
            CONCAT('Product Item ', i),
            ROUND(RAND() * 500 + 5, 2),
            FLOOR(RAND() * 200),
            ELT(FLOOR(RAND() * 3) + 1, 'IN_STOCK', 'LOW_STOCK', 'OUT_OF_STOCK')
        );
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL seed_products();
DROP PROCEDURE seed_products;
