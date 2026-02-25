<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Text fields
    $text_fields = ['bank_name', 'bank_account', 'bank_holder', 'momo_phone', 'zalopay_phone', 'viettelpay_phone'];
    
    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize($_POST[$field]);
            $db->query(
                "INSERT INTO payment_settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$field, $value, $value]
            );
        }
    }
    
    // Handle QR code uploads
    $qr_fields = ['qr_bank', 'qr_momo', 'qr_zalopay', 'qr_viettelpay'];
    
    foreach ($qr_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $filename = $field . '_' . time() . '.' . $ext;
                $upload_path = '../assets/images/qr/' . $filename;
                
                // Create directory if not exists
                if (!file_exists('../assets/images/qr/')) {
                    mkdir('../assets/images/qr/', 0777, true);
                }
                
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_path)) {
                    $db->query(
                        "INSERT INTO payment_settings (setting_key, setting_value) VALUES (?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = ?",
                        [$field, $filename, $filename]
                    );
                }
            }
        }
    }
    
    $success = 'Payment settings updated successfully!';
}

// Get current settings
$settings = [];
$all_settings = $db->fetchAll("SELECT * FROM payment_settings");
foreach ($all_settings as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings - Admin</title>
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
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item active">SETTINGS</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
    </aside>

    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">PAYMENT SETTINGS</h1>
        </header>

        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- BANK TRANSFER -->
                    <div class="card">
                        <div class="card-header">BANK TRANSFER</div>
                        
                        <div class="form-group">
                            <label>BANK NAME</label>
                            <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>ACCOUNT NUMBER</label>
                            <input type="text" name="bank_account" class="form-control" value="<?php echo htmlspecialchars($settings['bank_account'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>ACCOUNT HOLDER</label>
                            <input type="text" name="bank_holder" class="form-control" value="<?php echo htmlspecialchars($settings['bank_holder'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>QR CODE (Optional)</label>
                            <input type="file" name="qr_bank" class="form-control" accept="image/*">
                            <?php if (!empty($settings['qr_bank'])): ?>
                                <div style="margin-top: 15px;">
                                    <img src="../assets/images/qr/<?php echo $settings['qr_bank']; ?>" style="width: 200px; border-radius: 10px; border: 2px solid var(--border);">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- MOMO -->
                    <div class="card">
                        <div class="card-header">MOMO</div>
                        
                        <div class="form-group">
                            <label>PHONE NUMBER</label>
                            <input type="text" name="momo_phone" class="form-control" value="<?php echo htmlspecialchars($settings['momo_phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>QR CODE (Optional)</label>
                            <input type="file" name="qr_momo" class="form-control" accept="image/*">
                            <?php if (!empty($settings['qr_momo'])): ?>
                                <div style="margin-top: 15px;">
                                    <img src="../assets/images/qr/<?php echo $settings['qr_momo']; ?>" style="width: 200px; border-radius: 10px; border: 2px solid var(--border);">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ZALOPAY -->
                    <div class="card">
                        <div class="card-header">ZALOPAY</div>
                        
                        <div class="form-group">
                            <label>PHONE NUMBER</label>
                            <input type="text" name="zalopay_phone" class="form-control" value="<?php echo htmlspecialchars($settings['zalopay_phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>QR CODE (Optional)</label>
                            <input type="file" name="qr_zalopay" class="form-control" accept="image/*">
                            <?php if (!empty($settings['qr_zalopay'])): ?>
                                <div style="margin-top: 15px;">
                                    <img src="../assets/images/qr/<?php echo $settings['qr_zalopay']; ?>" style="width: 200px; border-radius: 10px; border: 2px solid var(--border);">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- VIETTELPAY -->
                    <div class="card">
                        <div class="card-header">VIETTELPAY</div>
                        
                        <div class="form-group">
                            <label>PHONE NUMBER</label>
                            <input type="text" name="viettelpay_phone" class="form-control" value="<?php echo htmlspecialchars($settings['viettelpay_phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>QR CODE (Optional)</label>
                            <input type="file" name="qr_viettelpay" class="form-control" accept="image/*">
                            <?php if (!empty($settings['qr_viettelpay'])): ?>
                                <div style="margin-top: 15px;">
                                    <img src="../assets/images/qr/<?php echo $settings['qr_viettelpay']; ?>" style="width: 200px; border-radius: 10px; border: 2px solid var(--border);">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn" style="padding: 18px 60px; font-size: 16px;">
                        SAVE SETTINGS
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
