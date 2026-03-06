# Phase 2 Quick Implementation Guide

## 🚀 What Was Built

A complete **Mentor-Mentee Request System** with strict validation rules, capacity management, and role-based access control.

---

## 📁 New Files Created (10 Files)

### Backend (Config & API)
1. **[config/requests.php](config/requests.php)** - 15+ request management functions
2. **[api/send_request.php](api/send_request.php)** - POST endpoint to send requests
3. **[api/accept_request.php](api/accept_request.php)** - POST endpoint to accept requests
4. **[api/reject_request.php](api/reject_request.php)** - POST endpoint to reject requests

### Mentee Pages
5. **[mentee/find_mentor.php](mentee/find_mentor.php)** - Browse mentors & send requests
6. **[mentee/my_requests.php](mentee/my_requests.php)** - View all sent requests
7. **[mentee/my_mentor.php](mentee/my_mentor.php)** - View current mentor

### Mentor Pages
8. **[mentor/pending_requests.php](mentor/pending_requests.php)** - Accept/reject requests
9. **[mentor/my_mentees.php](mentor/my_mentees.php)** - View accepted mentees

### Database & Documentation
10. **[phase2_setup.sql](phase2_setup.sql)** - Database schema & test data
11. **[PHASE2.md](PHASE2.md)** - Complete Phase 2 documentation

---

## 🗄️ Database Changes

### New Tables (5)
```
✅ domains              - Mentoring domains/categories
✅ mentor_profiles      - Mentor information & capacity
✅ mentee_profiles      - Mentee information & request tracking
✅ mentor_mentee_requests     - Request tracking (pending/accepted/rejected)
✅ mentor_mentee_connections  - Active mentorship relationships
```

### All Tables Have
- Proper indexes for performance
- Foreign key constraints for data integrity
- UNIQUE constraints to prevent duplicates
- Timestamps for audit trails

---

## ✅ Validation Rules Implemented

### Rule 1: Request Limit (2 max per mentee)
```php
can_mentee_send_request($pdo, $mentee_id)
// Returns error if mentee has sent 2 requests
```

### Rule 2: Capacity Limit (2 max per mentor)
```php
can_mentor_accept_mentee($pdo, $mentor_id)
// Returns: "Mentor is currently fully booked"
```

### Rule 3: Different Domains
```php
are_different_domains($pdo, $mentee_id, $mentor_id)
// Prevents same-domain mentoring
```

### Rule 4: Duplicate Prevention
```php
request_already_exists($pdo, $mentee_id, $mentor_id)
// Uses database UNIQUE constraint
```

### Rule 5: Backend Validation
- ✅ All validation in PHP (no client-side tricks)
- ✅ Role-based checks (mentee/mentor)
- ✅ Authorization verification
- ✅ Transaction support for consistency

---

## 🎯 Function Reference

### Request Management Functions
```php
// Create request with all validations
create_mentee_request($pdo, $mentee_id, $mentor_id, $message)

// Accept mentee request
accept_mentee_request($pdo, $request_id, $mentor_id)

// Reject mentee request
reject_mentee_request($pdo, $request_id, $mentor_id)
```

### Query Functions
```php
// Get pending requests for mentor
get_mentor_pending_requests($pdo, $mentor_id)

// Get accepted mentees for mentor
get_mentor_accepted_mentees($pdo, $mentor_id)

// Get requests sent by mentee
get_mentee_sent_requests($pdo, $mentee_id)

// Get current mentor for mentee
get_mentee_current_mentor($pdo, $mentee_id)

// Get available mentors for mentee
get_available_mentors_for_mentee($pdo, $mentee_id)
```

### Summary Functions
```php
get_mentee_request_summary($pdo, $mentee_id)    // Stats
get_mentor_request_summary($pdo, $mentor_id)    // Stats
```

---

## 🔌 API Endpoints

### 1. Send Request
```
POST /api/send_request.php
Parameters: mentor_id, message (optional)
Response: { success, message, request_id }
```

### 2. Accept Request
```
POST /api/accept_request.php
Parameters: request_id
Response: { success, message }
```

### 3. Reject Request
```
POST /api/reject_request.php
Parameters: request_id
Response: { success, message }
```

---

## 📄 User Interface Pages

### Mentee Pages
| Page | Purpose | Features |
|------|---------|----------|
| find_mentor.php | Browse mentors | Filter by domain, send requests, capacity indicators |
| my_requests.php | View requests | Statistics, status tracking, mentor info |
| my_mentor.php | View current mentor | Profile, bio, contact, connection date |

### Mentor Pages
| Page | Purpose | Features |
|------|---------|----------|
| pending_requests.php | Manage requests | Accept/reject, mentee info, capacity warnings |
| my_mentees.php | View mentees | Active connections, learning goals, capacity |

---

## 🚀 Installation Steps

### Step 1: Run Database Setup
```bash
# In phpMyAdmin
1. Open "mentors_connect" database
2. Go to "SQL" tab
3. Paste contents of phase2_setup.sql
4. Click "Go"
```

### Step 2: Test Mentee Features
```
1. Login as mentee1
2. Click "Find Mentor" in sidebar
3. See list of available mentors (different domains)
4. Send request to mentor1
5. Go to "My Requests" to view request
```

### Step 3: Test Mentor Features
```
1. Login as mentor1
2. Click "Pending Requests" in sidebar
3. See request from mentee1
4. Click "Accept" to create connection
5. Go to "My Mentees" to see mentee1
```

