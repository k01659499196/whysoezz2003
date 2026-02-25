<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

$orders = $db->fetchAll(
    "SELECT o.*, u.username, u.email, p.name as product_name, a.username as account_username
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     LEFT JOIN products p ON o.product_id = p.id
     LEFT JOIN accounts a ON o.account_id = a.id
     ORDER BY o.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <aside class="sidebar">
        <a href="/imperium-shop/admin/" class="sidebar-item">DASH</a>
        <a href="/imperium-shop/admin/products.php" class="sidebar-item">PRODUCTS</a>
        <a href="/imperium-shop/admin/categories.php" class="sidebar-item">CATEGORY</a>
        <a href="/imperium-shop/admin/accounts.php" class="sidebar-item">ACCOUNTS</a>
        <a href="/imperium-shop/admin/orders.php" class="sidebar-item active">ORDERS</a>
        <a href="/imperium-shop/admin/deposits.php" class="sidebar-item">DEPOSITS</a>
        <a href="/imperium-shop/admin/users.php" class="sidebar-item">USERS</a>
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item">SETTINGS</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
    </aside>


    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">ALL ORDERS</h1>
        </header>

        <div class="container">
            <div class="card">
                <div class="card-header">ORDER HISTORY</div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>USER</th>
                            <th>PRODUCT</th>
                            <th>ACCOUNT</th>
                            <th>PRICE</th>
                            <th>STATUS</th>
                            <th>DATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="font-weight: 700;">#<?php echo $order['id']; ?></td>
                                <td>
                                    <div style="font-weight: 700;"><?php echo htmlspecialchars($order['username']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($order['email']); ?></div>
                                </td>
                                <td style="font-weight: 700;"><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($order['account_username'] ?? 'N/A'); ?></td>
                                <td style="font-weight: 700; color: var(--primary);"><?php echo formatCurrency($order['price']); ?></td>
                                <td>
                                    <?php if ($order['status'] === 'completed'): ?>
                                        <span style="padding: 6px 12px; background: rgba(76, 175, 80, 0.2); border: 1px solid var(--success); border-radius: 6px; color: var(--success); font-size: 11px; font-weight: 700;">COMPLETED</span>
                                    <?php else: ?>
                                        <span style="padding: 6px 12px; background: rgba(244, 67, 54, 0.2); border: 1px solid var(--danger); border-radius: 6px; color: var(--danger); font-size: 11px; font-weight: 700;">REFUNDED</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 13px;"><?php echo formatDate($order['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
