<?php
/**
 * Submit Session Feedback API
 * POST /api/submit_feedback.php
 * 
 * Body: {
 *   "session_id": 123,
 *   "rating": 5,
 *   "comments": "Great mentor!"
 * }
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/requests.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'errors' => ['You must be logged in']
    ]);
    exit;
}

// Allow only mentees
if ($_SESSION['user_type'] !== 'mentee') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden',
        'errors' => ['Only mentees can submit feedback']
    ]);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'errors' => ['Use POST request']
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON',
        'errors' => ['Request body must be valid JSON']
    ]);
    exit;
}

// Validate required fields
$errors = [];
if (empty($input['session_id'])) {
    $errors[] = 'session_id is required';
}
if (!isset($input['rating'])) {
    $errors[] = 'rating is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields',
        'errors' => $errors
    ]);
    exit;
}

// Extract and sanitize input
$session_id = (int)$input['session_id'];
$rating = (int)$input['rating'];
$comments = isset($input['comments']) ? trim($input['comments']) : '';
$mentee_id = $_SESSION['user_id'];

// Validate rating range
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid rating',
        'errors' => ['Rating must be between 1 and 5']
    ]);
    exit;
}

// Limit comments length
if (strlen($comments) > 1000) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Comment too long',
        'errors' => ['Comments must not exceed 1000 characters']
    ]);
    exit;
}

try {
    // Call function to submit feedback
    $result = submit_session_feedback($pdo, $session_id, $mentee_id, $rating, $comments);
    
    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log('Error in submit_feedback.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'errors' => ['An unexpected error occurred']
    ]);
}
?>
