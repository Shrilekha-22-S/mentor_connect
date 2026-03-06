<?php
/**
 * Mentee: Schedule Sessions
 * View active mentor relationship and schedule sessions
 */

// Define base URL
define('BASE_URL', 'http://localhost/mentor_connect');

// Include database and auth files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

// Require mentee role
require_role('mentee');

$user_id = get_user_id();
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Sessions - Mentor Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
        .sidebar-menu li {
            padding: 0;
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
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
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
        .relationship-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .mentor-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 20px;
        }
        .mentor-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .mentor-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-expiring {
            background: #fff3cd;
            color: #856404;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-box .number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }
        .stat-box .label {
            font-size: 13px;
            color: #666;
        }
        .sessions-section {
            margin-top: 30px;
        }
        .sessions-section h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .session-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #667eea;
        }
        .session-item.completed {
            border-left-color: #28a745;
        }
        .session-date {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }
        .session-notes {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .session-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        .session-status.scheduled {
            background: #d1ecf1;
            color: #0c5460;
        }
        .session-status.completed {
            background: #d4edda;
            color: #155724;
        }
        .no-sessions {
            color: #999;
            font-style: italic;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .btn-schedule {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        .btn-schedule:hover {
            background: #5568d3;
            text-decoration: none;
            color: white;
        }
        .duration-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #0066cc;
        }
        .duration-info p {
            margin: 5px 0;
            color: #0066cc;
            font-size: 14px;
        }
        .danger-zone {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
            padding: 12px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .danger-zone p {
            margin: 0;
            font-size: 13px;
            color: #721c24;
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
            margin: 10px 0;
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .mentor-header {
                flex-direction: column;
            }
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
                <li><a href="schedule_sessions.php" class="active"><i class="bi bi-calendar-check"></i> Schedule Sessions</a></li>
                <li><a href="#"><i class="bi bi-person"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Topbar -->
            <div class="topbar">
                <h2><i class="bi bi-calendar-check"></i> Schedule Sessions</h2>
                <div class="user-menu">
                    <div class="user-info">
                        <p>Welcome back!</p>
                        <strong><?php echo htmlspecialchars($username); ?></strong>
                    </div>
                    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>

            <!-- Content -->
            <div class="main-content">
                <?php
                // Get active mentor connection for this mentee
                $stmt = $pdo->prepare('
                    SELECT c.connection_id, c.mentor_id, c.start_date, c.end_date, 
                           c.is_locked, c.sessions_scheduled, c.sessions_completed,
                           u.username as mentor_name, u.email as mentor_email,
                           mp.expertise, mp.bio, mp.verified, mp.rating,
                           DATEDIFF(c.end_date, CURRENT_TIMESTAMP) as days_remaining,
                           CASE 
                               WHEN CURRENT_TIMESTAMP > c.end_date THEN "expired"
                               WHEN DATEDIFF(c.end_date, CURRENT_TIMESTAMP) <= 7 THEN "expiring_soon"
                               ELSE "active"
                           END as lock_status
                    FROM mentor_mentee_connections c
                    JOIN users u ON c.mentor_id = u.user_id
                    JOIN mentor_profiles mp ON u.user_id = mp.user_id
                    WHERE c.mentee_id = ? AND c.status = "active"
                    LIMIT 1
                ');
                $stmt->execute([$user_id]);
                $connection = $stmt->fetch();
                
                if (!$connection) {
                    echo '<div class="empty-state">';
                    echo '<i class="bi bi-inbox"></i>';
                    echo '<p>You don\'t have an active mentor connection yet.</p>';
                    echo '<p><a href="my_requests.php" class="btn btn-primary btn-sm mt-3">View My Requests</a></p>';
                    echo '<p><a href="find_mentor.php" class="btn btn-secondary btn-sm mt-2">Find a Mentor</a></p>';
                    echo '</div>';
                } else {
                    // Get all sessions for this relationship
                    $stmt2 = $pdo->prepare('
                        SELECT * FROM mentor_mentee_sessions
                        WHERE connection_id = ?
                        ORDER BY scheduled_date ASC
                    ');
                    $stmt2->execute([$connection['connection_id']]);
                    $sessions = $stmt2->fetchAll();
                    
                    // Calculate remaining sessions
                    $total_sessions = $connection['sessions_scheduled'] + $connection['sessions_completed'];
                    $remaining = 6 - $total_sessions;
                    
                    echo '<div class="relationship-card">';
                    
                    // Mentor header
                    echo '<div class="mentor-header">';
                    echo '<div class="mentor-info">';
                    echo '<h3>' . htmlspecialchars($connection['mentor_name']) . '</h3>';
                    echo '<p><strong>Email:</strong> ' . htmlspecialchars($connection['mentor_email']) . '</p>';
                    echo '<p><strong>Expertise:</strong> ' . htmlspecialchars($connection['expertise']) . '</p>';
                    echo ($connection['verified'] ? '<p><i class="bi bi-check-circle-fill" style="color: #28a745;"></i> Verified Mentor</p>' : '');
                    echo '</div>';
                    echo '<div>';
                    echo '<span class="status-badge status-' . ($connection['lock_status'] === 'active' ? 'active' : 'expiring') . '">';
                    echo ucfirst($connection['lock_status']);
                    echo '</span>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Duration info
                    echo '<div class="duration-info">';
                    echo '<p><strong><i class="bi bi-calendar-event"></i> Relationship Duration</strong></p>';
                    echo '<p style="margin: 8px 0 0 0;">' . date('M d, Y', strtotime($connection['start_date'])) . ' to ' . date('M d, Y', strtotime($connection['end_date'])) . '</p>';
                    echo '</div>';
                    
                    // Statistics
                    echo '<div class="stats-grid">';
                    echo '<div class="stat-box">';
                    echo '<span class="number">' . $connection['sessions_completed'] . '</span>';
                    echo '<span class="label">Completed Sessions</span>';
                    echo '</div>';
                    echo '<div class="stat-box">';
                    echo '<span class="number">' . $connection['sessions_scheduled'] . '</span>';
                    echo '<span class="label">Scheduled Sessions</span>';
                    echo '</div>';
                    echo '<div class="stat-box">';
                    echo '<span class="number">' . $remaining . '</span>';
                    echo '<span class="label">Remaining Sessions</span>';
                    echo '</div>';
                    echo '<div class="stat-box">';
                    echo '<span class="number">' . $connection['days_remaining'] . '</span>';
                    echo '<span class="label">Days Remaining</span>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Sessions section
                    echo '<div class="sessions-section">';
                    echo '<h5>Sessions</h5>';
                    
                    if (empty($sessions)) {
                        echo '<div class="no-sessions">No sessions scheduled yet.</div>';
                    } else {
                        foreach ($sessions as $session) {
                            echo '<div class="session-item ' . $session['status'] . '">';
                            echo '<div>';
                            echo '<div class="session-date"><i class="bi bi-calendar-event"></i> ' . date('M d, Y \a\t H:i', strtotime($session['scheduled_date'])) . '</div>';
                            if ($session['notes']) {
                                echo '<div class="session-notes">' . htmlspecialchars($session['notes']) . '</div>';
                            }
                            echo '</div>';
                            echo '<span class="session-status ' . $session['status'] . '">' . ucfirst($session['status']) . '</span>';
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                    
                    // Schedule button
                    if ($remaining > 0 && $connection['lock_status'] === 'active') {
                        echo '<button class="btn-schedule" onclick="scheduleSession(' . $connection['connection_id'] . ')"><i class="bi bi-plus-lg"></i> Schedule Session</button>';
                    } else if ($remaining === 0) {
                        echo '<div class="danger-zone"><p><i class="bi bi-info-circle"></i> All 6 sessions have been scheduled.</p></div>';
                    }
                    
                    if ($connection['lock_status'] === 'expiring_soon') {
                        echo '<div class="danger-zone">';
                        echo '<p><i class="bi bi-exclamation-circle"></i> Your mentorship will expire in ' . $connection['days_remaining'] . ' days. Complete your remaining sessions soon!</p>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Schedule Session Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Session with Your Mentor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleForm">
                        <input type="hidden" id="connectionId" name="connection_id">
                        <div class="mb-3">
                            <label for="sessionDate" class="form-label">Session Date & Time</label>
                            <input type="datetime-local" class="form-control" id="sessionDate" name="scheduled_date" required>
                            <small class="text-muted">Choose a date within your 6-month mentorship period</small>
                        </div>
                        <div class="mb-3">
                            <label for="sessionNotes" class="form-label">What would you like to discuss?</label>
                            <textarea class="form-control" id="sessionNotes" name="notes" rows="3" placeholder="Topics, goals, questions, etc."></textarea>
                        </div>
                        <div id="scheduleError" class="alert alert-danger d-none"></div>
                        <div id="scheduleSuccess" class="alert alert-success d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitSchedule()">Schedule</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));

        function scheduleSession(connectionId) {
            document.getElementById('connectionId').value = connectionId;
            document.getElementById('sessionDate').value = '';
            document.getElementById('sessionNotes').value = '';
            document.getElementById('scheduleError').classList.add('d-none');
            document.getElementById('scheduleSuccess').classList.add('d-none');
            scheduleModal.show();
        }

        function submitSchedule() {
            const connectionId = document.getElementById('connectionId').value;
            const scheduledDate = document.getElementById('sessionDate').value;
            const notes = document.getElementById('sessionNotes').value;

            if (!scheduledDate) {
                showError('Please select a date and time');
                return;
            }

            // Convert datetime-local to ISO format
            const dateObj = new Date(scheduledDate);
            const isoDate = dateObj.getFullYear() + '-' +
                String(dateObj.getMonth() + 1).padStart(2, '0') + '-' +
                String(dateObj.getDate()).padStart(2, '0') + ' ' +
                String(dateObj.getHours()).padStart(2, '0') + ':' +
                String(dateObj.getMinutes()).padStart(2, '0') + ':00';

            fetch('/mentor_connect/api/schedule_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    connection_id: connectionId,
                    scheduled_date: isoDate,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Session scheduled successfully!');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showError(data.message || 'Failed to schedule session');
                    if (data.errors && data.errors.length > 0) {
                        showError(data.errors[0]);
                    }
                }
            })
            .catch(error => {
                showError('Error: ' + error.message);
            });
        }

        function showError(message) {
            const errorDiv = document.getElementById('scheduleError');
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('scheduleSuccess');
            successDiv.textContent = message;
            successDiv.classList.remove('d-none');
        }
    </script>
</body>
</html>
