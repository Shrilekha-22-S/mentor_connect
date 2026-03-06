-- ============================================
-- PHASE 4: FullCalendar Integration & Booking
-- ============================================
-- Mentor availability calendar with bookable time slots
-- Mentee can view and book available slots

-- ============================================
-- 1. Create calendar_availability_blocks table
-- ============================================
-- Concrete date/time slots generated from mentor availability
-- Each slot can be booked once
CREATE TABLE IF NOT EXISTS mentor_calendar_blocks (
    block_id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    availability_rule_id INT,
    block_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_booked BOOLEAN DEFAULT FALSE,
    booked_by_session_id INT,
    booked_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_calendar_mentor FOREIGN KEY (mentor_id) 
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_rule FOREIGN KEY (availability_rule_id) 
        REFERENCES mentor_session_availability(availability_id) ON DELETE SET NULL,
    CONSTRAINT fk_calendar_session FOREIGN KEY (booked_by_session_id) 
        REFERENCES mentor_mentee_sessions(session_id) ON DELETE SET NULL,
    
    INDEX idx_mentor_date (mentor_id, block_date),
    INDEX idx_is_booked (is_booked),
    INDEX idx_block_date (block_date),
    UNIQUE KEY unique_mentor_slot (mentor_id, block_date, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Update mentor_mentee_sessions table
-- ============================================
-- Link sessions to calendar blocks for booking reference
ALTER TABLE mentor_mentee_sessions 
ADD COLUMN IF NOT EXISTS calendar_block_id INT AFTER session_month,
ADD CONSTRAINT fk_session_calendar_block FOREIGN KEY (calendar_block_id) 
    REFERENCES mentor_calendar_blocks(block_id) ON DELETE SET NULL;

-- ============================================
-- 3. Create mentor_blackout_dates table
-- ============================================
-- Dates when mentor is unavailable (vacation, holidays, etc.)
CREATE TABLE IF NOT EXISTS mentor_blackout_dates (
    blackout_id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_blackout_mentor FOREIGN KEY (mentor_id) 
        REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_mentor_blackout (mentor_id),
    INDEX idx_blackout_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. Create v_available_slots view
-- ============================================
-- Shows only available (unboooked) slots for mentees to view
CREATE OR REPLACE VIEW v_available_slots AS
SELECT 
    b.block_id,
    b.mentor_id,
    b.block_date,
    b.start_time,
    b.end_time,
    CONCAT(b.block_date, ' ', b.start_time) as start_datetime,
    CONCAT(b.block_date, ' ', b.end_time) as end_datetime,
    TIMEDIFF(b.end_time, b.start_time) as duration,
    u.username as mentor_name,
    u.email as mentor_email,
    mp.domain_id,
    d.name as domain_name,
    mp.expertise,
    b.is_booked
FROM mentor_calendar_blocks b
JOIN users u ON b.mentor_id = u.user_id
JOIN mentor_profiles mp ON u.user_id = mp.user_id
JOIN domains d ON mp.domain_id = d.domain_id
WHERE b.is_booked = FALSE 
AND b.block_date >= CURRENT_DATE
AND CONCAT(b.block_date, ' ', b.end_time) > NOW()
ORDER BY b.block_date ASC, b.start_time ASC;

-- ============================================
-- 5. Create v_calendar_events view
-- ============================================
-- All sessions/blocks for calendar display
CREATE OR REPLACE VIEW v_calendar_events AS
SELECT 
    s.session_id as event_id,
    'session' as event_type,
    c.mentor_id,
    c.mentee_id,
    um.username as mentor_name,
    ume.username as mentee_name,
    s.scheduled_date as start_datetime,
    DATE_ADD(s.scheduled_date, INTERVAL s.duration_minutes MINUTE) as end_datetime,
    s.status,
    s.notes as title,
    CASE 
        WHEN s.status = 'completed' THEN '#28a745'
        WHEN s.status = 'scheduled' THEN '#667eea'
        ELSE '#6c757d'
    END as color
FROM mentor_mentee_sessions s
JOIN mentor_mentee_connections c ON s.connection_id = c.connection_id
JOIN users um ON c.mentor_id = um.user_id
JOIN users ume ON c.mentee_id = ume.user_id
UNION ALL
SELECT 
    b.block_id as event_id,
    'availability' as event_type,
    b.mentor_id,
    NULL as mentee_id,
    u.username as mentor_name,
    NULL as mentee_name,
    CONCAT(b.block_date, ' ', b.start_time) as start_datetime,
    CONCAT(b.block_date, ' ', b.end_time) as end_datetime,
    IF(b.is_booked, 'booked', 'available') as status,
    IF(b.is_booked, 'Booked', 'Available Slot') as title,
    IF(b.is_booked, '#ffc107', '#90ee90') as color
FROM mentor_calendar_blocks b
JOIN users u ON b.mentor_id = u.user_id
WHERE b.block_date >= CURRENT_DATE;

-- ============================================
-- 6. Sample availability blocks for testing
-- ============================================
-- Generate sample blocks for mentor_id = 2 (next 60 days)
INSERT IGNORE INTO mentor_calendar_blocks 
(mentor_id, availability_rule_id, block_date, start_time, end_time, is_booked) 
SELECT 
    2 as mentor_id,
    NULL as availability_rule_id,
    DATE_ADD(CURRENT_DATE, INTERVAL (@days:=@days+1) DAY) as block_date,
    '14:00:00' as start_time,
    '15:00:00' as end_time,
    FALSE as is_booked
FROM (SELECT @days:=-1) t,
     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
      UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
      UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20) numbers
WHERE DATE_ADD(CURRENT_DATE, INTERVAL (@days:=@days+1) DAY) <= DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY)
AND DAYNAME(DATE_ADD(CURRENT_DATE, INTERVAL @days DAY)) != 'Sunday';

-- Similar blocks for 3pm slot
INSERT IGNORE INTO mentor_calendar_blocks 
(mentor_id, availability_rule_id, block_date, start_time, end_time, is_booked) 
SELECT 
    2 as mentor_id,
    NULL as availability_rule_id,
    DATE_ADD(CURRENT_DATE, INTERVAL (@days2:=@days2+1) DAY) as block_date,
    '15:00:00' as start_time,
    '16:00:00' as end_time,
    FALSE as is_booked
FROM (SELECT @days2:=-1) t,
     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
      UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
      UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20) numbers
