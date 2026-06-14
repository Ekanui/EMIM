CREATE TABLE IF NOT EXISTS product_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    import_date DATETIME NOT NULL,
    supplier_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    import_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
