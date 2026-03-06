# Phase 4: FullCalendar Integration & Slot Booking
**Calendar Management with FullCalendar.js & Mentee Booking** | February 2026

## Overview

Phase 4 adds a **professional calendar system** using FullCalendar.js:

✅ **Mentor Availability Calendar** - Set availability time slots
✅ **Mentee Booking System** - Book available slots directly
✅ **Real-Time Slot Status** - Booked slots become unavailable instantly
✅ **Calendar Events Display** - View all sessions and availability
✅ **Database Persistence** - All slots stored in mentor_calendar_blocks
✅ **FullCalendar Integration** - Industry-standard calendar UI

## What's New in Phase 4

### For Mentors
- **Calendar Page** - Full calendar view with availability blocks
- **Add Availability** - Form to add specific date/time slots
- **Slot Management** - View and delete unbooked slots
- **Booking Status** - See which slots are booked and by whom
- **FullCalendar Display** - Professional month/week/day views

### For Mentees  
- **Book Slots Page** - Browse mentor's available slots
- **Calendar View** - See mentor's full calendar
- **One-Click Booking** - Click a slot → modal → book
- **Visual Calendar** - FullCalendar displays mentor's schedule
- **Availability Grid** - List view of all open slots

## Installation Steps

### Step 1: Database Setup

1. Open **phpMyAdmin** → Select `mentors_connect` database
2. Go to **SQL** tab
3. Copy entire contents of `phase4_setup.sql`
4. Paste into SQL editor
5. Click **Go** to execute

**Verify Setup**:
- Check **Structure** → Look for:
  - `mentor_calendar_blocks` (NEW)
  - `mentor_blackout_dates` (NEW)
  - `mentor_mentee_sessions` (Modified)

- Check **Triggers**:
  - `tr_mark_availability_booked`
  - `tr_free_availability_on_delete`

- Check **Views**:
  - `v_available_slots`
  - `v_calendar_events`

### Step 2: File Structure

**New Files Created (11)**:

| File | Purpose | Type |
|------|---------|------|
| `phase4_setup.sql` | Database schema | SQL |
| `config/requests.php` | Calendar functions added | Updated |
| `api/get_calendar_events.php` | Fetch calendar events | API |
| `api/get_available_slots.php` | List available slots | API |
| `api/book_slot.php` | Book a slot | API |
| `api/add_availability_block.php` | Add availability | API |
| `api/delete_availability_block.php` | Delete availability | API |
| `mentor/calendar.php` | Mentor calendar UI | Page |
| `mentee/book_slots.php` | Mentee booking UI | Page |

**Updated Files (2)**:
- `mentor/dashboard.php` - Added Calendar link
- `mentee/dashboard.php` - Added Book Slots link

### Step 3: Dependencies

**FullCalendar.js** (loaded from CDN):
```html
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
```

✅ No additional installation needed - uses CDN

## Database Schema

### mentor_calendar_blocks Table
```sql
block_id              INT         PRIMARY KEY
mentor_id             INT         Foreign Key → users
block_date            DATE        The specific date
start_time            TIME        Start time (HH:MM:SS)
end_time              TIME        End time (HH:MM:SS)
is_booked             BOOLEAN     FALSE (unbooked) / TRUE (booked)
booked_by_session_id  INT         FK → mentor_mentee_sessions (when booked)
booked_at             DATETIME    When the slot was booked
```

**Keys & Constraints**:
```sql
UNIQUE KEY unique_mentor_slot (mentor_id, block_date, start_time, end_time)
INDEX idx_mentor_date (mentor_id, block_date)
INDEX idx_is_booked (is_booked)
```

### mentor_blackout_dates Table
```sql
blackout_id           INT         PRIMARY KEY
mentor_id             INT         Foreign Key → users
start_date            DATETIME    Unavailable start
end_date              DATETIME    Unavailable end
reason                VARCHAR     Why unavailable (vacation, holiday, etc)
```

### Views

**v_available_slots**:
- Shows only `is_booked = FALSE` slots
- Future dates only
- Includes mentor info (name, email, expertise)
- Used by mentee API

