<?php
/**
 * Mentee: Book Calendar Slots
 * View mentor availability and book sessions
 */

define('BASE_URL', 'http://localhost/mentor_connect');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

require_role('mentee');

$user_id = get_user_id();
$username = $_SESSION['username'] ?? '';

// Get active mentor
$mentor = get_mentee_current_mentor($pdo, $user_id);

if ($mentor) {
    // Get connection ID for booking
    $stmt = $pdo->prepare('
        SELECT c.connection_id FROM mentor_mentee_connections c
        WHERE c.mentee_id = ? AND c.mentor_id = ? AND c.status = "active"
        LIMIT 1
    ');
    $stmt->execute([$user_id, $mentor['user_id']]);
    $connection_row = $stmt->fetch();
    $connection_id = $connection_row['connection_id'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Sessions - Mentor Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .sidebar-brand {
            color: white;
            font-weight: bold;
            font-size: 20px;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .topbar h2 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        .user-info {
            text-align: right;
        }
        .user-info p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        .user-info strong {
            display: block;
            color: #333;
        }
        .main-content {
            padding: 30px;
        }
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .calendar-header h3 {
            margin: 0;
            color: #333;
        }
        #calendar {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .fc .fc-button-primary {
            background-color: #667eea !important;
            border-color: #667eea !important;
        }
        .fc .fc-button-primary:hover {
            background-color: #5568d3 !important;
        }
        .fc .fc-button-primary.fc-button-active {
            background-color: #5568d3 !important;
            border-color: #5568d3 !important;
        }
        .fc .fc-col-header-cell {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
        }
        .fc-daygrid-day.fc-day-other {
            background-color: #fafafa;
        }
        .available-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .slot-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .slot-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            text-decoration: none;
            color: white;
        }
        .slot-date {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .slot-time {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .slot-mentor {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        .empty-state p {
            color: #999;
        }
        .alert-info {
            background: #e7f3ff;
            border-color: #0066cc;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar col-md-3 col-lg-2">
            <div class="sidebar-brand">
                <i class="bi bi-easel"></i> Mentor Connect
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="find_mentor.php"><i class="bi bi-search"></i> Find Mentor</a></li>
                <li><a href="my_requests.php"><i class="bi bi-person-check"></i> My Requests</a></li>
                <li><a href="my_mentor.php"><i class="bi bi-chat-left"></i> My Mentor</a></li>
                <li><a href="schedule_sessions.php"><i class="bi bi-calendar-check"></i> Sessions</a></li>
                <li><a href="book_slots.php" class="active"><i class="bi bi-calendar-event"></i> Book Slots</a></li>
                <li><a href="#"><i class="bi bi-person"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Topbar -->
            <div class="topbar">
                <h2><i class="bi bi-calendar-event"></i> Book Sessions</h2>
                <div class="user-info">
                    <p>Welcome back!</p>
                    <strong><?php echo htmlspecialchars($username); ?></strong>
                </div>
            </div>

            <!-- Content -->
            <div class="main-content">
                <?php if (!$mentor): ?>
                    <!-- No active mentor -->
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-circle"></i> You don't have an active mentor yet.
                        <a href="my_requests.php" class="alert-link">View your requests</a> or 
                        <a href="find_mentor.php" class="alert-link">find a mentor</a>.
                    </div>
                <?php else: ?>
                    <!-- Mentor info -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <div>
                                <h3><?php echo htmlspecialchars($mentor['username']); ?>'s Availability</h3>
                                <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                                    Expert in: <?php echo htmlspecialchars($mentor['expertise']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar View -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <h3><i class="bi bi-calendar-event"></i> Mentor's Calendar</h3>
                        </div>
                        <div id="calendar"></div>
                    </div>

                    <!-- Available Slots List -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <h3><i class="bi bi-list-check"></i> Available Slots</h3>
                        </div>
                        <div id="slotsContainer">
                            <div class="text-center p-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3">Loading available slots...</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Book Slot Modal -->
    <div class="modal fade" id="bookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book This Slot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="slotInfo" style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 15px;"></div>
                    <form id="bookForm">
                        <input type="hidden" id="blockId" name="block_id">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes for your mentor (optional)</label>
                            <textarea class="form-control" id="notes" class="notes" rows="3" placeholder="What would you like to discuss?"></textarea>
                        </div>
                        <div id="bookError" class="alert alert-danger d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitBook()">Book Slot</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
        const connectionId = <?php echo json_encode($connection_id ?? null); ?>;
        const mentorId = <?php echo json_encode($mentor['user_id'] ?? null); ?>;
        let bookModal;

        document.addEventListener('DOMContentLoaded', function() {
            bookModal = new bootstrap.Modal(document.getElementById('bookModal'));
            
            if (connectionId && mentorId) {
                initCalendar();
                loadAvailableSlots();
            }
        });

        function initCalendar() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek'
                },
                height: 'auto',
                contentHeight: 'auto',
                events: {
                    url: '/mentor_connect/api/get_calendar_events.php',
                    method: 'GET'
                }
            });
            calendar.render();
        }

        function loadAvailableSlots() {
            fetch(`/mentor_connect/api/get_available_slots.php?mentor_id=${mentorId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('slotsContainer');
                    if (!data.success || !data.slots || data.slots.length === 0) {
                        container.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No available slots right now</p></div>';
                        return;
                    }

                    let html = '<div class="available-slots">';
                    data.slots.forEach(slot => {
                        const date = new Date(slot.start_datetime);
                        const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                        const endTime = new Date(slot.end_datetime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

                        html += `
                            <div class="slot-item" onclick="selectSlot(${slot.block_id}, '${formattedDate}', '${formattedTime} - ${endTime}')">
                                <div class="slot-date">${formattedDate}</div>
                                <div class="slot-time"><i class="bi bi-clock"></i> ${formattedTime} - ${endTime}</div>
                                <div class="slot-mentor" style="cursor: pointer;">
                                    <i class="bi bi-check-circle-fill"></i> Click to book
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('slotsContainer').innerHTML = `<div class="alert alert-danger">Error loading slots: ${error.message}</div>`;
                });
        }

        function selectSlot(blockId, date, time) {
            document.getElementById('blockId').value = blockId;
            document.getElementById('notes').value = '';
            document.getElementById('bookError').classList.add('d-none');
            document.getElementById('slotInfo').innerHTML = `
                <strong>You are booking:</strong><br>
                Date: ${date}<br>
                Time: ${time}
            `;
            bookModal.show();
        }

        function submitBook() {
            const blockId = document.getElementById('blockId').value;
            const notes = document.getElementById('notes').value;

            if (!blockId) {
                alert('Error: slot not selected');
                return;
            }

            fetch('/mentor_connect/api/book_slot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    connection_id: connectionId,
                    block_id: parseInt(blockId),
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Session booked successfully!');
                    bookModal.hide();
                    loadAvailableSlots();
                } else {
                    document.getElementById('bookError').textContent = data.message || 'Failed to book';
                    document.getElementById('bookError').classList.remove('d-none');
                }
            })
            .catch(error => {
                document.getElementById('bookError').textContent = 'Error: ' + error.message;
                document.getElementById('bookError').classList.remove('d-none');
            });
        }
    </script>
</body>
</html>
