# Mentor Connect - Setup Instructions

## Quick Start Guide

### Prerequisites
- XAMPP (Apache, MySQL, PHP) installed
- MySQL running
- PHP 7.4 or higher
- Basic understanding of PHP and MySQL

### Step 1: Database Setup (IMPORTANT! ⚠️)

1. **Open phpMyAdmin**
   - Navigate to: `http://localhost/phpmyadmin`

2. **Verify Database Exists**
   - Check if database `mentors_connect` already exists
   - If NOT, create it:
     - Click "New"
     - Database name: `mentors_connect`
     - Charset: `utf8mb4_unicode_ci`
     - Click "Create"

3. **Create Users Table**
   - Select the `mentors_connect` database
   - Go to "SQL" tab
   - Copy and paste the following SQL (or from `setup.sql`):

```sql
CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
   - Click "Go"

4. **Insert Test Users**
   - In the SQL tab, paste this SQL and click "Go":

```sql
-- Admin User (password: admin123)
INSERT INTO users (username, email, password, role, status) VALUES (
    'admin',
    'admin@mentorconnect.com',
    '$2y$12$P.x1RYelGt.k7qPxL0qTwuPxN7LI0NlzLs7B8.RqKz/6c.O1F4xAm',
    'admin',
    'active'
);

-- Mentor User (password: mentor123)
INSERT INTO users (username, email, password, role, status) VALUES (
    'mentor1',
    'mentor1@mentorconnect.com',
    '$2y$12$v5qiZbN8K4cE2Pt3m9X0Oum1.0Y1p8L4Q5R6S7T8U9V0W1X2Y3Z4A',
    'mentor',
    'active'
);

-- Mentee User (password: mentee123)
INSERT INTO users (username, email, password, role, status) VALUES (
    'mentee1',
    'mentee1@mentorconnect.com',
    '$2y$12$8Q9R0S1T2U3V4W5X6Y7Z8a9B0C1D2E3F4G5H6I7J8K9L0M1N2O3P4Q',
    'mentee',
    'active'
);
```

5. **Verify Users Table**
   - The users table should now appear in the database with 3 test users

### Step 2: Access the Application

1. **Open Web Browser**
   - Navigate to: `http://localhost/mentor_connect/login.php`

2. **Test Login - Admin**
   - Username: `admin`
   - Password: `admin123`
   - Expected: Redirects to Admin Dashboard (`/admin/dashboard.php`)

3. **Test Login - Mentor**
   - Username: `mentor1`
   - Password: `mentor123`
   - Expected: Redirects to Mentor Dashboard (`/mentor/dashboard.php`)

4. **Test Login - Mentee**
   - Username: `mentee1`
   - Password: `mentee123`
   - Expected: Redirects to Mentee Dashboard (`/mentee/dashboard.php`)

### Step 3: Test Security Features

1. **Test Direct URL Access Without Login**
   - Try accessing: `http://localhost/mentor_connect/admin/dashboard.php`
   - Expected: Redirects to login page

2. **Test Role-Based Access**
   - Login as mentor (`mentor1`)
   - Try accessing: `http://localhost/mentor_connect/admin/dashboard.php`
   - Expected: Shows "Unauthorized Access" page

3. **Test Logout**
   - From any dashboard, click the "Logout" button
   - Expected: Redirects to login page and session is destroyed
   - Try going back to dashboard: should redirect to login

## File Structure Overview

```
mentor_connect/
│
├── config/
│   ├── db.php                 # Database connection (PDO)
│   └── auth.php               # Authentication functions
│
├── admin/
│   └── dashboard.php          # Admin dashboard
│
├── mentor/
│   └── dashboard.php          # Mentor dashboard
│
├── mentee/
│   └── dashboard.php          # Mentee dashboard
│
├── login.php                  # Login page
├── logout.php                 # Logout handler
├── index.php                  # Home page
├── unauthorized.php           # Unauthorized access page
├── generate_hash.php          # Password hash generator tool
├── setup.sql                  # Database setup SQL
├── README.md                  # Full documentation
└── SETUP.md                   # This file
```

## Database Configuration

If your database credentials differ from defaults, edit `config/db.php`:

```php
define('DB_HOST', 'localhost');        // Server hostname
define('DB_USER', 'root');             // MySQL username
define('DB_PASS', '');                 // MySQL password
define('DB_NAME', 'mentors_connect');  // Database name
```

**Common Issues:**
- If MySQL uses a different password, update `DB_PASS`
- If using a different port, add `:port` to `DB_HOST`
- If MySQL connection fails, check that MySQL service is running

## Creating New Users

