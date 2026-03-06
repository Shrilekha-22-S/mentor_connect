<?php
/**
 * Mentee Feedback Page
 * View completed sessions and submit/manage feedback
 */

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect if not mentee
if ($_SESSION['user_type'] !== 'mentee') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/requests.php';

$mentee_id = $_SESSION['user_id'];
$page_title = 'Feedback | Mentor Connect';

// Get pending feedback sessions (completed but not yet rated)
$pending_sessions = get_pending_feedback_sessions($pdo, $mentee_id);

// Get all completed sessions with their feedback
$stmt = $pdo->prepare('
    SELECT s.*, u.full_name as mentor_name, f.feedback_id, f.rating, f.comments, f.created_at as feedback_date
    FROM mentor_mentee_sessions s
    JOIN users u ON s.mentor_id = u.user_id
    LEFT JOIN session_feedback f ON s.session_id = f.session_id
    WHERE s.mentee_id = ? AND s.status = "completed"
    ORDER BY s.end_date DESC
');
$stmt->execute([$mentee_id]);
$completed_sessions = $stmt->fetchAll();

// Get mentee statistics
$mentee_stats = get_mentee_statistics($pdo, $mentee_id);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            margin-bottom: 0;
            opacity: 0.95;
        }

        .container-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .stats-card h6 {
            color: #999;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .stats-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .session-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .session-card.has-feedback {
            border-left-color: #28a745;
        }

        .session-card.no-feedback {
            border-left-color: #ffc107;
        }

        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .session-mentor {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }

        .session-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.85rem;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stars {
            display: flex;
            gap: 0.25rem;
        }

        .star {
            color: #ddd;
            font-size: 1.2rem;
        }

        .star.filled {
            color: #ffc107;
        }

        .rating-label {
            font-weight: 600;
            color: #333;
        }

        .feedback-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .feedback-content h6 {
            font-size: 0.9rem;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .feedback-text {
            color: #333;
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }

        .feedback-date {
            font-size: 0.85rem;
            color: #999;
        }

        .btn-submit-feedback {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-submit-feedback:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-submit-feedback:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-submitted {
            background-color: #d4edda;
            color: #155724;
        }

        .no-sessions {
            text-align: center;
            padding: 3rem 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .no-sessions i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
            display: block;
        }

        .no-sessions p {
            color: #999;
            margin: 0;
        }

        .modal-content {
            border-radius: 12px;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .rating-input {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .rating-btn {
            width: 50px;
            height: 50px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rating-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: scale(1.1);
        }

        .rating-btn.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: var(--primary);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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

        @media (max-width: 768px) {
            .session-header {
                flex-direction: column;
            }

            .session-info {
                grid-template-columns: 1fr;
            }

            .stats-card .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container-main">
            <h1><i class="bi bi-chat-left-dots"></i> Session Feedback</h1>
            <p>Rate your mentoring sessions and help mentors improve</p>
        </div>
    </div>

    <div class="container-main">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h6>Completed Sessions</h6>
                    <div class="stat-value"><?= count($completed_sessions) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h6>Feedback Submitted</h6>
                    <div class="stat-value"><?= $mentee_stats['feedback_submitted'] ?? 0 ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h6>Pending Feedback</h6>
                    <div class="stat-value"><?= count($pending_sessions) ?></div>
                </div>
            </div>
        </div>

        <!-- Pending Feedback Alert -->
        <?php if (count($pending_sessions) > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>You have <?= count($pending_sessions) ?> completed session(s) waiting for feedback.</strong>
                Scroll down to leave your rating and help mentors improve!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Sessions List -->
        <div>
            <h2 class="section-title">
                <i class="bi bi-calendar-check"></i>
                Completed Sessions
            </h2>

            <?php if (empty($completed_sessions)): ?>
                <div class="no-sessions">
                    <i class="bi bi-inbox"></i>
                    <p>No completed sessions yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($completed_sessions as $session): ?>
                    <div class="session-card <?= $session['feedback_id'] ? 'has-feedback' : 'no-feedback' ?>">
                        <div class="session-header">
                            <div>
                                <div class="session-mentor"><?= htmlspecialchars($session['mentor_name']) ?></div>
                                <small class="text-muted">Mentor Session</small>
                            </div>
                            <div>
                                <?php if ($session['feedback_id']): ?>
                                    <span class="badge badge-status badge-submitted">
                                        <i class="bi bi-check-circle"></i> Feedback Submitted
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-status badge-pending">
                                        <i class="bi bi-hourglass-split"></i> Pending Feedback
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="session-info">
                            <div class="info-item">
                                <span class="info-label">Date</span>
                                <span class="info-value"><?= date('M d, Y', strtotime($session['scheduled_date'])) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Time</span>
                                <span class="info-value"><?= date('h:i A', strtotime($session['scheduled_date'])) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Duration</span>
                                <span class="info-value">
                                    <?php
                                    $start = new DateTime($session['scheduled_date']);
                                    $end = new DateTime($session['end_date']);
                                    $interval = $start->diff($end);
                                    echo $interval->format('%h:%02d hours');
                                    ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($session['feedback_id']): ?>
                            <!-- Display Feedback -->
                            <div class="rating-display">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= $i <= $session['rating'] ? 'filled' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-label"><?= $session['rating'] ?>/5</span>
                            </div>

                            <?php if ($session['comments']): ?>
                                <div class="feedback-content">
                                    <h6>Your Feedback</h6>
                                    <p class="feedback-text"><?= htmlspecialchars($session['comments']) ?></p>
                                    <div class="feedback-date">
                                        Submitted <?= date('M d, Y \a\t h:i A', strtotime($session['feedback_date'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Submit Feedback -->
                            <button class="btn btn-submit-feedback" onclick="openFeedbackModal(
                                <?= $session['session_id'] ?>,
                                '<?= addslashes($session['mentor_name']) ?>'
                            )">
                                <i class="bi bi-star"></i> Submit Feedback
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-star"></i> Rate Your Session
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="mentorNameDisplay" style="font-weight: 600; margin-bottom: 1rem;"></div>

                    <label class="form-label">Rating *</label>
                    <div class="rating-input" id="ratingInput">
                        <button type="button" class="rating-btn" onclick="selectRating(1)">1★</button>
                        <button type="button" class="rating-btn" onclick="selectRating(2)">2★</button>
                        <button type="button" class="rating-btn" onclick="selectRating(3)">3★</button>
                        <button type="button" class="rating-btn" onclick="selectRating(4)">4★</button>
                        <button type="button" class="rating-btn" onclick="selectRating(5)">5★</button>
                    </div>
                    <input type="hidden" id="selectedRating" value="0">

                    <div class="mb-3">
                        <label class="form-label">Comments (Optional)</label>
                        <textarea class="form-control" id="feedbackComments" rows="4" 
                                  placeholder="Share your thoughts about the session..."></textarea>
                        <small class="text-muted">Max 1000 characters</small>
                    </div>

                    <div id="feedbackError" class="alert alert-danger d-none" role="alert"></div>
                    <div id="feedbackLoading" class="d-none">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Submitting feedback...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitFeedback()">
                        <i class="bi bi-check-circle"></i> Submit Feedback
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentSessionId = null;
        let modal = null;

        function openFeedbackModal(sessionId, mentorName) {
            currentSessionId = sessionId;
            document.getElementById('mentorNameDisplay').textContent = 'Session with ' + mentorName;
            document.getElementById('selectedRating').value = '0';
            document.getElementById('feedbackComments').value = '';
            document.getElementById('feedbackError').classList.add('d-none');
            document.getElementById('feedbackLoading').classList.add('d-none');

            // Reset rating buttons
            document.querySelectorAll('.rating-btn').forEach(btn => {
                btn.classList.remove('selected');
            });

            if (!modal) {
                modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
            }
            modal.show();
        }

        function selectRating(rating) {
            document.getElementById('selectedRating').value = rating;
            document.querySelectorAll('.rating-btn').forEach((btn, i) => {
                btn.classList.toggle('selected', i < rating);
            });
        }

        function submitFeedback() {
            const rating = parseInt(document.getElementById('selectedRating').value);
            const comments = document.getElementById('feedbackComments').value.trim();
            const errorDiv = document.getElementById('feedbackError');
            const loadingDiv = document.getElementById('feedbackLoading');

            // Validate rating
            if (rating === 0) {
                errorDiv.textContent = 'Please select a rating';
                errorDiv.classList.remove('d-none');
                return;
            }

            // Validate comment length
            if (comments.length > 1000) {
                errorDiv.textContent = 'Comments must not exceed 1000 characters';
                errorDiv.classList.remove('d-none');
                return;
            }

            // Show loading
            loadingDiv.classList.remove('d-none');
            errorDiv.classList.add('d-none');

            // Submit feedback
            fetch('/mentor_connect/api/submit_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: currentSessionId,
                    rating: rating,
                    comments: comments
                })
            })
            .then(response => response.json())
            .then(data => {
                loadingDiv.classList.add('d-none');

                if (data.success) {
                    // Close modal and reload page
                    modal.hide();
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    const errors = data.errors || [data.message];
                    errorDiv.textContent = errors[0] || 'Failed to submit feedback';
                    errorDiv.classList.remove('d-none');
                }
            })
            .catch(err => {
                loadingDiv.classList.add('d-none');
                errorDiv.textContent = 'Network error: ' + err.message;
                errorDiv.classList.remove('d-none');
            });
        }

        // Allow Enter key for modal
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey && document.activeElement.id === 'feedbackComments') {
                submitFeedback();
            }
        });
    </script>
</body>
</html>
