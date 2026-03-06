# Phase 3: Session Management & Duration Lock
**Version 1.0** | Released February 2026

## Overview

Phase 3 implements a comprehensive session scheduling system with mentor-mentee relationship duration tracking. When a mentor accepts a mentee's request, a 6-month mentorship relationship is created with automatic session limits and scheduling rules.

## Key Features

### 1. Relationship Duration Lock (6 Months)
- **Automatic Duration**: When a mentor accepts a request, the relationship is locked for exactly 6 months
- **Start Date**: Set to current timestamp when connection is created
- **End Date**: Automatically calculated as `start_date + 6 months`
- **Lock Status**: Relationship is `is_locked = TRUE` after creation
- **Expiry Handling**: Mentors and mentees can see days remaining
- **Warning System**: Alerts when relationship will expire within 7 days

### 2. Session Scheduling Rules

#### Maximum Sessions: 6 Total
- Each mentor-mentee pair can schedule exactly **6 sessions** during the 6-month period
- Includes both scheduled and completed sessions
- Cannot exceed this limit

#### One Session Per Month
- Only **1 session per month** can be scheduled within a relationship
- Prevents duplicate sessions in the same calendar month
- Uses `session_month` field in format `YYYY-MM` to track
- Database constraint: `UNIQUE KEY unique_connection_month (connection_id, session_month)`

#### Duration Constraints
- Sessions must fall within the 6-month relationship period
- Start date: relationship creation
- End date: 6 months from start
- Cannot schedule sessions outside this window

### 3. Session Status
Sessions support two lifecycle states:
- **scheduled**: Initial status when session is created
- **completed**: Set when mentor marks session as complete

## Database Schema

### Modified Tables

#### mentor_mentee_connections (Extended)
```sql
-- Added Columns:
- start_date DATETIME          -- Relationship start (auto-set to NOW())
- end_date DATETIME            -- Relationship end (auto-set to NOW() + 6 MONTHS)
- is_locked BOOLEAN            -- Lock status (default TRUE)
- sessions_scheduled INT       -- Count of scheduled sessions (updated by trigger)
- sessions_completed INT       -- Count of completed sessions (updated by trigger)
```

### New Tables

#### mentor_mentee_sessions
```sql
CREATE TABLE mentor_mentee_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    connection_id INT NOT NULL FK,           -- Links to relationship
    mentor_id INT NOT NULL FK,
    mentee_id INT NOT NULL FK,
    scheduled_date DATETIME NOT NULL,        -- When session occurs
    session_month VARCHAR(7) NOT NULL,       -- YYYY-MM format for uniqueness
    duration_minutes INT DEFAULT 60,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,                              -- Session agenda/notes
    completed_at DATETIME,                   -- When marked complete
    created_at TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    UNIQUE (connection_id, session_month)    -- One session per month
);
```

#### mentor_session_availability
```sql
CREATE TABLE mentor_session_availability (
    availability_id INT PRIMARY KEY AUTO_INCREMENT,
    mentor_id INT NOT NULL FK,
    day_of_week ENUM('Monday', 'Tuesday', ..., 'Sunday'),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### New Views

#### v_active_connections
Shows active relationships with time remaining and mentor/mentee info
```sql
SELECT connection_id, mentee_id, mentor_id, start_date, end_date,
       days_remaining, lock_status (active/expiring_soon/expired)
FROM mentor_mentee_connections...
```

#### v_session_statistics
Shows session counts per relationship
```sql
SELECT connection_id, completed_sessions, scheduled_sessions,
       total_sessions, remaining_sessions, used_months
