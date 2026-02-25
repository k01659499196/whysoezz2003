<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$db = new Database();
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
$user_balance = $user['balance'] ?? 0;


$success = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $_SESSION['user_id']]);
        $success = 'Password changed successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <aside class="sidebar">
        <a href="/imperium-shop/" class="sidebar-item">HOME</a>
        <a href="/imperium-shop/user/dashboard.php" class="sidebar-item">ORDERS</a>
        <a href="/imperium-shop/user/deposit.php" class="sidebar-item">DEPOSIT</a>
        <a href="/imperium-shop/user/profile.php" class="sidebar-item active">PROFILE</a>
        <a href="/imperium-shop/user/logout.php" class="sidebar-item" style="margin-top: auto;">LOGOUT</a>
    </aside>

    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px; position: relative;">
            <h1 style="font-size: 48px;">MY PROFILE</h1>
            
            <!-- Cart Badge -->
            <a href="/imperium-shop/cart.php" class="cart-badge">
                <span class="cart-icon">🛒</span>
                <span class="cart-count"><?php echo getCartCount(); ?></span>
            </a>
            
            <!-- Balance Display -->
            <div class="balance-display">
                <span>Balance:</span>
                <strong><?php echo formatCurrency($user_balance); ?></strong>
            </div>
        </header>


        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <!-- USER INFO -->
                <div class="card">
                    <div class="card-header">ACCOUNT INFORMATION</div>
                    <div style="line-height: 2;">
                        <div><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></div>
                        <div><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></div>
                        <div><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></div>
                        <div><strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?></div>
                    </div>
                </div>

                <!-- CHANGE PASSWORD -->
                <div class="card">
                    <div class="card-header">CHANGE PASSWORD</div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>CURRENT PASSWORD</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>NEW PASSWORD</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>CONFIRM NEW PASSWORD</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn" style="width: 100%;">UPDATE PASSWORD</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
