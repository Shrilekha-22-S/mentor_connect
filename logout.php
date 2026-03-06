<?php
/**
 * Logout Page
 */

// Define base URL
define('BASE_URL', 'http://localhost/mentor_connect');

// Include auth file
require_once __DIR__ . '/config/auth.php';

// Logout user
logout_user();

// Redirect to login page
header('Location: ' . BASE_URL . '/login.php?message=You have been logged out successfully.');
exit;
?>