FROM mentor_mentee_connections...
```

### Database Triggers

#### tr_set_connection_end_date
Automatically sets `end_date` when connection is created
- Executes: `BEFORE INSERT ON mentor_mentee_connections`
- Sets: `end_date = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 6 MONTH)`

#### tr_update_session_count & tr_update_session_count_completion
Automatically updates `sessions_scheduled` and `sessions_completed` counters
- Executes: `AFTER INSERT/UPDATE ON mentor_mentee_sessions`
- Updates: `sessions_scheduled` and `sessions_completed` in connection record

## Function Reference

### Session Validation Functions

#### is_connection_active($pdo, $connection_id)
Checks if a relationship is active and within the 6-month period
```php
$result = is_connection_active($pdo, 123);
// Returns: ['is_active' => bool, 'message' => string, 'days_remaining' => int]
```

**Checks**:
- Connection exists
- `is_locked = TRUE`
- `status = 'active'`
- Current date ≤ end_date

**Returns**:
- `is_active`: TRUE if all checks pass
- `days_remaining`: Integer days until expiry (0 if expired)

#### can_schedule_session_in_month($pdo, $connection_id, $year_month)
Validates if a session can be scheduled in a specific month
```php
$result = can_schedule_session_in_month($pdo, 123, '2026-03');
// Returns: ['can_schedule' => bool, 'message' => string, 'sessions_limit' => int]
```

**Validates**:
- Connection is active
- Total sessions < 6
- No existing session in this month
- Date format is correct (YYYY-MM)

**Returns**:
- `can_schedule`: TRUE if session can be scheduled
- `sessions_limit`: Remaining sessions allowed
- `message`: Reason if cannot schedule

### Session Management Functions

#### schedule_session($pdo, $connection_id, $mentor_id, $mentee_id, $scheduled_date, $notes)
Creates a new session with validation
```php
$result = schedule_session($pdo, 123, 1, 2, '2026-03-15 14:00:00', 'Discuss goals');
// Returns: ['success' => bool, 'message' => string, 'errors' => [], 'data' => [...]]
```

**Parameters**:
- `$connection_id`: ID of the mentor-mentee relationship
- `$mentor_id`: Mentor's user ID
- `$mentee_id`: Mentee's user ID
- `$scheduled_date`: DateTime string (YYYY-MM-DD HH:MM:SS)
- `$notes`: Optional session notes/agenda

**Validations**:
- Calls all validation functions
- Verifies connection ownership
- Uses transaction for consistency

**Returns**:
- `session_id` if successful
- Error messages if validation fails

#### complete_session($pdo, $session_id, $mentor_id)
Marks a scheduled session as completed
```php
$result = complete_session($pdo, 456, 1);
// Returns: ['success' => bool, 'message' => string, ...]
```

**Parameters**:
- `$session_id`: ID of session to complete
- `$mentor_id`: Mentor's user ID (for authorization)

**Authorizations**:
- Session must belong to this mentor
- Session must be in 'scheduled' status

**Results**:
- Sets `status = 'completed'`
- Sets `completed_at = CURRENT_TIMESTAMP`
- Triggers update to connection counters

### Query Functions

#### get_sessions_for_connection($pdo, $connection_id)
Returns all sessions for a relationship
```php
$sessions = get_sessions_for_connection($pdo, 123);
// Returns: [
//   ['session_id' => 1, 'scheduled_date' => '2026-03-15 14:00:00', 
//    'status' => 'scheduled', 'notes' => '...', ...]
// ]
```

**Fields Returned**:
- session_id, connection_id, mentor_id, mentee_id
- scheduled_date, session_month, duration_minutes
- status, notes, completed_at
- Created/Updated timestamps
- Mentor and mentee usernames

#### get_available_session_months($pdo, $connection_id)
Returns months available for scheduling within the 6-month period
```php
$months = get_available_session_months($pdo, 123);
// Returns: [
//   'available_months' => ['2026-02', '2026-03', '2026-04', ...],
//   'used_months' => ['2026-01'],
//   'message' => string
// ]
```

**Logic**:
- Gets all months between `start_date` and `end_date`
- Excludes months that already have a session
- Returns remaining available months

#### get_relationship_summary($pdo, $connection_id)
Comprehensive relationship overview with session stats
```php
$summary = get_relationship_summary($pdo, 123);
// Returns: [
//   'total_sessions' => 2, 'remaining_sessions' => 4,
//   'days_remaining' => 45, 'lock_status' => 'active',
//   'mentor_name' => 'John', 'mentee_name' => 'Jane',
//   ...
// ]
```

#### get_mentor_availability($pdo, $mentor_id)
Returns mentor's available time slots
```php
$slots = get_mentor_availability($pdo, 1);
// Returns: [
//   ['availability_id' => 1, 'day_of_week' => 'Monday', 
//    'start_time' => '18:00:00', 'end_time' => '20:00:00'],
//   ...
// ]
```

#### add_mentor_availability($pdo, $mentor_id, $day, $start_time, $end_time)
Adds an available time slot for a mentor
```php
$result = add_mentor_availability($pdo, 1, 'Monday', '18:00:00', '20:00:00');
```

## API Endpoints

### POST /api/schedule_session.php
Creates a new session in a relationship

**Request**:
```json
{
    "connection_id": 123,
    "scheduled_date": "2026-03-15 14:00:00",
    "notes": "Discuss career goals and progress"
}
```

**Response (Success - 201)**:
```json
{
    "success": true,
    "message": "Session scheduled successfully",
    "data": {
        "session_id": 456,
        "connection_id": 123,
        "scheduled_date": "2026-03-15 14:00:00",
        "session_month": "2026-03"
    }
}
```

**Response (Error - 400)**:
```json
{
    "success": false,
    "message": "A session is already scheduled for 2026-03. Only 1 per month allowed",
    "errors": ["..."]
}
```

**Status Codes**:
- 201: Session created successfully
- 400: Validation error (duplicate month, exceeds 6 sessions, date outside window)
- 403: Unauthorized (not part of relationship)
- 404: Connection not found
- 405: Method not allowed (only POST)

### POST /api/complete_session.php
Marks a session as completed (mentor only)

**Request**:
```json
{
    "session_id": 456
}
```

**Response (Success - 200)**:
```json
{
    "success": true,
    "message": "Session marked as completed",
    "data": {
        "session_id": 456
    }
}
```

**Status Codes**:
- 200: Session completed
- 400: Session already completed or invalid
- 403: Unauthorized or wrong role
- 404: Session not found
- 405: Method not allowed (only POST)

### GET /api/get_relationship_status.php?connection_id=123
Retrieves complete relationship status with session info

**Query Parameters**:
- `connection_id`: The relationship ID (required)

**Response (Success - 200)**:
```json
{
    "success": true,
    "data": {
        "relationship": {
            "connection_id": 123,
            "mentor_id": 1,
            "mentee_id": 2,
            "start_date": "2026-02-17 10:00:00",
            "end_date": "2026-08-17 10:00:00",
            "sessions_scheduled": 1,
            "sessions_completed": 0,
            "total_sessions": 1,
            "remaining_sessions": 5,
            "days_remaining": 180,
            "lock_status": "active"
        },
        "available_months": ["2026-03", "2026-04", "2026-05", ...],
        "used_months": ["2026-02"],
        "sessions": [
            {
                "session_id": 456,
                "scheduled_date": "2026-02-20 14:00:00",
                "session_month": "2026-02",
                "status": "scheduled",
                "notes": "..."
            }
        ]
    }
}
```

**Status Codes**:
- 200: Success
- 403: Unauthorized (not part of relationship)
- 404: Relationship not found
- 405: Method not allowed (only GET)

## UI Pages

### Mentor: Manage Sessions (`mentor/manage_sessions.php`)

**Features**:
- View all active mentee relationships
- For each relationship:
  - Mentee name, email, learning goals
  - Relationship lock status (active/expiring_soon/expired)
  - Session statistics: completed, scheduled, remaining, days left
  - Duration info (start and end dates)
  - List of all sessions with status
  - Ability to mark sessions as complete
  - Button to schedule new sessions (if slots available)
- Warning when relationship expiring in ≤ 7 days
- Empty state when no active relationships

**Modal**: Schedule Session
- Date/time picker
- Session notes field
- Client-side and server-side validation
- Success/error messages
- Auto-reload on success

### Mentee: Schedule Sessions (`mentee/schedule_sessions.php`)

**Features**:
- View active mentor connection
- Mentor profile information
- Relationship duration display
- Session statistics: completed, scheduled, remaining, days left
- List of all scheduled/completed sessions
- Button to schedule new session (if slots available)
- Empty state with links to find mentor or check requests

**Modal**: Schedule Session
- Date/time picker
- Session agenda/notes field
- Validation with error display
- Real-time feedback

## Validation Rules Summary

### Session Validation Rules

| Rule | Check | Error Message | Source |
|------|-------|---------------|--------|
| Connection Active | is_locked = TRUE, status = 'active', current_date ≤ end_date | "Cannot schedule: Relationship is no longer active" | is_connection_active() |
| Total Sessions Limit | (scheduled + completed) < 6 | "Maximum number of sessions (6) already scheduled or completed" | can_schedule_session_in_month() |
| One Per Month | No existing session in same YYYY-MM | "A session is already scheduled for YYYY-MM" | can_schedule_session_in_month() |
| Date Format | YYYY-MM format | "Invalid date format. Use YYYY-MM" | can_schedule_session_in_month() |
| Connection Ownership | connection belongs to mentor+mentee | "Connection verification failed" | schedule_session() |
| Authorization | Mentor owns the session | "Session not found or unauthorized" | complete_session() |

## Workflow Example

### Scenario: Mentor-Mentee Session Lifecycle

```
1. Mentee sends request to Mentor
   → Post: /api/send_request.php
   
