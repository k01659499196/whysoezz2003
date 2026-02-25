<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

$users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <aside class="sidebar">
        <a href="/imperium-shop/admin/" class="sidebar-item">DASH</a>
        <a href="/imperium-shop/admin/products.php" class="sidebar-item">PRODUCTS</a>
        <a href="/imperium-shop/admin/categories.php" class="sidebar-item">CATEGORY</a>
        <a href="/imperium-shop/admin/accounts.php" class="sidebar-item">ACCOUNTS</a>
        <a href="/imperium-shop/admin/orders.php" class="sidebar-item">ORDERS</a>
        <a href="/imperium-shop/admin/deposits.php" class="sidebar-item">DEPOSITS</a>
        <a href="/imperium-shop/admin/users.php" class="sidebar-item active">USERS</a>
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item">SETTINGS</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
    </aside>


    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">ALL USERS</h1>
        </header>

        <div class="container">
            <div class="card">
                <div class="card-header">USER LIST</div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>USERNAME</th>
                            <th>EMAIL</th>
                            <th>BALANCE</th>
                            <th>ROLE</th>
                            <th>JOINED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td style="font-weight: 700;">#<?php echo $user['id']; ?></td>
                                <td style="font-weight: 700;"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td style="font-weight: 700; color: var(--primary);"><?php echo formatCurrency($user['balance']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span style="padding: 6px 12px; background: rgba(212, 175, 55, 0.2); border: 1px solid var(--primary); border-radius: 6px; color: var(--primary); font-size: 11px; font-weight: 700;">ADMIN</span>
                                    <?php else: ?>
                                        <span style="padding: 6px 12px; background: var(--bg-card); border-radius: 6px; font-size: 11px; font-weight: 700;">USER</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 13px;"><?php echo formatDate($user['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