**v_calendar_events**:
- Union of sessions + availability blocks
- Color-coded: green=available, yellow=booked
- For FullCalendar display

## API Endpoints

### 1. Get Calendar Events
```
GET /api/get_calendar_events.php?start=2026-03-01&end=2026-04-01
```

**Response**:
```json
{
  "success": true,
  "events": [
    {
      "id": 123,
      "title": "Available Slot",
      "start": "2026-03-15T14:00:00",
      "end": "2026-03-15T15:00:00",
      "color": "#90ee90",
      "extendedProps": {
        "type": "availability",
        "status": "available"
      }
    }
  ]
}
```

### 2. Get Available Slots
```
GET /api/get_available_slots.php?mentor_id=2&start_date=2026-03-01&end_date=2026-04-01
```

**Response**:
```json
{
  "success": true,
  "slots": [
    {
      "block_id": 123,
      "mentor_id": 2,
      "mentor_name": "mentor1",
      "block_date": "2026-03-15",
      "start_time": "14:00:00",
      "end_time": "15:00:00",
      "start_datetime": "2026-03-15 14:00:00",
      "end_datetime": "2026-03-15 15:00:00",
      "duration": "01:00:00"
    }
  ],
  "count": 1
}
```

### 3. Book a Slot
```
POST /api/book_slot.php

{
  "connection_id": 123,
  "block_id": 456,
  "notes": "Want to discuss career goals"
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Session booked successfully",
  "data": {
    "session_id": 789,
    "connection_id": 123,
    "calendar_block_id": 456,
    "scheduled_date": "2026-03-15 14:00:00"
  }
}
```

### 4. Add Availability Block
```
POST /api/add_availability_block.php

{
  "block_date": "2026-03-15",
  "start_time": "14:00:00",
  "end_time": "15:00:00"
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Availability slot added",
  "data": {
    "block_id": 456,
    "mentor_id": 2,
    "block_date": "2026-03-15",
    "start_time": "14:00:00",
    "end_time": "15:00:00"
  }
}
```

### 5. Delete Availability Block
```
POST /api/delete_availability_block.php

{
  "block_id": 456
}
```

**Authorization**: Only mentor who owns the block
**Validation**: Cannot delete if `is_booked = TRUE`

## Quick Start: Test Scenario

### Complete Workflow in 15 Minutes

**Setup**:
```
Mentor: mentor1 (user_id=2)
Mentee: mentee1 (user_id=3)
Both must have active relationship
```

**Step-by-Step**:

```
1. Login as mentor1
   URL: http://localhost/mentor_connect/login.php

2. Go to Calendar
   Dashboard → Calendar
   
3. Add Availability Slot
   Date: Today + 7 days
   Time: 14:00 to 15:00
   Click "Add Slot"
   → Slot appears in list

4. Verify Calendar
   Slot appears in FullCalendar with green color
   Status: "AVAILABLE"

5. Login as mentee1
   Logout → Login with mentee1 credentials

6. Go to Book Slots
   Dashboard → Book Slots
   
7. View Calendar
   Mentor's calendar displays with availability
   
8. View Available Slots
   Scroll down to see grid of available slots
   
9. Click a Slot
   Card shows date/time
   Modal opens: "You are booking: ..."
   
10. Add Notes (optional)
    "Want to discuss career path"
    
11. Book Slot
    Click "Book Slot"
    → Success message
    → Page refreshes
    → Slot disappears from available list (now booked)

12. Verify as Mentor
    Login as mentor1
    Go to Calendar → See booked slot now yellow
    Booked by: mentee1
    
13. Verify Session Created
    Mentor Dashboard → Sessions
    New session appears (from calendar book)
```

**Expected Results**:
- ✅ Mentor can add/delete availability
- ✅ Mentee sees mentor's calendar
- ✅ Mentee can book available slots
- ✅ Booked slots become unavailable instantly
- ✅ Session created with correct date/time
- ✅ Both see updated status in calendar

## Key Features

