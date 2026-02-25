<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireLogin();

$db = new Database();

$success = '';
$error = '';

// Get user balance
$user = $db->fetchOne("SELECT balance FROM users WHERE id = ?", [$_SESSION['user_id']]);
$user_balance = $user['balance'] ?? 0;

// Handle Remove from Cart
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $db->query("DELETE FROM cart WHERE id = ? AND user_id = ?", [$cart_id, $_SESSION['user_id']]);
    $success = 'Item removed from cart!';
}

// Handle Checkout All
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Get all cart items
    $cart_items = $db->fetchAll(
        "SELECT c.*, p.name, p.price, p.stock 
         FROM cart c
         LEFT JOIN products p ON c.product_id = p.id
         WHERE c.user_id = ?",
        [$_SESSION['user_id']]
    );
    
    if (empty($cart_items)) {
        $error = 'Your cart is empty';
    } else {
        // Calculate total
        $total = 0;
        foreach ($cart_items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        if ($user_balance < $total) {
            $error = 'Insufficient balance. Need ' . formatCurrency($total - $user_balance) . ' more.';
        } else {
            // Start transaction
            $db->getConnection()->beginTransaction();
            
            try {
                $purchased_count = 0;
                
                foreach ($cart_items as $item) {
                    if ($item['stock'] <= 0) {
                        throw new Exception($item['name'] . ' is out of stock');
                    }
                    
                    // Get available account
                    $account = $db->fetchOne(
                        "SELECT * FROM accounts WHERE product_id = ? AND status = 'available' LIMIT 1",
                        [$item['product_id']]
                    );
                    
                    if (!$account) {
                        throw new Exception('No accounts available for ' . $item['name']);
                    }
                    
                    // Update account status
                    $db->query(
                        "UPDATE accounts SET status = 'sold', sold_to = ?, sold_at = NOW() WHERE id = ?",
                        [$_SESSION['user_id'], $account['id']]
                    );
                    
                    // Update product stock
                    $db->query(
                        "UPDATE products SET stock = stock - ? WHERE id = ?",
                        [$item['quantity'], $item['product_id']]
                    );
                    
                    // Create order
                    $db->query(
                        "INSERT INTO orders (user_id, product_id, account_id, price, status) VALUES (?, ?, ?, ?, 'completed')",
                        [$_SESSION['user_id'], $item['product_id'], $account['id'], $item['price']]
                    );
                    
                    $purchased_count++;
                }
                
                // Deduct total balance
                $db->query(
                    "UPDATE users SET balance = balance - ? WHERE id = ?",
                    [$total, $_SESSION['user_id']]
                );
                
                // Log transaction
                $db->query(
                    "INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'purchase', ?, ?)",
                    [$_SESSION['user_id'], $total, "Cart checkout: {$purchased_count} items"]
                );
                
                // Clear cart
                $db->query("DELETE FROM cart WHERE user_id = ?", [$_SESSION['user_id']]);
                
                $db->getConnection()->commit();
                
                // Redirect to orders
                header('Location: /imperium-shop/user/dashboard.php?success=checkout');
                exit;
                
            } catch (Exception $e) {
                $db->getConnection()->rollBack();
                $error = 'Checkout failed: ' . $e->getMessage();
            }
        }
    }
}

// Get cart items
$cart_items = $db->fetchAll(
    "SELECT c.*, p.name, p.price, p.stock, p.image 
     FROM cart c
     LEFT JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?
     ORDER BY c.created_at DESC",
    [$_SESSION['user_id']]
);

// Calculate totals
$total_items = 0;
$total_price = 0;
foreach ($cart_items as $item) {
    $total_items += $item['quantity'];
    $total_price += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="/imperium-shop/" class="sidebar-item">HOME</a>
        <a href="/imperium-shop/user/dashboard.php" class="sidebar-item">ORDERS</a>
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
            <h1 style="font-size: 48px;">SHOPPING CART</h1>
            
            <!-- Cart Badge -->
            <a href="/imperium-shop/cart.php" class="cart-badge" style="pointer-events: none; opacity: 0.8;">
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
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($cart_items)): ?>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <!-- CART ITEMS -->
                    <div>
                        <div class="card">
                            <div class="card-header">CART ITEMS (<?php echo $total_items; ?>)</div>
                            
                            <?php foreach ($cart_items as $item): ?>
                                <div style="display: flex; gap: 20px; padding: 20px; border-bottom: 1px solid var(--border);">
                                    <!-- Image -->
                                    <div style="width: 100px; height: 100px; background: var(--bg-secondary); border-radius: 10px; overflow: hidden; flex-shrink: 0;">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="assets/images/products/<?php echo $item['image']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 32px;"></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Details -->
                                    <div style="flex: 1;">
                                        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 10px;">
                                            <a href="product.php?id=<?php echo $item['product_id']; ?>" style="color: var(--text-primary); text-decoration: none;">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        </h3>
                                        <div style="font-size: 24px; font-weight: 800; color: var(--primary); margin-bottom: 10px;">
                                            <?php echo formatCurrency($item['price']); ?>
                                        </div>
                                        <div style="font-size: 14px; color: var(--text-secondary);">
                                            Stock: <strong><?php echo $item['stock']; ?></strong> available
                                        </div>
                                    </div>
                                    
                                    <!-- Remove Button -->
                                    <div style="display: flex; align-items: center;">
                                        <a href="?remove=<?php echo $item['id']; ?>" class="btn btn-danger" style="padding: 12px 20px; font-size: 13px;" onclick="return confirm('Remove this item?')">
                                            REMOVE
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ORDER SUMMARY -->
                    <div>
                        <div class="card" style="position: sticky; top: 20px;">
                            <div class="card-header">ORDER SUMMARY</div>
                            
                            <div style="padding: 20px 0;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border);">
                                    <span style="color: var(--text-secondary);">Items</span>
                                    <strong><?php echo $total_items; ?></strong>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border);">
                                    <span style="color: var(--text-secondary);">Subtotal</span>
                                    <strong style="color: var(--primary);"><?php echo formatCurrency($total_price); ?></strong>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 20px;">
                                    <strong>Total</strong>
                                    <strong style="color: var(--primary);"><?php echo formatCurrency($total_price); ?></strong>
                                </div>
                                
                                <?php if ($user_balance >= $total_price): ?>
                                    <form method="POST">
                                        <button type="submit" name="checkout" class="btn" style="width: 100%; padding: 18px; font-size: 16px;">
                                            CHECKOUT
                                        </button>
                                    </form>
                                    <div style="text-align: center; margin-top: 15px; color: var(--text-secondary); font-size: 13px;">
                                        After checkout: <?php echo formatCurrency($user_balance - $total_price); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        Insufficient balance!<br>
                                        Need: <strong><?php echo formatCurrency($total_price - $user_balance); ?></strong> more
                                    </div>
                                    <a href="user/deposit.php" class="btn" style="width: 100%; padding: 18px; font-size: 16px; display: block; text-align: center;">
                                        ADD FUNDS
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div style="text-align: center; padding: 100px 20px;">
                        <div style="font-size: 80px; margin-bottom: 30px;">🛒</div>
                        <h2 style="font-size: 32px; font-weight: 800; margin-bottom: 15px;">YOUR CART IS EMPTY</h2>
                        <p style="color: var(--text-secondary); font-size: 16px; margin-bottom: 40px;">Add some products to get started!</p>
                        <a href="/imperium-shop/" class="btn" style="padding: 18px 40px; font-size: 16px;">
                            BROWSE PRODUCTS
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
