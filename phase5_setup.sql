-- ============================================================================
-- PHASE 5: FEEDBACK SYSTEM & ADMIN PANEL
-- Database Schema: Session Feedback, Admin Views, Rating System
-- ============================================================================

-- ============================================================================
-- 1. SESSION FEEDBACK TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS session_feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  mentee_id INT NOT NULL,
  rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  comments TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (session_id) REFERENCES mentor_mentee_sessions(session_id) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (mentee_id) REFERENCES users(user_id) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  UNIQUE KEY unique_session_feedback (session_id, mentee_id),
  INDEX idx_mentee_id (mentee_id),
  INDEX idx_rating (rating),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Feedback submitted by mentees after session completion';

-- ============================================================================
-- 2. VIEWS FOR ADMIN REPORTING
-- ============================================================================

-- View: v_sessions_with_feedback
-- Shows all sessions with mentor/mentee info and feedback details
CREATE OR REPLACE VIEW v_sessions_with_feedback AS
SELECT 
  s.session_id,
  s.connection_id,
  s.mentor_id,
  s.mentee_id,
  u_mentor.full_name AS mentor_name,
  u_mentor.email AS mentor_email,
  u_mentor.expertise AS mentor_expertise,
  u_mentor.user_type AS mentor_type,
  u_mentee.full_name AS mentee_name,
  u_mentee.email AS mentee_email,
  u_mentee.user_type AS mentee_type,
  s.scheduled_date,
  s.end_date,
  s.status,
  CASE 
    WHEN s.end_date IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, s.scheduled_date, s.end_date)
    ELSE NULL
  END AS duration_minutes,
  s.sessions_scheduled,
  s.sessions_attended,
  s.created_at AS session_created_at,
  f.feedback_id,
  f.rating,
  f.comments,
  f.created_at AS feedback_created_at
FROM mentor_mentee_sessions s
JOIN users u_mentor ON s.mentor_id = u_mentor.user_id
JOIN users u_mentee ON s.mentee_id = u_mentee.user_id
LEFT JOIN session_feedback f ON s.session_id = f.session_id
ORDER BY s.scheduled_date DESC;

-- View: v_completed_sessions_ready_for_feedback
-- Sessions completed but no feedback yet (mentee can still submit)
CREATE OR REPLACE VIEW v_completed_sessions_ready_for_feedback AS
SELECT 
  s.session_id,
  s.connection_id,
  s.mentor_id,
  s.mentee_id,
  u_mentor.full_name AS mentor_name,
  u_mentor.expertise AS mentor_expertise,
  u_mentee.full_name AS mentee_name,
  s.scheduled_date,
  s.end_date,
  TIMESTAMPDIFF(MINUTE, s.scheduled_date, s.end_date) AS duration_minutes,
  DATEDIFF(NOW(), s.end_date) AS days_since_completion
FROM mentor_mentee_sessions s
JOIN users u_mentor ON s.mentor_id = u_mentor.user_id
JOIN users u_mentee ON s.mentee_id = u_mentee.user_id
WHERE s.status = 'completed' 
  AND s.end_date IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM session_feedback f 
    WHERE f.session_id = s.session_id
  )
ORDER BY s.end_date DESC;

-- View: v_mentor_statistics
-- Mentor performance metrics: avg rating, total sessions, feedback count
CREATE OR REPLACE VIEW v_mentor_statistics AS
SELECT 
  u.user_id,
  u.full_name AS mentor_name,
  u.email,
  u.expertise,
  u.created_at AS joined_date,
  COUNT(DISTINCT s.session_id) AS total_sessions,
  SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) AS completed_sessions,
  SUM(CASE WHEN s.status = 'upcoming' THEN 1 ELSE 0 END) AS upcoming_sessions,
  COUNT(DISTINCT f.feedback_id) AS feedback_count,
  ROUND(AVG(f.rating), 2) AS avg_rating,
  MIN(f.rating) AS min_rating,
  MAX(f.rating) AS max_rating,
  COUNT(CASE WHEN f.rating = 5 THEN 1 END) AS five_star_count,
  COUNT(CASE WHEN f.rating = 4 THEN 1 END) AS four_star_count,
  COUNT(CASE WHEN f.rating = 3 THEN 1 END) AS three_star_count,
  COUNT(CASE WHEN f.rating = 2 THEN 1 END) AS two_star_count,
  COUNT(CASE WHEN f.rating = 1 THEN 1 END) AS one_star_count
