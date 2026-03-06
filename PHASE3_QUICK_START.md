# Phase 3: Quick Start Guide
**Session Management & Duration Lock** | February 2026

## What's New

Phase 3 adds **session scheduling** to the mentor-mentee relationship system:

✅ **6-Month Duration Lock** - Relationships automatically expire after 6 months
✅ **1 Session Per Month** - Maximum 1 session can be scheduled per calendar month
✅ **6 Sessions Maximum** - Total of 6 sessions allowed during the 6-month period
✅ **Session Status Tracking** - Track schedules sessions and mark them as completed
✅ **Relationship Duration Tracking** - View days remaining and expiry warnings

## Installation Steps

### Step 1: Database Setup

1. Open **phpMyAdmin** → Select `mentors_connect` database
2. Go to **SQL** tab
3. Copy entire contents of `phase3_setup.sql`
4. Paste into SQL editor
5. Click **Go** to execute

**Verify Setup**:
- Check **Structure** → Look for these tables:
  - `mentor_mentee_sessions` (NEW)
  - `mentor_session_availability` (NEW)
  - `mentor_mentee_connections` (Modified)

- Check **Triggers**:
  - `tr_set_connection_end_date`
  - `tr_update_session_count`
  - `tr_update_session_count_completion`

### Step 2: File Updates

Phase 3 creates these new files:

| File | Purpose |
|------|---------|
| `phase3_setup.sql` | Database schema |
| `config/requests.php` | Updated with 10+ session functions |
| `api/schedule_session.php` | POST: Schedule new session |
| `api/complete_session.php` | POST: Mark session as completed |
| `api/get_relationship_status.php` | GET: Fetch relationship + sessions |
| `mentor/manage_sessions.php` | Mentor page: View & manage all sessions |
| `mentee/schedule_sessions.php` | Mentee page: View & schedule sessions |

✅ All files have been created automatically
✅ Dashboards updated with new navigation links
✅ Configuration is production-ready

### Step 3: Verify Navigation Links

Test that all new pages are accessible:

**For Mentors**:
1. Sidebar → "Manage Sessions" should link to `/mentor/manage_sessions.php` ✓

**For Mentees**:
1. Sidebar → "Sessions" should link to `/mentee/schedule_sessions.php` ✓
2. My Mentor page → "Schedule Session" button should link to sessions page ✓

## Quick Test Scenario

### Complete Flow in 10 Minutes

**Setup (1 min)**:
```
Users needed:
- Mentee User (mentee1@test.com / password123)
- Mentor User (mentor1@test.com / password123)
```

**Test Steps (9 min)**:

```
1. Login as mentee1
   URL: http://localhost/mentor_connect/login.php
   
2. Find mentor
   Mentee Dashboard → Find Mentor
   Click "Send Request" to mentor1
   
3. Login as mentor1
   Logout from current user
   Login as mentor1@test.com
   
4. Accept request
   Mentor Dashboard → Pending Requests
   Click "Accept Request" for mentee1
   → Relationship created with 6-month duration
   
5. View relationships
   Mentor Dashboard → Manage Sessions
   Verify: mentee1 appears with "Active" status
   Verify: "0/6 sessions" and "180 days remaining"
   
6. Schedule first session
   Click "Schedule Session"
   Date: Today's date + 5 days at 14:00
   Notes: "Initial mentoring session"
   Click "Schedule"
   → Success: Session appears in list
   
7. View as mentee
   Login as mentee1
   Dashboard → Sessions
   → Verify: Session appears in list with "Scheduled" status
   
8. Complete session
   Login as mentor1
   Manage Sessions
   Find the session
   Click "Mark Complete"
   → Verify: Status changes to "Completed"
   
9. View statistics
   Both pages now show:
   - 1 completed, 0 scheduled, 5 remaining
   - Still 180 days remaining
```

**Expected Results**:
- ✅ Relationship created automatically on acceptance
- ✅ End date = now + 6 months
- ✅ Session scheduled successfully
- ✅ Status updates on completion
- ✅ Both mentor and mentee see same data

## Key Dates & Limits

### Relationship Duration
| Item | Value |
|------|-------|
| Duration | 6 months (automatic) |
| Lock Status | is_locked = TRUE |
| Start Date | When mentor accepts request |
| End Date | Auto-calculated: start + 6 months |
| Warning | Displays when ≤ 7 days remaining |

### Session Scheduling
| Limit | Value |
|-------|-------|
| Sessions per month | Maximum 1 |
| Total sessions | Maximum 6 |
| Session duration | Default 60 minutes (editable) |
| Status options | "scheduled" or "completed" |

