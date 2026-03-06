# Phase 5 Quick Start Guide
**Feedback System & Admin Panel** | 5 min setup

## 30-Second Overview

Phase 5 adds:
- ✅ **Mentee Feedback** - Rate completed sessions 1-5 stars
- ✅ **Comments** - Optional text feedback for mentors
- ✅ **Admin Dashboard** - View mentors, mentees, relationships, sessions, feedback
- ✅ **Performance Metrics** - Average ratings, session counts, engagement

## Install (2 Steps)

### 1️⃣ Run Database Setup

```bash
File: phase5_setup.sql
```

**In phpMyAdmin**:
1. Select `mentors_connect` database
2. Click **SQL** tab  
3. Copy entire `phase5_setup.sql`
4. Paste and click **Go**

**Result**: 
```
✅ session_feedback table created
✅ 6 reporting views created
✅ Trigger for validation created
✅ 14 functions available
```

### 2️⃣ Verify Files

Check these files exist:
```
✅ api/submit_feedback.php
✅ api/get_feedback.php
✅ mentee/feedback.php
✅ admin/dashboard.php (redesigned)
```

**Done!** Mentee dashboard now shows "Feedback" link.
Admin dashboard now has 6 tabs.

## Test It (5 Minutes)

### Setup: Need a Completed Session

First, create a session that's marked "completed":

```
1. Login as mentor
   Dashboard → Sessions

2. Find a session
   Change status to: completed
   Verify it has end_date
   
   (OR: Book a slot as mentee, then manually complete it)
```

### As Mentee: Submit Feedback

```
1. Login as mentee
   Dashboard → Feedback (NEW LINK)

2. See:
   📊 Statistics cards:
     - Completed Sessions: X
     - Feedback Submitted: Y
     - Pending Feedback: Z

3. Scroll down to completed sessions list

4. Find the completed session

5. Click: "Submit Feedback" button
   Modal opens with:
   - 5 stars to click
   - Comment field (optional)

6. Click 5 stars (or any 1-5)

7. Add comment (optional):
   "Great mentor, very helpful!"

8. Click: "Submit Feedback"

Expected:
   ✅ Success message
   ✅ Modal closes
   ✅ Page reloads
   ✅ Feedback now shows in list
   ✅ Stars display: ★★★★★ (5/5)
   ✅ Button changes to "Feedback Submitted" badge
```

### As Admin: View Everything

```
1. Login as admin
   Go to: admin/dashboard.php (redesigned)

2. See overview:
   📊 6 stat cards
     - Total Mentors
     - Total Mentees
     - Active Relationships
     - Total Sessions
     - Feedback Submissions
     - Average Rating

3. Click tabs at top:

   [Overview] ← Shows summary + recent sessions
   
   [Mentors] ← All mentors with:
     - Name, Email, Expertise
     - Sessions (completed + upcoming)
     - Feedback count
     - Average rating (★★★★★ 4.5/5)
     
   [Mentees] ← All mentees with:
     - Name, Email, Domain
     - Sessions taken
     - Feedback given
     
   [Relationships] ← All connections:
     - Mentor name, Mentee name
     - Status: Active/Expired/Locked
     - Session counts
     - Dates
     
   [Sessions] ← All sessions:
     - Date, Mentor, Mentee
     - Status badge
     - Duration
     - Pagination (20 per page)
     
   [Feedback] ← All feedback:
     - Date, Mentor, Mentee
     - Rating (★★★★★ or "No feedback")
     - Comment preview
```

**All working!** ✅ You completed Phase 5 setup.

## What You Get

### Mentee Features
| Feature | Location |
|---------|----------|
| View Completed Sessions | Dashboard → Feedback |
| Submit 1-5 Rating | Feedback page → Click session → Modal |
| Add Comments | Modal → Textarea |
| See History | Feedback page shows all past feedback |
| Pending Count | Statistics card shows how many left |

### Admin Features
| Feature | Location |
|---------|----------|
| Overview Stats | admin/dashboard.php → Overview tab |
| All Mentors | Mentors tab (with ratings) |
| All Mentees | Mentees tab (with engagement) |
| Relationships | Relationships tab |
| Sessions | Sessions tab (paginated) |
| Feedback | Feedback tab (all ratings) |

## Database What Changed

**New Table**:
```sql
session_feedback
  ├─ feedback_id (unique)
  ├─ session_id (which session)
  ├─ mentee_id (who rated)
  ├─ rating (1-5 stars)
  ├─ comments (optional text)
  └─ created_at (when submitted)

UNIQUE KEY (session_id, mentee_id)
  → Only 1 feedback per session
```

**New Views** (6):
```
v_sessions_with_feedback     → All sessions + feedback
v_completed_sessions_ready_for_feedback → No feedback yet
v_mentor_statistics          → Per-mentor metrics
v_mentee_statistics          → Per-mentee engagement
v_relationship_status        → Connection details
v_feedback_summary           → Rating distribution
```

**New Trigger**:
```
tr_validate_feedback_completion
  → Only allow feedback on completed sessions
```

## Key Rules

- ✅ Only mentees can submit feedback
- ✅ Only for sessions with status "completed" + end_date
- ✅ Rating must be 1-5 stars
- ✅ Comments optional, max 1000 characters
- ✅ One feedback per session (can't rate twice)
- ✅ Can submit within 6 months after session ends

## Mobile Support

✅ Works on:
- Desktop (1920px+)
- Tablet (768px)
- Mobile (320px)
- Touch-friendly star rating
- Responsive table layouts

## Colors

**Admin Dashboard**:
- 🟢 Green = Active/Completed
- 🟡 Yellow = Upcoming
- 🔵 Blue = Info
- 🔴 Red = Locked/Failed

**Ratings**:
- ⭐⭐⭐⭐⭐ (5/5) = Gold stars
- Average displayed as: 4.5★

## Troubleshooting

| Problem | Solution |
|---------|----------|
| No "Feedback" link on menu | Refresh page or clear cache |
| Can't submit feedback | Is session status "completed"? |
| Can't select 1 star | All 5 buttons work - try each |
| Comments won't submit | Check length < 1000 chars |
| Admin dashboard blank | Check browser console (F12) |
| No mentors shown | Database query might be empty |

## API Reference

**Submit Feedback**:
```
POST /api/submit_feedback.php
{
  "session_id": 123,
  "rating": 5,
  "comments": "Great mentor!"
}
```

**Get Feedback**:
```
GET /api/get_feedback.php?session_id=123
```

## Files Summary

**New**:
- phase5_setup.sql
- api/submit_feedback.php
- api/get_feedback.php
- mentee/feedback.php

**Updated**:
- config/requests.php (+800 lines)
- admin/dashboard.php (complete rewrite)
- mentee/dashboard.php (+1 link)

## Next: Customize

Want to change something?

```
Star colors:
  → Edit: mentee/feedback.php (CSS .stars)

Comments max length:
  → Edit: mentee/feedback.php JavaScript
  → Edit: api/submit_feedback.php validation

Admin stats shown:
  → Edit: admin/dashboard.php top 6 cards

Rating required?
  → It is (can't skip stars)
  → To make optional: edit validation
```

## Summary

✅ **Database**: Feedback table + 6 views created
✅ **API**: 2 endpoints for feedback
✅ **UI**: Mentee feedback page + admin dashboard (6 tabs)
✅ **Functions**: 14 database functions for feedback/stats
✅ **Validation**: Triggers + constraints prevent bad data
✅ **Features**: Star rating, comments, feedback history, admin metrics

**Status**: Phase 5 production-ready 🚀

---

For detailed docs, see [PHASE5.md](PHASE5.md)