FROM users u
LEFT JOIN mentor_mentee_sessions s ON u.user_id = s.mentor_id
LEFT JOIN session_feedback f ON s.session_id = f.session_id
WHERE u.user_type = 'mentor'
GROUP BY u.user_id, u.full_name, u.email, u.expertise, u.created_at;

-- View: v_mentee_statistics
-- Mentee engagement: sessions taken, average mentor rating, feedback submitted
CREATE OR REPLACE VIEW v_mentee_statistics AS
SELECT 
  u.user_id,
  u.full_name AS mentee_name,
  u.email,
  u.domain,
  u.created_at AS joined_date,
  COUNT(DISTINCT s.session_id) AS total_sessions,
  SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) AS completed_sessions,
  SUM(CASE WHEN s.status = 'upcoming' THEN 1 ELSE 0 END) AS upcoming_sessions,
  COUNT(DISTINCT f.feedback_id) AS feedback_submitted,
  ROUND(AVG(f.rating), 2) AS avg_rating_given
FROM users u
LEFT JOIN mentor_mentee_sessions s ON u.user_id = s.mentee_id
LEFT JOIN session_feedback f ON s.session_id = f.session_id
WHERE u.user_type = 'mentee'
GROUP BY u.user_id, u.full_name, u.email, u.domain, u.created_at;

-- View: v_relationship_status
-- Current mentor-mentee relationships with status
CREATE OR REPLACE VIEW v_relationship_status AS
SELECT 
  c.connection_id,
  u_mentor.full_name AS mentor_name,
  u_mentor.email AS mentor_email,
  u_mentor.expertise,
  u_mentee.full_name AS mentee_name,
  u_mentee.email AS mentee_email,
  u_mentee.domain,
  c.status,
  c.is_locked,
  c.start_date,
  c.end_date,
  CASE 
    WHEN c.is_locked = 1 THEN 'Locked'
    WHEN c.status = 'active' AND c.end_date > NOW() THEN 'Active'
    WHEN c.status = 'active' AND c.end_date <= NOW() THEN 'Expired'
    ELSE 'Inactive'
  END AS current_status,
  COUNT(DISTINCT s.session_id) AS total_sessions,
  COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN s.session_id END) AS completed_sessions,
  COUNT(DISTINCT CASE WHEN s.status = 'upcoming' THEN s.session_id END) AS upcoming_sessions
FROM mentor_mentee_connections c
JOIN users u_mentor ON c.mentor_id = u_mentor.user_id
JOIN users u_mentee ON c.mentee_id = u_mentee.user_id
LEFT JOIN mentor_mentee_sessions s ON c.connection_id = s.connection_id
GROUP BY c.connection_id, u_mentor.full_name, u_mentor.email, u_mentor.expertise,
         u_mentee.full_name, u_mentee.email, u_mentee.domain, c.status, c.is_locked,
         c.start_date, c.end_date;

-- View: v_feedback_summary
-- Feedback statistics by rating, mentor, mentee
CREATE OR REPLACE VIEW v_feedback_summary AS
SELECT 
  f.rating,
  COUNT(*) AS count,
  ROUND(COUNT(*) * 100 / 
    (SELECT COUNT(*) FROM session_feedback), 2) AS percentage,
  MIN(f.created_at) AS earliest_feedback,
  MAX(f.created_at) AS latest_feedback
FROM session_feedback f
GROUP BY f.rating
ORDER BY f.rating DESC;

-- ============================================================================
-- 3. STORED PROCEDURES FOR ADMIN QUERIES
-- ============================================================================

-- Procedure: Get all completed sessions with feedback
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_get_all_completed_sessions(
  IN limit_rows INT,
  IN offset_rows INT
)
BEGIN
  SELECT *
  FROM v_sessions_with_feedback
  WHERE status = 'completed'
  ORDER BY session_created_at DESC
  LIMIT limit_rows OFFSET offset_rows;
