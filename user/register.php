<?php
require_once '../config/config.php';
require_once '../config/database.php';

$db = new Database();
$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /imperium-shop/');
    exit;
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username exists
        $existing = $db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existing) {
            $error = 'Username or email already exists';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $db->query(
                "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')",
                [$username, $email, $hashed_password]
            );
            
            $success = 'Registration successful! You can now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
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
            
            <!-- REGISTER CARD -->
            <div class="card" style="padding: 45px;">
                <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 30px; text-align: center; letter-spacing: 3px; text-transform: uppercase;">
                    CREATE ACCOUNT
                </h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <a href="login.php" style="color: var(--success); text-decoration: underline; font-weight: 700;">Login now</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>USERNAME</label>
                        <input type="text" name="username" class="form-control" required autofocus value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>EMAIL</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>PASSWORD</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>CONFIRM PASSWORD</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%; margin-top: 20px; padding: 18px;">
                        REGISTER
                    </button>
                </form>
                
                <div style="text-align: center; margin-top: 30px; padding-top: 30px; border-top: 1px solid var(--border);">
                    <p style="color: var(--text-secondary); font-size: 14px;">
                        Already have an account? 
                        <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 700;">
                            Login here
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
