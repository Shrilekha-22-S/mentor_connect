<?php
/**
 * API Endpoint: Book Calendar Slot
 * POST /api/book_slot.php
 * 
 * Mentee books an available slot
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
        'errors' => []
    ]);
    exit;
}

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'errors' => []
    ]);
    exit;
}

if (get_user_role() !== 'mentee') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Only mentees can book slots',
        'errors' => []
    ]);
    exit;
}

try {
    $user_id = get_user_id();
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No data provided',
            'errors' => []
        ]);
        exit;
    }
    
    $connection_id = $data['connection_id'] ?? null;
    $block_id = $data['block_id'] ?? null;
    $notes = $data['notes'] ?? '';
    
    // Validate
    $errors = [];
    if (!$connection_id) $errors[] = 'connection_id is required';
    if (!$block_id) $errors[] = 'block_id is required';
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Get connection and verify mentee
    $stmt = $pdo->prepare('
        SELECT connection_id, mentor_id, mentee_id FROM mentor_mentee_connections
        WHERE connection_id = ? AND mentee_id = ?
    ');
    $stmt->execute([$connection_id, $user_id]);
    $connection = $stmt->fetch();
    
    if (!$connection) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. You are not part of this connection.',
            'errors' => []
        ]);
        exit;
    }
    
    // Book the slot
    $result = book_calendar_slot($pdo, $connection_id, $connection['mentor_id'], $connection['mentee_id'], $block_id, $notes);
    
    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Error in book_slot.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'errors' => [$e->getMessage()]
    ]);
}
?>
