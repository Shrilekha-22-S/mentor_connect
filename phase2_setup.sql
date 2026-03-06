-- Phase 2: Mentor-Mentee Request System
-- This SQL creates tables for mentors, mentees, domains, and request management

-- 1. Domains Table
CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Mentor Profiles Table
CREATE TABLE IF NOT EXISTS mentor_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    domain_id INT NOT NULL,
    expertise TEXT,
    bio TEXT,
    max_mentees INT DEFAULT 2,
    current_mentees INT DEFAULT 0,
    availability VARCHAR(100),
    hourly_rate DECIMAL(8, 2),
    verified BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3, 2),
    total_ratings INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES domains(id),
    INDEX(domain_id),
    INDEX(verified),
    INDEX(current_mentees)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Mentee Profiles Table
CREATE TABLE IF NOT EXISTS mentee_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    domain_id INT NOT NULL,
    learning_goals TEXT,
    bio TEXT,
    experience_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    request_count INT DEFAULT 0,
    max_requests INT DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES domains(id),
    INDEX(domain_id),
    INDEX(request_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Mentor-Mentee Requests Table
CREATE TABLE IF NOT EXISTS mentor_mentee_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    message TEXT,
    mentee_domain_id INT NOT NULL,
    mentor_domain_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_domain_id) REFERENCES domains(id),
    FOREIGN KEY (mentor_domain_id) REFERENCES domains(id),
    INDEX(status),
    INDEX(mentee_id),
    INDEX(mentor_id),
    INDEX(created_at),
    UNIQUE KEY unique_request (mentee_id, mentor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Accepted Connections Table (for active mentor-mentee relationships)
CREATE TABLE IF NOT EXISTS mentor_mentee_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (request_id) REFERENCES mentor_mentee_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(status),
    INDEX(mentee_id),
    INDEX(mentor_id),
    UNIQUE KEY unique_connection (mentee_id, mentor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================
-- INSERT SAMPLE DATA (Test Domains)
-- ===========================================================

-- Sample Domains
INSERT INTO domains (name, description) VALUES 
('Web Development', 'Frontend, Backend, Full-stack'),
('Data Science', 'Machine Learning, Data Analysis'),
('Mobile Development', 'iOS, Android, React Native'),
('DevOps', 'CI/CD, Cloud Infrastructure'),
('UI/UX Design', 'Graphic Design, User Experience')
ON DUPLICATE KEY UPDATE id=id;

-- ===========================================================
-- INSERT MENTOR & MENTEE PROFILES (Linked to existing users)
-- ===========================================================

-- Admin Profile as Mentor (domain_id = 1, Web Development)
INSERT IGNORE INTO mentor_profiles (user_id, domain_id, expertise, bio, max_mentees, availability, verified, rating)
VALUES (1, 1, 'Full-stack PHP, Laravel, MySQL', 'Experienced developer with 10+ years in web development', 2, 'Weekends', TRUE, 4.8);

-- Mentor Profile (domain_id = 2, Data Science)
INSERT IGNORE INTO mentor_profiles (user_id, domain_id, expertise, bio, max_mentees, availability, verified, rating)
VALUES (2, 2, 'Machine Learning, Python, TensorFlow', 'Data science expert with real-world experience', 2, 'Weekday evenings', TRUE, 4.9);

-- Mentee Profile (domain_id = 3, Mobile Development - different from admin mentor)
INSERT IGNORE INTO mentee_profiles (user_id, domain_id, learning_goals, bio, experience_level, max_requests)
VALUES (3, 3, 'Learn React Native and mobile app development', 'Beginner developer looking for guidance', 'beginner', 2);

-- ===========================================================
-- SAMPLE REQUEST (Optional - for testing)
-- ===========================================================
-- Uncomment to add a sample pending request:
-- INSERT IGNORE INTO mentor_mentee_requests 
-- (mentee_id, mentor_id, status, message, mentee_domain_id, mentor_domain_id)
-- VALUES (3, 1, 'pending', 'Hi, I would like to learn web development from you', 3, 1);

-- ===========================================================
-- Verify Installation
-- ===========================================================
-- SELECT 'Domains Table' as Table_Name, COUNT(*) as Count FROM domains
-- UNION ALL
-- SELECT 'Mentor Profiles', COUNT(*) FROM mentor_profiles
-- UNION ALL
-- SELECT 'Mentee Profiles', COUNT(*) FROM mentee_profiles
-- UNION ALL
-- SELECT 'Requests', COUNT(*) FROM mentor_mentee_requests
-- UNION ALL
-- SELECT 'Connections', COUNT(*) FROM mentor_mentee_connections;
