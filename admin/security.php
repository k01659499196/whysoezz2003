<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

// Read security logs
$log_file = __DIR__ . '/../storage/security_log.txt';
$security_logs = [];

if (file_exists($log_file)) {
    $logs = array_reverse(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    $security_logs = array_slice($logs, 0, 100); // Last 100 entries
}

// Read banned IPs
$ban_file = __DIR__ . '/../storage/banned_ips.json';
$banned_ips = [];

if (file_exists($ban_file)) {
    $banned_ips = json_decode(file_get_contents($ban_file), true) ?: [];
}

// Handle unban
if (isset($_GET['unban'])) {
    $ip_to_unban = $_GET['unban'];
    if (isset($banned_ips[$ip_to_unban])) {
        unset($banned_ips[$ip_to_unban]);
        file_put_contents($ban_file, json_encode($banned_ips, JSON_PRETTY_PRINT));
        header('Location: security.php?success=unbanned');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard - Admin</title>
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
        <a href="/imperium-shop/admin/users.php" class="sidebar-item">USERS</a>
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item">SETTINGS</a>
        <a href="/imperium-shop/admin/security.php" class="sidebar-item active">SECURITY</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
    </aside>

    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">🛡️ SECURITY DASHBOARD</h1>
        </header>

        <div class="container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">IP unbanned successfully!</div>
            <?php endif; ?>

            <!-- BANNED IPS -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">BANNED IPS (<?php echo count($banned_ips); ?>)</div>
                
                <?php if (!empty($banned_ips)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>IP ADDRESS</th>
                                <th>BANNED AT</th>
                                <th>EXPIRES</th>
                                <th>REASON</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banned_ips as $ip => $data): ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: 700;"><?php echo htmlspecialchars($ip); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $data['banned_at']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $data['expires']); ?></td>
                                    <td style="color: var(--danger);"><?php echo htmlspecialchars($data['reason']); ?></td>
                                    <td>
                                        <a href="?unban=<?php echo urlencode($ip); ?>" class="btn btn-success" style="padding: 6px 12px; font-size: 11px;" onclick="return confirm('Unban this IP?')">UNBAN</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="padding: 40px; text-align: center; color: var(--text-secondary);">No banned IPs</p>
                <?php endif; ?>
            </div>

            <!-- SECURITY LOGS -->
            <div class="card">
                <div class="card-header">SECURITY LOGS (Last 100)</div>
                
                <?php if (!empty($security_logs)): ?>
                    <div style="max-height: 600px; overflow-y: auto; font-family: monospace; font-size: 12px; padding: 20px; background: var(--bg-card); border-radius: 10px;">
                        <?php foreach ($security_logs as $log): ?>
                            <div style="padding: 8px; margin-bottom: 5px; background: rgba(255, 255, 255, 0.02); border-radius: 4px; white-space: nowrap; overflow-x: auto;">
                                <?php echo htmlspecialchars($log); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="padding: 40px; text-align: center; color: var(--text-secondary);">No security logs</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
