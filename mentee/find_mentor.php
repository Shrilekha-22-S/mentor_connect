<?php
/**
 * Mentee: Find Mentors Page
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

// Get available mentors
$mentors = get_available_mentors_for_mentee($pdo, $user_id);

// Get mentee profile information
$stmt = $pdo->prepare('
    SELECT mp.id, mp.domain_id, mp.request_count, mp.max_requests, d.name as domain_name
    FROM mentee_profiles mp
    JOIN domains d ON mp.domain_id = d.id
    WHERE mp.user_id = ?
');
$stmt->execute([$user_id]);
$mentee_profile = $stmt->fetch();

// Get request summary
$summary = get_mentee_request_summary($pdo, $user_id);
$can_request = $summary['request_count'] < $summary['max_requests'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Mentors - Mentor Connect</title>
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
            margin-bottom: 10px;
            padding: 0 15px;
        }
        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            padding: 12px 15px;
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
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }
        .main-content {
            padding: 30px;
        }
        .mentor-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .mentor-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }
        .mentor-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .mentor-name {
            color: #667eea;
            font-weight: 600;
            font-size: 18px;
            margin: 0;
        }
        .mentor-domain {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .mentor-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .mentor-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .rating-stars {
            color: #ffc107;
        }
        .mentor-bio {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .mentor-expertise {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #555;
        }
        .mentor-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e0e0e0;
            padding-top: 15px;
        }
        .request-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .request-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .request-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .badge-verified {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 5px;
        }
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
            border: none;
        }
        .filters-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state-icon {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }
        .capacity-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 3px;
            font-weight: 600;
        }
        .capacity-available {
            background: #d4edda;
            color: #155724;
        }
        .capacity-full {
            background: #f8d7da;
            color: #721c24;
        }
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
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
                <li><a href="../mentee/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="find_mentor.php" class="active"><i class="bi bi-search"></i> Find Mentor</a></li>
                <li><a href="my_requests.php"><i class="bi bi-person-check"></i> My Requests</a></li>
                <li><a href="my_mentor.php"><i class="bi bi-chat-left"></i> My Mentor</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Topbar -->
            <div class="topbar">
                <h2><i class="bi bi-search"></i> Find Mentors</h2>
                <div class="user-menu">
                    <div class="user-info">
                        <p>Welcome back!</p>
                        <strong><?php echo htmlspecialchars($username); ?></strong>
                    </div>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- Page Content -->
            <div class="main-content">
                <!-- Request Status -->
                <div class="alert alert-info">
                    <strong><i class="bi bi-info-circle"></i> Request Status:</strong>
                    You have sent <strong><?php echo $summary['request_count']; ?></strong> of <strong><?php echo $summary['max_requests']; ?></strong> maximum requests.
                    <?php if (!$can_request): ?>
                        <span class="text-danger">You have reached your request limit.</span>
                    <?php endif; ?>
                </div>

                <!-- Filters -->
                <div class="filters-bar">
                    <h5 style="color: #667eea; margin-bottom: 15px;">
                        <i class="bi bi-funnel"></i> Your Domain: <strong><?php echo htmlspecialchars($mentee_profile['domain_name']); ?></strong>
                    </h5>
                    <p class="text-muted mb-0">
                        <i class="bi bi-lightbulb"></i> Mentors shown are from different domains to provide fresh perspectives
                    </p>
                </div>

                <!-- Mentors List -->
                <?php if (empty($mentors)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">😕</div>
                        <h5>No mentors available</h5>
                        <p>There are no mentors available for your request at the moment.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($mentors as $mentor): ?>
                            <div class="col-md-6 mb-3">
                                <div class="mentor-card">
                                    <div class="mentor-header">
                                        <div>
                                            <h5 class="mentor-name">
                                                <?php echo htmlspecialchars($mentor['username']); ?>
                                                <?php if ($mentor['verified']): ?>
                                                    <span class="badge-verified">✓ Verified</span>
                                                <?php endif; ?>
                                            </h5>
                                        </div>
                                        <span class="mentor-domain"><?php echo htmlspecialchars($mentor['domain_name']); ?></span>
                                    </div>

                                    <div class="mentor-meta">
                                        <div class="mentor-meta-item">
                                            <span class="rating-stars">★★★★★</span>
                                            <span><?php echo number_format($mentor['rating'], 1); ?> (<?php echo $mentor['total_ratings']; ?> reviews)</span>
                                        </div>
                                        <div class="mentor-meta-item">
                                            <i class="bi bi-people"></i>
                                            <span><?php echo $mentor['current_mentees']; ?>/<?php echo $mentor['max_mentees']; ?> Mentees</span>
                                        </div>
                                    </div>

                                    <div class="mentor-bio">
                                        <?php echo nl2br(htmlspecialchars($mentor['bio'])); ?>
                                    </div>

                                    <div class="mentor-expertise">
                                        <strong>Expertise:</strong><br>
                                        <?php echo htmlspecialchars($mentor['expertise']); ?>
                                    </div>

                                    <div class="mentor-footer">
                                        <span class="capacity-badge <?php echo $mentor['is_full'] ? 'capacity-full' : 'capacity-available'; ?>">
                                            <?php echo $mentor['is_full'] ? '❌ Fully Booked' : '✓ Has Capacity'; ?>
                                        </span>
                                        
                                        <?php if ($mentor['is_full']): ?>
                                            <button class="request-btn" disabled title="Mentor is fully booked">
                                                Fully Booked
                                            </button>
                                        <?php else: ?>
                                            <button class="request-btn" onclick="sendRequest(<?php echo $mentor['user_id']; ?>, '<?php echo htmlspecialchars($mentor['username']); ?>');" 
                                                    <?php echo !$can_request ? 'disabled' : ''; ?>>
                                                Send Request
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Request to <span id="mentorName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="requestForm" method="POST" onsubmit="submitRequest(event)">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" 
                                    placeholder="Tell the mentor why you want to learn from them (optional)"></textarea>
                        </div>
                        <input type="hidden" id="mentorId" name="mentor_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let requestModal;

        document.addEventListener('DOMContentLoaded', function() {
            requestModal = new bootstrap.Modal(document.getElementById('requestModal'));
        });

        function sendRequest(mentorId, mentorName) {
            document.getElementById('mentorId').value = mentorId;
            document.getElementById('mentorName').textContent = mentorName;
            document.getElementById('message').value = '';
            requestModal.show();
        }

        function submitRequest(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('requestForm'));
            const mentorId = formData.get('mentor_id');
            const message = formData.get('message');

            // Send request via AJAX
            fetch('<?php echo BASE_URL; ?>/api/send_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    mentor_id: mentorId,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Request sent successfully!');
                    requestModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending the request.');
            });
        }
    </script>
</body>
</html>