WHERE DATE_ADD(CURRENT_DATE, INTERVAL (@days2:=@days2+1) DAY) <= DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY)
AND DAYNAME(DATE_ADD(CURRENT_DATE, INTERVAL @days2 DAY)) != 'Sunday'
AND DATE_ADD(CURRENT_DATE, INTERVAL @days2 DAY) <= DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY);

-- ============================================
-- 7. Trigger to mark availability as booked
-- ============================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_mark_availability_booked
AFTER INSERT ON mentor_mentee_sessions
FOR EACH ROW
BEGIN
    IF NEW.calendar_block_id IS NOT NULL THEN
        UPDATE mentor_calendar_blocks
        SET is_booked = TRUE,
            booked_by_session_id = NEW.session_id,
            booked_at = CURRENT_TIMESTAMP
        WHERE block_id = NEW.calendar_block_id;
    END IF;
END//
DELIMITER ;

-- ============================================
-- 8. Trigger to free availability on session delete
-- ============================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_free_availability_on_delete
AFTER DELETE ON mentor_mentee_sessions
FOR EACH ROW
BEGIN
    IF OLD.calendar_block_id IS NOT NULL THEN
        UPDATE mentor_calendar_blocks
        SET is_booked = FALSE,
            booked_by_session_id = NULL,
            booked_at = NULL
        WHERE block_id = OLD.calendar_block_id;
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
-- 5. Verify tables: mentor_calendar_blocks, mentor_blackout_dates
-- 6. Check triggers: tr_mark_availability_booked, tr_free_availability_on_delete
-- 7. Test: Should see calendar blocks for next 60 days for mentor_id=2
--
-- Test Query:
-- SELECT * FROM mentor_calendar_blocks WHERE mentor_id = 2 LIMIT 10;
-- SELECT * FROM v_available_slots LIMIT 10;