### Step 4: Test Validation Rules
```
1. Try sending 3rd request (blocked)
2. Try requesting same-domain mentor (blocked)
3. Try requesting mentor who accepted 2 mentees (blocked)
4. Try sending duplicate request (blocked)
```

---

## 🔒 Security Features

✅ **Backend Validation Only** - No JavaScript bypasses  
✅ **Role-Based Access Control** - Mentee/mentor specific pages  
✅ **Authorization Checks** - Can only manage own data  
✅ **Prepared Statements** - SQL injection prevention  
✅ **Database Constraints** - UNIQUE, NOT NULL, FK  
✅ **Transaction Support** - Atomic operations  
✅ **Error Messages** - Don't leak sensitive info  
✅ **HTTP Status Codes** - Proper response codes (201, 400, 403, 404, 405)  

---

## 📊 Test Scenarios

### Scenario 1: Send First Request ✓
1. Mentee navigates to "Find Mentor"
2. Clicks "Send Request" on available mentor
3. Enters optional message
4. Submits form
5. **Result**: Request created, appears in "My Requests" as pending

### Scenario 2: Max 2 Requests ✓
1. Mentee sends 2 requests successfully
2. Tries to send 3rd request
3. **Result**: Error message shown, button disabled

### Scenario 3: Mentor Full ✓
1. Mentor has accepted 2 mentees
2. Mentee tries to send request to full mentor
3. **Result**: Error "Mentor is currently fully booked"

### Scenario 4: Different Domains ✓
1. Mentee in "Web Development"
2. Tries to request mentor also in "Web Development"
3. **Result**: Error "must be from different domains"

### Scenario 5: Duplicate Request ✓
1. Mentee sends request to mentor1
2. Tries to send another request to mentor1
3. **Result**: Error "already sent a request"

### Scenario 6: Accept Request ✓
1. Mentor clicks "Accept" on pending request
2. **Result**: 
   - Connection created
   - Mentor sees mentee in "My Mentees"
   - Mentee sees request status as "Accepted"

### Scenario 7: Reject Request ✓
1. Mentor clicks "Reject" on pending request
2. **Result**:
   - Request status becomes "Rejected"
   - Mentee can send new request now
   - Request count decreases for mentee

---

## 📈 Database Query Examples

### Get Mentor's Pending Requests
```php
$requests = get_mentor_pending_requests($pdo, $mentor_id);
// Returns array of pending requests with mentee details
```

### Get Mentee's Request Stats
```php
$stats = get_mentee_request_summary($pdo, $mentee_id);
// Returns:
// - request_count (used)
// - max_requests (limit)
// - pending_count
// - accepted_count
// - rejected_count
```

### Get Available Mentors
```php
$mentors = get_available_mentors_for_mentee($pdo, $mentee_id);
// Returns mentors that:
// - Are from different domain
// - Don't have pending request from this mentee
// - Sorted by verified, rating, capacity
```

---

## 🐛 Common Issues & Solutions

### Issue: "Database connection failed"
**Solution**: Check credentials in `config/db.php`

### Issue: "Mentor is currently fully booked" appears for all mentors
**Solution**: 
- Run: `SELECT * FROM mentor_profiles`
- Check that `max_mentees` is set to 2
- Verify `current_mentees` count is correct

### Issue: Can send more than 2 requests
**Solution**:
- Check `mentee_profiles.max_requests` is 2
- Verify `request_count` is being incremented
- Check validation is being called in API

### Issue: Same domain requests allowed
**Solution**:
- Verify both profiles exist
- Check `are_different_domains()` is being called
- Verify domain_id values in database

### Issue: Can request self
**Solution**:
- Check `if ($mentee_id == $mentor_id)` validation
- Verify mentor/mentee IDs are different

---

## 🔍 Debugging Tips

### Enable Error Logging
```php
// In config/requests.php - errors are logged
error_log('Database error: ' . $e->getMessage());
```

### Check Request Status
```sql
SELECT * FROM mentor_mentee_requests 
WHERE mentee_id = ? OR mentor_id = ?;
```

### Verify Capacities
```sql
SELECT 
    u.name, 
    mp.current_mentees, 
    mp.max_mentees,
    (mp.current_mentees >= mp.max_mentees) as is_full
FROM mentor_profiles mp
JOIN users u ON mp.user_id = u.id;
```

### Check Connections
```sql
SELECT 
    CONCAT(m.username, ' <- -> ', n.username) as connection,
    mmc.status,
    mmc.started_at
FROM mentor_mentee_connections mmc
JOIN users m ON mmc.mentor_id = m.id
JOIN users n ON mmc.mentee_id = n.id;
```

---

## 📞 Support

**Documentation**: See [PHASE2.md](PHASE2.md) for comprehensive documentation  
**Database Setup**: See [phase2_setup.sql](phase2_setup.sql)  
**Integration**: See function comments in [config/requests.php](config/requests.php)  

---

## ✨ What's Ready for Phase 3

With Phase 2 complete, Phase 3 can now implement:
- ✅ Messaging system (connections exist)
- ✅ Session scheduling (connections exist)
- ✅ Ratings & reviews (mentor_profiles.rating field)
- ✅ Progress tracking (connections exist)
- ✅ Email notifications (user emails available)
- ✅ Analytics dashboard (all data in place)

**The foundation is rock-solid! 🚀**

---

**Status**: ✅ COMPLETE  
**Files Created**: 11  
**Functions**: 15+  
**Lines of Code**: 2500+  
**Database Tables**: 5  
**Validation Rules**: 5  
**API Endpoints**: 3  
**UI Pages**: 5  

**Phase 2 Complete!**
