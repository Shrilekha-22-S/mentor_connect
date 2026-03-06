-- ============================================
-- PHASE 3: Session Management & Duration Lock
-- ============================================
-- Duration: 6 months, 1 session per month (max 6 sessions)
-- Status: Extends Phase 2 implementation

-- ============================================
-- 1. Extend mentor_mentee_connections table
-- ============================================
-- Add duration tracking columns
ALTER TABLE mentor_mentee_connections 
ADD COLUMN IF NOT EXISTS start_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status,
ADD COLUMN IF NOT EXISTS end_date DATETIME NOT NULL AFTER start_date,
ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT TRUE AFTER end_date,
ADD COLUMN IF NOT EXISTS sessions_scheduled INT DEFAULT 0 AFTER is_locked,
ADD COLUMN IF NOT EXISTS sessions_completed INT DEFAULT 0 AFTER sessions_scheduled;

-- ============================================
-- 2. Create mentor_mentee_sessions table
-- ============================================
-- Track individual sessions within the 6-month relationship
CREATE TABLE IF NOT EXISTS mentor_mentee_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    connection_id INT NOT NULL,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    scheduled_date DATETIME NOT NULL,
    session_month VARCHAR(7) NOT NULL COMMENT 'YYYY-MM format to prevent duplicate sessions in same month',
    duration_minutes INT DEFAULT 60,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    CONSTRAINT fk_session_connection FOREIGN KEY (connection_id) 
        REFERENCES mentor_mentee_connections(connection_id) ON DELETE CASCADE,
    CONSTRAINT fk_session_mentor FOREIGN KEY (mentor_id) 
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_session_mentee FOREIGN KEY (mentee_id) 
        REFERENCES users(user_id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_connection_id (connection_id),
    INDEX idx_mentor_id (mentor_id),
    INDEX idx_mentee_id (mentee_id),
    INDEX idx_session_month (session_month),
    INDEX idx_status (status),
    
    -- Unique constraint: Only 1 session per month per connection
    UNIQUE KEY unique_connection_month (connection_id, session_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. Create session_availability table
-- ============================================
-- Track available time slots for mentors
CREATE TABLE IF NOT EXISTS mentor_session_availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_availability_mentor FOREIGN KEY (mentor_id) 
        REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_mentor_availability (mentor_id),
    INDEX idx_day_of_week (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. Sample Data for Testing
-- ============================================

-- Sample mentor availability (mentor_id = 2)
INSERT IGNORE INTO mentor_session_availability (mentor_id, day_of_week, start_time, end_time, is_active) VALUES
(2, 'Monday', '18:00:00', '20:00:00', TRUE),
(2, 'Wednesday', '18:00:00', '20:00:00', TRUE),
(2, 'Friday', '18:00:00', '20:00:00', TRUE),
(2, 'Saturday', '10:00:00', '12:00:00', TRUE),
(2, 'Saturday', '14:00:00', '16:00:00', TRUE);

-- ============================================
-- 5. View for active relationships
-- ============================================
CREATE OR REPLACE VIEW v_active_connections AS
SELECT 
    c.connection_id,
    c.request_id,
    c.mentee_id,
    c.mentor_id,
    c.status,
    c.start_date,
    c.end_date,
    c.is_locked,
    c.sessions_scheduled,
    c.sessions_completed,
    DATEDIFF(c.end_date, c.start_date) as days_remaining,
    CASE 
        WHEN CURRENT_TIMESTAMP > c.end_date THEN 'expired'
        WHEN DATEDIFF(c.end_date, CURRENT_TIMESTAMP) <= 7 THEN 'expiring_soon'
        ELSE 'active'
    END as lock_status,
    um.email as mentor_email,
    um.username as mentor_username,
    ume.email as mentee_email,
    ume.username as mentee_username
FROM mentor_mentee_connections c
JOIN users um ON c.mentor_id = um.user_id
JOIN users ume ON c.mentee_id = ume.user_id
WHERE c.status = 'active' AND c.is_locked = TRUE;

-- ============================================
-- 6. View for session statistics
-- ============================================
CREATE OR REPLACE VIEW v_session_statistics AS
SELECT 
    c.connection_id,
    c.mentor_id,
    c.mentee_id,
    COUNT(CASE WHEN s.status = 'completed' THEN 1 END) as completed_sessions,
    COUNT(CASE WHEN s.status = 'scheduled' THEN 1 END) as scheduled_sessions,
    COUNT(CASE WHEN s.status IN ('completed', 'scheduled') THEN 1 END) as total_sessions,
    (6 - COUNT(CASE WHEN s.status IN ('completed', 'scheduled') THEN 1 END)) as remaining_sessions,
    GROUP_CONCAT(DISTINCT s.session_month ORDER BY s.session_month) as used_months
FROM mentor_mentee_connections c
LEFT JOIN mentor_mentee_sessions s ON c.connection_id = s.connection_id 
    AND s.status IN ('completed', 'scheduled')
GROUP BY c.connection_id, c.mentor_id, c.mentee_id;

-- ============================================
-- 7. Trigger to calculate end_date on connection creation
-- ============================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_set_connection_end_date
BEFORE INSERT ON mentor_mentee_connections
FOR EACH ROW
BEGIN
    SET NEW.end_date = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 6 MONTH);
    SET NEW.is_locked = TRUE;
END//
DELIMITER ;

-- ============================================
-- 8. Trigger to update session counters
-- ============================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_update_session_count
AFTER INSERT ON mentor_mentee_sessions
FOR EACH ROW
BEGIN
    UPDATE mentor_mentee_connections
    SET sessions_scheduled = (
        SELECT COUNT(*) FROM mentor_mentee_sessions 
        WHERE connection_id = NEW.connection_id AND status = 'scheduled'
    ),
    sessions_completed = (
        SELECT COUNT(*) FROM mentor_mentee_sessions 
        WHERE connection_id = NEW.connection_id AND status = 'completed'
    )
    WHERE connection_id = NEW.connection_id;
END//
DELIMITER ;

-- ============================================
-- 9. Trigger to update session counters on completion
-- ============================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_update_session_count_completion
AFTER UPDATE ON mentor_mentee_sessions
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        UPDATE mentor_mentee_connections
        SET sessions_scheduled = (
            SELECT COUNT(*) FROM mentor_mentee_sessions 
            WHERE connection_id = NEW.connection_id AND status = 'scheduled'
        ),
        sessions_completed = (
            SELECT COUNT(*) FROM mentor_mentee_sessions 
            WHERE connection_id = NEW.connection_id AND status = 'completed'
        )
        WHERE connection_id = NEW.connection_id;
    END IF;
END//
DELIMITER ;

COMMIT;

-- ============================================
-- SETUP INSTRUCTIONS
-- ============================================
-- 1. In phpMyAdmin, select 'mentors_connect' database
-- 2. Go to 'SQL' tab
-- 3. Paste this entire script
-- 4. Click 'Go' to execute
-- 5. Verify tables: mentor_mentee_sessions, mentor_session_availability created
-- 6. Verify columns added to mentor_mentee_connections
-- 7. Check triggers exist in Structure
--
-- Test: Send mentee-to-mentor request, accept it, verify end_date is 6 months from now
