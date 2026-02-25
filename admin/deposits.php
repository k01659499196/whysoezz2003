<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

$success = '';
$error = '';

// Handle approve/reject
if (isset($_POST['action'])) {
    $deposit_id = (int)$_POST['deposit_id'];
    $action = $_POST['action'];
    $admin_note = sanitize($_POST['admin_note'] ?? '');
    
    $deposit = $db->fetchOne("SELECT * FROM deposits WHERE id = ?", [$deposit_id]);
    
    if (!$deposit) {
        $error = 'Deposit not found';
    } elseif ($deposit['status'] !== 'pending') {
        $error = 'Deposit already processed';
    } else {
        if ($action === 'approve') {
            // Start transaction
            $db->getConnection()->beginTransaction();
            
            try {
                // Update deposit status
                $db->query(
                    "UPDATE deposits SET status = 'approved', admin_note = ?, updated_at = NOW() WHERE id = ?",
                    [$admin_note, $deposit_id]
                );
                
                // Add balance to user
                $db->query(
                    "UPDATE users SET balance = balance + ? WHERE id = ?",
                    [$deposit['amount'], $deposit['user_id']]
                );
                
                // Log transaction
                $db->query(
                    "INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'deposit', ?, ?)",
                    [$deposit['user_id'], $deposit['amount'], "Deposit approved: #{$deposit_id}"]
                );
                
                $db->getConnection()->commit();
                $success = 'Deposit approved successfully!';
                
            } catch (Exception $e) {
                $db->getConnection()->rollBack();
                $error = 'Failed to approve deposit: ' . $e->getMessage();
            }
            
        } elseif ($action === 'reject') {
            $db->query(
                "UPDATE deposits SET status = 'rejected', admin_note = ?, updated_at = NOW() WHERE id = ?",
                [$admin_note, $deposit_id]
            );
            $success = 'Deposit rejected!';
        }
    }
}

// Get all deposits
$deposits = $db->fetchAll(
    "SELECT d.*, u.username, u.email 
     FROM deposits d 
     LEFT JOIN users u ON d.user_id = u.id 
     ORDER BY 
        CASE d.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        d.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Deposits - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <aside class="sidebar">
        <a href="/imperium-shop/admin/" class="sidebar-item">DASH</a>
        <a href="/imperium-shop/admin/products.php" class="sidebar-item">PRODUCTS</a>
        <a href="/imperium-shop/admin/categories.php" class="sidebar-item">CATEGORY</a>
        <a href="/imperium-shop/admin/accounts.php" class="sidebar-item">ACCOUNTS</a>
        <a href="/imperium-shop/admin/orders.php" class="sidebar-item">ORDERS</a>
        <a href="/imperium-shop/admin/deposits.php" class="sidebar-item active">DEPOSITS</a>
        <a href="/imperium-shop/admin/users.php" class="sidebar-item">USERS</a>
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item">SETTINGS</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
    </aside>


    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">MANAGE DEPOSITS</h1>
        </header>

        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">ALL DEPOSITS</div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>USER</th>
                            <th>AMOUNT</th>
                            <th>METHOD</th>
                            <th>TRANSACTION CODE</th>
                            <th>STATUS</th>
                            <th>DATE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposits as $deposit): ?>
                            <tr>
                                <td style="font-weight: 700;">#<?php echo $deposit['id']; ?></td>
                                <td>
                                    <div style="font-weight: 700;"><?php echo htmlspecialchars($deposit['username']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($deposit['email']); ?></div>
                                </td>
                                <td style="font-weight: 700; color: var(--primary); font-size: 18px;"><?php echo formatCurrency($deposit['amount']); ?></td>
                                <td><?php echo htmlspecialchars($deposit['payment_method']); ?></td>
                                <td style="font-family: monospace; background: var(--bg-card); padding: 8px; border-radius: 6px;"><?php echo htmlspecialchars($deposit['transaction_code']); ?></td>
                                <td>
                                    <?php if ($deposit['status'] === 'approved'): ?>
                                        <span style="padding: 6px 12px; background: rgba(76, 175, 80, 0.2); border: 1px solid var(--success); border-radius: 6px; color: var(--success); font-size: 11px; font-weight: 700;">APPROVED</span>
                                    <?php elseif ($deposit['status'] === 'rejected'): ?>
                                        <span style="padding: 6px 12px; background: rgba(244, 67, 54, 0.2); border: 1px solid var(--danger); border-radius: 6px; color: var(--danger); font-size: 11px; font-weight: 700;">REJECTED</span>
                                    <?php else: ?>
                                        <span style="padding: 6px 12px; background: rgba(255, 152, 0, 0.2); border: 1px solid var(--warning); border-radius: 6px; color: var(--warning); font-size: 11px; font-weight: 700;">PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 13px;"><?php echo formatDate($deposit['created_at']); ?></td>
                                <td>
                                    <?php if ($deposit['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline-block; margin-right: 5px;">
                                            <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="admin_note" value="Approved">
                                            <button type="submit" class="btn btn-success" style="padding: 8px 16px; font-size: 11px;">APPROVE</button>
                                        </form>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="admin_note" value="Rejected">
                                            <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 11px;">REJECT</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-size: 12px;">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
