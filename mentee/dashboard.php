<?php
/**
 * Mentee Dashboard
 */

// Define base URL
define('BASE_URL', 'http://localhost/mentor_connect');

// Include database and auth files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Require mentee role
require_role('mentee');

$user_id = get_user_id();
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
echo "<pre>";
print_r($_SESSION);
exit();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentee Dashboard - Mentor Connect</title>
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
        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }
        .dashboard-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .stat-card .icon {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .stat-card h5 {
            color: #333;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #764ba2;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .welcome-section h2 {
            margin: 0;
            font-weight: 600;
        }
        .welcome-section p {
            margin: 10px 0 0 0;
            opacity: 0.9;
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
                <li><a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="find_mentor.php"><i class="bi bi-search"></i> Find Mentor</a></li>
                <li><a href="my_requests.php"><i class="bi bi-person-check"></i> My Requests</a></li>
                <li><a href="my_mentor.php"><i class="bi bi-chat-left"></i> My Mentor</a></li>
                <li><a href="schedule_sessions.php"><i class="bi bi-calendar-check"></i> Sessions</a></li>
                <li><a href="book_slots.php"><i class="bi bi-calendar-event"></i> Book Slots</a></li>
                <li><a href="feedback.php"><i class="bi bi-chat-left-dots"></i> Feedback</a></li>
                <li><a href="#"><i class="bi bi-person"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Topbar -->
            <div class="topbar">
                <h2><i class="bi bi-speedometer2"></i> Mentee Dashboard</h2>
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
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h2>Welcome, <?php echo htmlspecialchars($username); ?>! 👋</h2>
                    <p>Find a mentor, learn, grow, and achieve your goals with their guidance.</p>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-person-check"></i></div>
                            <h5>My Mentor</h5>
                            <div class="number">-</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-chat-left"></i></div>
                            <h5>Messages</h5>
                            <div class="number">0</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-calendar"></i></div>
                            <h5>Sessions</h5>
                            <div class="number">0</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-star"></i></div>
                            <h5>Rating</h5>
                            <div class="number">-</div>
                        </div>
                    </div>
                </div>

                <!-- Main Features -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h3><i class="bi bi-search"></i> Find a Mentor</h3>
                            <p>Browse available mentors and connect with someone who can help you grow.</p>
                            <button class="btn btn-primary mt-3" disabled>Coming Soon</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h3><i class="bi bi-chat-left"></i> Messages</h3>
                            <p>Communicate with your mentor and ask questions about your goals.</p>
                            <button class="btn btn-primary mt-3" disabled>Coming Soon</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
