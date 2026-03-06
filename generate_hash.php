<?php
/**
 * Password Hash Generator
 * Use this file to generate bcrypt password hashes for creating new users
 * 
 * Usage: php generate_hash.php <password>
 * or access in browser: http://localhost/mentor_connect/generate_hash.php?password=<password>
 * 
 * WARNING: Only for internal use during development. Don't expose in production!
 */

// Check if password is provided
$password = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['password'])) {
    $password = $_GET['password'];
} elseif (!empty($argv[1])) {
    $password = $argv[1];
}

// HTML for web access
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
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
        .container-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .form-control, .form-control-plaintext {
            border-radius: 5px;
            border: 2px solid #e0e0e0;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-generate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-generate:hover {
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
        .copy-btn {
            background: #28a745;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .copy-btn:hover {
            background: #218838;
        }
        .hash-output {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            word-wrap: break-word;
            margin-top: 10px;
            position: relative;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-box">
        <h1>🔐 Password Hash Generator</h1>
        
        <div class="warning">
            <strong>⚠️ Warning:</strong> This tool is for internal development use only. 
            Do not expose this file in production environments.
        </div>

        <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group">
                <label for="password" class="form-label">Enter Password to Hash</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    placeholder="Enter password"
                    value="<?php echo htmlspecialchars($password ?? ''); ?>"
                    required
                >
            </div>

            <button type="submit" class="btn-generate">Generate bcrypt Hash</button>
        </form>

        <?php if ($password !== null && $password !== ''): ?>
            <?php
                // Generate bcrypt hash
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Verify the hash works
                $verification = password_verify($password, $hashed_password);
            ?>
            
            <div class="alert alert-success" role="alert" style="margin-top: 20px;">
                ✓ Hash generated successfully
            </div>

            <div class="form-group">
                <label class="form-label">Generated Hash (bcrypt)</label>
                <div class="hash-output">
                    <?php echo htmlspecialchars($hashed_password); ?>
                    <button type="button" class="copy-btn" onclick="copyToClipboard(this)">Copy</button>
                </div>
                <small class="form-text text-muted" style="display: block; margin-top: 10px;">
                    Use this hash in your database INSERT statements for the password field.
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Verification Status</label>
                <div class="alert <?php echo $verification ? 'alert-info' : 'alert-danger'; ?>">
                    <?php echo $verification ? '✓ Password verification successful' : '✗ Password verification failed'; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Sample SQL INSERT</label>
                <div class="hash-output" style="background: #e8f5e9;">
                    <code>
INSERT INTO users (username, email, password, role, status) VALUES (
    'username',
    'email@example.com',
    '<?php echo htmlspecialchars($hashed_password); ?>',
    'role',
    'active'
);
                    </code>
                    <button type="button" class="copy-btn" onclick="copyToClipboard(this)">Copy SQL</button>
                </div>
            </div>

            <hr style="margin: 20px 0;">

            <h5 style="color: #667eea; margin-bottom: 15px;">Test Credentials for This Hash:</h5>
            <table class="table table-sm">
                <tbody>
                    <tr>
                        <th width="30%">Password</th>
                        <td><code><?php echo htmlspecialchars($password); ?></code></td>
                    </tr>
                    <tr>
                        <th>Bcrypt Cost</th>
                        <td>12 (recommended)</td>
                    </tr>
                    <tr>
                        <th>Hash Algorithm</th>
                        <td>PASSWORD_BCRYPT</td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard(btn) {
            const text = btn.previousElementSibling.textContent || btn.parentElement.textContent;
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(() => {
                btn.textContent = originalText;
            }, 2000);
        }
    </script>
</body>
</html>
