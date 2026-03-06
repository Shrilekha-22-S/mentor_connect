<?php
/**
 * Mentor: Pending Requests Page
 */

// Define base URL
define('BASE_URL', 'http://localhost/mentor_connect');

// Include database and auth files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/requests.php';

// Require mentor role
require_role('mentor');

$user_id = get_user_id();
$username = $_SESSION['username'] ?? '';

// Get pending requests
$requests = get_mentor_pending_requests($pdo, $user_id);

// Get mentor profile
$stmt = $pdo->prepare('
    SELECT mp.current_mentees, mp.max_mentees
    FROM mentor_profiles mp
    WHERE mp.user_id = ?
');
$stmt->execute([$user_id]);
$mentor_profile = $stmt->fetch();

// Get request summary
$summary = get_mentor_request_summary($pdo, $user_id);
$can_accept = $mentor_profile['current_mentees'] < $mentor_profile['max_mentees'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Requests - Mentor Connect</title>
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
        .request-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #ffc107;
        }
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .mentee-name {
            color: #667eea;
            font-weight: 600;
            font-size: 18px;
            margin: 0;
        }
        .request-date {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .request-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        .request-message {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            color: #555;
        }
        .mentee-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
            font-size: 14px;
        }
        .info-item {
            display: flex;
            gap: 10px;
        }
        .info-item strong {
            color: #667eea;
            min-width: 100px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-accept {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-accept:hover:not(:disabled) {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .btn-accept:disabled,
        .btn-reject:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-around;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
            border: none;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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
                <li><a href="../mentor/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="pending_requests.php" class="active"><i class="bi bi-inbox"></i> Pending Requests</a></li>
                <li><a href="my_mentees.php"><i class="bi bi-person-hearts"></i> My Mentees</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Topbar -->
            <div class="topbar">
                <h2><i class="bi bi-inbox"></i> Pending Requests</h2>
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
                <!-- Capacity Status -->
                <div class="stats-card">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($requests); ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $summary['active_mentees']; ?></div>
                        <div class="stat-label">Active Mentees</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $summary['active_mentees']; ?>/<?php echo $mentor_profile['max_mentees']; ?></div>
                        <div class="stat-label">Capacity</div>
                    </div>
                </div>

                <!-- Capacity Warning -->
                <?php if (!$can_accept): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>You are at full capacity.</strong> 
                        You have reached the maximum of <?php echo $mentor_profile['max_mentees']; ?> mentees. 
                        You cannot accept new requests until you have space available.
                    </div>
                <?php endif; ?>

                <!-- Requests List -->
                <?php if (empty($requests)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h5>No pending requests</h5>
                        <p>You don't have any pending mentee requests at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div>
                                    <h5 class="mentee-name">
                                        <i class="bi bi-person-circle"></i> 
                                        <?php echo htmlspecialchars($request['username']); ?>
                                    </h5>
                                    <p class="request-date">
                                        <i class="bi bi-calendar"></i> 
                                        Requested: <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?>
                                    </p>
                                </div>
                                <span style="background: #ffc107; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                    Pending
                                </span>
                            </div>

                            <div class="request-meta">
                                <span><i class="bi bi-bookmark"></i> <?php echo htmlspecialchars($request['domain_name']); ?></span>
                            </div>

                            <?php if ($request['message']): ?>
                                <div class="request-message">
                                    <strong>Their Message:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($request['message'])); ?>
                                </div>
                            <?php endif; ?>

                            <div class="mentee-info">
                                <div class="info-item">
                                    <strong>Email:</strong>
                                    <span><?php echo htmlspecialchars($request['email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Domain:</strong>
                                    <span><?php echo htmlspecialchars($request['domain_name']); ?></span>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button class="btn-accept" 
                                        onclick="acceptRequest(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['username']); ?>')"
                                        <?php echo !$can_accept ? 'disabled title="You are at full capacity"' : ''; ?>>
                                    <i class="bi bi-check-circle"></i> Accept
                                </button>
                                <button class="btn-reject" 
                                        onclick="rejectRequest(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['username']); ?>');">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function acceptRequest(requestId, menteeeName) {
            if (confirm('Accept request from ' + menteeeName + '?')) {
                const formData = new FormData();
                formData.append('request_id', requestId);

                fetch('<?php echo BASE_URL; ?>/api/accept_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Request accepted! ' + menteeeName + ' is now your mentee.');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while accepting the request.');
                });
            }
        }

        function rejectRequest(requestId, menteeName) {
            if (confirm('Reject request from ' + menteeName + '?')) {
                const formData = new FormData();
                formData.append('request_id', requestId);

                fetch('<?php echo BASE_URL; ?>/api/reject_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Request rejected.');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while rejecting the request.');
                });
            }
        }
    </script>
</body>
</html>
