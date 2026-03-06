<?php
/**
 * Mentor: My Mentees Page
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

// Get accepted mentees
$mentees = get_mentor_accepted_mentees($pdo, $user_id);

// Get mentor profile
$stmt = $pdo->prepare('
    SELECT mp.current_mentees, mp.max_mentees
    FROM mentor_profiles mp
    WHERE mp.user_id = ?
');
$stmt->execute([$user_id]);
$mentor_profile = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Mentees - Mentor Connect</title>
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
        .mentee-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #28a745;
        }
        .mentee-header {
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
        .status-badge {
            background: #d4edda;
            color: #155724;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .mentee-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        .mentee-bio {
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
        .action-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
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
        .date-small {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
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
                <li><a href="pending_requests.php"><i class="bi bi-person-plus"></i> Pending Requests</a></li>
                <li><a href="my_mentees.php" class="active"><i class="bi bi-people"></i> My Mentees</a></li>
                <li><a href="manage_sessions.php"><i class="bi bi-calendar-check"></i> Manage Sessions</a></li>
                <li><a href="#"><i class="bi bi-chat-left"></i> Messages</a></li>
                <li><a href="#"><i class="bi bi-person"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Topbar -->
            <div class="topbar">
                <h2><i class="bi bi-person-hearts"></i> My Mentees</h2>
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
                        <div class="stat-number"><?php echo count($mentees); ?></div>
                        <div class="stat-label">Active Mentees</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $mentor_profile['max_mentees'] - count($mentees); ?></div>
                        <div class="stat-label">Slots Available</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($mentees); ?>/<?php echo $mentor_profile['max_mentees']; ?></div>
                        <div class="stat-label">Capacity</div>
                    </div>
                </div>

                <!-- Mentees List -->
                <?php if (empty($mentees)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <h5>No mentees yet</h5>
                        <p>You don't have any active mentees yet.</p>
                        <a href="pending_requests.php" class="action-button" style="margin-top: 20px;">
                            <i class="bi bi-inbox"></i> Check Pending Requests
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($mentees as $mentee): ?>
                        <div class="mentee-card">
                            <div class="mentee-header">
                                <div>
                                    <h5 class="mentee-name">
                                        <i class="bi bi-person-circle"></i> 
                                        <?php echo htmlspecialchars($mentee['username']); ?>
                                    </h5>
                                    <p class="date-small">
                                        <i class="bi bi-calendar"></i> 
                                        Connected: <?php echo date('M d, Y', strtotime($mentee['started_at'])); ?>
                                    </p>
                                </div>
                                <span class="status-badge">
                                    ✓ Active
                                </span>
                            </div>

                            <div class="mentee-meta">
                                <span><i class="bi bi-bookmark"></i> <?php echo htmlspecialchars($mentee['domain_name']); ?></span>
                            </div>

                            <div class="mentee-bio">
                                <strong>Learning Goals:</strong><br>
                                <?php echo nl2br(htmlspecialchars($mentee['learning_goals'])); ?>
                            </div>

                            <div class="mentee-info">
                                <div class="info-item">
                                    <strong>Email:</strong>
                                    <span><?php echo htmlspecialchars($mentee['email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Domain:</strong>
                                    <span><?php echo htmlspecialchars($mentee['domain_name']); ?></span>
                                </div>
                            </div>

                            <div style="margin-top: 15px;">
                                <a href="#" class="action-button" onclick="alert('Messaging feature coming soon'); return false;">
                                    <i class="bi bi-chat-dots"></i> Send Message
                                </a>
                                <a href="#" class="action-button" onclick="alert('Session scheduling coming soon'); return false;">
                                    <i class="bi bi-calendar"></i> Schedule Session
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
