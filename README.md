# Mentor Connect - Authentication System Setup Guide

## Overview
This is Phase 1 of the Mentor-Mentee Management System. This phase implements a secure authentication system with role-based access control.

## Project Structure
```
mentor_connect/
├── config/
│   ├── db.php              # Database connection (PDO)
│   └── auth.php            # Authentication helper functions
├── admin/
│   └── dashboard.php       # Admin dashboard
├── mentor/
│   └── dashboard.php       # Mentor dashboard
├── mentee/
│   └── dashboard.php       # Mentee dashboard
├── login.php               # Login page
├── logout.php              # Logout functionality
├── index.php               # Home page (redirects based on auth)
├── unauthorized.php        # Unauthorized access page
└── README.md               # This file
```

## Database Requirements

### Users Table Schema
The system expects a `users` table in the `mentors_connect` database with the following structure:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mentor', 'mentee') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(username),
    INDEX(email),
    INDEX(role),
    INDEX(status)
);
```

### Insert Test Users
Run the following SQL queries to create test users for authentication testing:

```sql
-- Admin User
INSERT INTO users (username, email, password, role, status) VALUES (
    'admin',
    'admin@mentorconnect.com',
    '$2y$12$NixWe7ZrwFZgnyy7lZ8h.uVxvI8WNq8X5Q7KZqZ5K5K5K5K5K5K5K',
    'admin',
    'active'
);

-- Mentor User
INSERT INTO users (username, email, password, role, status) VALUES (
    'mentor1',
    'mentor1@mentorconnect.com',
    '$2y$12$NixWe7ZrwFZgnyy7lZ8h.uVxvI8WNq8X5Q7KZqZ5K5K5K5K5K5K5K',
    'mentor',
    'active'
);

-- Mentee User
INSERT INTO users (username, email, password, role, status) VALUES (
    'mentee1',
    'mentee1@mentorconnect.com',
    '$2y$12$NixWe7ZrwFZgnyy7lZ8h.uVxvI8WNq8X5Q7KZqZ5K5K5K5K5K5K5K',
    'mentee',
    'active'
);
```

### Test Credentials
- **Admin Login**: username: `admin`, password: `admin123`
- **Mentor Login**: username: `mentor1`, password: `mentor123`
- **Mentee Login**: username: `mentee1`, password: `mentee123`

## Installation & Setup

### 1. Database Setup
1. Create the `mentors_connect` database in MySQL
2. Run the CREATE TABLE query above
3. Insert the test users using the SQL queries provided above

### 2. Configuration
1. Update database credentials in `config/db.php` if different from defaults:
   - DB_HOST: localhost
   - DB_USER: root
   - DB_PASS: (empty)
   - DB_NAME: mentors_connect

### 3. Access the Application
1. Navigate to `http://localhost/mentor_connect/login.php`
2. Use one of the test credentials to login

## Features Implemented

### Security Features
- ✅ PDO prepared statements for SQL injection prevention
- ✅ Password hashing using bcrypt (password_hash with BCRYPT algorithm)
- ✅ Password verification using password_verify
- ✅ Session-based authentication
- ✅ Session ID regeneration after login
- ✅ HTTPOnly and Secure cookie flags
- ✅ CSRF protection through session validation
- ✅ Role-based access control (RBAC)
- ✅ Protected routes with require_role() function

### Authentication System
- ✅ Secure login form with bootstrap 5 UI
- ✅ User authentication with username/email and password
- ✅ Session management with user data
- ✅ Logout functionality with session destruction
- ✅ Remember user information in session
- ✅ IP address logging for security audit

### Dashboard System
- ✅ Role-based dashboards:
  - Admin Dashboard (`/admin/dashboard.php`)
  - Mentor Dashboard (`/mentor/dashboard.php`)
  - Mentee Dashboard (`/mentee/dashboard.php`)
- ✅ Bootstrap 5 responsive UI
- ✅ Sidebar navigation menus
- ✅ User info display in topbar
- ✅ Quick logout button

### Helper Functions (in config/auth.php)
- `start_session()` - Initialize secure session
- `require_login()` - Check if user is logged in
- `require_role($role)` - Check if user has specific role
- `hash_password($password)` - Hash password using bcrypt
- `verify_password($password, $hash)` - Verify password
- `authenticate_user($pdo, $username, $password)` - Authenticate user
- `logout_user()` - Destroy session and logout
- `is_logged_in()` - Check if user is logged in
- `get_user_role()` - Get current user's role
- `get_user_id()` - Get current user's ID
- `redirect_to_dashboard()` - Redirect to role-based dashboard
- `redirect_to_login()` - Redirect to login page
- `sanitize_input($input)` - Sanitize user input
- `validate_email($email)` - Validate email format

## Usage Examples

### Protecting Pages with Role-Based Access
```php
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Only admin can access this page
require_role('admin');

// Your page content here
?>
```

### Checking if User is Logged In
```php
<?php
if (is_logged_in()) {
    echo "User is logged in";
    echo "Role: " . get_user_role();
} else {
    redirect_to_login();
}
?>
```

### Getting User Information
```php
<?php
$user_id = get_user_id();
$user_role = get_user_role();
$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>
```

## File Descriptions

### config/db.php
- Database connection using PDO
- Error handling with PDOException
- Configured with UTF-8 charset
- Prepared statements enabled

### config/auth.php
- All authentication helper functions
- Session management utilities
- Password hashing and verification
- User database queries
- Security and sanitization functions

### login.php
- Clean and responsive login form
- Bootstrap 5 UI with gradient background
- Error and success message display
- Automatic redirect if already logged in
- Test credentials displayed for reference

### admin/dashboard.php, mentor/dashboard.php, mentee/dashboard.php
- Role-specific dashboards
- Sidebar navigation menus
- User information display
- Statistics and card layouts
- Bootstrap 5 responsive design

### logout.php
- Session destruction
- User logout
- Redirect to login page

### index.php
- Entry point for the application
- Redirects to dashboard if logged in
- Redirects to login if not authenticated

### unauthorized.php
- Custom error page for unauthorized access
- Displays when user tries to access restricted role

## Security Considerations

1. **Password Security**
   - Passwords are hashed using bcrypt with cost factor 12
   - Never stored in plain text
   - Verified using password_verify function

2. **Session Security**
   - Session IDs are regenerated after login
   - HTTPOnly flag prevents JavaScript access
   - Secure flag enabled (configure for HTTPS)
   - SameSite policy set to Strict

3. **SQL Injection Prevention**
   - All queries use prepared statements with PDO
   - User input is parameterized

4. **Access Control**
   - Role-based access control on all protected pages
   - require_role() function enforces role checks
   - Direct URL access without authentication is prevented

5. **Input Validation**
   - User inputs are sanitized and validated
   - Email validation using filter_var
   - XSS protection through htmlspecialchars

## Next Steps (Phase 2)
- Mentor management system
- Mentee management system
- Mentor-mentee matching
- Messaging system
- Profile management
- Session scheduling

## Support
For issues or questions, please check the database connection and ensure the users table exists with the correct schema.

---

**Created**: February 17, 2026
**Version**: 1.0 - Phase 1: Authentication System
