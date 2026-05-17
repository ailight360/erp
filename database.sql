-- Create Database
CREATE DATABASE IF NOT EXISTS erp_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE erp_system;

-- 1. Settings & Dropdowns
CREATE TABLE dropdown_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('Product', 'MFG', 'Qty_UoM', 'Pkg_UoM', 'Sources', 'Company') NOT NULL,
    value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE group_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    product_1_id INT NOT NULL,
    product_2_id INT NOT NULL
);

CREATE TABLE company_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logo_path VARCHAR(255),
    company_name VARCHAR(255),
    address TEXT,
    mobile VARCHAR(50),
    email VARCHAR(100)
);

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    mobile VARCHAR(50),
    remarks TEXT
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE,
    setting_value TEXT
);

-- 2. Stock In
CREATE TABLE stock_in (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    shift ENUM('Day', 'Night') NOT NULL,
    mfg_source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE stock_in_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_in_id INT NOT NULL,
    product_type ENUM('Single', 'Group') NOT NULL,
    product_id INT, -- References dropdown ID or Group ID
    qty DECIMAL(10,2),
    qty_unit VARCHAR(50),
    pkg DECIMAL(10,2),
    pkg_unit VARCHAR(50),
    FOREIGN KEY (stock_in_id) REFERENCES stock_in(id) ON DELETE CASCADE
);

-- 3. Stock Out
CREATE TABLE stock_out (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    invoice_no VARCHAR(100),
    company_id INT,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE stock_out_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_out_id INT NOT NULL,
    product_type ENUM('Single', 'Group') NOT NULL,
    product_id INT,
    qty DECIMAL(10,2),
    qty_unit VARCHAR(50),
    pkg DECIMAL(10,2),
    pkg_unit VARCHAR(50),
    FOREIGN KEY (stock_out_id) REFERENCES stock_out(id) ON DELETE CASCADE
);

-- 4. Inventory (View logic mostly, but we can store snapshots if needed)
-- We will calculate this dynamically from Stock In - Stock Out

-- 5. Resin
CREATE TABLE resin_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    shift ENUM('Day', 'Night') NOT NULL,
    transaction_type ENUM('In', 'Out') NOT NULL,
    source VARCHAR(255),
    pp DECIMAL(10,2) DEFAULT 0,
    lldpe DECIMAL(10,2) DEFAULT 0,
    hdpe DECIMAL(10,2) DEFAULT 0,
    fg_pp DECIMAL(10,2) DEFAULT 0,
    tpt DECIMAL(10,2) DEFAULT 0,
    cc DECIMAL(10,2) DEFAULT 0,
    total_bags INT DEFAULT 0,
    total_kgs DECIMAL(10,2) DEFAULT 0
);

-- Seed Default Data
INSERT INTO dropdown_values (type, value) VALUES 
('Product', 'Product A'), ('Product', 'Product B'), ('Product', 'Product C'),
('Qty_UoM', 'Pcs'), ('Qty_UoM', 'KG'), ('Qty_UoM', 'LBS'),
('Pkg_UoM', 'Cartons'), ('Pkg_UoM', 'Bags'), ('Pkg_UoM', 'Dopes'),
('MFG', 'Machine A'), ('MFG', 'Machine B'),
('Sources', 'Supplier X'), ('Sources', 'Internal');

INSERT INTO group_products (group_name, product_1_id, product_2_id) VALUES
('Combo Pack A', 1, 2), ('Combo Pack B', 2, 3);

INSERT INTO settings (setting_key, setting_value) VALUES 
('theme', 'light'), ('accent_color', '#3b82f6'), ('layout', 'auto');

INSERT INTO company_profile (company_name, address, mobile, email) VALUES 
('My ERP Company', '123 Industrial Area', '+123456789', 'info@erp.com');

INSERT INTO companies (name, address, mobile, remarks) VALUES
('ABC Corporation', '456 Business St', '+111111111', 'Regular customer'),
('XYZ Ltd', '789 Trade Ave', '+222222222', 'VIP customer');
