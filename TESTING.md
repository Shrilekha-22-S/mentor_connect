# Testing Checklist - Mentor Connect Authentication System

Use this checklist to verify all authentication features are working correctly.

## Pre-Testing Setup
- [ ] Database `mentors_connect` created
- [ ] `users` table created with correct schema
- [ ] Test users inserted (admin, mentor1, mentee1)
- [ ] `config/db.php` credentials verified
- [ ] Application accessible at `http://localhost/mentor_connect/`

---

## Login Page Tests

### Basic Login Functionality
- [ ] Login page loads at `/login.php`
- [ ] Page has username and password fields
- [ ] Submit button works
- [ ] Error message displays for invalid credentials
- [ ] Success message displays for valid login

### Admin Login
- [ ] Login with username: `admin`, password: `admin123`
- [ ] Redirects to `/admin/dashboard.php`
- [ ] User info displays correctly in topbar

### Mentor Login
- [ ] Login with username: `mentor1`, password: `mentor123`
- [ ] Redirects to `/mentor/dashboard.php`
- [ ] User info displays correctly in topbar

### Mentee Login
- [ ] Login with username: `mentee1`, password: `mentee123`
- [ ] Redirects to `/mentee/dashboard.php`
- [ ] User info displays correctly in topbar

### Login Validation
- [ ] Empty username shows error
- [ ] Empty password shows error
- [ ] Wrong username shows "Invalid username or password"
- [ ] Wrong password shows "Invalid username or password"
- [ ] Case-sensitive username validation works

### Session Management
- [ ] Session created after successful login
- [ ] Session ID in browser cookies
- [ ] Session variables stored correctly (`user_id`, `username`, etc.)

---

## Dashboard Tests

### Admin Dashboard (`/admin/dashboard.php`)
- [ ] Page loads and displays admin content
- [ ] Sidebar menu visible with correct options
- [ ] User greeting shows "Welcome, admin!"
- [ ] Topbar shows "Admin Dashboard"
- [ ] Statistics cards display

### Mentor Dashboard (`/mentor/dashboard.php`)
- [ ] Page loads and displays mentor content
- [ ] Sidebar menu visible with mentor-specific options
- [ ] User greeting shows "Welcome, mentor1!"
- [ ] Topbar shows "Mentor Dashboard"
- [ ] Mentor-specific statistics cards display

### Mentee Dashboard (`/mentee/dashboard.php`)
- [ ] Page loads and displays mentee content
- [ ] Sidebar menu visible with mentee-specific options
- [ ] User greeting shows "Welcome, mentee1!"
- [ ] Topbar shows "Mentee Dashboard"
- [ ] Mentee-specific statistics cards display

### Dashboard Navigation
- [ ] Sidebar links are clickable
- [ ] Dashboard links have proper styling
- [ ] Responsive design works on mobile
- [ ] Bootstrap 5 styling applied correctly

---

## Role-Based Access Control Tests

### Admin Access Control
- [ ] Admin can access `/admin/dashboard.php`
- [ ] Admin cannot access `/mentor/dashboard.php` → shows "Unauthorized Access"
- [ ] Admin cannot access `/mentee/dashboard.php` → shows "Unauthorized Access"

### Mentor Access Control
- [ ] Mentor can access `/mentor/dashboard.php`
- [ ] Mentor cannot access `/admin/dashboard.php` → shows "Unauthorized Access"
- [ ] Mentor cannot access `/mentee/dashboard.php` → shows "Unauthorized Access"

### Mentee Access Control
- [ ] Mentee can access `/mentee/dashboard.php`
- [ ] Mentee cannot access `/admin/dashboard.php` → shows "Unauthorized Access"
- [ ] Mentee cannot access `/mentor/dashboard.php` → shows "Unauthorized Access"

---

## Direct URL Access Protection Tests

### Without Login
- [ ] Accessing `/admin/dashboard.php` redirects to `/login.php`
- [ ] Accessing `/mentor/dashboard.php` redirects to `/login.php`
- [ ] Accessing `/mentee/dashboard.php` redirects to `/login.php`
- [ ] Accessing `/index.php` redirects to `/login.php`

### With Login (Session Valid)
- [ ] After login, dashboard URLs are accessible
- [ ] Session persists across page navigation
- [ ] Session data remains valid

### With Expired Session
- [ ] Manual session destruction redirects to login on next action
- [ ] Accessing dashboard with invalid session redirects to login

---

## Logout Tests

### Logout Functionality
- [ ] Logout button visible in topbar
- [ ] Logout button works (clicks successfully)
- [ ] Redirects to login page after logout
- [ ] Displays logout message (optional)