### Monthly Constraint Example
```
Month    Status     Can Schedule Another?
-------  --------   ----------------------
Jan      1 session  NO  - Already has 1
Feb      0 sessions YES - Can schedule
Feb      2 sessions NO  - Already has 2 (ERROR PREVENTED BY CODE)
Mar      0 sessions YES - Can schedule
```

## Database Structure Reference

### New Fields in mentor_mentee_connections

```
start_date              DATETIME  - When relationship created
end_date                DATETIME  - When relationship expires (auto: NOW() + 6 MONTHS)
is_locked               BOOLEAN   - Lock status (TRUE after creation)
sessions_scheduled      INT       - Count (auto-updated by trigger)
sessions_completed      INT       - Count (auto-updated by trigger)
```

### mentor_mentee_sessions Table

```
session_id              INT       PRIMARY KEY
connection_id           INT       FK → mentor_mentee_connections
mentor_id               INT       FK → users
mentee_id               INT       FK → users
scheduled_date          DATETIME  ← Date/time user selects
session_month           VARCHAR   ← YYYY-MM format (enforces 1 per month)
duration_minutes        INT       Default: 60
status                  ENUM      'scheduled' or 'completed'
notes                   TEXT      Session agenda/notes
completed_at            DATETIME  Set when status='completed'
created_at              TIMESTAMP Auto
updated_at              TIMESTAMP Auto

UNIQUE KEY (connection_id, session_month)  ← Prevents duplicate months
```

## API Endpoints

### Schedule Session
```
POST /api/schedule_session.php
Content-Type: application/json

{
    "connection_id": 123,
    "scheduled_date": "2026-03-15 14:00:00",
    "notes": "Discuss career goals"
}

Response (Success - 201):
{
    "success": true,
    "message": "Session scheduled successfully",
    "data": {
        "session_id": 456,
        "connection_id": 123,
        "session_month": "2026-03"
    }
}

Response (Error - 400):
{
    "success": false,
    "message": "A session is already scheduled for 2026-03",
    "errors": ["..."]
}
```

### Complete Session
```
POST /api/complete_session.php
Content-Type: application/json

{
    "session_id": 456
}

Response (Success - 200):
{
    "success": true,
    "message": "Session marked as completed"
}
```

### Get Relationship Status
```
GET /api/get_relationship_status.php?connection_id=123

Response (Success - 200):
{
    "success": true,
    "data": {
        "relationship": {
            "total_sessions": 1,
            "remaining_sessions": 5,
            "days_remaining": 180,
            "lock_status": "active"
        },
        "available_months": ["2026-03", "2026-04", ...],
        "sessions": [...]
    }
}
```

## Common Errors & Solutions

### Error: "A session is already scheduled for 2026-03"
**Cause**: You already have a session in March 2026
**Solution**: Schedule for a different month (April, May, etc.)

### Error: "Maximum number of sessions (6) already scheduled"
**Cause**: All 6 sessions have been used
**Solution**: Wait for relationship to expire, or complete existing sessions

### Error: "Cannot schedule: Relationship has been unlocked"
**Cause**: Relationship duration has expired
**Solution**: Request mentorship from a different mentor

### Button disabled: "Schedule Session"
**Cause**: Either all 6 sessions used OR relationship expired
**Check**: 
- Remaining sessions counter: Should be > 0
- Days remaining: Should be > 0
- Lock status: Should be "Active"

### Session not appearing
**Cause**: Connection_id mismatch or session created with wrong date
**Check**:
- Verify you have an active relationship (My Mentor page)
- Check Session list refreshes after clicking "Schedule"
- Try page refresh (F5)

## Function Quick Reference

### For Mentors

#### View All Sessions
```
Go to: Mentor Dashboard → Manage Sessions
Shows:
- All mentee relationships
- Session counts per mentee
- Days remaining per relationship
- List of sessions with status
- Buttons to schedule & complete
```

#### Schedule a Session
```
1. Click "Schedule Session" button
2. Enter date/time
3. Add notes (optional)
4. Click "Schedule"
5. Session appears in list with "Scheduled" status
```

#### Complete a Session
```
1. Find session in list with "Scheduled" status
2. Click "Mark Complete" button
3. Session status changes to "Completed"
4. Counters update automatically
```

### For Mentees

#### View Current Mentor
```
Go to: Mentee Dashboard → My Mentor
Shows:
- Mentor profile
- Connection date
- Button to schedule sessions
```

#### Schedule with Mentor
```
1. Click "Schedule Session" button
2. Enter date/time within relationship period
3. Add notes (optional)
4. Click "Schedule"
5. Session appears in Sessions page
```

