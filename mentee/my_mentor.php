<?php
/**
 * Mentee: My Mentor Page
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

// Get current mentor
$mentor = get_mentee_current_mentor($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Mentor - Mentor Connect</title>
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
        .mentor-profile {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            max-width: 600px;
            margin: 0 auto;
        }
        .mentor-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 50px;
            margin: 0 auto 20px;
        }
        .mentor-name {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .mentor-rating {
            text-align: center;
            color: #ffc107;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .mentor-domain {
            text-align: center;
            background: #667eea;
            color: white;
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            margin: 0 auto 30px;
            display: table;
        }
        .mentor-bio {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            line-height: 1.8;
            color: #555;
        }
        .mentor-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .detail-value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .empty-state {
            text-align: center;
            padding: 60px 40px;
        }
        .empty-state-icon {
            font-size: 80px;
            color: #ccc;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }
        .empty-state p {
            color: #999;
            margin-bottom: 25px;
        }
        .connection-badge {
            background: #d4edda;
            color: #155724;
            padding: 10px 20px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .verified-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 5px;
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
                <li><a href="my_mentor.php" class="active"><i class="bi bi-chat-left"></i> My Mentor</a></li>
                <li><a href="schedule_sessions.php"><i class="bi bi-calendar-check"></i> Sessions</a></li>
                <li><a href="#"><i class="bi bi-person"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Topbar -->
            <div class="topbar">
                <h2><i class="bi bi-person-check"></i> My Mentor</h2>
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
                <?php if (!$mentor): ?>
                    <!-- No Mentor -->
                    <div class="mentor-profile">
                        <div class="empty-state">
                            <div class="empty-state-icon">😔</div>
                            <h3>No Mentor Yet</h3>
                            <p>You don't have a mentor connection yet. Send a request to a mentor to get started!</p>
                            <a href="find_mentor.php" class="btn btn-primary">
                                <i class="bi bi-search"></i> Find a Mentor
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Mentor Profile -->
                    <div class="mentor-profile">
                        <div class="connection-badge">
                            <i class="bi bi-check-circle-fill"></i> Connected since <?php echo date('M d, Y', strtotime($mentor['started_at'])); ?>
                        </div>

                        <div class="mentor-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>

                        <h2 class="mentor-name">
                            <?php echo htmlspecialchars($mentor['username']); ?>
                            <?php if ($mentor['verified']): ?>
                                <span class="verified-badge">✓ Verified</span>
                            <?php endif; ?>
                        </h2>

                        <div class="mentor-rating">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <?php if ($i < floor($mentor['rating'])): ?>
                                    ★
                                <?php else: ?>
                                    ☆
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span style="margin-left: 10px; color: #333;"><?php echo number_format($mentor['rating'], 1); ?>/5</span>
                        </div>

                        <div style="text-align: center; margin-bottom: 30px;">
                            <span class="mentor-domain"><?php echo htmlspecialchars($mentor['domain_name']); ?></span>
                        </div>

                        <div class="mentor-bio">
                            <strong>About:</strong><br><br>
                            <?php echo nl2br(htmlspecialchars($mentor['bio'])); ?>
                        </div>

                        <div class="mentor-details">
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value"><?php echo htmlspecialchars($mentor['email']); ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Expertise</div>
                                <div class="detail-value"><?php echo htmlspecialchars($mentor['expertise']); ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Connected Since</div>
                                <div class="detail-value"><?php echo date('M d, Y', strtotime($mentor['started_at'])); ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 3px;">
                                        Active
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <a href="#" class="btn btn-primary" onclick="alert('Chat feature coming soon'); return false;">
                                <i class="bi bi-chat-dots"></i> Send Message
                            </a>
                            <a href="schedule_sessions.php" class="btn btn-primary">
                                <i class="bi bi-calendar-check"></i> Schedule Session
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