### Session Destruction
- [ ] Session variables cleared after logout
- [ ] Browser cookies cleared
- [ ] Cannot access dashboard after logout
- [ ] Manual URL access to dashboard redirects to login

### Multiple Role Logout
- [ ] Admin can logout successfully
- [ ] Mentor can logout successfully
- [ ] Mentee can logout successfully

---

## Security Tests

### Password Hashing
- [ ] Passwords stored as bcrypt hashes in database
- [ ] Plain text passwords not accepted
- [ ] Hash verification works with password_verify()

### Prepared Statements
- [ ] SQL queries use prepared statements (no concatenation)
- [ ] Input parameters properly bound
- [ ] SQL injection attempts fail gracefully

### Input Validation
- [ ] Username sanitized (XSS prevention)
- [ ] Email validated with filter_var()
- [ ] Special characters handled correctly

### Session Security
- [ ] Session ID regenerates after login
- [ ] HTTPOnly flag set on cookies
- [ ] Secure flag consideration for HTTPS
- [ ] SameSite policy enforced

---

## UI/UX Tests

### Bootstrap 5 Styling
- [ ] Login page styled with Bootstrap 5
- [ ] Dashboards styled with Bootstrap 5
- [ ] Gradients and colors applied correctly
- [ ] Icons from Bootstrap Icons library display

### Responsive Design
- [ ] Layout works on desktop (1920px width)
- [ ] Layout works on tablet (768px width)
- [ ] Layout works on mobile (375px width)
- [ ] Sidebar collapses on small screens (if implemented)

### Accessibility
- [ ] Form labels properly associated with inputs
- [ ] Input fields have proper placeholders
- [ ] Error messages clearly visible
- [ ] Buttons have clear labels

### Navigation
- [ ] All navigation links work correctly
- [ ] Current page highlighted in menu
- [ ] Logout link available on all pages
- [ ] Back/forward browser buttons work

---

## Error Handling Tests

### Database Connection Errors
- [ ] Appropriate error message if database unavailable
- [ ] Error logged but user doesn't see technical details
- [ ] Application doesn't crash on DB error

### Invalid Credentials
- [ ] Generic error message (doesn't reveal if user exists)
- [ ] Multiple failed attempts handled
- [ ] User can retry login

### Unauthorized Access
- [ ] Unauthorized page displays cleanly
- [ ] Back to login link works
- [ ] Error is logged for audit

---

## Cross-Browser Testing

- [ ] Works in Chrome/Chromium
- [ ] Works in Firefox
- [ ] Works in Safari
- [ ] Works in Edge
- [ ] Mobile browser compatibility verified

---

## Performance Tests

- [ ] Login page loads in < 2 seconds
- [ ] Dashboard pages load in < 2 seconds
- [ ] No console errors in browser
- [ ] No PHP warnings or notices
- [ ] Database queries are efficient

---

## Documentation Tests

- [ ] README.md is complete and accurate
- [ ] SETUP.md has clear instructions
- [ ] Code comments are present in key files
- [ ] Function documentation in auth.php is clear

---

## Integration Tests

### Complete User Journey - Admin
- [ ] Navigate to login page
- [ ] Login as admin
- [ ] See admin dashboard
- [ ] Navigate using sidebar
- [ ] Try accessing mentor dashboard → unauthorized
- [ ] Logout successfully
- [ ] Cannot access dashboard after logout

### Complete User Journey - Mentor
- [ ] Navigate to login page
- [ ] Login as mentor1
- [ ] See mentor dashboard
- [ ] Navigate using sidebar
- [ ] Try accessing admin dashboard → unauthorized
- [ ] Logout successfully
- [ ] Cannot access dashboard after logout

### Complete User Journey - Mentee
- [ ] Navigate to login page
- [ ] Login as mentee1
- [ ] See mentee dashboard
- [ ] Navigate using sidebar
- [ ] Try accessing mentor dashboard → unauthorized
- [ ] Logout successfully
- [ ] Cannot access dashboard after logout

---

## Final Verification

- [ ] All tests passed
- [ ] No critical bugs found
- [ ] System ready for Phase 2 development
- [ ] Documentation complete
- [ ] Code follows best practices
- [ ] Security measures in place

---

## Notes & Issues Found

```
[Add any issues, bugs, or observations here]
```

---

**Tested By**: _________________  
**Date**: _________________  
**Version**: 1.0  
**Status**: ☐ PASS / ☐ FAIL / ☐ NEEDS FIXES

---

If any test fails, please:
1. Document the issue above
2. Check the browser console for errors
3. Check PHP error logs in XAMPP
4. Verify database connection
5. Review SETUP.md troubleshooting section