2. Mentor accepts request
   → Post: /api/accept_request.php
   → Triggers: Relationship created with start_date, end_date = NOW() + 6 months
   
3. Mentor views relationships
   → Page: /mentor/manage_sessions.php
   → Shows: All active mentee relationships with session counters
   
4. Mentor schedules first session
   → Modal form on manage_sessions.php
   → Post: /api/schedule_session.php
   → Validates: Connection active, not exceeding 6, not duplicate month
   → Creates: Session #1 with status='scheduled'
   
5. Both view relationships
   → Mentor: /mentor/manage_sessions.php
   → Mentee: /mentee/schedule_sessions.php
   → Both see: 1 scheduled, 0 completed, 5 remaining
   
6. Session occurs and Mentor completes it
   → Mentor clicks "Mark Complete" button
   → Post: /api/complete_session.php
   → Updates: Session status='completed', completed_at=NOW()
   → Trigger: Updates sessions_completed counter
   
7. Second session scheduled
   → Must be different month (e.g., next month)
   → Same process repeats
   
8. Process continues until...
   → 6 sessions completed/scheduled, OR
   → 6 months expires
```

## Migration from Phase 2

### Database Changes
```bash
1. Run phase3_setup.sql in phpMyAdmin
2. Verify 3 new tables created:
   - mentor_mentee_sessions
   - mentor_session_availability
   - 2 views (v_active_connections, v_session_statistics)
