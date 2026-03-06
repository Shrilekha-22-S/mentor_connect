<?php
/**
 * Request Management Functions
 * Handles mentor-mentee request system with validation
 */

// ============================================================================
// VALIDATION FUNCTIONS
// ============================================================================

/**
 * Check if mentee can send a request (max 2 requests)
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id User ID of mentee
 * @return array ['can_request' => bool, 'message' => string, 'count' => int]
 */
function can_mentee_send_request($pdo, $mentee_id) {
    try {
        // Get mentee profile
        $stmt = $pdo->prepare('
            SELECT mp.id, mp.request_count, mp.max_requests
            FROM mentee_profiles mp
            WHERE mp.user_id = ?
        ');
        $stmt->execute([$mentee_id]);
        $mentee_profile = $stmt->fetch();
        
        if (!$mentee_profile) {
            return [
                'can_request' => false,
                'message' => 'Mentee profile not found',
                'count' => 0
            ];
        }
        
        // Check if mentee has reached request limit
        if ($mentee_profile['request_count'] >= $mentee_profile['max_requests']) {
            return [
                'can_request' => false,
                'message' => 'You have reached the maximum number of requests (' . $mentee_profile['max_requests'] . ')',
                'count' => $mentee_profile['request_count']
            ];
        }
        
        return [
            'can_request' => true,
            'message' => 'You can send a request',
            'count' => $mentee_profile['request_count']
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in can_mentee_send_request: ' . $e->getMessage());
        return [
            'can_request' => false,
            'message' => 'Database error occurred',
            'count' => 0
        ];
    }
}

/**
 * Check if mentor can accept more mentees (max 2)
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id User ID of mentor
 * @return array ['can_accept' => bool, 'message' => string, 'current' => int, 'max' => int]
 */
function can_mentor_accept_mentee($pdo, $mentor_id) {
    try {
        // Get mentor profile
        $stmt = $pdo->prepare('
            SELECT mp.id, mp.current_mentees, mp.max_mentees
            FROM mentor_profiles mp
            WHERE mp.user_id = ?
        ');
        $stmt->execute([$mentor_id]);
        $mentor_profile = $stmt->fetch();
        
        if (!$mentor_profile) {
            return [
                'can_accept' => false,
                'message' => 'Mentor profile not found',
                'current' => 0,
                'max' => 0
            ];
        }
        
        // Check if mentor has capacity
        if ($mentor_profile['current_mentees'] >= $mentor_profile['max_mentees']) {
            return [
                'can_accept' => false,
                'message' => 'Mentor is currently fully booked',
                'current' => $mentor_profile['current_mentees'],
                'max' => $mentor_profile['max_mentees']
            ];
        }
        
        return [
            'can_accept' => true,
            'message' => 'Mentor can accept this mentee',
            'current' => $mentor_profile['current_mentees'],
            'max' => $mentor_profile['max_mentees']
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in can_mentor_accept_mentee: ' . $e->getMessage());
        return [
            'can_accept' => false,
            'message' => 'Database error occurred',
            'current' => 0,
            'max' => 0
        ];
    }
}

/**
 * Check if mentor and mentee are from different domains
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id User ID of mentee
 * @param int $mentor_id User ID of mentor
 * @return array ['different_domain' => bool, 'mentee_domain' => string, 'mentor_domain' => string]
 */
function are_different_domains($pdo, $mentee_id, $mentor_id) {
    try {
        // Get mentee domain
        $stmt = $pdo->prepare('
            SELECT d.id, d.name
            FROM mentee_profiles mp
            JOIN domains d ON mp.domain_id = d.id
            WHERE mp.user_id = ?
        ');
        $stmt->execute([$mentee_id]);
        $mentee_domain = $stmt->fetch();
        
        // Get mentor domain
        $stmt = $pdo->prepare('
            SELECT d.id, d.name
            FROM mentor_profiles mp
            JOIN domains d ON mp.domain_id = d.id
            WHERE mp.user_id = ?
        ');
        $stmt->execute([$mentor_id]);
        $mentor_domain = $stmt->fetch();
        
        if (!$mentee_domain || !$mentor_domain) {
            return [
                'different_domain' => false,
                'mentee_domain' => $mentee_domain['name'] ?? 'Unknown',
                'mentor_domain' => $mentor_domain['name'] ?? 'Unknown',
                'message' => 'Profile information incomplete'
            ];
        }
        
        $are_different = $mentee_domain['id'] !== $mentor_domain['id'];
        
        return [
            'different_domain' => $are_different,
            'mentee_domain' => $mentee_domain['name'],
            'mentor_domain' => $mentor_domain['name'],
            'message' => !$are_different ? 'Mentor and mentee must be from different domains' : 'Domains are different'
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in are_different_domains: ' . $e->getMessage());
        return [
            'different_domain' => false,
            'mentee_domain' => 'Unknown',
            'mentor_domain' => 'Unknown',
            'message' => 'Database error occurred'
        ];
    }
}

/**
 * Check if request already exists between mentee and mentor
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id User ID of mentee
 * @param int $mentor_id User ID of mentor
 * @return array ['already_exists' => bool, 'existing_request' => array|null]
 */
function request_already_exists($pdo, $mentee_id, $mentor_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT id, status, created_at
            FROM mentor_mentee_requests
            WHERE mentee_id = ? AND mentor_id = ?
            LIMIT 1
        ');
        $stmt->execute([$mentee_id, $mentor_id]);
        $request = $stmt->fetch();
        
        return [
            'already_exists' => $request !== false,
            'existing_request' => $request
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in request_already_exists: ' . $e->getMessage());
        return [
            'already_exists' => false,
            'existing_request' => null
        ];
    }
}

// ============================================================================
// REQUEST MANAGEMENT FUNCTIONS
// ============================================================================

/**
 * Create a new mentor-mentee request (with all validations)
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id User ID of mentee
 * @param int $mentor_id User ID of mentor
 * @param string $message Request message
 * @return array ['success' => bool, 'message' => string, 'request_id' => int|null, 'errors' => array]
 */
function create_mentee_request($pdo, $mentee_id, $mentor_id, $message = '') {
    $errors = [];
    
    // Validate inputs
    if ($mentee_id == $mentor_id) {
        $errors[] = 'You cannot send a request to yourself';
    }
    
    // Check if mentee can send request
    $mentee_check = can_mentee_send_request($pdo, $mentee_id);
    if (!$mentee_check['can_request']) {
        $errors[] = $mentee_check['message'];
    }
    
    // Check if mentor can accept
    $mentor_check = can_mentor_accept_mentee($pdo, $mentor_id);
    if (!$mentor_check['can_accept']) {
        $errors[] = $mentor_check['message'];
    }
    
    // Check if request already exists
    $exists_check = request_already_exists($pdo, $mentee_id, $mentor_id);
    if ($exists_check['already_exists']) {
        $errors[] = 'You have already sent a request to this mentor';
    }
    
    // Check if domains are different
    $domain_check = are_different_domains($pdo, $mentee_id, $mentor_id);
    if (!$domain_check['different_domain']) {
        $errors[] = $domain_check['message'];
    }
    
    // If there are errors, return them
    if (!empty($errors)) {
        return [
            'success' => false,
            'message' => implode('. ', $errors),
            'request_id' => null,
            'errors' => $errors
        ];
    }
    
    try {
        // Create the request
        $stmt = $pdo->prepare('
            INSERT INTO mentor_mentee_requests 
            (mentee_id, mentor_id, status, message, mentee_domain_id, mentor_domain_id)
            SELECT 
                ?,
                ?,
                "pending",
                ?,
                mp.domain_id,
                mr.domain_id
            FROM mentee_profiles mp
            JOIN mentor_profiles mr ON mr.user_id = ?
            WHERE mp.user_id = ?
        ');
        
        $stmt->execute([$mentee_id, $mentor_id, $message, $mentor_id, $mentee_id]);
        
        // Increment mentee request count
        $stmt = $pdo->prepare('
            UPDATE mentee_profiles 
            SET request_count = request_count + 1
            WHERE user_id = ?
        ');
        $stmt->execute([$mentee_id]);
        
        $request_id = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Request sent successfully',
            'request_id' => $request_id,
            'errors' => []
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in create_mentee_request: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating request: ' . $e->getMessage(),
            'request_id' => null,
            'errors' => ['Database error occurred']
        ];
    }
}

/**
 * Accept a mentor-mentee request
 * 
 * @param PDO $pdo Database connection
 * @param int $request_id Request ID to accept
 * @param int $mentor_id User ID of mentor (for authorization)
 * @return array ['success' => bool, 'message' => string]
 */
function accept_mentee_request($pdo, $request_id, $mentor_id) {
    try {
        // Get request details
        $stmt = $pdo->prepare('
            SELECT id, mentee_id, mentor_id, status
            FROM mentor_mentee_requests
            WHERE id = ? AND mentor_id = ?
        ');
        $stmt->execute([$request_id, $mentor_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Request not found or unauthorized'
            ];
        }
        
        if ($request['status'] !== 'pending') {
            return [
                'success' => false,
                'message' => 'This request has already been ' . $request['status']
            ];
        }
        
        // Check if mentor still has capacity
        $capacity_check = can_mentor_accept_mentee($pdo, $mentor_id);
        if (!$capacity_check['can_accept']) {
            return [
                'success' => false,
                'message' => $capacity_check['message']
            ];
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update request status
            $stmt = $pdo->prepare('
                UPDATE mentor_mentee_requests
                SET status = "accepted", responded_at = NOW()
                WHERE id = ?
            ');
            $stmt->execute([$request_id]);
            
            // Create connection
            $stmt = $pdo->prepare('
                INSERT INTO mentor_mentee_connections
                (request_id, mentee_id, mentor_id, status)
                VALUES (?, ?, ?, "active")
            ');
            $stmt->execute([$request_id, $request['mentee_id'], $mentor_id]);
            
            // Increment mentor's current mentees count
            $stmt = $pdo->prepare('
                UPDATE mentor_profiles
                SET current_mentees = current_mentees + 1
                WHERE user_id = ?
            ');
            $stmt->execute([$mentor_id]);
            
            // Commit transaction
            $pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Request accepted successfully'
            ];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        error_log('Database error in accept_mentee_request: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error accepting request: ' . $e->getMessage()
        ];
    }
}

/**
 * Reject a mentor-mentee request
 * 
 * @param PDO $pdo Database connection
 * @param int $request_id Request ID to reject
 * @param int $mentor_id User ID of mentor (for authorization)
 * @return array ['success' => bool, 'message' => string]
 */
function reject_mentee_request($pdo, $request_id, $mentor_id) {
    try {
        // Get request details
        $stmt = $pdo->prepare('
            SELECT id, mentee_id, mentor_id, status
            FROM mentor_mentee_requests
            WHERE id = ? AND mentor_id = ?
        ');
        $stmt->execute([$request_id, $mentor_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Request not found or unauthorized'
            ];
        }
        
        if ($request['status'] !== 'pending') {
            return [
                'success' => false,
                'message' => 'This request has already been ' . $request['status']
            ];
        }
        
        // Update request status
        $stmt = $pdo->prepare('
            UPDATE mentor_mentee_requests
            SET status = "rejected", responded_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$request_id]);
        
        // Decrement mentee request count
        $stmt = $pdo->prepare('
            UPDATE mentee_profiles
            SET request_count = request_count - 1
            WHERE user_id = ?
        ');
        $stmt->execute([$request['mentee_id']]);
        
        return [
            'success' => true,
            'message' => 'Request rejected successfully'
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in reject_mentee_request: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error rejecting request: ' . $e->getMessage()
        ];
    }
}

// ============================================================================
// QUERY & RETRIEVAL FUNCTIONS
// ============================================================================

/**
 * Get all pending requests for a mentor
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id User ID of mentor
 * @return array Array of pending requests
 */
function get_mentor_pending_requests($pdo, $mentor_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                mmr.id,
                mmr.mentee_id,
                mmr.status,
                mmr.message,
                mmr.created_at,
                u.username,
                u.email,
                mp.bio,
                d.name as domain_name
            FROM mentor_mentee_requests mmr
            JOIN users u ON mmr.mentee_id = u.id
            LEFT JOIN mentee_profiles mp ON mp.user_id = u.id
            LEFT JOIN domains d ON d.id = mp.domain_id
            WHERE mmr.mentor_id = ? AND mmr.status = "pending"
            ORDER BY mmr.created_at DESC
        ');
        $stmt->execute([$mentor_id]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentor_pending_requests: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get accepted mentees for a mentor
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id User ID of mentor
 * @return array Array of accepted mentees
 */
function get_mentor_accepted_mentees($pdo, $mentor_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                mmc.id as connection_id,
                mmc.mentee_id,
                mmc.status,
                mmc.started_at,
                u.username,
                u.email,
                mp.bio,
                mp.learning_goals,
                d.name as domain_name
            FROM mentor_mentee_connections mmc
            JOIN users u ON mmc.mentee_id = u.id
            LEFT JOIN mentee_profiles mp ON mp.user_id = u.id
            LEFT JOIN domains d ON d.id = mp.domain_id
            WHERE mmc.mentor_id = ? AND mmc.status = "active"
            ORDER BY mmc.started_at DESC
        ');
        $stmt->execute([$mentor_id]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentor_accepted_mentees: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all sent requests for a mentee
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id User ID of mentee
 * @return array Array of sent requests
 */
function get_mentee_sent_requests($pdo, $mentee_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                mmr.id,
                mmr.mentor_id,
                mmr.status,
                mmr.message,
                mmr.created_at,
                mmr.responded_at,
                u.username,
                u.email,
                mr.expertise,
                mr.bio,
                mr.verified,
                mr.rating,
                d.name as domain_name
            FROM mentor_mentee_requests mmr
            JOIN users u ON mmr.mentor_id = u.id
            LEFT JOIN mentor_profiles mr ON mr.user_id = u.id
            LEFT JOIN domains d ON d.id = mr.domain_id
            WHERE mmr.mentee_id = ?
            ORDER BY mmr.created_at DESC
        ');
        $stmt->execute([$mentee_id]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentee_sent_requests: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get current mentor for a mentee
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id User ID of mentee
 * @return array|false Mentor information or false
 */
function get_mentee_current_mentor($pdo, $mentee_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                mmc.id as connection_id,
                mmc.mentor_id,
                mmc.started_at,
                u.username,
                u.email,
                mr.bio,
                mr.expertise,
                mr.verified,
                mr.rating,
                d.name as domain_name
            FROM mentor_mentee_connections mmc
            JOIN users u ON mmc.mentor_id = u.id
            LEFT JOIN mentor_profiles mr ON mr.user_id = u.id
            LEFT JOIN domains d ON d.id = mr.domain_id
            WHERE mmc.mentee_id = ? AND mmc.status = "active"
            LIMIT 1
        ');
        $stmt->execute([$mentee_id]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentee_current_mentor: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get available mentors in different domain for a mentee
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id User ID of mentee
 * @return array Array of available mentors
 */
function get_available_mentors_for_mentee($pdo, $mentee_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                mr.id as profile_id,
                mr.user_id,
                mr.expertise,
                mr.bio,
                mr.availability,
                mr.verified,
                mr.rating,
                mr.total_ratings,
                mr.current_mentees,
                mr.max_mentees,
                d.id as domain_id,
                d.name as domain_name,
                u.username,
                u.email,
                (mr.current_mentees >= mr.max_mentees) as is_full
            FROM mentor_profiles mr
            JOIN users u ON mr.user_id = u.id
            JOIN domains d ON mr.domain_id = d.id
            WHERE mr.domain_id NOT IN (
                SELECT mp.domain_id 
                FROM mentee_profiles mp 
                WHERE mp.user_id = ?
            )
            AND mr.user_id NOT IN (
                SELECT mmr.mentor_id
                FROM mentor_mentee_requests mmr
                WHERE mmr.mentee_id = ?
            )
            ORDER BY mr.verified DESC, mr.rating DESC, mr.current_mentees ASC
        ');
        $stmt->execute([$mentee_id, $mentee_id]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_available_mentors_for_mentee: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get request summary for mentee
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id User ID of mentee
 * @return array Summary with counts
 */
function get_mentee_request_summary($pdo, $mentee_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                mp.request_count,
                mp.max_requests,
                (SELECT COUNT(*) FROM mentor_mentee_requests 
                 WHERE mentee_id = ? AND status = "pending") as pending_count,
                (SELECT COUNT(*) FROM mentor_mentee_requests 
                 WHERE mentee_id = ? AND status = "accepted") as accepted_count,
                (SELECT COUNT(*) FROM mentor_mentee_requests 
                 WHERE mentee_id = ? AND status = "rejected") as rejected_count
            FROM mentee_profiles mp
            WHERE mp.user_id = ?
        ');
        $stmt->execute([$mentee_id, $mentee_id, $mentee_id, $mentee_id]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentee_request_summary: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get request summary for mentor
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id User ID of mentor
 * @return array Summary with counts
 */
function get_mentor_request_summary($pdo, $mentor_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                mp.current_mentees,
                mp.max_mentees,
                (SELECT COUNT(*) FROM mentor_mentee_requests 
                 WHERE mentor_id = ? AND status = "pending") as pending_count,
                (SELECT COUNT(*) FROM mentor_mentee_connections 
                 WHERE mentor_id = ? AND status = "active") as active_mentees,
                (SELECT COUNT(*) FROM mentor_mentee_requests 
                 WHERE mentor_id = ? AND status = "accepted") as total_accepted
            FROM mentor_profiles mp
            WHERE mp.user_id = ?
        ');
        $stmt->execute([$mentor_id, $mentor_id, $mentor_id, $mentor_id]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentor_request_summary: ' . $e->getMessage());
        return [];
    }
}

// ============================================================================
// PHASE 3: SESSION MANAGEMENT FUNCTIONS
// ============================================================================

/**
 * Check if relationship is still active and locked
 * 
 * @param PDO $pdo Database connection
 * @param int $connection_id Connection ID
 * @return array ['is_active' => bool, 'message' => string, 'days_remaining' => int]
 */
function is_connection_active($pdo, $connection_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                c.connection_id,
                c.is_locked,
                c.start_date,
                c.end_date,
                c.status,
                DATEDIFF(c.end_date, CURRENT_TIMESTAMP) as days_remaining
            FROM mentor_mentee_connections c
            WHERE c.connection_id = ?
        ');
        $stmt->execute([$connection_id]);
        $connection = $stmt->fetch();
        
        if (!$connection) {
            return [
                'is_active' => false,
                'message' => 'Connection not found',
                'days_remaining' => 0
            ];
        }
        
        if (!$connection['is_locked']) {
            return [
                'is_active' => false,
                'message' => 'Relationship has been unlocked',
                'days_remaining' => 0
            ];
        }
        
        if ($connection['status'] !== 'active') {
            return [
                'is_active' => false,
                'message' => 'Relationship is no longer active',
                'days_remaining' => 0
            ];
        }
        
        if ($connection['days_remaining'] < 0) {
            return [
                'is_active' => false,
                'message' => 'Relationship duration has expired',
                'days_remaining' => 0
            ];
        }
        
        return [
            'is_active' => true,
            'message' => 'Relationship is active',
            'days_remaining' => (int)$connection['days_remaining']
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in is_connection_active: ' . $e->getMessage());
        return [
            'is_active' => false,
            'message' => 'Database error checking connection status',
            'days_remaining' => 0
        ];
    }
}

/**
 * Check if a session can be scheduled in a specific month
 * Prevents duplicate sessions in the same month (max 1 per month)
 * 
 * @param PDO $pdo Database connection
 * @param int $connection_id Connection ID
 * @param string $year_month Year-month in format YYYY-MM
 * @return array ['can_schedule' => bool, 'message' => string, 'sessions_limit' => int]
 */
function can_schedule_session_in_month($pdo, $connection_id, $year_month) {
    try {
        // Validate year-month format
        if (!preg_match('/^\d{4}-\d{2}$/', $year_month)) {
            return [
                'can_schedule' => false,
                'message' => 'Invalid date format. Use YYYY-MM',
                'sessions_limit' => 0
            ];
        }
        
        // Check if connection is active
        $active = is_connection_active($pdo, $connection_id);
        if (!$active['is_active']) {
            return [
                'can_schedule' => false,
                'message' => 'Cannot schedule: ' . $active['message'],
                'sessions_limit' => 0
            ];
        }
        
        // Get connection details
        $stmt = $pdo->prepare('
            SELECT 
                c.connection_id,
                c.sessions_scheduled,
                c.sessions_completed,
                (c.sessions_scheduled + c.sessions_completed) as total_sessions
            FROM mentor_mentee_connections c
            WHERE c.connection_id = ?
        ');
        $stmt->execute([$connection_id]);
        $connection = $stmt->fetch();
        
        if (!$connection) {
            return [
                'can_schedule' => false,
                'message' => 'Connection not found',
                'sessions_limit' => 0
            ];
        }
        
        // Check if already 6 sessions scheduled/completed
        if ($connection['total_sessions'] >= 6) {
            return [
                'can_schedule' => false,
                'message' => 'Maximum number of sessions (6) already scheduled or completed',
                'sessions_limit' => 6
            ];
        }
        
        // Check if a session already exists in this month
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as session_count
            FROM mentor_mentee_sessions
            WHERE connection_id = ? AND session_month = ? AND status IN ("scheduled", "completed")
        ');
        $stmt->execute([$connection_id, $year_month]);
        $result = $stmt->fetch();
        
        if ($result['session_count'] > 0) {
            return [
                'can_schedule' => false,
                'message' => 'A session is already scheduled for ' . $year_month . '. Only 1 session per month allowed',
                'sessions_limit' => 1
            ];
        }
        
        return [
            'can_schedule' => true,
            'message' => 'Can schedule session for ' . $year_month,
            'sessions_limit' => (6 - $connection['total_sessions'])
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in can_schedule_session_in_month: ' . $e->getMessage());
        return [
            'can_schedule' => false,
            'message' => 'Database error checking session availability',
            'sessions_limit' => 0
        ];
    }
}

/**
 * Schedule a new session
 * 
 * @param PDO $pdo Database connection
 * @param int $connection_id Connection ID
 * @param int $mentor_id Mentor user ID
 * @param int $mentee_id Mentee user ID
 * @param string $scheduled_date DateTime string (YYYY-MM-DD HH:MM:SS)
 * @param string $notes Optional session notes
 * @return array Success/error response
 */
function schedule_session($pdo, $connection_id, $mentor_id, $mentee_id, $scheduled_date, $notes = '') {
    try {
        // Extract year-month from scheduled_date
        $session_month = date('Y-m', strtotime($scheduled_date));
        
        // Check if can schedule in this month
        $can_schedule = can_schedule_session_in_month($pdo, $connection_id, $session_month);
        if (!$can_schedule['can_schedule']) {
            return [
                'success' => false,
                'message' => $can_schedule['message'],
                'errors' => [$can_schedule['message']],
                'data' => null
            ];
        }
        
        // Verify connection belongs to this mentor and mentee
        $stmt = $pdo->prepare('
            SELECT connection_id FROM mentor_mentee_connections
            WHERE connection_id = ? AND mentor_id = ? AND mentee_id = ?
        ');
        $stmt->execute([$connection_id, $mentor_id, $mentee_id]);
        if (!$stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Connection verification failed',
                'errors' => ['Unauthorized: Connection does not belong to this mentor-mentee pair'],
                'data' => null
            ];
        }
        
        // Insert session
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                INSERT INTO mentor_mentee_sessions 
                (connection_id, mentor_id, mentee_id, scheduled_date, session_month, status, notes)
                VALUES (?, ?, ?, ?, ?, "scheduled", ?)
            ');
            $stmt->execute([$connection_id, $mentor_id, $mentee_id, $scheduled_date, $session_month, $notes]);
            
            $session_id = $pdo->lastInsertId();
            
            $pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Session scheduled successfully',
                'errors' => [],
                'data' => [
                    'session_id' => $session_id,
                    'connection_id' => $connection_id,
                    'scheduled_date' => $scheduled_date,
                    'session_month' => $session_month
                ]
            ];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        error_log('Database error in schedule_session: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to schedule session',
            'errors' => ['Database error: ' . $e->getMessage()],
            'data' => null
        ];
    }
}

/**
 * Get all sessions for a connection
 * 
 * @param PDO $pdo Database connection
 * @param int $connection_id Connection ID
 * @return array Array of sessions
 */
function get_sessions_for_connection($pdo, $connection_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                s.session_id,
                s.connection_id,
                s.mentor_id,
                s.mentee_id,
                s.scheduled_date,
                s.session_month,
                s.duration_minutes,
                s.status,
                s.notes,
                s.completed_at,
                s.created_at,
                u_mentor.username as mentor_name,
                u_mentee.username as mentee_name
            FROM mentor_mentee_sessions s
            JOIN users u_mentor ON s.mentor_id = u_mentor.user_id
            JOIN users u_mentee ON s.mentee_id = u_mentee.user_id
            WHERE s.connection_id = ?
            ORDER BY s.scheduled_date ASC
        ');
        $stmt->execute([$connection_id]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_sessions_for_connection: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get available months for scheduling (within the 6-month lock period)
 * 
 * @param PDO $pdo Database connection
 * @param int $connection_id Connection ID
 * @return array ['available_months' => [], 'used_months' => [], 'message' => string]
 */
function get_available_session_months($pdo, $connection_id) {
    try {
        // Get connection start and end dates
        $stmt = $pdo->prepare('
            SELECT c.start_date, c.end_date
            FROM mentor_mentee_connections c
            WHERE c.connection_id = ?
        ');
        $stmt->execute([$connection_id]);
        $connection = $stmt->fetch();
        
        if (!$connection) {
            return [
                'available_months' => [],
                'used_months' => [],
                'message' => 'Connection not found'
            ];
        }
        
        // Get all months between start and end date
        $start = new DateTime($connection['start_date']);
        $end = new DateTime($connection['end_date']);
        $all_months = [];
        
        while ($start <= $end) {
            $all_months[] = $start->format('Y-m');
            $start->modify('+1 month');
        }
        
        // Get used months
        $stmt = $pdo->prepare('
            SELECT DISTINCT session_month
            FROM mentor_mentee_sessions
            WHERE connection_id = ? AND status IN ("scheduled", "completed")
            ORDER BY session_month ASC
        ');
        $stmt->execute([$connection_id]);
        $used_months = array_column($stmt->fetchAll(), 'session_month');
        
        // Calculate available months
        $available_months = array_diff($all_months, $used_months);
        
        return [
            'available_months' => array_values($available_months),
            'used_months' => $used_months,
            'message' => 'Months retrieved successfully'
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in get_available_session_months: ' . $e->getMessage());
        return [
            'available_months' => [],
            'used_months' => [],
            'message' => 'Database error retrieving months'
        ];
    }
}

/**
 * Complete/Mark a session as done
 * 
 * @param PDO $pdo Database connection
 * @param int $session_id Session ID
 * @param int $mentor_id Mentor user ID (for authorization)
 * @return array Success/error response
 */
function complete_session($pdo, $session_id, $mentor_id) {
    try {
        // Verify session exists and belongs to mentor
        $stmt = $pdo->prepare('
            SELECT session_id, status FROM mentor_mentee_sessions
            WHERE session_id = ? AND mentor_id = ?
        ');
        $stmt->execute([$session_id, $mentor_id]);
        $session = $stmt->fetch();
        
        if (!$session) {
            return [
                'success' => false,
                'message' => 'Session not found or unauthorized',
                'errors' => ['Session not found'],
                'data' => null
            ];
        }
        
        if ($session['status'] === 'completed') {
            return [
                'success' => false,
                'message' => 'Session is already marked as completed',
                'errors' => ['Session already completed'],
                'data' => null
            ];
        }
        
        // Update session status
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                UPDATE mentor_mentee_sessions
                SET status = "completed", completed_at = CURRENT_TIMESTAMP
                WHERE session_id = ?
            ');
            $stmt->execute([$session_id]);
            
            $pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Session marked as completed',
                'errors' => [],
                'data' => ['session_id' => $session_id]
            ];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        error_log('Database error in complete_session: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to complete session',
            'errors' => ['Database error: ' . $e->getMessage()],
            'data' => null
        ];
    }
}

/**
 * Get mentor's available time slots
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID
 * @return array Array of availability slots
 */
function get_mentor_availability($pdo, $mentor_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                availability_id,
                mentor_id,
                day_of_week,
                start_time,
                end_time,
                is_active
            FROM mentor_session_availability
            WHERE mentor_id = ? AND is_active = TRUE
            ORDER BY 
                FIELD(day_of_week, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"),
                start_time ASC
        ');
        $stmt->execute([$mentor_id]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentor_availability: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add availability slot for mentor
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID
 * @param string $day_of_week Day name (Monday-Sunday)
 * @param string $start_time Start time HH:MM:SS
 * @param string $end_time End time HH:MM:SS
 * @return array Success/error response
 */
function add_mentor_availability($pdo, $mentor_id, $day_of_week, $start_time, $end_time) {
    try {
        // Validate day of week
        $valid_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        if (!in_array($day_of_week, $valid_days)) {
            return [
                'success' => false,
                'message' => 'Invalid day of week',
                'errors' => ['Day must be Monday-Sunday'],
                'data' => null
            ];
        }
        
        // Validate times
        if (strtotime($start_time) >= strtotime($end_time)) {
            return [
                'success' => false,
                'message' => 'Start time must be before end time',
                'errors' => ['Invalid time range'],
                'data' => null
            ];
        }
        
        // Insert availability
        $stmt = $pdo->prepare('
            INSERT INTO mentor_session_availability 
            (mentor_id, day_of_week, start_time, end_time, is_active)
            VALUES (?, ?, ?, ?, TRUE)
        ');
        $stmt->execute([$mentor_id, $day_of_week, $start_time, $end_time]);
        
        return [
            'success' => true,
            'message' => 'Availability slot added successfully',
            'errors' => [],
            'data' => [
                'availability_id' => $pdo->lastInsertId(),
                'mentor_id' => $mentor_id,
                'day_of_week' => $day_of_week,
                'start_time' => $start_time,
                'end_time' => $end_time
            ]
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in add_mentor_availability: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to add availability slot',
            'errors' => ['Database error: ' . $e->getMessage()],
            'data' => null
        ];
    }
}

/**
 * Get relationship status summary
 * 
 * @param PDO $pdo Database connection
 * @param int $connection_id Connection ID
 * @return array Relationship details with session counters
 */
function get_relationship_summary($pdo, $connection_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                c.connection_id,
                c.mentor_id,
                c.mentee_id,
                c.status,
                c.start_date,
                c.end_date,
                c.is_locked,
                c.sessions_scheduled,
                c.sessions_completed,
                (c.sessions_scheduled + c.sessions_completed) as total_sessions,
                (6 - (c.sessions_scheduled + c.sessions_completed)) as remaining_sessions,
                DATEDIFF(c.end_date, CURRENT_TIMESTAMP) as days_remaining,
                CASE 
                    WHEN CURRENT_TIMESTAMP > c.end_date THEN "expired"
                    WHEN DATEDIFF(c.end_date, CURRENT_TIMESTAMP) <= 7 THEN "expiring_soon"
                    ELSE "active"
                END as lock_status,
                u_mentor.username as mentor_name,
                u_mentee.username as mentee_name
            FROM mentor_mentee_connections c
            JOIN users u_mentor ON c.mentor_id = u_mentor.user_id
            JOIN users u_mentee ON c.mentee_id = u_mentee.user_id
            WHERE c.connection_id = ?
        ');
        $stmt->execute([$connection_id]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log('Database error in get_relationship_summary: ' . $e->getMessage());
        return null;
    }
}

// ============================================================================
// PHASE 4: CALENDAR & AVAILABILITY FUNCTIONS
// ============================================================================

/**
 * Get available calendar slots for mentee to view
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID (optional - filter by mentor)
 * @param string $start_date Start date YYYY-MM-DD
 * @param string $end_date End date YYYY-MM-DD
 * @return array Array of available slots
 */
function get_available_calendar_slots($pdo, $mentor_id = null, $start_date = null, $end_date = null) {
    try {
        // Set default date range if not provided
        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d', strtotime('+60 days'));
        
        $query = 'SELECT * FROM v_available_slots WHERE block_date >= ? AND block_date <= ?';
        $params = [$start_date, $end_date];
        
        if ($mentor_id) {
            $query .= ' AND mentor_id = ?';
            $params[] = $mentor_id;
        }
        
        $query .= ' ORDER BY block_date ASC, start_time ASC';
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_available_calendar_slots: ' . $e->getMessage());
        return [];
    }
}

/**
 * Book an available calendar slot for mentee
 * Creates mentor_mentee_session from the available block
 * 
 * @param PDO $pdo Database connection
 * @param int $connection_id Mentor-mentee connection ID
 * @param int $mentor_id Mentor user ID
 * @param int $mentee_id Mentee user ID
 * @param int $calendar_block_id The calendar block to book
 * @param string $notes Optional session notes
 * @return array Success/error response
 */
function book_calendar_slot($pdo, $connection_id, $mentor_id, $mentee_id, $calendar_block_id, $notes = '') {
    try {
        // Get the calendar block details
        $stmt = $pdo->prepare('
            SELECT b.block_id, b.block_date, b.start_time, b.end_time, b.is_booked
            FROM mentor_calendar_blocks b
            WHERE b.block_id = ? AND b.mentor_id = ?
        ');
        $stmt->execute([$calendar_block_id, $mentor_id]);
        $block = $stmt->fetch();
        
        if (!$block) {
            return [
                'success' => false,
                'message' => 'Calendar block not found',
                'errors' => ['Block does not exist or does not belong to this mentor'],
                'data' => null
            ];
        }
        
        if ($block['is_booked']) {
            return [
                'success' => false,
                'message' => 'This slot has already been booked',
                'errors' => ['Slot is no longer available'],
                'data' => null
            ];
        }
        
        // Create session from this slot
        $scheduled_date = $block['block_date'] . ' ' . $block['start_time'];
        $session_month = date('Y-m', strtotime($scheduled_date));
        
        // Check if can schedule in this month (1 per month rule)
        $stmt2 = $pdo->prepare('
            SELECT COUNT(*) as count FROM mentor_mentee_sessions
            WHERE connection_id = ? AND session_month = ? AND status IN ("scheduled", "completed")
        ');
        $stmt2->execute([$connection_id, $session_month]);
        $result = $stmt2->fetch();
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'A session is already scheduled for ' . $session_month,
                'errors' => ['Only 1 session per month allowed'],
                'data' => null
            ];
        }
        
        // Check relationship is still active
        $active = is_connection_active($pdo, $connection_id);
        if (!$active['is_active']) {
            return [
                'success' => false,
                'message' => 'Cannot book: ' . $active['message'],
                'errors' => [$active['message']],
                'data' => null
            ];
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        try {
            // Create session
            $stmt3 = $pdo->prepare('
                INSERT INTO mentor_mentee_sessions
                (connection_id, mentor_id, mentee_id, scheduled_date, session_month, calendar_block_id, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, "scheduled", ?)
            ');
            $stmt3->execute([$connection_id, $mentor_id, $mentee_id, $scheduled_date, $session_month, $calendar_block_id, $notes]);
            
            $session_id = $pdo->lastInsertId();
            
            // Trigger will automatically mark block as booked
            // But let's do it explicitly too
            $stmt4 = $pdo->prepare('
                UPDATE mentor_calendar_blocks
                SET is_booked = TRUE, booked_by_session_id = ?, booked_at = CURRENT_TIMESTAMP
                WHERE block_id = ?
            ');
            $stmt4->execute([$session_id, $calendar_block_id]);
            
            $pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Session booked successfully',
                'errors' => [],
                'data' => [
                    'session_id' => $session_id,
                    'connection_id' => $connection_id,
                    'calendar_block_id' => $calendar_block_id,
                    'scheduled_date' => $scheduled_date
                ]
            ];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        error_log('Database error in book_calendar_slot: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to book slot',
            'errors' => ['Database error: ' . $e->getMessage()],
            'data' => null
        ];
    }
}

/**
 * Get calendar events for display (sessions + available slots)
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $user_role User role (mentor/mentee)
 * @param string $start_date Start date YYYY-MM-DD
 * @param string $end_date End date YYYY-MM-DD
 * @return array Array of calendar events
 */
function get_calendar_events($pdo, $user_id, $user_role, $start_date = null, $end_date = null) {
    try {
        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d', strtotime('+90 days'));
        
        $query = 'SELECT * FROM v_calendar_events WHERE ';
        $params = [];
        
        if ($user_role === 'mentor') {
            $query .= 'mentor_id = ? AND start_datetime >= ? AND start_datetime <= ?';
            $params = [$user_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        } else {
            // Mentee sees only their sessions
            $query .= 'mentee_id = ? AND event_type = "session" AND start_datetime >= ? AND start_datetime <= ?';
            $params = [$user_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        }
        
        $query .= ' ORDER BY start_datetime ASC';
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_calendar_events: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add a blackout date (mentor unavailable period)
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID
 * @param string $start_date Start datetime YYYY-MM-DD HH:MM:SS
 * @param string $end_date End datetime YYYY-MM-DD HH:MM:SS
 * @param string $reason Reason for unavailability
 * @return array Success/error response
 */
function add_blackout_date($pdo, $mentor_id, $start_date, $end_date, $reason = '') {
    try {
        if (strtotime($start_date) >= strtotime($end_date)) {
            return [
                'success' => false,
                'message' => 'Start date must be before end date',
                'errors' => ['Invalid date range'],
                'data' => null
            ];
        }
        
        $stmt = $pdo->prepare('
            INSERT INTO mentor_blackout_dates
            (mentor_id, start_date, end_date, reason)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$mentor_id, $start_date, $end_date, $reason]);
        
        return [
            'success' => true,
            'message' => 'Blackout date added',
            'errors' => [],
            'data' => [
                'blackout_id' => $pdo->lastInsertId(),
                'mentor_id' => $mentor_id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in add_blackout_date: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to add blackout date',
            'errors' => ['Database error: ' . $e->getMessage()],
            'data' => null
        ];
    }
}

/**
 * Get mentor's blackout dates
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID
 * @return array Array of blackout periods
 */
function get_mentor_blackout_dates($pdo, $mentor_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM mentor_blackout_dates
            WHERE mentor_id = ? AND end_date > CURRENT_TIMESTAMP
            ORDER BY start_date ASC
        ');
        $stmt->execute([$mentor_id]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentor_blackout_dates: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add manual calendar availability block (specific date/time)
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID
 * @param string $block_date Date YYYY-MM-DD
 * @param string $start_time Time HH:MM:SS
 * @param string $end_time Time HH:MM:SS
 * @return array Success/error response
 */
function add_calendar_availability_block($pdo, $mentor_id, $block_date, $start_time, $end_time) {
    try {
        // Validate times
        if (strtotime($start_time) >= strtotime($end_time)) {
            return [
                'success' => false,
                'message' => 'Start time must be before end time',
                'errors' => ['Invalid time range'],
                'data' => null
            ];
        }
        
        // Validate date is in future
        if ($block_date < date('Y-m-d')) {
            return [
                'success' => false,
                'message' => 'Cannot add availability for past dates',
                'errors' => ['Date must be in the future'],
                'data' => null
            ];
        }
        
        $stmt = $pdo->prepare('
            INSERT INTO mentor_calendar_blocks
            (mentor_id, block_date, start_time, end_time, is_booked)
            VALUES (?, ?, ?, ?, FALSE)
        ');
        $stmt->execute([$mentor_id, $block_date, $start_time, $end_time]);
        
        return [
            'success' => true,
            'message' => 'Availability slot added',
            'errors' => [],
            'data' => [
                'block_id' => $pdo->lastInsertId(),
                'mentor_id' => $mentor_id,
                'block_date' => $block_date,
                'start_time' => $start_time,
                'end_time' => $end_time
            ]
        ];
        
    } catch (PDOException $e) {
        // Check if duplicate
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return [
                'success' => false,
                'message' => 'A slot already exists for this date and time',
                'errors' => ['Duplicate availability slot'],
                'data' => null
            ];
        }
        
        error_log('Database error in add_calendar_availability_block: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to add availability slot',
            'errors' => ['Database error: ' . $e->getMessage()],
            'data' => null
        ];
    }
}

/**
 * Get all availability calendar blocks for mentor (booked and unbooked)
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID
 * @param string $start_date Start date YYYY-MM-DD
 * @param string $end_date End date YYYY-MM-DD
 * @return array Array of blocks
 */
function get_mentor_availability_blocks($pdo, $mentor_id, $start_date = null, $end_date = null) {
    try {
        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d', strtotime('+90 days'));
        
        $stmt = $pdo->prepare('
            SELECT b.*, s.session_id, s.notes as session_notes, u.username as booked_by_username
            FROM mentor_calendar_blocks b
            LEFT JOIN mentor_mentee_sessions s ON b.booked_by_session_id = s.session_id
            LEFT JOIN users u ON s.mentee_id = u.user_id
            WHERE b.mentor_id = ? AND b.block_date >= ? AND b.block_date <= ?
            ORDER BY b.block_date ASC, b.start_time ASC
        ');
        $stmt->execute([$mentor_id, $start_date, $end_date]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Database error in get_mentor_availability_blocks: ' . $e->getMessage());
        return [];
    }
}

/**
 * Delete a calendar availability block
 * Only possible if not yet booked
 * 
 * @param PDO $pdo Database connection
 * @param int $block_id Block ID
 * @param int $mentor_id Mentor ID (for authorization)
 * @return array Success/error response
 */
function delete_calendar_block($pdo, $block_id, $mentor_id) {
    try {
        // Verify ownership and get details
        $stmt = $pdo->prepare('SELECT is_booked, mentor_id FROM mentor_calendar_blocks WHERE block_id = ?');
        $stmt->execute([$block_id]);
        $block = $stmt->fetch();
        
        if (!$block) {
            return [
                'success' => false,
                'message' => 'Block not found',
                'errors' => ['Block does not exist'],
                'data' => null
            ];
        }
        
        if ($block['mentor_id'] != $mentor_id) {
            return [
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => ['You do not own this block'],
                'data' => null
            ];
        }
        
        if ($block['is_booked']) {
            return [
                'success' => false,
                'message' => 'Cannot delete booked slot',
                'errors' => ['This slot has been booked and cannot be deleted'],
                'data' => null
            ];
        }
        
        // Delete the block
        $stmt2 = $pdo->prepare('DELETE FROM mentor_calendar_blocks WHERE block_id = ?');
        $stmt2->execute([$block_id]);
        
        return [
            'success' => true,
            'message' => 'Availability slot deleted',
            'errors' => [],
            'data' => ['block_id' => $block_id]
        ];
        
    } catch (PDOException $e) {
        error_log('Database error in delete_calendar_block: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to delete block',
            'errors' => ['Database error: ' . $e->getMessage()],
            'data' => null
        ];
    }
}

// ============================================================================
// FEEDBACK SYSTEM FUNCTIONS (Phase 5)
// ============================================================================

/**
 * Submit feedback for a completed session
 * 
 * @param PDO $pdo Database connection
 * @param int $session_id Session ID
 * @param int $mentee_id Mentee user ID
 * @param int $rating Rating 1-5
 * @param string $comments Optional feedback comments
 * @return array ['success' => bool, 'message' => string, 'data' => array|null]
 */
function submit_session_feedback($pdo, $session_id, $mentee_id, $rating, $comments = '') {
    try {
        // Validate rating
        if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            return [
                'success' => false,
                'message' => 'Invalid rating',
                'errors' => ['Rating must be between 1 and 5'],
                'data' => null
            ];
        }

        // Fetch session to validate it exists and is completed
        $stmt = $pdo->prepare('
            SELECT s.session_id, s.mentee_id, s.status, s.end_date, s.mentor_id,
                   u.full_name as mentor_name
            FROM mentor_mentee_sessions s
            JOIN users u ON s.mentor_id = u.user_id
            WHERE s.session_id = ?
        ');
        $stmt->execute([$session_id]);
        $session = $stmt->fetch();

        if (!$session) {
            return [
                'success' => false,
                'message' => 'Session not found',
                'errors' => ['Invalid session ID'],
                'data' => null
            ];
        }

        // Validate session is completed
        if ($session['status'] !== 'completed') {
            return [
                'success' => false,
                'message' => 'Cannot submit feedback',
                'errors' => ['Feedback can only be submitted for completed sessions'],
                'data' => null
            ];
        }

        // Validate end_date exists
        if (!$session['end_date']) {
            return [
                'success' => false,
                'message' => 'Cannot submit feedback',
                'errors' => ['Session must have completed date'],
                'data' => null
            ];
        }

        // Validate mentee matches
        if ($session['mentee_id'] != $mentee_id) {
            return [
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => ['You did not participate in this session'],
                'data' => null
            ];
        }

        // Check if feedback already exists
        $stmt2 = $pdo->prepare('
            SELECT feedback_id FROM session_feedback 
            WHERE session_id = ? AND mentee_id = ?
        ');
        $stmt2->execute([$session_id, $mentee_id]);
        $existing = $stmt2->fetch();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Feedback already submitted',
                'errors' => ['You have already submitted feedback for this session'],
                'data' => null
            ];
        }

        // Insert feedback
        $stmt3 = $pdo->prepare('
            INSERT INTO session_feedback (session_id, mentee_id, rating, comments)
            VALUES (?, ?, ?, ?)
        ');
        $stmt3->execute([$session_id, $mentee_id, $rating, $comments]);
        $feedback_id = $pdo->lastInsertId();

        return [
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'errors' => [],
            'data' => [
                'feedback_id' => $feedback_id,
                'session_id' => $session_id,
                'mentor_id' => $session['mentor_id'],
                'mentor_name' => $session['mentor_name'],
                'rating' => (int)$rating,
                'comments' => $comments,
                'submitted_at' => date('Y-m-d H:i:s')
            ]
        ];

    } catch (PDOException $e) {
        error_log('Database error in submit_session_feedback: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to submit feedback',
            'errors' => ['Database error: ' . $e->getMessage()],
            'data' => null
        ];
    }
}

/**
 * Get feedback for a specific session
 * 
 * @param PDO $pdo Database connection
 * @param int $session_id Session ID
 * @return array Feedback record or null
 */
function get_session_feedback($pdo, $session_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT f.feedback_id, f.session_id, f.mentee_id, f.rating, f.comments,
                   f.created_at, u.full_name as mentee_name
            FROM session_feedback f
            LEFT JOIN users u ON f.mentee_id = u.user_id
            WHERE f.session_id = ?
        ');
        $stmt->execute([$session_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Database error in get_session_feedback: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get all completed sessions without feedback for a mentee
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id Mentee user ID
 * @return array List of sessions ready for feedback
 */
function get_pending_feedback_sessions($pdo, $mentee_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM v_completed_sessions_ready_for_feedback
            WHERE mentee_id = ?
            ORDER BY days_since_completion ASC
        ');
        $stmt->execute([$mentee_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Database error in get_pending_feedback_sessions: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all sessions with feedback (for admin)
 * 
 * @param PDO $pdo Database connection
 * @param int $limit Limit results
 * @param int $offset Offset
 * @return array Sessions with feedback
 */
function get_all_sessions_with_feedback($pdo, $limit = 50, $offset = 0) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM v_sessions_with_feedback
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Database error in get_all_sessions_with_feedback: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all completed sessions (for admin)
 * 
 * @param PDO $pdo Database connection
 * @param int $limit Limit results
 * @param int $offset Offset
 * @return array Completed sessions
 */
function get_all_completed_sessions_admin($pdo, $limit = 50, $offset = 0) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM v_sessions_with_feedback
            WHERE status = "completed"
            ORDER BY session_created_at DESC
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Database error in get_all_completed_sessions_admin: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get mentor statistics (rating, session count, etc)
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID
 * @return array Mentor statistics
 */
function get_mentor_statistics($pdo, $mentor_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM v_mentor_statistics
            WHERE user_id = ?
        ');
        $stmt->execute([$mentor_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Database error in get_mentor_statistics: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get all mentor statistics (for admin)
 * 
 * @param PDO $pdo Database connection
 * @return array List of mentor statistics
 */
function get_all_mentor_statistics($pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM v_mentor_statistics
            ORDER BY avg_rating DESC, completed_sessions DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Database error in get_all_mentor_statistics: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get mentee statistics (sessions taken, feedback given, etc)
 * 
 * @param PDO $pdo Database connection
 * @param int $mentee_id Mentee user ID
 * @return array Mentee statistics
 */
function get_mentee_statistics($pdo, $mentee_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM v_mentee_statistics
            WHERE user_id = ?
        ');
        $stmt->execute([$mentee_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Database error in get_mentee_statistics: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get all mentee statistics (for admin)
 * 
 * @param PDO $pdo Database connection
 * @return array List of mentee statistics
 */
function get_all_mentee_statistics($pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM v_mentee_statistics
            ORDER BY completed_sessions DESC, joined_date DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Database error in get_all_mentee_statistics: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all active relationships (for admin)
 * 
 * @param PDO $pdo Database connection
 * @return array List of relationships
 */
function get_all_relationships($pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM v_relationship_status
            ORDER BY current_status, start_date DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Database error in get_all_relationships: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get feedback summary (count by rating)
 * 
 * @param PDO $pdo Database connection
 * @return array Feedback statistics
 */
function get_feedback_summary($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM v_feedback_summary');
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Database error in get_feedback_summary: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get feedback for a specific mentor
 * 
 * @param PDO $pdo Database connection
 * @param int $mentor_id Mentor user ID
 * @return array Mentor's feedback
 */
function get_mentor_feedback($pdo, $mentor_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT f.feedback_id, f.session_id, f.rating, f.comments, f.created_at,
                   u.full_name as mentee_name, s.scheduled_date, s.status
            FROM session_feedback f
            JOIN mentor_mentee_sessions s ON f.session_id = s.session_id
            JOIN users u ON f.mentee_id = u.user_id
            WHERE s.mentor_id = ?
            ORDER BY f.created_at DESC
        ');
        $stmt->execute([$mentor_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Database error in get_mentor_feedback: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get total count of sessions for pagination
 * 
 * @param PDO $pdo Database connection
 * @return int Count of all sessions
 */
function get_total_sessions_count($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM mentor_mentee_sessions');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Database error in get_total_sessions_count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get total count of users by role
 * 
 * @param PDO $pdo Database connection
 * @param string $role User role (mentor/mentee/admin)
 * @return int Count of users
 */
function get_users_count_by_role($pdo, $role) {
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM users WHERE user_type = ?
        ');
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Database error in get_users_count_by_role: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get dashboard statistics for admin
 * 
 * @param PDO $pdo Database connection
 * @return array Dashboard statistics
 */
function get_admin_dashboard_stats($pdo) {

    $stats = [];

    try {

        // Total mentors
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'mentor'");
        $stmt->execute();
        $stats['total_mentors'] = (int)$stmt->fetchColumn();

        // Total mentees
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'mentee'");
        $stmt->execute();
        $stats['total_mentees'] = (int)$stmt->fetchColumn();

        // Total admins
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $stats['total_admins'] = (int)$stmt->fetchColumn();

        // Total sessions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions");
        $stmt->execute();
        $stats['total_sessions'] = (int)$stmt->fetchColumn();

        // Total relationships
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mentor_mentee_connections");
        $stmt->execute();
        $stats['total_relationships'] = (int)$stmt->fetchColumn();

        // Active relationships
        // $stmt = $pdo->prepare("
        //     SELECT COUNT(*) 
        //     FROM mentor_mentee_connections 
        //     WHERE status = 'active' AND is_locked = 0
        // ");
        $stmt->execute();
        $stats['active_relationships'] = (int)$stmt->fetchColumn();

        // Total feedback
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback");
        $stmt->execute();
        $stats['total_feedback'] = (int)$stmt->fetchColumn();

        // Average rating
        $stmt = $pdo->prepare("SELECT AVG(rating) FROM feedback");
        $stmt->execute();
        $avg = $stmt->fetchColumn();
        $stats['avg_rating'] = $avg ? round($avg, 2) : 0;

        return $stats;

    } catch (PDOException $e) {

        die("Stats Query Failed: " . $e->getMessage());
    }
}

//  function get_admin_dashboard_stats($pdo) {
//      try {
//          $stats = [
//              'total_mentors' => get_users_count_by_role($pdo, 'mentor'),
//              'total_mentees' => get_users_count_by_role($pdo, 'mentee'),
//              'total_admins' => get_users_count_by_role($pdo, 'admin'),
//              'total_sessions' => get_total_sessions_count($pdo),
//             'total_relationships' => 0,
//              'active_relationships' => 0,
//             'total_feedback' => 0,
//             'avg_rating' => 0
//          ];
        


//         // Get relationship counts
//        $stmt = $pdo->prepare('SELECT COUNT(*) FROM mentor_mentee_connections');
//         $stmt->execute();
//         $stats['total_relationships'] = (int)$stmt->fetchColumn();

//         $stmt = $pdo->prepare('
//             SELECT COUNT(*) FROM mentor_mentee_connections 
//             WHERE status = "active" AND is_locked = 0
//        ');
//          $stmt->execute();
//          $stats['active_relationships'] = (int)$stmt->fetchColumn();

//     //     // Get feedback counts
//     //    $stmt = $pdo->prepare('SELECT COUNT(*) FROM session_feedback');
//     //    $stmt->execute();
//     //     $stats['total_feedback'] = (int)$stmt->fetchColumn();

//     //    $stmt = $pdo->prepare('SELECT AVG(rating) FROM session_feedback');
//     //     $stmt->execute();
//     //      $avg = $stmt->fetchColumn();
//     //      $stats['avg_rating'] = $avg ? round($avg, 2) : 0;
//          // Total relationships (all statuses)
// //  $stmt = $pdo->query("SELECT COUNT(*) as total FROM relationships");
// //  $stats['total_relationships'] = $stmt->fetch()['total'] ?? 0;


//          return $stats;
//      } catch (PDOException $e) {
//          error_log('Database error in get_admin_dashboard_stats: ' . $e->getMessage());
//          return [];
//      }
    
// }




