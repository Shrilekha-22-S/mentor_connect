<?php
/**
 * Home Page / Index
 * Redirects to login or appropriate dashboard based on authentication status
 */

// Define base URL
define('BASE_URL', 'http://localhost/mentor_connect');

// Include database and auth files
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Start session
start_session();

// If user is already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    redirect_to_dashboard();
}

// Otherwise redirect to login page
header('Location: ' . BASE_URL . '/login.php');
exit;
?>
