<?php
require_once '../config/config.php';
require_once '../config/database.php';

$db = new Database();
$error = '';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /imperium-shop/');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE username = ? OR email = ?",
            [$username, $username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: /imperium-shop/admin/');
            } else {
                header('Location: /imperium-shop/');
            }
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: var(--bg-primary);">
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="width: 100%; max-width: 480px;">
            <!-- LOGO HEADER -->
            <div style="text-align: center; margin-bottom: 50px;">
                <h1 style="font-size: 64px; font-weight: 900; letter-spacing: 10px; margin-bottom: 12px; background: linear-gradient(135deg, var(--primary) 0%, #f4e4c1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    <?php echo SITE_NAME; ?>
                </h1>
                <p style="color: var(--text-secondary); font-size: 14px; letter-spacing: 3px; text-transform: uppercase; font-weight: 500;">
                    <?php echo SITE_TAGLINE; ?>
                </p>
            </div>
            
            <!-- LOGIN CARD -->
            <div class="card" style="padding: 45px;">
                <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 30px; text-align: center; letter-spacing: 3px; text-transform: uppercase;">
                    LOGIN
                </h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>USERNAME OR EMAIL</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label>PASSWORD</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%; margin-top: 20px; padding: 18px;">
                        LOGIN
                    </button>
                </form>
                
                <div style="text-align: center; margin-top: 30px; padding-top: 30px; border-top: 1px solid var(--border);">
                    <p style="color: var(--text-secondary); font-size: 14px;">
                        Don't have an account? 
                        <a href="register.php" style="color: var(--primary); text-decoration: none; font-weight: 700;">
                            Register here
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- BACK TO HOME -->
            <div style="text-align: center; margin-top: 30px;">
                <a href="/imperium-shop/" style="color: var(--text-secondary); text-decoration: none; font-size: 14px; font-weight: 600; letter-spacing: 1px;">
                    ← BACK TO HOME
                </a>
            </div>
        </div>
    </div>
</body>
</html>
