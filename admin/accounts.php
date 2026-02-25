<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

$success = '';
$error = '';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Get product
$product = null;
if ($product_id > 0) {
    $product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$product_id]);
}

// Handle bulk add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_accounts'])) {
    $pid = (int)$_POST['product_id'];
    $accounts_text = $_POST['accounts'] ?? '';
    
    if ($pid <= 0) {
        $error = 'Please select a product';
    } elseif (empty($accounts_text)) {
        $error = 'Please enter accounts';
    } else {
        $lines = explode("\n", $accounts_text);
        $added = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Format: username:password or username|password
            $parts = preg_split('/[:|]/', $line);
            if (count($parts) >= 2) {
                $username = trim($parts[0]);
                $password = trim($parts[1]);
                
                if (!empty($username) && !empty($password)) {
                    $db->query(
                        "INSERT INTO accounts (product_id, username, password, status) VALUES (?, ?, ?, 'available')",
                        [$pid, $username, $password]
                    );
                    $added++;
                }
            }
        }
        
        // Update product stock
        $db->query("UPDATE products SET stock = stock + ? WHERE id = ?", [$added, $pid]);
        
        $success = "Added $added accounts successfully!";
        
        // Refresh product
        if ($product_id > 0) {
            $product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$product_id]);
        }
    }
}

// Handle delete account
if (isset($_GET['delete_account'])) {
    $acc_id = (int)$_GET['delete_account'];
    $db->query("DELETE FROM accounts WHERE id = ? AND status = 'available'", [$acc_id]);
    $success = 'Account deleted!';
}

// Get products for dropdown
$products = $db->fetchAll("SELECT id, name FROM products ORDER BY name ASC");

// Get accounts for selected product
$accounts = [];
if ($product_id > 0) {
    $accounts = $db->fetchAll(
        "SELECT * FROM accounts WHERE product_id = ? ORDER BY status ASC, created_at DESC",
        [$product_id]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <aside class="sidebar">
        <a href="/imperium-shop/admin/" class="sidebar-item">DASH</a>
        <a href="/imperium-shop/admin/products.php" class="sidebar-item">PRODUCTS</a>
        <a href="/imperium-shop/admin/categories.php" class="sidebar-item">CATEGORY</a>
        <a href="/imperium-shop/admin/accounts.php" class="sidebar-item active">ACCOUNTS</a>
        <a href="/imperium-shop/admin/orders.php" class="sidebar-item">ORDERS</a>
        <a href="/imperium-shop/admin/deposits.php" class="sidebar-item">DEPOSITS</a>
        <a href="/imperium-shop/admin/users.php" class="sidebar-item">USERS</a>
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item">SETTINGS</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
    </aside>


    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">MANAGE ACCOUNTS</h1>
        </header>

        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($product): ?>
                <div class="alert" style="background: rgba(212, 175, 55, 0.1); border-color: var(--primary); color: var(--primary);">
                    Managing accounts for: <strong><?php echo htmlspecialchars($product['name']); ?></strong> (Stock: <?php echo $product['stock']; ?>)
                </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 40px;">
                <div class="card-header">ADD ACCOUNTS (BULK)</div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>SELECT PRODUCT</label>
                        <select name="product_id" class="form-control" required>
                            <option value="">Choose product</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $product_id == $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>ACCOUNTS (One per line: username:password)</label>
                        <textarea name="accounts" class="form-control" rows="10" placeholder="user1:pass1&#10;user2:pass2&#10;user3:pass3" required></textarea>
                        <small style="color: var(--text-secondary); font-size: 12px;">Format: username:password or username|password (one per line)</small>
                    </div>

                    <button type="submit" name="bulk_accounts" class="btn">ADD ACCOUNTS</button>
                </form>
            </div>

            <?php if (!empty($accounts)): ?>
                <div class="card">
                    <div class="card-header">ACCOUNTS LIST</div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>USERNAME</th>
                                <th>PASSWORD</th>
                                <th>STATUS</th>
                                <th>SOLD TO</th>
                                <th>DATE</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $acc): ?>
                                <tr>
                                    <td style="font-weight: 700;">#<?php echo $acc['id']; ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($acc['username']); ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($acc['password']); ?></td>
                                    <td>
                                        <?php if ($acc['status'] === 'available'): ?>
                                            <span style="padding: 6px 12px; background: rgba(76, 175, 80, 0.2); border: 1px solid var(--success); border-radius: 6px; color: var(--success); font-size: 11px; font-weight: 700;">AVAILABLE</span>
                                        <?php else: ?>
                                            <span style="padding: 6px 12px; background: rgba(244, 67, 54, 0.2); border: 1px solid var(--danger); border-radius: 6px; color: var(--danger); font-size: 11px; font-weight: 700;">SOLD</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($acc['sold_to']): ?>
                                            <?php
                                            $buyer = $db->fetchOne("SELECT username FROM users WHERE id = ?", [$acc['sold_to']]);
                                            echo htmlspecialchars($buyer['username'] ?? 'N/A');
                                            ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--text-secondary); font-size: 13px;"><?php echo formatDate($acc['created_at']); ?></td>
                                    <td>
                                        <?php if ($acc['status'] === 'available'): ?>
                                            <a href="?product_id=<?php echo $product_id; ?>&delete_account=<?php echo $acc['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 11px;" onclick="return confirm('Delete?')">DELETE</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
