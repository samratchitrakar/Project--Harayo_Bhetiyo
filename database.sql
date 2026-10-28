-- Harayo Bhetiyo Database
-- Run this in phpMyAdmin

CREATE DATABASE IF NOT EXISTS harayo_bhetiyo;
USE harayo_bhetiyo;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    otp VARCHAR(6) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Items table
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('Lost', 'Found') NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    category ENUM('Electronics', 'Documents', 'Clothing & Accessories', 'Bags & Wallets', 'Keys', 'Jewellery', 'Books & Stationery', 'Other') NOT NULL,
    description TEXT,
    district VARCHAR(100) NOT NULL,
    area VARCHAR(100) NOT NULL,
    additional_location TEXT,
    image VARCHAR(255),
    secret_detail VARCHAR(255),
    status ENUM('Open', 'Claimed', 'Resolved') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Claims table
CREATE TABLE claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    proof_description TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    reporter_decision TEXT,
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default admin account
-- Password is: admin123
INSERT INTO users (name, email, password, phone, role, is_verified)
VALUES ('Admin', 'admin@harayobhetiyo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9800000000', 'admin', 1);
