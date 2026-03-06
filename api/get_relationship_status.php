<?php
/**
 * API Endpoint: Get Relationship Status
 * GET /api/get_relationship_status.php?connection_id=123
 * 
 * Returns relationship details, session counts, available months
 */

header('Content-Type: application/json');

// Include dependencies
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

// Only GET requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use GET.',
        'errors' => ['Only GET requests are accepted']
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
    
    // Get connection_id from query string
    $connection_id = $_GET['connection_id'] ?? null;
    
    if (!$connection_id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'connection_id query parameter is required',
            'errors' => ['connection_id is required']
        ]);
        exit;
    }
    
    // Verify user is part of this connection
    $stmt = $pdo->prepare('
        SELECT connection_id, mentor_id, mentee_id
        FROM mentor_mentee_connections
        WHERE connection_id = ? AND (mentor_id = ? OR mentee_id = ?)
    ');
    $stmt->execute([$connection_id, $user_id, $user_id]);
    $connection = $stmt->fetch();
    
    if (!$connection) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. You are not part of this connection.',
            'errors' => ['User not authorized for this connection']
        ]);
        exit;
    }
    
    // Get relationship summary
    $summary = get_relationship_summary($pdo, $connection_id);
    
    if (!$summary) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Relationship not found',
            'errors' => ['The specified connection does not exist']
        ]);
        exit;
    }
    
    // Get available months for scheduling
    $months = get_available_session_months($pdo, $connection_id);
    
    // Get all sessions for this connection
    $sessions = get_sessions_for_connection($pdo, $connection_id);
    
    // Get connection activity status
    $active_status = is_connection_active($pdo, $connection_id);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Relationship status retrieved successfully',
        'errors' => [],
        'data' => [
            'relationship' => $summary,
            'available_months' => $months['available_months'],
            'used_months' => $months['used_months'],
            'sessions' => $sessions,
            'is_active' => $active_status['is_active'],
            'active_message' => $active_status['message']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_relationship_status.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again.',
        'errors' => ['Internal server error: ' . $e->getMessage()]
    ]);
}
?>
