<?php
/**
 * API Endpoint: Delete Availability Block
 * POST /api/delete_availability_block.php
 * 
 * Mentor deletes an unbooked availability slot
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

if (get_user_role() !== 'mentor') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Only mentors can delete availability blocks',
        'errors' => []
    ]);
    exit;
}

try {
    $user_id = get_user_id();
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['block_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'block_id is required',
            'errors' => []
        ]);
        exit;
    }
    
    // Delete the block
    $result = delete_calendar_block($pdo, $data['block_id'], $user_id);
    
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Error in delete_availability_block.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'errors' => [$e->getMessage()]
    ]);
}
?>
