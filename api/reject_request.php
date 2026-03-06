<?php
/**
 * API Handler: Reject Mentee Request
 * POST /api/reject_request.php
 * 
 * Parameters:
 *   - request_id (int): ID of request to reject
 */

// Define base URL
define('BASE_URL', 'http://localhost/mentor_connect');

// Include database and auth files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is a mentor
require_login();

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed. Use POST.';
    echo json_encode($response);
    exit;
}

// Get POST data
$request_id = filter_var($_POST['request_id'] ?? 0, FILTER_VALIDATE_INT);

// Validate input
if (!$request_id || $request_id <= 0) {
    http_response_code(400);
    $response['message'] = 'Invalid request ID';
    echo json_encode($response);
    exit;
}

// Get current user
$user_id = get_user_id();
$user_role = get_user_role();

// Verify user is a mentor
if ($user_role !== 'mentor') {
    http_response_code(403);
    $response['message'] = 'Only mentors can reject requests';
    echo json_encode($response);
    exit;
}

// Reject the request
$result = reject_mentee_request($pdo, $request_id, $user_id);

if ($result['success']) {
    http_response_code(200);
    $response['success'] = true;
    $response['message'] = $result['message'];
} else {
    http_response_code(400);
    $response['message'] = $result['message'];
}

echo json_encode($response);
?>