END //
DELIMITER ;

-- Procedure: Get feedback for a specific mentor
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_get_mentor_feedback(
  IN p_mentor_id INT
)
BEGIN
  SELECT 
    f.feedback_id,
    f.session_id,
    u.full_name AS mentee_name,
    f.rating,
    f.comments,
    f.created_at,
    s.scheduled_date,
    s.status
  FROM session_feedback f
  JOIN mentor_mentee_sessions s ON f.session_id = s.session_id
  JOIN users u ON f.mentee_id = u.user_id
  WHERE s.mentor_id = p_mentor_id
  ORDER BY f.created_at DESC;
END //
DELIMITER ;

-- Procedure: Get all pending feedback (completed sessions without feedback)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_get_pending_feedback(
  IN p_mentee_id INT
)
BEGIN
  SELECT *
  FROM v_completed_sessions_ready_for_feedback
  WHERE mentee_id = p_mentee_id
  ORDER BY days_since_completion ASC;
END //
DELIMITER ;

-- ============================================================================
-- 4. SAMPLE DATA
-- ============================================================================

-- Insert sample feedback for testing
-- Assuming sessions exist from Phase 4
INSERT IGNORE INTO session_feedback (session_id, mentee_id, rating, comments, created_at)
VALUES 
  (1, 3, 5, 'Excellent mentor! Very helpful and knowledgeable. Highly recommend!', DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (2, 5, 4, 'Good session, learned a lot about the industry. Could have gone deeper on technical skills.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (3, 3, 5, 'Outstanding guidance on career planning. Very encouraging!', DATE_SUB(NOW(), INTERVAL 7 DAY)),
  (4, 5, 3, 'Decent session but felt rushed. Could use more structured approach.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (5, 7, 5, 'Amazing mentor! Clear, concise, and very supportive. Changed my perspective!', DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE 
  rating = VALUES(rating),
  comments = VALUES(comments);

-- ============================================================================
-- 5. TRIGGER: Validate feedback only for completed sessions
-- ============================================================================

DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_validate_feedback_completion
BEFORE INSERT ON session_feedback
FOR EACH ROW
BEGIN
  DECLARE session_status VARCHAR(20);
  DECLARE session_end_date DATETIME;
  
  -- Get session status
  SELECT status, end_date INTO session_status, session_end_date
  FROM mentor_mentee_sessions
  WHERE session_id = NEW.session_id;
  
  -- Check if session is completed and has end_date
  IF session_status != 'completed' OR session_end_date IS NULL THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Feedback can only be submitted for completed sessions with end_date.';
  END IF;
END //
DELIMITER ;

-- ============================================================================
-- 6. INDEXES FOR PERFORMANCE
-- ============================================================================

CREATE INDEX IF NOT EXISTS idx_session_feedback_session_date 
  ON session_feedback(session_id, created_at);
CREATE INDEX IF NOT EXISTS idx_session_feedback_mentee 
  ON session_feedback(mentee_id, session_id);
CREATE INDEX IF NOT EXISTS idx_session_feedback_rating_date 
  ON session_feedback(rating, created_at);

-- ============================================================================
-- 7. VERIFICATION QUERIES
-- ============================================================================

-- Verify tables created
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE '%feedback%';

-- Verify views created
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS 
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'v_%';

-- Sample query - all sessions with feedback
-- SELECT * FROM v_sessions_with_feedback LIMIT 10;

-- Sample query - mentor statistics
-- SELECT * FROM v_mentor_statistics ORDER BY avg_rating DESC;

-- Sample query - sessions ready for feedback (by mentee)
-- SELECT * FROM v_completed_sessions_ready_for_feedback WHERE mentee_id = 3;

-- ============================================================================
-- Phase 5 Setup Complete
-- ============================================================================
-- Tables: session_feedback
-- Views: v_sessions_with_feedback, v_completed_sessions_ready_for_feedback,
--        v_mentor_statistics, v_mentee_statistics, v_relationship_status,
--        v_feedback_summary
-- Triggers: tr_validate_feedback_completion
-- Procedures: sp_get_all_completed_sessions, sp_get_mentor_feedback,
--             sp_get_pending_feedback
-- ============================================================================
