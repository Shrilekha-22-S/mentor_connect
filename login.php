<?php
/**
 * Login Page
 */

// Define base URL
define('BASE_URL', 'http://localhost/mentor_connect');

// Include database and auth files
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Start session
start_session();

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect_to_dashboard();
}

// Initialize error message
$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        // Attempt to authenticate user
        $result = authenticate_user($pdo, $email, $password);
        
        // if ($result['success']) {
        //     redirect_to_dashboard();
        // } 
        if ($result['success']) {
         header("Location: /mentor_connect/mentee/dashboard.php");
         exit();
       }


        
        else {
            $error = $result['message'];
        }
       

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Connect - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #667eea;
            font-weight: bold;
            font-size: 28px;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
            border: none;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .form-text {
            color: #666;
            font-size: 13px;
            margin-top: 15px;
            text-align: center;
        }
        .form-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .form-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Mentor Connect</h1>
            <p>Secure Login</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-login">Login</button>
        </form>

        <div class="form-text">
            Demo Credentials:<br>
            Admin: <code>admin@mentorconnect.com</code> / <code>admin123</code><br>
            Mentor: <code>mentor1@mentorconnect.com</code> / <code>mentor123</code><br>
            Mentee: <code>mentee1@mentorconnect.com</code> / <code>mentee123</code>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