### Calendar Rendering
```javascript
new FullCalendar.Calendar(element, {
  initialView: 'dayGridMonth',  // Month view by default
  headerToolbar: {
    left: 'prev,next today',    // Navigation
    center: 'title',             // Month name
    right: 'dayGridMonth,dayGridWeek,listMonth'  // View switcher
  },
  events: '/api/get_calendar_events.php'  // Load events dynamically
})
```

### Event Colors
```
Green (#90ee90)   = Available slot
Yellow (#ffc107)  = Booked slot
Blue (#667eea)    = Scheduled session
Green (#28a745)   = Completed session
```

### Booking Flow
```
Mentee clicks slot
  ↓
Modal shows date/time confirmation
  ↓
Mentee adds notes (optional)
  ↓
Click "Book Slot"
  ↓
POST to /api/book_slot.php
  ↓
Validates:
  - User is mentee
  - Slot not already booked
  - 1 session per month rule
  - Relationship still active
  ↓
Creates mentor_mentee_sessions entry
  ↓
Marks calendar_block as is_booked=TRUE
  ↓
Returns session_id
  ↓
Page reloads, available slots updated
```

## Database Triggers

### tr_mark_availability_booked
**When**: Session created with calendar_block_id
**Action**: Updates mentor_calendar_blocks.is_booked = TRUE

```sql
IF NEW.calendar_block_id IS NOT NULL THEN
  UPDATE mentor_calendar_blocks
  SET is_booked = TRUE, booked_by_session_id = NEW.session_id
  WHERE block_id = NEW.calendar_block_id
END
```

### tr_free_availability_on_delete
**When**: Session deleted that has calendar_block_id
**Action**: Frees the slot for new bookings

```sql
IF OLD.calendar_block_id IS NOT NULL THEN
  UPDATE mentor_calendar_blocks
  SET is_booked = FALSE, booked_by_session_id = NULL
  WHERE block_id = OLD.calendar_block_id
END
```

## Calendar Functions Added to requests.php

| Function | Purpose |
|----------|---------|
| `get_available_calendar_slots()` | Get bookable slots |
| `book_calendar_slot()` | Book a slot (creates session) |
| `get_calendar_events()` | Fetch calendar display events |
| `add_blackout_date()` | Mark unavailable period |
| `get_mentor_blackout_dates()` | Get unavailable dates |
| `add_calendar_availability_block()` | Add specific slot |
| `get_mentor_availability_blocks()` | Get mentor's blocks |
| `delete_calendar_block()` | Remove unbooked slot |

## Validation Rules

| Rule | Check | Location |
|------|-------|----------|
| Only unbooked can delete | `is_booked = FALSE` | delete_calendar_block() |
| Prevent duplicate slots | UNIQUE constraint | Database |
| Date in future | `block_date >= CURRENT_DATE` | add_calendar_availability_block() |
| Time range valid | `start < end` | add_calendar_availability_block() |
| Not double-booked | `is_booked = FALSE` | book_calendar_slot() |
| Monthly limit | Max 1 per month | book_calendar_slot() |
| Relationship active | Check is_locked & end_date | book_calendar_slot() |

## Integration with Phase 3

**Session Creation**: When mentee books a slot:
1. Calendar block is used to create mentor_mentee_sessions
2. Session inherits date/time from calendar block
3. Both track the relationship via connection_id
4. Counters (sessions_scheduled) auto-update

**Example**:
```
Calendar Block (is_booked=FALSE)
  ↓ Mentee books
  ↓ POST /api/book_slot.php
  ↓ Creates mentor_mentee_sessions entry
  ↓ calendar_block_id field links them
  ↓ Trigger marks block as_booked=TRUE
```

## Mobile Responsive

✅ **Desktop** (1920px+)
- Full FullCalendar
- Side-by-side calendar + slots

✅ **Tablet** (768px-1024px)
- Stacked layout
- Calendar full width
- Slots below

✅ **Mobile** (320px-767px)
- Calendar responsive
- Single-column slots
- Touch-friendly buttons

## Common Use Cases

