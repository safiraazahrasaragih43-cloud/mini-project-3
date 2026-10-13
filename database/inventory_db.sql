-- Database Schema untuk Mini Project 3: Inventory Management System
-- Pertemuan 4: Relational Database + Advanced PHP

CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

-- 1. Tabel Users (Authentication & Authorization: admin / staff)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel User Profiles (Relasi One-to-One dengan users)
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Categories (Relasi One-to-Many ke products)
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Products (Tabel Utama Produk)
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Suppliers
CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(30),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Product Suppliers (Junction Table Many-to-Many: products <-> suppliers)
CREATE TABLE IF NOT EXISTS product_suppliers (
    product_id INT NOT NULL,
    supplier_id INT NOT NULL,
    supplier_price DECIMAL(12,2) DEFAULT NULL,
    PRIMARY KEY (product_id, supplier_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel Stock Movements (Histori Transaksi Masuk dan Keluar)
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('IN', 'OUT') NOT NULL,
    quantity INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- DATA AWAL / SEEDER
-- Password default untuk admin dan staff: "password123"
-- Hash: $2y$10$qRMgTB7vE9u0O7o14bE3o.7b175Y4jE2Ew5w9d8AOm9lF8uE9F6gW (atau generate via PHP)
-- Di bawah ini hash untuk 'admin123' dan 'staff123'
-- ========================================================

-- Akun Default:
-- 1. Username: admin | Password: password123 (Role: admin)
-- 2. Username: staff | Password: password123 (Role: staff)
INSERT INTO users (id, username, password_hash, role) VALUES
(1, 'admin', '$2y$10$vUUDnIg3l.I0dhqCwd6CHuSKqyHZK4GtFJq9fsDIe2VV7d4DBlV2a', 'admin'),
(2, 'staff', '$2y$10$vUUDnIg3l.I0dhqCwd6CHuSKqyHZK4GtFJq9fsDIe2VV7d4DBlV2a', 'staff')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO user_profiles (user_id, full_name, email, phone) VALUES
(1, 'Administrator Gudang', 'admin@inventory.local', '081234567890'),
(2, 'Staff Operasional', 'staff@inventory.local', '089876543210')
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Seed Categories
INSERT INTO categories (id, name, description) VALUES
(1, 'Kamera Analog & Retro', 'Kamera film 35mm, point and shoot retro vintage'),
(2, 'Digicam & Compact', 'Kamera digital saku klasik era 2000-an'),
(3, 'Lensa & Aksesoris', 'Lensa fix, zoom, filter, strap, dan tas kamera'),
(4, 'Film Roll & Baterai', 'Roll film color/BW, baterai khusus kamera jadul')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Suppliers
INSERT INTO suppliers (id, name, contact_person, phone, address) VALUES
(1, 'PT Retro Optik Nusantara', 'Budi Santoso', '081122334455', 'Jl. Malioboro No. 45, Yogyakarta'),
(2, 'DigiStore Importir Utama', 'Siti Rahma', '082233445566', 'Jl. Mangga Dua Raya No. 12, Jakarta'),
(3, 'Analog Supply Co.', 'Andi Pratama', '083344556677', 'Jl. Riau No. 88, Bandung')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Products
INSERT INTO products (id, category_id, sku, name, price, stock, minimum_stock) VALUES
(1, 1, 'CAM-OLY-01', 'Olympus Trip 35 Vintage', 1250000.00, 8, 3),
(2, 1, 'CAM-CAN-02', 'Canon Canonet QL17 GIII', 2100000.00, 2, 4),
(3, 2, 'DIG-SON-01', 'Sony Cyber-shot DSC-W55 Silver', 850000.00, 15, 5),
(4, 2, 'DIG-CAN-02', 'Canon IXY Digital 50 (Mint)', 950000.00, 4, 5),
(5, 3, 'ACC-FLT-01', 'Filter UV Kenko 49mm', 75000.00, 25, 10),
(6, 4, 'FLM-KOD-01', 'Kodak ColorPlus 200 (36 Exp)', 145000.00, 30, 10)
ON DUPLICATE KEY UPDATE id=id;

-- Seed Product Suppliers
INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES
(1, 1, 950000.00),
(1, 3, 980000.00),
(2, 1, 1700000.00),
(3, 2, 600000.00),
(4, 2, 700000.00),
(5, 3, 45000.00),
(6, 3, 110000.00)
ON DUPLICATE KEY UPDATE product_id=product_id;

-- Seed Initial Stock Movements
INSERT INTO stock_movements (product_id, user_id, type, quantity, notes) VALUES
(1, 1, 'IN', 10, 'Stok awal kedatangan distributor'),
(1, 2, 'OUT', 2, 'Penjualan ke pelanggan offline'),
(2, 1, 'IN', 5, 'Stok awal koleksi Canonet'),
(2, 2, 'OUT', 3, 'Penjualan paket bundling'),
(3, 1, 'IN', 20, 'Restock import batch 1'),
(3, 2, 'OUT', 5, 'Penjualan marketplace'),
(4, 1, 'IN', 10, 'Restock IXY series'),
(4, 2, 'OUT', 6, 'Penjualan exhibition'),
(5, 1, 'IN', 30, 'Suplai aksesoris'),
(5, 2, 'OUT', 5, 'Bonus pembelian kamera'),
(6, 1, 'IN', 40, 'Suplai Kodak box baru'),
(6, 2, 'OUT', 10, 'Penjualan komunitas fotografi')
ON DUPLICATE KEY UPDATE id=id;
