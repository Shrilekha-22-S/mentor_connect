<?php
/**
 * API Endpoint: Schedule Session
 * POST /api/schedule_session.php
 * 
 * Allows mentor or mentee to schedule a new session
 * Validates: connection active, within 6-month period, 1 per month, max 6 sessions
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

// Must be logged in
if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in.',
        'errors' => ['User not authenticated']
    ]);
    exit;
}

try {
    // Get user info
    $user_id = get_user_id();
    $user_role = get_user_role();
    
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
    $connection_id = $data['connection_id'] ?? null;
    $scheduled_date = $data['scheduled_date'] ?? null;
    $notes = $data['notes'] ?? '';
    
    // Validate required fields
    $errors = [];
    if (!$connection_id) $errors[] = 'connection_id is required';
    if (!$scheduled_date) $errors[] = 'scheduled_date is required (format: YYYY-MM-DD HH:MM:SS)';
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Get connection details and verify authorization
    $stmt = $pdo->prepare('
        SELECT connection_id, mentor_id, mentee_id, status
        FROM mentor_mentee_connections
        WHERE connection_id = ?
    ');
    $stmt->execute([$connection_id]);
    $connection = $stmt->fetch();
    
    if (!$connection) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Connection not found',
            'errors' => ['The specified connection does not exist']
        ]);
        exit;
    }
    
    // Verify user is either mentor or mentee in this connection
    if ($user_id != $connection['mentor_id'] && $user_id != $connection['mentee_id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. You are not part of this connection.',
            'errors' => ['User not authorized for this connection']
        ]);
        exit;
    }
    
    // Determine mentor and mentee IDs
    $mentor_id = $connection['mentor_id'];
    $mentee_id = $connection['mentee_id'];
    
    // Schedule the session
    $result = schedule_session($pdo, $connection_id, $mentor_id, $mentee_id, $scheduled_date, $notes);
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log('Error in schedule_session.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again.',
        'errors' => ['Internal server error: ' . $e->getMessage()]
    ]);
}
?>
