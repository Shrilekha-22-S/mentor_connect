<?php
/**
 * API Endpoint: Add Availability Block
 * POST /api/add_availability_block.php
 * 
 * Mentor adds a new availability time slot
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
        'message' => 'Only mentors can add availability',
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
    
    $block_date = $data['block_date'] ?? null;
    $start_time = $data['start_time'] ?? null;
    $end_time = $data['end_time'] ?? null;
    
    // Validate
    $errors = [];
    if (!$block_date) $errors[] = 'block_date is required (YYYY-MM-DD)';
    if (!$start_time) $errors[] = 'start_time is required (HH:MM:SS)';
    if (!$end_time) $errors[] = 'end_time is required (HH:MM:SS)';
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Add availability block
    $result = add_calendar_availability_block($pdo, $user_id, $block_date, $start_time, $end_time);
    
    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Error in add_availability_block.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'errors' => [$e->getMessage()]
    ]);
}
?>
