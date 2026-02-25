<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$db = new Database();

// Get user info
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
$user_balance = $user['balance'] ?? 0;


// Get orders with account details
$orders = $db->fetchAll(
    "SELECT o.*, p.name as product_name, p.image, a.username as account_username, a.password as account_password
     FROM orders o
     LEFT JOIN products p ON o.product_id = p.id
     LEFT JOIN accounts a ON o.account_id = a.id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC",
    [$_SESSION['user_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="/imperium-shop/" class="sidebar-item">HOME</a>
        <a href="/imperium-shop/user/dashboard.php" class="sidebar-item active">ORDERS</a>
        <a href="/imperium-shop/user/deposit.php" class="sidebar-item">DEPOSIT</a>
        <a href="/imperium-shop/user/profile.php" class="sidebar-item">PROFILE</a>
        <a href="/imperium-shop/user/logout.php" class="sidebar-item" style="margin-top: auto;">LOGOUT</a>
        
        <?php if (isAdmin()): ?>
            <a href="/imperium-shop/admin/" class="sidebar-item" style="border-top: 1px solid var(--border); margin-top: 20px; padding-top: 20px;">ADMIN</a>
        <?php endif; ?>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HEADER -->
        <header class="page-header" style="padding: 40px 20px 30px; position: relative;">
            <h1 style="font-size: 48px;">MY ORDERS</h1>
            
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
            <!-- USER INFO CARD -->
            <div class="card" style="margin-bottom: 40px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; font-weight: 800; margin-bottom: 10px;">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h3>
                        <p style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <a href="deposit.php" class="btn">ADD FUNDS</a>
                </div>
            </div>

            <!-- ORDERS TABLE -->
            <div class="card">
                <div class="card-header">PURCHASE HISTORY</div>

                <?php if (!empty($orders)): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ORDER ID</th>
                                    <th>PRODUCT</th>
                                    <th>ACCOUNT DETAILS</th>
                                    <th>PRICE</th>
                                    <th>DATE</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: var(--primary);">#<?php echo $order['id']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 15px;">
                                                <?php if (!empty($order['image'])): ?>
                                                    <img src="../assets/images/products/<?php echo $order['image']; ?>" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                                                <?php endif; ?>
                                                <strong><?php echo htmlspecialchars($order['product_name']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($order['account_username']): ?>
                                                <div style="background: var(--bg-card); padding: 12px; border-radius: 8px; font-family: monospace;">
                                                    <div style="margin-bottom: 5px;">
                                                        <strong style="color: var(--primary);">Username:</strong> 
                                                        <?php echo htmlspecialchars($order['account_username']); ?>
                                                    </div>
                                                    <div>
                                                        <strong style="color: var(--primary);">Password:</strong> 
                                                        <?php echo htmlspecialchars($order['account_password']); ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--text-secondary);">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight: 700; color: var(--primary);"><?php echo formatCurrency($order['price']); ?></td>
                                        <td style="color: var(--text-secondary);"><?php echo formatDate($order['created_at']); ?></td>
                                        <td>
                                            <?php if ($order['status'] === 'completed'): ?>
                                                <span style="padding: 6px 12px; background: rgba(76, 175, 80, 0.2); border: 1px solid var(--success); border-radius: 6px; color: var(--success); font-size: 11px; font-weight: 700;">COMPLETED</span>
                                            <?php else: ?>
                                                <span style="padding: 6px 12px; background: rgba(244, 67, 54, 0.2); border: 1px solid var(--danger); border-radius: 6px; color: var(--danger); font-size: 11px; font-weight: 700;">REFUNDED</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 80px 20px; color: var(--text-secondary);">
                        <div style="font-size: 64px; margin-bottom: 20px;"></div>
                        <h3 style="font-size: 24px; margin-bottom: 10px; font-weight: 800;">NO ORDERS YET</h3>
                        <p style="margin-bottom: 30px;">Start shopping to see your orders here</p>
                        <a href="/imperium-shop/" class="btn">BROWSE PRODUCTS</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
