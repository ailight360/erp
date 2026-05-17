<?php
// Security headers for login page
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>ERP System Login</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div style="background: #ef4444; color: white; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    Invalid username or password. Try admin/admin123
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="includes/auth.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="admin" placeholder="Enter username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #6b7280;">
                Demo credentials: <strong>admin / admin123</strong>
            </p>
        </div>
    </div>

    <script>
        // Fallback client-side auth if server is not available
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if(username === 'admin' && password === 'admin123') {
                sessionStorage.setItem('user_logged_in', 'true');
                sessionStorage.setItem('username', username);
            }
        });
    </script>
</body>
</html>
