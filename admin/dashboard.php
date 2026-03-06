<?php

require_once __DIR__ . '/../config/requests.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/requests.php';


// Get username from session safely
$username = $_SESSION['username'] 
            ?? $_SESSION['name'] 
            ?? $_SESSION['user']['username'] 
            ?? 'Administrator';

// Load stats
$stats = get_admin_dashboard_stats($pdo);



// // Initialize default values
// $stats = [
//     'total_mentors' => 0,
//     'total_mentees' => 0,
//     'active_relationships' => 0,
//     'total_sessions' => 0,
//     'total_feedback' => 0,
//     'avg_rating' => 0
// ];

// try {
//     // Mentors
//     $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='mentor'");
//     $stats['total_mentors'] = $stmt->fetchColumn();

//     // Mentees
//     $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='mentee'");
//     $stats['total_mentees'] = $stmt->fetchColumn();

//     // Relationships
//     $stmt = $pdo->query("SELECT COUNT(*) FROM mentor_mentee");
//     $stats['active_relationships'] = $stmt->fetchColumn();

//     // Sessions
//     $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
//     $stats['total_sessions'] = $stmt->fetchColumn();

//     // Feedback
//     $stmt = $pdo->query("SELECT COUNT(*) FROM feedback");
//     $stats['total_feedback'] = $stmt->fetchColumn();

//     // Average Rating
//     $stmt = $pdo->query("SELECT AVG(rating) FROM feedback");
//     $stats['avg_rating'] = round($stmt->fetchColumn(), 1);

// } catch (Exception $e) {
//     // If any table missing, keep defaults as 0
// }




/**
 * Admin Dashboard - Phase 5
 * Comprehensive view of all mentors, mentees, relationships, sessions, and feedback
 */

// session_start();
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }
// // Redirect if not logged in
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../login.php');
//     exit;
// }

// // Redirect if not admin
// if ($_SESSION['user_type'] !== 'admin') {
//     header('Location: ../login.php');
//     exit;
// }

 require_once __DIR__ . '/../config/config.php';
 require_once __DIR__ . '/../config/requests.php';

$page_title = 'Admin Dashboard';

// Get all statistics
$stats = get_admin_dashboard_stats($pdo);
// Total users
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$stats['total_users'] = (int)$stmt->fetchColumn();


// \// Get mentors, mentees, relationships
// $mentors = get_all_mentor_statistics($pdo);
// $mentees = get_all_mentee_statistics($pdo);
// $relationships = get_all_relationships($pdo);

// Get sessions with feedback
$all_sessions = get_all_sessions_with_feedback($pdo, 100);
$feedback_summary = get_feedback_summary($pdo);

// Get pagination
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$total_sessions = get_total_sessions_count($pdo);
$total_pages = ceil($total_sessions / $per_page);

