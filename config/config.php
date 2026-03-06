<?php
/**
 * Application Configuration File
 * Database and global settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mentors_connect');

// Base URL
define('BASE_URL', 'http://localhost:8000/mentor_connect');

// Application Settings
define('APP_NAME', 'Mentor Connect');
define('APP_VERSION', '5.0');
define('DEBUG_MODE', true);

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_REGENERATE_INTERVAL', 900); // 15 minutes

// Input Validation
define('MAX_USERNAME_LENGTH', 50);
define('MAX_EMAIL_LENGTH', 100);
define('MAX_PASSWORD_LENGTH', 255);
define('MIN_PASSWORD_LENGTH', 8);

// Upload Configuration
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_UPLOAD_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Email Configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SENDER_EMAIL', 'noreply@mentorconnect.com');
define('SENDER_NAME', 'Mentor Connect');

// Create PDO connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log('Database Connection Error: ' . $e->getMessage());
    if (DEBUG_MODE) {
        die('Database connection failed: ' . $e->getMessage());
    } else {
        die('Database connection failed. Please try again later.');
    }
}
?>
 