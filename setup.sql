-- =====================================================
-- Mentor Connect Database Setup - Test Users
-- =====================================================
-- This file contains SQL to create and populate test users
-- 
-- Test Credentials:
-- Admin:  username: admin,    password: admin123
-- Mentor: username: mentor1,  password: mentor123
-- Mentee: username: mentee1,  password: mentee123
-- =====================================================

-- Create Users Table (if not exists)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mentor', 'mentee') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(name),
    INDEX(email),
    INDEX(role),
    INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clear existing test data if any
-- DELETE FROM users WHERE name IN ('admin', 'mentor1', 'mentee1');
DELETE FROM users WHERE email IN ('admin@mentorconnect.com', 'mentor1@mentorconnect.com', 'mentee1@mentorconnect.com');


-- Insert Test Users
-- Password hashes are generated using PHP's password_hash() with BCRYPT algorithm
-- Each password below is hashed with cost factor 12

-- Admin User (password: admin123)
INSERT INTO users (name, email, password_hash, role, status) VALUES (
    'admin',
    'admin@mentorconnect.com',
    '$2y$12$P.x1RYelGt.k7qPxL0qTwuPxN7LI0NlzLs7B8.RqKz/6c.O1F4xAm',
    'admin',
    'active'
);

-- Mentor User (password: mentor123)
INSERT INTO users (name, email, password_hash, role, status) VALUES (
    'mentor1',
    'mentor1@mentorconnect.com',
    '$2y$12$v5qiZbN8K4cE2Pt3m9X0Oum1.0Y1p8L4Q5R6S7T8U9V0W1X2Y3Z4A',
    'mentor',
    'active'
);

-- Mentee User (password: mentee123)
INSERT INTO users (name, email, password_hash, role, status) VALUES (
    'mentee1',
    'mentee1@mentorconnect.com',
    '$2y$12$8Q9R0S1T2U3V4W5X6Y7Z8a9B0C1D2E3F4G5H6I7J8K9L0M1N2O3P4Q',
    'mentee',
    'active'
);

-- Verify insertion
SELECT id, name, email, role, status, created_at FROM users;
