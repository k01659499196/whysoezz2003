<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$db = new Database();

$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
$user_balance = $user['balance'] ?? 0;


$success = '';
$error = '';

// Handle deposit request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    $transaction_code = sanitize($_POST['transaction_code'] ?? '');

    if ($amount < 10000) {
        $error = 'Minimum deposit is 10,000đ';
    } elseif (empty($payment_method) || empty($transaction_code)) {
        $error = 'Please fill in all fields';
    } else {
        $db->query(
            "INSERT INTO deposits (user_id, amount, payment_method, transaction_code, status) VALUES (?, ?, ?, ?, 'pending')",
            [$_SESSION['user_id'], $amount, $payment_method, $transaction_code]
        );
        
        $success = 'Deposit request submitted! Please wait for admin approval.';
    }
}

// Get deposit history
$deposits = $db->fetchAll(
    "SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC",
    [$_SESSION['user_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="/imperium-shop/" class="sidebar-item">HOME</a>
        <a href="/imperium-shop/user/dashboard.php" class="sidebar-item">ORDERS</a>
        <a href="/imperium-shop/user/deposit.php" class="sidebar-item active">DEPOSIT</a>
        <a href="/imperium-shop/user/profile.php" class="sidebar-item">PROFILE</a>
        <a href="/imperium-shop/user/logout.php" class="sidebar-item" style="margin-top: auto;">LOGOUT</a>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px; position: relative;">
            <h1 style="font-size: 48px;">DEPOSIT FUNDS</h1>
            
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
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                <!-- DEPOSIT FORM -->
                <div class="card">
                    <div class="card-header">NEW DEPOSIT</div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>AMOUNT (VNĐ)</label>
                            <input type="number" name="amount" class="form-control" min="10000" step="1000" required>
                            <small style="color: var(--text-secondary); font-size: 12px;">Minimum: 10,000đ</small>
                        </div>

                        <div class="form-group">
                            <label>PAYMENT METHOD</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="">Select method</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Momo">Momo</option>
                                <option value="ZaloPay">ZaloPay</option>
                                <option value="ViettelPay">ViettelPay</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>TRANSACTION CODE</label>
                            <input type="text" name="transaction_code" class="form-control" placeholder="Enter transaction ID" required>
                        </div>

                        <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">SUBMIT REQUEST</button>
                    </form>
                </div>

            <!-- PAYMENT INFO -->
            <div class="card">
                <div class="card-header">PAYMENT INFORMATION</div>
                
                <?php
                $bank_name = getPaymentSetting('bank_name');
                $bank_account = getPaymentSetting('bank_account');
                $bank_holder = getPaymentSetting('bank_holder');
                $qr_bank = getPaymentSetting('qr_bank');
                
                $momo_phone = getPaymentSetting('momo_phone');
                $qr_momo = getPaymentSetting('qr_momo');
                
                $zalopay_phone = getPaymentSetting('zalopay_phone');
                $qr_zalopay = getPaymentSetting('qr_zalopay');
                
                $viettelpay_phone = getPaymentSetting('viettelpay_phone');
                $qr_viettelpay = getPaymentSetting('qr_viettelpay');
                ?>
                
                <div style="background: var(--bg-card); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h4 style="font-size: 16px; font-weight: 800; margin-bottom: 15px; color: var(--primary);">BANK TRANSFER</h4>
                    <div style="font-family: monospace; line-height: 2;">
                        <div><strong>Bank:</strong> <?php echo htmlspecialchars($bank_name); ?></div>
                        <div><strong>Account:</strong> <?php echo htmlspecialchars($bank_account); ?></div>
                        <div><strong>Name:</strong> <?php echo htmlspecialchars($bank_holder); ?></div>
                    </div>
                    <?php if (!empty($qr_bank)): ?>
                        <div style="margin-top: 15px;">
                            <img src="../assets/images/qr/<?php echo $qr_bank; ?>" style="width: 100%; max-width: 250px; border-radius: 10px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div style="background: var(--bg-card); padding: 20px; border-radius: 10px;">
                    <h4 style="font-size: 16px; font-weight: 800; margin-bottom: 15px; color: var(--primary);">E-WALLET</h4>
                    <div style="font-family: monospace; line-height: 2;">
                        <?php if (!empty($momo_phone)): ?>
                            <div><strong>Momo:</strong> <?php echo htmlspecialchars($momo_phone); ?></div>
                            <?php if (!empty($qr_momo)): ?>
                                <img src="../assets/images/qr/<?php echo $qr_momo; ?>" style="width: 150px; border-radius: 8px; margin: 10px 0;">
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($zalopay_phone)): ?>
                            <div><strong>ZaloPay:</strong> <?php echo htmlspecialchars($zalopay_phone); ?></div>
                            <?php if (!empty($qr_zalopay)): ?>
                                <img src="../assets/images/qr/<?php echo $qr_zalopay; ?>" style="width: 150px; border-radius: 8px; margin: 10px 0;">
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($viettelpay_phone)): ?>
                            <div><strong>ViettelPay:</strong> <?php echo htmlspecialchars($viettelpay_phone); ?></div>
                            <?php if (!empty($qr_viettelpay)): ?>
                                <img src="../assets/images/qr/<?php echo $qr_viettelpay; ?>" style="width: 150px; border-radius: 8px; margin: 10px 0;">
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: rgba(255, 152, 0, 0.1); border: 1px solid var(--warning); border-radius: 10px;">
                    <p style="color: var(--warning); font-size: 13px; line-height: 1.6;">
                        <strong>Note:</strong> After transfer, submit the transaction code above. Admin will approve within 24 hours.
                    </p>
                </div>
            </div>


            <!-- DEPOSIT HISTORY -->
            <div class="card" style="margin-top: 40px;">
                <div class="card-header">DEPOSIT HISTORY</div>

                <?php if (!empty($deposits)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>AMOUNT</th>
                                <th>METHOD</th>
                                <th>TRANSACTION CODE</th>
                                <th>STATUS</th>
                                <th>DATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deposits as $deposit): ?>
                                <tr>
                                    <td style="font-weight: 700;">#<?php echo $deposit['id']; ?></td>
                                    <td style="font-weight: 700; color: var(--primary);"><?php echo formatCurrency($deposit['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($deposit['payment_method']); ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($deposit['transaction_code']); ?></td>
                                    <td>
                                        <?php if ($deposit['status'] === 'approved'): ?>
                                            <span style="padding: 6px 12px; background: rgba(76, 175, 80, 0.2); border: 1px solid var(--success); border-radius: 6px; color: var(--success); font-size: 11px; font-weight: 700;">APPROVED</span>
                                        <?php elseif ($deposit['status'] === 'rejected'): ?>
                                            <span style="padding: 6px 12px; background: rgba(244, 67, 54, 0.2); border: 1px solid var(--danger); border-radius: 6px; color: var(--danger); font-size: 11px; font-weight: 700;">REJECTED</span>
                                        <?php else: ?>
                                            <span style="padding: 6px 12px; background: rgba(255, 152, 0, 0.2); border: 1px solid var(--warning); border-radius: 6px; color: var(--warning); font-size: 11px; font-weight: 700;">PENDING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--text-secondary);"><?php echo formatDate($deposit['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <p>No deposit history</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
