<?php
/**
 * Get Session Feedback API
 * GET /api/get_feedback.php?session_id=123
 * 
 * Returns feedback for a specific session
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

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'errors' => ['Use GET request']
    ]);
    exit;
}

// Validate session_id
if (empty($_GET['session_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing parameter',
        'errors' => ['session_id is required']
    ]);
    exit;
}

$session_id = (int)$_GET['session_id'];

try {
    // Fetch feedback
    $feedback = get_session_feedback($pdo, $session_id);
    
    if (!$feedback) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No feedback found',
            'data' => null
        ]);
        exit;
    }
    
    // Return feedback
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Feedback retrieved',
        'data' => $feedback
    ]);

} catch (Exception $e) {
    error_log('Error in get_feedback.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'errors' => ['An unexpected error occurred']
    ]);
}
?>
