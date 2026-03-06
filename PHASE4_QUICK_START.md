# Phase 4 Quick Start Guide
**FullCalendar Integration & Mentee Slot Booking** | 5 min setup

## 30-Second Overview

Phase 4 adds a **calendar booking system** where:
- ✅ Mentors add availability time slots
- ✅ Mentees browse and book available slots
- ✅ Booked slots disappear instantly
- ✅ Professional FullCalendar.js UI

## Install (2 Steps)

### 1️⃣ Run Database Setup

```bash
File: phase4_setup.sql
Location: root of mentor_connect folder
```

**In phpMyAdmin**:
1. Select `mentors_connect` database
2. Click **SQL** tab  
3. Copy entire `phase4_setup.sql`
4. Paste and click **Go**

**Result**: 
```
✅ mentor_calendar_blocks table created
✅ mentor_blackout_dates table created
✅ Triggers for auto-booking created
✅ Sample slots added for testing
```

### 2️⃣ Verify Files

Check these files exist in your workspace:
```
✅ api/get_calendar_events.php
✅ api/get_available_slots.php
✅ api/book_slot.php
✅ api/add_availability_block.php
✅ api/delete_availability_block.php
✅ mentor/calendar.php
✅ mentee/book_slots.php
```

**Done!** Both mentor and mentee dashboards now show Calendar/Book Slots links.

## Test It (5 Minutes)

### Login as Mentor

```
URL: http://localhost/mentor_connect/login.php
User: mentor1
Pass: password123
```

### Add a Slot

```
Dashboard → Calendar (left sidebar)

Form Fields:
  Date: Pick today + 7 days (March 22, 2026)
  Start Time: 14:00
  End Time: 15:00

Click: "Add Availability Slot"
Expected: ✅ "Slot added successfully"
         Calendar shows green slot
```

### Login as Mentee

```
Logout first
Login as: mentee1
Pass: password123
```

### Book the Slot

```
Dashboard → Book Slots (left sidebar)

See: Mentor's calendar displayed
See: Grid of available slots below calendar

Click: Any available slot card
Modal: Shows date/time confirmation

Notes: (optional) "Want to discuss career progression"

Click: "Book Slot"
Expected: ✅ Success message
         Slot disappears from available list
         Calendar updates to show booked
```

### Verify as Mentor

```
Login as mentor1
Go to: Dashboard → Calendar

See: Slot is now YELLOW (booked)
See: "Booked by: mentee1"
See: Cannot delete booked slots (button disabled)

Go to: Dashboard → Sessions
See: New session "with mentee1" on booked date
```

**All working!** ✅ You just completed Phase 4 setup.

## What You Get

### For Mentors
| Feature | Location |
|---------|----------|
| Add Availability | Dashboard → Calendar → Form |
| View Calendar | Dashboard → Calendar → FullCalendar |
| Manage Slots | Dashboard → Calendar → Slot Grid |
| Delete Unbooked | Each slot card (button disables when booked) |

### For Mentees  
| Feature | Location |
|---------|----------|
| Browse Slots | Dashboard → Book Slots |
| View Calendar | Full FullCalendar display |
| Book Slot | Click card → Modal → Submit |
| See Status | Available slots list |

## Database What Changed

**New Tables**:
```sql
mentor_calendar_blocks
  ├─ block_id (unique slot ID)
  ├─ mentor_id (who created it)
  ├─ block_date (which day)
  ├─ start_time, end_time (when)
  ├─ is_booked (TRUE = taken, FALSE = available)
  └─ booked_by_session_id (session it created)

mentor_blackout_dates  
  ├─ blackout_id
  ├─ mentor_id
  ├─ start_date, end_date (unavailable period)
  └─ reason
```

**Modified Table**:
```sql
mentor_mentee_sessions
  └─+ calendar_block_id (links to the slot booked)
```

**Auto-Magic** (Triggers):
```
When slot booked:   is_booked automatically → TRUE
When session deleted: slot automatically → available again
```

## API Endpoints (3 Main)

### Mentees Use
```
GET  /api/get_available_slots.php
     → Shows slots ready to book
     
POST /api/book_slot.php
     → Books a slot (creates session)
```

### Mentor Calendar Uses
```
GET  /api/get_calendar_events.php
     → FullCalendar event data
     
POST /api/add_availability_block.php  
     → Mentor adds slot
     
POST /api/delete_availability_block.php
     → Mentor removes unbooked slot
```

## Key Rules (From Phase 3)

Booking still respects:
- ✅ 1 session per calendar month (can't book 2 in same month)
- ✅ 6 sessions total in 6-month period
- ✅ Relationship must be active (not expired/locked)
- ✅ Booked slot becomes unavailable for others

## Mobile Support

✅ Works on:
- Desktop (1920px+)
- Tablet (768px)
- Mobile (320px) - touch-friendly

## Colors in Calendar

```
🟢 Green   = Available (mentee can book)
🟡 Yellow  = Booked (taken by someone)
🔵 Blue    = Session scheduled
```

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Slots not showing | Refresh page (Ctrl+F5) |
| Can't book (error about month) | Already have session in that month - pick different month |
| Slot shows available but errors | Database might be stale - refresh page |
| Calendar blank | Check browser console (F12) for JavaScript errors |

## Files Added

```
phase4_setup.sql           ← Run this first in phpMyAdmin
api/get_calendar_events.php
api/get_available_slots.php  
api/book_slot.php
api/add_availability_block.php
api/delete_availability_block.php
mentor/calendar.php        ← Mentor goes here
mentee/book_slots.php      ← Mentee goes here
```

**Plus**: Updated `config/requests.php` with 10 new calendar functions

## Next: Customize

Want to adjust something?

```
Slot duration (default 1 hour):
  → Edit: api/add_availability_block.php (line XXX)

Calendar view (default month):
  → Edit: mentor/calendar.php 
          mentee/book_slots.php
  → Change: initialView: 'dayGridMonth'

Colors:
  → Edit: API response color field

Timezone:
  → Global setting: config/config.php
```

## Summary

✅ **Database**: Tables created, triggers active, sample data loaded
✅ **API**: 5 endpoints functional with validation
✅ **UI**: Mentor calendar + Mentee booking pages
✅ **Integration**: Connected to dashboard + Phase 3 rules

**Status**: Phase 4 production-ready 🚀

---

For detailed docs, see [PHASE4.md](PHASE4.md)
