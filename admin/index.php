<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

// Get statistics
$stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'],
    'total_products' => $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'],
    'total_orders' => $db->fetchOne("SELECT COUNT(*) as count FROM orders")['count'],
    'total_revenue' => $db->fetchOne("SELECT SUM(price) as total FROM orders WHERE status = 'completed'")['total'] ?? 0,
    'pending_deposits' => $db->fetchOne("SELECT COUNT(*) as count FROM deposits WHERE status = 'pending'")['count']
];

// Get recent orders
$recent_orders = $db->fetchAll(
    "SELECT o.*, u.username, p.name as product_name 
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     LEFT JOIN products p ON o.product_id = p.id
     ORDER BY o.created_at DESC
     LIMIT 10"
);

// Get low stock products
$low_stock = $db->fetchAll("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="/imperium-shop/admin/" class="sidebar-item active">DASH</a>
        <a href="/imperium-shop/admin/products.php" class="sidebar-item">PRODUCTS</a>
        <a href="/imperium-shop/admin/categories.php" class="sidebar-item">CATEGORY</a>
        <a href="/imperium-shop/admin/accounts.php" class="sidebar-item">ACCOUNTS</a>
        <a href="/imperium-shop/admin/orders.php" class="sidebar-item">ORDERS</a>
        <a href="/imperium-shop/admin/deposits.php" class="sidebar-item">DEPOSITS</a>
        <a href="/imperium-shop/admin/users.php" class="sidebar-item">USERS</a>
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item">SETTINGS</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
        <a href="/imperium-shop/user/logout.php" class="sidebar-item">LOGOUT</a>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">ADMIN DASHBOARD</h1>
        </header>

        <div class="container">
            <!-- STATS GRID -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 40px;">
                <div class="card" style="text-align: center; padding: 30px;">
                    <div style="font-size: 48px; font-weight: 900; color: var(--primary); margin-bottom: 10px;"><?php echo $stats['total_users']; ?></div>
                    <div style="color: var(--text-secondary); font-size: 14px; letter-spacing: 1px;">TOTAL USERS</div>
                </div>

                <div class="card" style="text-align: center; padding: 30px;">
                    <div style="font-size: 48px; font-weight: 900; color: var(--primary); margin-bottom: 10px;"><?php echo $stats['total_products']; ?></div>
                    <div style="color: var(--text-secondary); font-size: 14px; letter-spacing: 1px;">PRODUCTS</div>
                </div>

                <div class="card" style="text-align: center; padding: 30px;">
                    <div style="font-size: 48px; font-weight: 900; color: var(--primary); margin-bottom: 10px;"><?php echo $stats['total_orders']; ?></div>
                    <div style="color: var(--text-secondary); font-size: 14px; letter-spacing: 1px;">TOTAL ORDERS</div>
                </div>

                <div class="card" style="text-align: center; padding: 30px;">
                    <div style="font-size: 36px; font-weight: 900; color: var(--success); margin-bottom: 10px;"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                    <div style="color: var(--text-secondary); font-size: 14px; letter-spacing: 1px;">REVENUE</div>
                </div>

                <div class="card" style="text-align: center; padding: 30px;">
                    <div style="font-size: 48px; font-weight: 900; color: var(--warning); margin-bottom: 10px;"><?php echo $stats['pending_deposits']; ?></div>
                    <div style="color: var(--text-secondary); font-size: 14px; letter-spacing: 1px;">PENDING DEPOSITS</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <!-- RECENT ORDERS -->
                <div class="card">
                    <div class="card-header">RECENT ORDERS</div>
                    
                    <?php if (!empty($recent_orders)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>USER</th>
                                    <th>PRODUCT</th>
                                    <th>PRICE</th>
                                    <th>DATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td style="font-weight: 700;">#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td style="color: var(--primary); font-weight: 700;"><?php echo formatCurrency($order['price']); ?></td>
                                        <td style="color: var(--text-secondary); font-size: 13px;"><?php echo formatDate($order['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="orders.php" class="btn btn-secondary">VIEW ALL ORDERS</a>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; padding: 40px; color: var(--text-secondary);">No orders yet</p>
                    <?php endif; ?>
                </div>

                <!-- LOW STOCK ALERT -->
                <div class="card">
                    <div class="card-header">LOW STOCK ALERT</div>
                    
                    <?php if (!empty($low_stock)): ?>
                        <?php foreach ($low_stock as $product): ?>
                            <div style="padding: 15px; margin-bottom: 10px; background: var(--bg-card); border-radius: 8px; border-left: 3px solid var(--warning);">
                                <div style="font-weight: 700; margin-bottom: 5px;"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div style="color: var(--warning); font-size: 13px;">Stock: <?php echo $product['stock']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 40px; color: var(--text-secondary);">All products in stock</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