### Mentor Sets Weekly Availability
```
Monday:    14:00-15:00, 16:00-17:00
Wednesday: 14:00-15:00, 16:00-17:00
Friday:    14:00-15:00, 16:00-17:00
Saturday:  10:00-11:00, 14:00-15:00
```

→ Mentor adds 8 slots per week manually, or could batch generate

### Mentee Books Multiple Slots
```
Session 1 (Month 1): Feb 15, 14:00
Session 2 (Month 2): Mar 10, 15:00
Session 3 (Month 3): Apr 12, 14:00
...
Session 6 (Month 6): Jul 8, 16:00
```

→ 1 slot per calendar month, 6 total in 6-month period

### Mentor Goes on Vacation
```
Add blackout: Mar 1 to Mar 15
OR
Remove slots for those dates
```

→ Mentees can't see/book those dates

## Performance Considerations

### Indexing
```sql
INDEX idx_mentor_date (mentor_id, block_date)    -- Fast mentor lookups
INDEX idx_is_booked (is_booked)                  -- Quick availability checks
INDEX idx_block_date (block_date)                -- Date range queries
UNIQUE (mentor_id, block_date, start_time)       -- Prevents duplicates
```

### Query Optimization
- v_available_slots filters: `is_booked = FALSE` AND `block_date >= TODAY`
- v_calendar_events UNION optimized with proper indexes
- Calendar loads via AJAX (lazy loading)

### Scalability
- Per mentor: ~500 slots across 6 months = ~100 per month
- Load: Light (simple SELECT with WHERE)
- Booking: Atomic (single transaction)

## Troubleshooting

### Slots Not Showing
1. Check: `SELECT * FROM mentor_calendar_blocks WHERE mentor_id = ?`
2. Verify: `is_booked = FALSE`
3. Verify: `block_date >= CURRENT_DATE`
4. Check: Mentor timezone in server

### Can't Book Slot
**Error**: "A session is already scheduled for 2026-03"
→ Solution: Choose different month

**Error**: "Slot has already been booked"
→ Solution: Page might be stale - refresh

### Calendar Not Loading
1. Check browser console (F12)
2. Verify: `/api/get_calendar_events.php` returns JSON
3. Check: FullCalendar CDN loads (network tab)
4. Verify: User is logged in (403 if not)

### Booked Slot Still Shows as Available
1. Page cache - refresh (Ctrl+F5)
2. Database issue:
   ```sql
   SELECT * FROM mentor_calendar_blocks WHERE block_id = ?;
   -- Should show is_booked = 1
   ```
3. Check trigger fired:
   ```sql
   SELECT * FROM mentor_mentee_sessions WHERE calendar_block_id = ?;
   -- Should have session_id
   ```

## Next Steps (Phase 5+)

Possible enhancements:
- **Recurring Availability** - Set "Every Monday 2-3pm"
- **Timezone Support** - Mentee sees slots in their timezone
- **Email Notifications** - Confirmation emails on booking
- **Rescheduling** - Move booked slot to different time
- **Buffer Time** - Minimum time between sessions
- **Confirmation** - Mentor confirms before session

## Files Summary

### New (11 files)
- `phase4_setup.sql` (150+ lines)
- `config/requests.php` (+280 lines for calendar functions)
- `api/get_calendar_events.php` (50 lines)
- `api/get_available_slots.php` (60 lines)
- `api/book_slot.php` (80 lines)
- `api/add_availability_block.php` (70 lines)
- `api/delete_availability_block.php` (60 lines)
- `mentor/calendar.php` (400+ lines)
- `mentee/book_slots.php` (450+ lines)

### Modified (2 files)
- `mentor/dashboard.php` (added Calendar link)
- `mentee/dashboard.php` (added Book Slots link)

### Total Code Added
- **2000+ lines** of PHP/SQL/JS
- **5 API endpoints**
- **2 new UI pages**
- **3 database tables/views**
- **2 database triggers**

---

**Phase 4 Complete!** Calendar system with FullCalendar integration is production-ready. 🎉
