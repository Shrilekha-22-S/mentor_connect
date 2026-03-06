<?php
/**
 * API Handler: Send Mentee Request
 * POST /api/send_request.php
 * 
 * Parameters:
 *   - mentor_id (int): ID of mentor to request
 *   - message (string): Request message
 */

// Define base URL
define('BASE_URL', 'http://localhost/mentor_connect');

// Include database and auth files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is a mentee
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
$mentor_id = filter_var($_POST['mentor_id'] ?? 0, FILTER_VALIDATE_INT);
$message = sanitize_input($_POST['message'] ?? '');

// Validate input
if (!$mentor_id || $mentor_id <= 0) {
    http_response_code(400);
    $response['message'] = 'Invalid mentor ID';
    echo json_encode($response);
    exit;
}

// Get current user
$user_id = get_user_id();
$user_role = get_user_role();

// Verify user is a mentee
if ($user_role !== 'mentee') {
    http_response_code(403);
    $response['message'] = 'Only mentees can send requests';
    echo json_encode($response);
    exit;
}

// Verify mentor exists
$stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ? AND role = "mentor" AND status = "active"');
$stmt->execute([$mentor_id]);
$mentor = $stmt->fetch();

if (!$mentor) {
    http_response_code(404);
    $response['message'] = 'Mentor not found';
    echo json_encode($response);
    exit;
}

// Create the request with all validations
$result = create_mentee_request($pdo, $user_id, $mentor_id, $message);

if ($result['success']) {
    http_response_code(201);
    $response['success'] = true;
    $response['message'] = $result['message'];
    $response['data'] = [
        'request_id' => $result['request_id']
    ];
} else {
    http_response_code(400);
    $response['message'] = $result['message'];
    $response['data'] = [
        'errors' => $result['errors']
    ];
}

echo json_encode($response);
?>
