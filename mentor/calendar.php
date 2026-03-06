<?php
/**
 * Mentor: Calendar & Availability Management
 * Set availability slots for mentees to book
 */

define('BASE_URL', 'http://localhost/mentor_connect');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

require_role('mentor');

$user_id = get_user_id();
$username = $_SESSION['username'] ?? '';

// Get upcoming blocks
$upcoming_blocks = get_mentor_availability_blocks($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Mentor Connect</title>
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
            position: fixed;
            width: 16.66%;
            left: 0;
            top: 0;
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
        .main-content {
            margin-left: 16.66%;
            padding: 0;
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
        .content {
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
        .btn-add-slot {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-add-slot:hover {
            background: #5568d3;
            color: white;
            text-decoration: none;
        }
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .slot-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        .slot-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .slot-date {
            font-weight: 600;
            color: #667eea;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .slot-time {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .slot-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .slot-status.available {
            background: #d4edda;
            color: #155724;
        }
        .slot-status.booked {
            background: #fff3cd;
            color: #856404;
        }
        .slot-booked-by {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .slot-actions {
            display: flex;
            gap: 8px;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        .btn-delete:hover {
            background: #c82333;
            text-decoration: none;
            color: white;
        }
        .btn-delete:disabled {
            background: #ccc;
            cursor: not-allowed;
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
        .fc .fc-daygrid-day:hover {
            background-color: #f0f0f0;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        .empty-state p {
            color: #999;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
                margin-bottom: 20px;
            }
            .main-content {
                margin-left: 0;
            }
            .slots-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-easel"></i> Mentor Connect
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="pending_requests.php"><i class="bi bi-person-plus"></i> Pending Requests</a></li>
                <li><a href="my_mentees.php"><i class="bi bi-people"></i> My Mentees</a></li>
                <li><a href="manage_sessions.php"><i class="bi bi-calendar-check"></i> Sessions</a></li>
                <li><a href="calendar.php" class="active"><i class="bi bi-calendar-event"></i> Calendar</a></li>
                <li><a href="#"><i class="bi bi-chat-left"></i> Messages</a></li>
                <li><a href="#"><i class="bi bi-person"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content col-md-9 col-lg-10">
            <!-- Topbar -->
            <div class="topbar">
                <h2><i class="bi bi-calendar-event"></i> Calendar & Availability</h2>
                <div class="user-info">
                    <p>Welcome back!</p>
                    <strong><?php echo htmlspecialchars($username); ?></strong>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- Add Slot Section -->
                <div class="calendar-container">
                    <div class="calendar-header">
                        <h3><i class="bi bi-plus-circle"></i> Add Availability Slot</h3>
                    </div>
                    <form id="addSlotForm">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" id="slotDate" name="block_date" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="startTime" name="start_time" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Time</label>
                                <input type="time" class="form-control" id="endTime" name="end_time" required>
                            </div>
                        </div>
                        <div id="slotError" class="alert alert-danger d-none mt-3"></div>
                        <div id="slotSuccess" class="alert alert-success d-none mt-3"></div>
                        <button type="button" class="btn btn-primary mt-3" onclick="addAvailabilitySlot()">
                            <i class="bi bi-plus-lg"></i> Add Slot
                        </button>
                    </form>
                </div>

                <!-- Calendar -->
                <div class="calendar-container">
                    <div class="calendar-header">
                        <h3><i class="bi bi-calendar2-event"></i> Your Availability Calendar</h3>
                    </div>
                    <div id="calendar"></div>
                </div>

                <!-- Upcoming Slots List -->
                <div class="calendar-container">
                    <div class="calendar-header">
                        <h3><i class="bi bi-list-check"></i> Availability Slots (Next 30 Days)</h3>
                    </div>
                    
                    <?php if (empty($upcoming_blocks)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <p>No availability slots scheduled</p>
                            <p><small>Add slots above so mentees can book with you</small></p>
                        </div>
                    <?php else: ?>
                        <div class="slots-grid">
                            <?php foreach ($upcoming_blocks as $block): ?>
                                <div class="slot-card">
                                    <div class="slot-date">
                                        <?php echo date('M d, Y', strtotime($block['block_date'])); ?>
                                    </div>
                                    <div class="slot-time">
                                        <i class="bi bi-clock"></i> 
                                        <?php echo date('H:i', strtotime($block['start_time'])); ?> - 
                                        <?php echo date('H:i', strtotime($block['end_time'])); ?>
                                    </div>
                                    <div>
                                        <?php if ($block['is_booked']): ?>
                                            <span class="slot-status booked">BOOKED</span>
                                            <div class="slot-booked-by">
                                                Booked by: <strong><?php echo htmlspecialchars($block['booked_by_username']); ?></strong>
                                            </div>
                                        <?php else: ?>
                                            <span class="slot-status available">AVAILABLE</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$block['is_booked']): ?>
                                        <div class="slot-actions">
                                            <button class="btn-delete" onclick="deleteSlot(<?php echo $block['block_id']; ?>)">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="slot-actions">
                                            <button class="btn-delete" disabled title="Cannot delete booked slots">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,listMonth'
                },
                height: 'auto',
                contentHeight: 'auto',
                events: {
                    url: '/mentor_connect/api/get_calendar_events.php',
                    method: 'GET',
                    failure: function() {
                        alert('Error loading events');
                    }
                },
                eventClick: function(info) {
                    const event = info.event;
                    const props = event.extendedProps;
                    let message = event.title + '\n';
                    message += 'Date: ' + event.start.toLocaleString() + '\n';
                    message += 'Status: ' + props.status;
                    alert(message);
                }
            });
            calendar.render();
        });

        function addAvailabilitySlot() {
            const blockDate = document.getElementById('slotDate').value;
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;

            if (!blockDate || !startTime || !endTime) {
                showSlotError('Please fill in all fields');
                return;
            }

            // Validate end time is after start time
            if (startTime >= endTime) {
                showSlotError('End time must be after start time');
                return;
            }

            fetch('/mentor_connect/api/add_availability_block.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    block_date: blockDate,
                    start_time: startTime + ':00',
                    end_time: endTime + ':00'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSlotSuccess('Availability slot added! Refreshing...');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showSlotError(data.message || 'Failed to add slot');
                }
            })
            .catch(error => {
                showSlotError('Error: ' + error.message);
            });
        }

        function deleteSlot(blockId) {
            if (!confirm('Delete this availability slot?')) return;

            fetch('/mentor_connect/api/delete_availability_block.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    block_id: blockId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        function showSlotError(message) {
            const errorDiv = document.getElementById('slotError');
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
        }

        function showSlotSuccess(message) {
            const successDiv = document.getElementById('slotSuccess');
            successDiv.textContent = message;
            successDiv.classList.remove('d-none');
        }
    </script>
</body>
</html>
