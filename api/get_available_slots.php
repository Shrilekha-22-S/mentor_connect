<?php
/**
 * API Endpoint: Get Available Slots
 * GET /api/get_available_slots.php?mentor_id=2&start_date=2026-03-01&end_date=2026-04-01
 * 
 * Mentees can see available slots to book
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use GET.',
        'slots' => []
    ]);
    exit;
}

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'slots' => []
    ]);
    exit;
}

try {
    $mentor_id = $_GET['mentor_id'] ?? null;
    $start_date = $_GET['start_date'] ?? date('Y-m-d');
    $end_date = $_GET['end_date'] ?? date('Y-m-d', strtotime('+60 days'));
    
    if (!$mentor_id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'mentor_id is required',
            'slots' => []
        ]);
        exit;
    }
    
    // Get available slots
    $slots = get_available_calendar_slots($pdo, $mentor_id, $start_date, $end_date);
    
    // Format response
    $formatted_slots = [];
    foreach ($slots as $slot) {
        $formatted_slots[] = [
            'block_id' => $slot['block_id'],
            'mentor_id' => $slot['mentor_id'],
            'mentor_name' => $slot['mentor_name'],
            'mentor_email' => $slot['mentor_email'],
            'domain_name' => $slot['domain_name'],
            'expertise' => $slot['expertise'],
            'block_date' => $slot['block_date'],
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time'],
            'start_datetime' => $slot['start_datetime'],
            'end_datetime' => $slot['end_datetime'],
            'duration' => $slot['duration']
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'slots' => $formatted_slots,
        'count' => count($formatted_slots)
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_available_slots.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'slots' => []
    ]);
}
?>