// Get current tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-admin h1 {
            color: white;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 4px solid var(--primary);
        }

        .stat-card .stat-label {
            font-size: 0.9rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-card:nth-child(2) {
            border-top-color: #28a745;
        }

        .stat-card:nth-child(3) {
            border-top-color: #ffc107;
        }

        .stat-card:nth-child(4) {
            border-top-color: #17a2b8;
        }

        .stat-card:nth-child(5) {
            border-top-color: #dc3545;
        }

        .tab-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .nav-tabs .nav-link {
            color: #666;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            font-weight: 500;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            background: none;
            border-bottom-color: var(--primary);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            border-bottom: 2px solid #eee;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            vertical-align: middle;
            color: #333;
        }

        .badge-role {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .badge-mentor {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-mentee {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-upcoming {
            background: #fff3cd;
            color: #856404;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1rem;
        }

        .table-responsive {
            border-radius: 8px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .no-data {
            text-align: center;
            padding: 3rem 1rem;
            color: #999;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            color: #ddd;
        }

        .pagination {
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .pagination .page-link {
            border-radius: 6px;
        }

        .pagination .page-link.active {
            background: var(--primary);
            border-color: var(--primary);
        }

        @media (max-width: 768px) {
            .tab-content {
                padding: 1rem;
            }

            .stat-card {
                margin-bottom: 1rem;
            }

            .table {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar-admin">
        <div class="container-fluid">
            <h1><i class="bi bi-shield-lock"></i> Admin Dashboard</h1>
        </div>
    </div>

    <div class="container-fluid p-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-label">Total Mentors</div>
                    <div class="stat-value"><?= $stats['total_mentors']?? 0  ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-label">Total Mentees</div>
                    <div class="stat-value"><?= $stats['total_mentees'] ?? 0  ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-label">Active Relationships</div>
                    <div class="stat-value"><?= $stats['active_relationships'] ?? 0  ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-label">Total Sessions</div>
                    <div class="stat-value"><?= $stats['total_sessions'] ?? 0 ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-label">Feedback Submissions</div>
                    <div class="stat-value"><?= $stats['total_feedback'] ?? 0  ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-label">Average Rating</div>
                    <div class="stat-value">
                        <?= $stats['avg_rating'] ?? 0 ?>
                        <span class="rating-stars" style="font-size: 1.2rem; margin-left: 0.5rem;">★</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'overview' ? 'active' : '' ?>" href="?tab=overview">
                    <i class="bi bi-speedometer2"></i> Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'mentors' ? 'active' : '' ?>" href="?tab=mentors">
                    <i class="bi bi-person-check"></i> Mentors
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'mentees' ? 'active' : '' ?>" href="?tab=mentees">
                    <i class="bi bi-people"></i> Mentees
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'relationships' ? 'active' : '' ?>" href="?tab=relationships">
                    <i class="bi bi-diagram-3"></i> Relationships
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'sessions' ? 'active' : '' ?>" href="?tab=sessions">
                    <i class="bi bi-calendar-check"></i> Sessions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'feedback' ? 'active' : '' ?>" href="?tab=feedback">
                    <i class="bi bi-chat-left-dots"></i> Feedback
                </a>
            </li>
        </ul>

        <!-- Overview Tab -->
        <?php if ($tab === 'overview'): ?>
            <div class="tab-content">
                <h2 class="section-title"><i class="bi bi-bar-chart"></i> Platform Overview</h2>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="mb-3">Relationship Status Distribution</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                 <tr >
                                    <td>Active Relationship</td>
                                    <td><strong><?= $stats['active_relationships'] ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Total Relationships</td>
                                    <td><strong><?= $stats['total_relationships'] ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Expired/Locked</td>
                                    <td><strong><?= $stats['total_relationships'] - $stats['active_relationships'] ?></strong></td>
                                </tr> 
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-3">Feedback Rating Distribution</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <?php foreach ($feedback_summary as $summary): ?>
                                    <tr>
                                        <td>
                                            <?php for ($i = 0; $i < $summary['rating']; $i++): ?>
                                                <span class="rating-stars">★</span>
                                            <?php endfor; ?>
                                        </td>
                                        <td><strong><?= $summary['count'] ?></strong> rating(s) (<?= $summary['percentage'] ?>%)</td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Recent Sessions with Feedback</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Mentor</th>
                                <th>Mentee</th>
                                <th>Status</th>
                                <th>Rating</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $count = 0;
                            foreach ($all_sessions as $session):
                                if ($count >= 10) break;
                                $count++;
                            ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($session['scheduled_date'])) ?></td>
                                    <td><?= htmlspecialchars($session['mentor_name']) ?></td>
                                    <td><?= htmlspecialchars($session['mentee_name']) ?></td>
                                    <td>
                                        <span class="badge badge-status badge-<?= $session['status'] ?>">
                                            <?= ucfirst($session['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($session['rating']): ?>
                                            <span class="rating-stars">
                                                <?php for ($i = 0; $i < $session['rating']; $i++) echo '★'; ?>
                                            </span>
                                            <span>(<?= $session['rating'] ?>/5)</span>
                                        <?php else: ?>
                                            <span class="text-muted">No feedback</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($session['comments']): ?>
                                            <small><?= htmlspecialchars(substr($session['comments'], 0, 50)) ?>...</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
<!-- Mentors Tab -->
<?php if ($tab === 'mentors'): ?>
    <div class="tab-content">
        <h2 class="section-title">All Mentors</h2>

        <?php
        $mentor_stats = $pdo->query("SELECT * FROM v_mentor_statistics")->fetchAll();
        ?>

        <?php if (empty($mentor_stats)): ?>
            <div class="no-data">
                <p>No mentors found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Total Connections</th>
                            <th>Total Sessions</th>
                            <th>Average Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mentor_stats as $mentor): ?>
                            <tr>
                                <td><?= htmlspecialchars($mentor['name']) ?></td>
                                <td><?= htmlspecialchars($mentor['email']) ?></td>
                                <td><?= $mentor['total_connections'] ?></td>
                                <td><?= $mentor['total_sessions'] ?></td>
                                <td><?= number_format($mentor['avg_rating'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<!-- Mentees Tab -->
<?php if ($tab === 'mentees'): ?>
    <div class="tab-content">
        <h2 class="section-title">All Mentees</h2>

        <?php
        $mentees = $pdo->query("
            SELECT id, name, email, created_at
            FROM users
            WHERE role = 'mentee'
        ")->fetchAll();
        ?>

        <?php if (empty($mentees)): ?>
            <div class="no-data">
                <p>No mentees found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mentees as $mentee): ?>
                            <tr>
                                <td><?= htmlspecialchars($mentee['name']) ?></td>
                                <td><?= htmlspecialchars($mentee['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($mentee['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

        <!-- Relationships Tab -->
<?php if ($tab === 'relationships'): ?>
    <div class="tab-content">
        <h2 class="section-title">Mentor-Mentee Relationships</h2>

        <?php
        $relationships = $pdo->query("
            SELECT 
                m.name AS mentor_name,
                me.name AS mentee_name,
                c.status,
                c.started_at,
                c.ended_at
            FROM mentor_mentee_connections c
            JOIN users m ON c.mentor_id = m.id
            JOIN users me ON c.mentee_id = me.id
        ")->fetchAll();
        ?>

        <?php if (empty($relationships)): ?>
            <div class="no-data">
                <p>No relationships found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mentor</th>
                            <th>Mentee</th>
                            <th>Status</th>
                            <th>Started At</th>
                            <th>Ended At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relationships as $rel): ?>
                            <tr>
                                <td><?= htmlspecialchars($rel['mentor_name']) ?></td>
                                <td><?= htmlspecialchars($rel['mentee_name']) ?></td>
                                <td>
                                    <?php if ($rel['status'] === 'active'): ?>
                                        <span style="color: green;">Active</span>
                                    <?php elseif ($rel['status'] === 'paused'): ?>
                                        <span style="color: orange;">Paused</span>
                                    <?php else: ?>
                                        <span style="color: red;">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($rel['started_at'])) ?></td>
                                <td>
                                    <?= $rel['ended_at'] ? date('M d, Y', strtotime($rel['ended_at'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

        <!-- Feedback Tab -->
        <?php if ($tab === 'feedback'): ?>
            <div class="tab-content">
                <h2 class="section-title"><i class="bi bi-chat-left-dots"></i> Session Feedback</h2>
                
                <?php if (empty($feedback_data)): ?>
                        <i class="bi bi-inbox"></i>
                        <p>No feedback yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Mentor</th>
                                    <th>Mentee</th>
                                    <th>Status</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($feedback_data as $session): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($session['date'])) ?></td>
                                        <td><?= htmlspecialchars($session['mentor_name']) ?></td>
                                        <td><?= htmlspecialchars($session['mentee_name']) ?></td>
                                        <td>
                                            <span class="badge badge-status badge-<?= $session['status'] ?>">
                                                <?= ucfirst($session['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($session['rating']): ?>
                                                <span class="rating-stars">
                                                    <?php for ($i = 0; $i < $session['rating']; $i++) echo '★'; ?>
                                                </span>
                                                <span>(<?= $session['rating'] ?>/5)</span>
                                            <?php else: ?>
                                                <span class="text-muted">No feedback</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($session['comments']): ?>
                                                <small title="<?= htmlspecialchars($session['comments']) ?>">
                                                    <?= htmlspecialchars(substr($session['comments'], 0, 50)) ?>...
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
     
<!-- Sessions Tab -->
<?php if ($tab === 'sessions'): ?>
    <div class="tab-content">
        <h2 class="section-title">All Sessions</h2>

        <?php
        $all_sessions = $pdo->query("
            SELECT 
                s.date,
                s.time,
                s.subject,
                s.skill,
                s.created_at,
                m.name AS mentor_name,
                me.name AS mentee_name
            FROM sessions s
            JOIN users m ON s.mentor_id = m.id
            JOIN users me ON s.mentee_id = me.id
            ORDER BY s.date DESC, s.time DESC
        ")->fetchAll();
        ?>

        <?php if (empty($all_sessions)): ?>
            <div class="no-data">
                <p>No sessions found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Mentor</th>
                            <th>Mentee</th>
                            <th>Subject</th>
                            <th>Skill</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_sessions as $session): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($session['date'])) ?></td>
                                <td><?= date('H:i', strtotime($session['time'])) ?></td>
                                <td><?= htmlspecialchars($session['mentor_name']) ?></td>
                                <td><?= htmlspecialchars($session['mentee_name']) ?></td>
                                <td><?= htmlspecialchars($session['subject']) ?></td>
                                <td><?= htmlspecialchars($session['skill']) ?></td>
                                <td><?= date('M d, Y', strtotime($session['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
               
                    <?php
$per_page = 10;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $per_page;

/* Get total sessions count */
$total_sessions = $pdo->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
$total_pages = ceil($total_sessions / $per_page);

/* Fetch paginated sessions */
$stmt = $pdo->prepare("
    SELECT 
        s.date,
        s.time,
        s.subject,
        s.skill,
        s.created_at,
        m.name AS mentor_name,
        me.name AS mentee_name
    FROM sessions s
    JOIN users m ON s.mentor_id = m.id
    JOIN users me ON s.mentee_id = me.id
    ORDER BY s.date DESC, s.time DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$all_sessions = $stmt->fetchAll();
?>
   <!-- Feedback Tab -->
        <?php if ($tab === 'feedback'): ?>
            <div class="tab-content">
                <h2 class="section-title"><i class="bi bi-chat-left-dots"></i> Session Feedback</h2>
                
                <?php if (empty($all_sessions)): ?>
                    <div class="no-data">
                        <i class="bi bi-inbox"></i>
                        <p>No feedback yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Mentor</th>
                                    <th>Mentee</th>
                                    <th>Status</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_sessions as $session): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($session['scheduled_date'])) ?></td>
                                        <td><?= htmlspecialchars($session['mentor_name']) ?></td>
                                        <td><?= htmlspecialchars($session['mentee_name']) ?></td>
                                        <td>
                                            <span class="badge badge-status badge-<?= $session['status'] ?>">
                                                <?= ucfirst($session['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($session['rating']): ?>
                                                <span class="rating-stars">
                                                    <?php for ($i = 0; $i < $session['rating']; $i++) echo '★'; ?>
                                                </span>
                                                <span>(<?= $session['rating'] ?>/5)</span>
                                            <?php else: ?>
                                                <span class="text-muted">No feedback</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($session['comments']): ?>
                                                <small title="<?= htmlspecialchars($session['comments']) ?>">
                                                    <?= htmlspecialchars(substr($session['comments'], 0, 50)) ?>...
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <style>
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
        .dashboard-card .metric {
            font-size: 28px;
            font-weight: bold;
            color: #764ba2;
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
         <!-- Sidebar  -->
        <div class="sidebar col-md-3 col-lg-2">
            <div class="sidebar-brand">
                <i class="bi bi-easel"></i> Mentor Connect
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="#"><i class="bi bi-people"></i> Manage Users</a></li>
                <li><a href="#"><i class="bi bi-person-check"></i> Mentors</a></li>
                <li><a href="#"><i class="bi bi-person-hearts"></i> Mentees</a></li>
                <li><a href="#"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

         <!-- Main Content  -->
        <div class="col-md-9 col-lg-10">
            <!-- Topbar  -->
            <div class="topbar">
                <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
                <div class="user-menu">
                    <div class="user-info">
                        <p>Welcome back!</p>
                        <strong><?php echo htmlspecialchars($username); ?></strong>
                    </div>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

             <!-- Page Content  -->
            <div class="main-content">
                Welcome Section 
                <div class="welcome-section">
                    <h2>Welcome, <?php echo htmlspecialchars($username); ?>! 👋</h2>
                    <p>You are logged in as an Administrator, Manage mentors, mentees, and system settings from here.</p>
                </div>

                 <!-- Statistics  -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-people"></i></div>
                            <h5>Total Users</h5>
                               
                            <div class="number"><?= $stats['total_users']  ?></div> 
                            

                        </div>
                    </div>
                   
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-person-check"></i></div>
                            <h5>Mentors</h5>
                            <div class="number"><?= $stats['total_mentors'] ?? 0 ?></div>

                        
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-person-hearts"></i></div>
                            <h5>Mentees</h5>
                            <div class="number"><?= $stats['total_mentees'] ?? 0 ?></div>

                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-link-45deg"></i></div>
                            <h5>Connections</h5>
                            <div class="number"><?= $stats['total_relationships'] ?? 0 ?></div>

                        </div>
                    </div>
                </div>

                <!-- Main Features  -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h3><i class="bi bi-people"></i> Manage Users</h3>
                            <p>View, edit, and manage all users in the system including mentors and mentees.</p>
                            <button class="btn btn-primary mt-3" disabled>Coming Soon</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h3><i class="bi bi-gear"></i> System Settings</h3>
                            <p>Configure system settings, manage roles, and customize the platform.</p>
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