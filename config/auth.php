<?php
/**
 * Authentication Helper Functions
 * Handles user authentication, session management, and security
 */

// Start secure session if not already started
// function start_session() {
//     if (session_status() === PHP_SESSION_NONE) {
//         ini_set('session.use_strict_mode', 1);
//         ini_set('session.use_only_cookies', 1);
//         // ini_set('session.cookie_httponly', 1);
//         //  ini_set('session.cookie_secure', 0); // Enable for HTTPS only
//         ini_set('session.cookie_samesite', 'Strict');
//         session_start();
//     }
// }
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}



/**
 * Redirect to login if user is not authenticated
 */
// function require_login() {
//     start_session();
//     if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
//         header('Location: ' . BASE_URL . '/login.php');
//         exit;
//     }
// }
function require_login() {
    if (!is_logged_in()) {
        header("Location: /mentor_connect/login.php");
        exit();
    }
}



/**
 * Redirect to appropriate dashboard based on role
 */
// function require_role($required_role) {
//     start_session();
    
//     if (!isset($_SESSION['user_id'])) {
//         header('Location: ' . BASE_URL . '/login.php');
//         exit;
//     }
    
//     // Map role names: admin stays admin, mentor stays mentor, mentee stays mentee
//     if (!in_array($_SESSION['user_type'], (array)$required_role)) {
//         header('Location: ' . BASE_URL . '/unauthorized.php');
//         exit;
//     }
// }
// function require_role($role) {
//     require_login();

//     if ($_SESSION['user_type'] !== $role) {
//         header("Location: /mentor_connect/login.php");
//         exit();
//     }
// }
// function require_role($role) {
//     start_session();
//     echo "<pre>";
//     print_r($_SESSION);
//     exit();
// }
function require_role($role) {
    start_session();

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header("Location: /mentor_connect/login.php");
        exit();
    }

    if ($_SESSION['user_type'] !== $role && $_SESSION['user_type'] !== 'leadership') {
        header("Location: /mentor_connect/login.php");
        exit();
    }
}



// function require_role($required_role) {
//     start_session();
    
//     if (!isset($_SESSION['user_id'])) {
//         header('Location: ' . BASE_URL . '/login.php');
//         exit;
//     }
    
//     if (!in_array($_SESSION['user_type'], (array)$required_role)) {
//         header('Location: ' . BASE_URL . '/unauthorized.php');
//         exit;
//     }
// }



/**
 * Hash password using PHP's password_hash function
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, [
        'cost' => 12
    ]);
}

/**
 * Verify password using password_verify function
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get user by email from database
 */
function get_user_by_email($pdo, $email) {
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? AND status = 1');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Get user by ID from database
 */
function get_user_by_id($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE id = ? AND status = 1');
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Authenticate user and create session
 */
function authenticate_user($pdo, $email, $password) {
    // Get user from database
    $user = get_user_by_email($pdo, $email);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Verify password
    if (!verify_password($password, $user['password_hash'])) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Password is correct, create session
    start_session();
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_type'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user_type' => $user['role']
    ];
}

/**
 * Logout user and destroy session
 */
function logout_user() {
    start_session();
    session_unset();
    session_destroy();
}

/**
 * Check if user is logged in
 */
// function is_logged_in() {
//     start_session();
//     return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
// }
function is_logged_in() {
    start_session();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}


/**
 * Get current user role
 */
function get_user_role() {
    start_session();
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current user ID
 */
// function get_user_id() {
//     start_session();
//     return $_SESSION['user_id'] ?? null;
// }
function get_user_id() {
    start_session();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Redirect to login page
 */
function redirect_to_login() {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

/**
 * Redirect to role-based dashboard
 */
// function redirect_to_dashboard() {
//     $role = get_user_role();
    
//     switch ($role) {
//         case 'admin':
//             header('Location: ' . BASE_URL . '/admin/dashboard.php');
//             break;
//         case 'mentor':
//             header('Location: ' . BASE_URL . '/mentor/dashboard.php');
//             break;
//         case 'mentee':
//             header('Location: ' . BASE_URL . '/mentee/dashboard.php');
//             break;
//         default:
//             header('Location: ' . BASE_URL . '/login.php');
//     }
//     exit;
// }

//  function redirect_to_dashboard() {
//     start_session();

//     if (!isset($_SESSION['user_type'])) {
//         header("Location: /mentor_connect/login.php");
//         exit();
//     }

//     if ($_SESSION['user_type'] === 'admin') {
//         header("Location: /mentor_connect/admin/dashboard.php");
//     } elseif ($_SESSION['user_type'] === 'mentor') {
//         header("Location: /mentor_connect/mentor/dashboard.php");
//     } elseif ($_SESSION['user_type'] === 'mentee') {
//         header("Location: /mentor_connect/mentee/dashboard.php");
//     } else {
//         header("Location: /mentor_connect/login.php");
//     }

//     exit();
// }
function redirect_to_dashboard() {
    start_session();

    if (!isset($_SESSION['user_type'])) {
        header("Location: /mentor_connect/login.php");
        exit();
    }

    if ($_SESSION['user_type'] === 'admin') {
        header("Location: /mentor_connect/admin/dashboard.php");

    } elseif ($_SESSION['user_type'] === 'mentor') {
        header("Location: /mentor_connect/mentor/dashboard.php");

    } elseif ($_SESSION['user_type'] === 'mentee') {
        header("Location: /mentor_connect/mentee/dashboard.php");

    } elseif ($_SESSION['user_type'] === 'leadership') {
        header("Location: /mentor_connect/mentee/dashboard.php"); // you can change later

    } else {
        header("Location: /mentor_connect/login.php");
    }

    exit();
}



/**
 * Sanitize user input
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>
