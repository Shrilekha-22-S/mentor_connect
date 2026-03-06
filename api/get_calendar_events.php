<?php
/**
 * API Endpoint: Get Calendar Events
 * GET /api/get_calendar_events.php?start_date=2026-03-01&end_date=2026-04-01
 * 
 * Returns events for FullCalendar display
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
        'events' => []
    ]);
    exit;
}

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'events' => []
    ]);
    exit;
}

try {
    $user_id = get_user_id();
    $user_role = get_user_role();
    
    $start_date = $_GET['start'] ?? date('Y-m-d');
    $end_date = $_GET['end'] ?? date('Y-m-d', strtotime('+90 days'));
    
    // Get events
    $events = get_calendar_events($pdo, $user_id, $user_role, $start_date, $end_date);
    
    // Format for FullCalendar
    $formatted_events = [];
    foreach ($events as $event) {
        $formatted_events[] = [
            'id' => $event['event_id'],
            'title' => $event['title'],
            'start' => $event['start_datetime'],
            'end' => $event['end_datetime'],
            'status' => $event['status'],
            'color' => $event['color'],
            'extendedProps' => [
                'type' => $event['event_type'],
                'status' => $event['status'],
                'mentor_name' => $event['mentor_name'],
                'mentee_name' => $event['mentee_name']
            ]
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'events' => $formatted_events
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_calendar_events.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'events' => []
    ]);
}
?>