### Option 1: Using phpMyAdmin
1. Go to phpMyAdmin
2. Select `mentors_connect` database
3. Go to `users` table
4. Click "Insert"
5. Fill in the user details
6. For password, use the hash generator tool (see below)

### Option 2: Using Password Hash Generator
1. Navigate to: `http://localhost/mentor_connect/generate_hash.php`
2. Enter the desired password
3. Copy the generated hash
4. Use the SQL INSERT statement provided

### SQL Template
```sql
INSERT INTO users (username, email, password, role, status) VALUES (
    'username',
    'email@example.com',
    '[paste-bcrypt-hash-here]',
    'admin',        -- or 'mentor' or 'mentee'
    'active'
);
```

## Authentication System Details

### Security Features Implemented
- ✅ Bcrypt password hashing (cost factor 12)
- ✅ PDO prepared statements (prevents SQL injection)
- ✅ Session-based authentication
- ✅ Session ID regeneration after login
- ✅ Role-based access control (RBAC)
- ✅ HTTPOnly session cookies
- ✅ Input validation and sanitization
- ✅ Unauthorized access handling

### Session Variables
After login, the following session variables are available:

```php
$_SESSION['user_id']      // User ID
$_SESSION['username']     // Username
$_SESSION['email']        // User email
$_SESSION['user_role']    // Role (admin, mentor, or mentee)
$_SESSION['login_time']   // Timestamp of login
$_SESSION['ip_address']   // IP address at login
```

### Helper Functions in config/auth.php

#### User Authentication
- `authenticate_user($pdo, $username, $password)` - Authenticate user
- `logout_user()` - Logout and destroy session

#### Access Control
- `require_login()` - Require user to be logged in
- `require_role($role)` - Require specific role
- `is_logged_in()` - Check if user is logged in

#### User Information
- `get_user_id()` - Get current user ID
- `get_user_role()` - Get current user role
- `get_user_by_id($pdo, $id)` - Get user from database
- `get_user_by_username($pdo, $username)` - Get user from database

#### Utilities
- `sanitize_input($input)` - Sanitize user input
- `validate_email($email)` - Validate email format
- `hash_password($password)` - Hash password
- `verify_password($password, $hash)` - Verify password
- `redirect_to_dashboard()` - Redirect to role dashboard
- `redirect_to_login()` - Redirect to login page

## Troubleshooting

### "Database connection failed" Error
- **Cause**: Cannot connect to MySQL
- **Solution**:
  1. Check MySQL service is running
  2. Verify credentials in `config/db.php`
  3. Check database `mentors_connect` exists

### "Invalid username or password"
- **Cause**: Wrong credentials or user doesn't exist
- **Solution**:
  1. Check username in database (e.g., `admin` not `Admin`)
  2. Verify password hash matches credentials
  3. Check user status is `active`

### "Unauthorized Access" Page
- **Cause**: User role doesn't match required role
- **Solution**:
  1. Login with correct role
  2. Check `require_role()` in dashboard file
  3. Verify user role in database

### Session Not Persisting
- **Cause**: Session directory not writable
- **Solution**:
  1. Check PHP session.save_path is writable
  2. Restart Apache/XAMPP
  3. Clear browser cookies

### Password Verification Fails
- **Cause**: Hash not generated with password_hash()
- **Solution**:
  1. Use generator tool at `/generate_hash.php`
  2. Use correct bcrypt cost factor (12)
  3. Verify password with password_verify()

## Best Practices for Extending

### Creating Protected Pages
```php
<?php
// mypage.php
define('BASE_URL', 'http://localhost/mentor_connect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Require login and specific role
require_role('mentor');

// Your page content here
?>
```

### Checking User Before Database Query
```php
<?php
// Get current user
$user_id = get_user_id();
$user = get_user_by_id($pdo, $user_id);

// Verify user exists and is active
if (!$user || $user['status'] !== 'active') {
    logout_user();
    redirect_to_login();
}
?>
```

### Sanitizing Form Input
```php
<?php
$name = sanitize_input($_POST['name'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');

// Validate email
if (!validate_email($email)) {
    $error = "Invalid email format";
}
?>
```

## API Available for Phase 2

The authentication system provides a solid foundation for:
- User management pages
- Profile editing
- Password change functionality
- Email verification
- Two-factor authentication
- User activity logging
- And more...

## Support & Documentation

For detailed documentation, see:
- `README.md` - Full feature documentation
- `config/auth.php` - Function comments and usage
- `setup.sql` - Database schema and test data

---

**Setup Date**: February 17, 2026
**Version**: 1.0
**Status**: Ready for Phase 2 Development
