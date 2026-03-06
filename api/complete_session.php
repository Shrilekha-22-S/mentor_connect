<?php
/**
 * API Endpoint: Complete Session
 * POST /api/complete_session.php
 * 
 * Allows mentor to mark a scheduled session as completed
 */

header('Content-Type: application/json');

// Include dependencies
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
        'errors' => ['Only POST requests are accepted']
    ]);
    exit;
}

// Must be logged in as mentor
if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in.',
        'errors' => ['User not authenticated']
    ]);
    exit;
}

if (get_user_role() !== 'mentor') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Only mentors can complete sessions.',
        'errors' => ['User is not a mentor']
    ]);
    exit;
}

try {
    // Get user info
    $user_id = get_user_id();
    
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request data. Expected JSON.',
            'errors' => ['No data provided']
        ]);
        exit;
    }
    
    // Required fields
    $session_id = $data['session_id'] ?? null;
    
    if (!$session_id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'session_id is required',
            'errors' => ['session_id is required']
        ]);
        exit;
    }
    
    // Complete the session (passes mentor_id for authorization)
    $result = complete_session($pdo, $session_id, $user_id);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log('Error in complete_session.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again.',
        'errors' => ['Internal server error: ' . $e->getMessage()]
    ]);
}
?>