#### View All Sessions
```
Go to: Mentee Dashboard → Sessions
Shows:
- Current mentor profile
- Session counters: completed/scheduled/remaining
- Days remaining in relationship
- List of all sessions
```

## Validation Rules (All Execute on Server)

| Rule | Check | Error |
|------|-------|-------|
| Connection Active | Must be active & within 6-month window | "Cannot schedule: Relationship..." |
| Monthly Limit | No existing session in same YYYY-MM | "A session is already scheduled for..." |
| Session Limit | Total < 6 | "Maximum number of sessions (6)..." |
| Authorization | User must be part of relationship | "Unauthorized. You are not part..." |
| Manager Completion | Only mentor can mark complete | "Only mentors can complete sessions" |

All validation happens **in PHP backend** - no client-side loopholes

## Database Views (for advanced queries)

### v_active_connections
Shows all active relationships with time tracking
```sql
SELECT * FROM v_active_connections
WHERE days_remaining > 0
AND mentor_id = 1;
```

### v_session_statistics
Shows session statistics per relationship
```sql
SELECT * FROM v_session_statistics
WHERE remaining_sessions > 0;
```

## Backup & Recovery

### Before Running Setup
```
1. In phpMyAdmin: Export mentors_connect database (full backup)
2. File → Export → mentors_connect_backup.sql
3. Keep this file safe
```

### If Setup Fails
```
1. Drop trigger: DROP TRIGGER tr_set_connection_end_date;
2. Run phase3_setup.sql again from top
3. Verify tables 3 tables created
4. Verify 4 triggers attached
```

### If Relationship Data Corrupted
```
-- Recalculate all counters:
UPDATE mentor_mentee_connections c
SET sessions_scheduled = (
    SELECT COUNT(*) FROM mentor_mentee_sessions 
    WHERE connection_id = c.connection_id 
    AND status = 'scheduled'
),
sessions_completed = (
    SELECT COUNT(*) FROM mentor_mentee_sessions 
    WHERE connection_id = c.connection_id 
    AND status = 'completed'
);
```

## Troubleshooting

### Session Counters Not Updating
**Issue**: Sessions_scheduled/completed staying at 0
**Solution**:
1. Check triggers exist: phpMyAdmin → Structure → Triggers
2. Verify trigger is ACTIVE
3. Manual fix: Run query above

### Date/Time Issues
**Issue**: Sessions scheduled at wrong time
**Solution**:
1. Check server timezone: `php -r "echo date_default_timezone_get();"`
2. Update in `php.ini` if needed
3. Verify `scheduled_date` being submitted in correct format

### Responsive Design Issues (Mobile)
**Solution**: All Phase 3 pages responsive for:
- Desktop (1920px+)
- Tablet (768px - 1024px)
- Mobile (320px - 767px)

## Performance Tips

### Large Lists (50+ sessions)
- Sessions page sorts by date automatically
- Use filters for faster browsing
- Archive old completed sessions

### Query Optimization
- Indexes on: connection_id, session_month, status
- UNIQUE constraint prevents redundant queries
- Triggers keep counters accurate

## Migration Notes (Phase 2 → Phase 3)

### What Changed
- Database: 3 new tables + 2 views + 4 triggers
- Code: 10+ new functions in requests.php
- API: 3 new endpoints (non-breaking)
- UI: 2 new pages + updated dashboards

### What Still Works
- All Phase 2 features: requests, pending list, my mentees, find mentor
- No changes to authentication system
- No changes to role-based access
- All existing relationships work normally

### No Data Loss
- Phase 3 is additive (doesn't modify Phase 2 data)
- Existing users/mentors/mentees unaffected
- Requests/connections from Phase 2 still visible
- Only new connections after Phase 3 have sessions

## Next Steps (Phase 4+)

Possible future features:
- Messaging system (using existing connections)
- Availability calendar
- Email notifications
- Rating/review system
- Virtual meeting integration
- Session recording storage

For now, Phase 3 provides complete session management:
- Duration-based relationships
- Monthly session limits
- Completion tracking
- Status visibility

## Support

### Report Issues
Include:
1. Browser (Chrome, Firefox, Safari, etc.)
2. Error message (exact text)
3. Steps to reproduce
4. User role (mentor/mentee/admin)
5. Server error log (`Apache error.log`)

### Check Logs
```
MySQL Logs: /xampp/mysql/data/error.log
PHP Errors: /xampp/apache/logs/error.log
Application: Check browser console (F12)
```

---

**Phase 3 Ready!** 🚀

All session management features are live and tested. Proceed with test scenarios above.