3. Verify columns added to mentor_mentee_connections:
   - start_date, end_date, is_locked, sessions_scheduled, sessions_completed
4. Verify 4 triggers created
```

### Code Integration
- New functions added to `config/requests.php` (Phase 2 functions still work)
- 3 new API endpoints (non-breaking, separate from Phase 2 endpoints)
- 2 new UI pages (separate, non-breaking)
- Updated dashboards with new navigation

### Backward Compatibility
- Phase 2 request system fully operational
- Existing relationships continue to work
- New sessions only apply to relationships created after Phase 3
- No breaking changes to existing API endpoints

## Testing Scenarios

### Test 1: Basic Session Scheduling
```
1. Login as Mentee A
2. Send request to Mentor B (different domain)
3. Login as Mentor B
4. Accept request from Mentee A
5. Verify: relationship created with end_date = NOW() + 6 months
6. Go to Manage Sessions
7. Click "Schedule Session" for Mentee A
8. Schedule session for 2026-03-15 at 14:00
9. Verify: appearance in sessions list
10. Click "Mark Complete"
11. Verify: status changes to 'completed'
```

### Test 2: One Session Per Month Limit
```
1. Follow above steps
2. Try to schedule another session in same month (e.g., 2026-03-20)
3. Verify: Error "A session is already scheduled for 2026-03"
4. Schedule session in different month (e.g., 2026-04-15)
5. Verify: Successfully created
```

### Test 3: 6 Session Maximum
```
1. Schedule sessions in: Feb, Mar, Apr, May, Jun, Jul (6 sessions)
2. Try to schedule 7th session
3. Verify: Error "Maximum number of sessions (6) already scheduled"
```

### Test 4: Relationship Expiry Warning
```
1. Create relationship to expire in 5 days
2. View manage_sessions.php
3. Verify: "expiring_soon" badge
4. Verify: Warning message displayed
5. Verify: Remaining sessions counter accurate
```

### Test 5: Authorization Checks
```
1. Login as Mentee
2. Try to call complete_session API
3. Verify: 403 Forbidden (only mentors can complete)
4. Try to access relationship of different user
5. Verify: 403 Unauthorized
```

## Performance Optimizations

### Indexes
- `idx_connection_id` on session table
- `idx_session_month` on session table for monthly lookups
- `idx_status` on session table for filtering
- Unique constraint on `(connection_id, session_month)` enforces single session per month

### Triggers
- Automatically maintain counters (no manual updates needed)
- Reduce application logic overhead
- Ensure data consistency at database level

### Query Patterns
- Use prepared statements (no SQL injection)
- Join with user table once for names
- Sort by scheduled_date for chronological display

## Security Considerations

### Authorization
- All endpoints verify user role (mentor/mentee)
- Session completion only by mentor
- Connection must belong to requesting user
- Mentor and Mentee must match connection IDs

### Input Validation
- DateTime validation (YYYY-MM-DD HH:MM:SS format)
- Month format validation (YYYY-MM)
- HTML escape output (htmlspecialchars)
- Prepared statements throughout (no SQL injection)

### Data Integrity
- Database triggers maintain consistency
- Transactions for multi-step operations
- Foreign key constraints prevent orphaned records
- UNIQUE constraints prevent duplicates

## Known Limitations & Future Work

### Current Limitations
1. No time slot conflict detection (allows overlapping sessions)
2. Availability tracking exists but not enforced in scheduling
3. No email notifications for scheduled sessions
4. No recurring/recurring session patterns
5. No session cancellation (only completion status)

### Future Enhancements
- Integration with calendar systems (Google Calendar, Outlook)
- Automated email reminders before sessions
- Session recording and notes storage
- Rating/feedback after each session
- Rescheduling within same month
- Session recording links
- Virtual meeting room integration (Zoom, Google Meet)
- Mentor availability calendar blocking
- Time zone handling for international pairs

## Troubleshooting

### Sessions Not Showing
- **Problem**: Scheduled sessions not visible
- **Causes**: 
  - Connection may have expired (check end_date)
  - Connection status is not 'active'
  - Different connection_id used
- **Solution**: Verify connection is active and dates are current

### Cannot Schedule Session
- **Problem**: "Maximum number of sessions" error when only 1-5 exist
- **Causes**:
  - Counting includes cancelled sessions
  - Trigger may have an issue
  - Cache/old connection count
- **Solution**: 
  - Check `sessions_scheduled + sessions_completed` in database
  - Verify triggers are running
  - Clear application cache

### Month Validation Error
- **Problem**: "Invalid date format" error
- **Causes**:
  - Datetime-local input format not converted correctly
  - Server timezone mismatch
- **Solution**:
  - Ensure client-side converts to YYYY-MM-DD HH:MM:SS
  - Check server timezone in php.ini
  - Verify database timezone

## Support & Maintenance

### Current Status
- Phase 3: Complete and tested
- Database Schema: Stable
- API Endpoints: Production ready
- UI Pages: Full responsive design

### Version History
- **v1.0 (2026-02-17)**: Initial release with 6-month duration, 1 session/month, 6 session limit

### Maintenance Notes
- Update trigger definitions if changing session logic
- Monitor `mentor_mentee_sessions` table size (may grow large)
- Consider archiving old sessions after relationship expires
- Review connection expiry policies annually
