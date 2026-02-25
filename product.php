<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = new Database();

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: /imperium-shop/');
    exit;
}

// Get product details
$product = $db->fetchOne(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.id = ?",
    [$product_id]
);

if (!$product) {
    header('Location: /imperium-shop/');
    exit;
}

// Get user balance if logged in
$user_balance = 0;
if (isLoggedIn()) {
    $user = $db->fetchOne("SELECT balance FROM users WHERE id = ?", [$_SESSION['user_id']]);
    $user_balance = $user['balance'] ?? 0;
}

$error = '';
$success = '';

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $error = 'Please login to add to cart';
    } elseif ($product['stock'] <= 0) {
        $error = 'Product out of stock';
    } else {
        // Check if already in cart
        $existing = $db->fetchOne(
            "SELECT * FROM cart WHERE user_id = ? AND product_id = ?",
            [$_SESSION['user_id'], $product_id]
        );
        
        if ($existing) {
            $error = 'Product already in cart';
        } else {
            $db->query(
                "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)",
                [$_SESSION['user_id'], $product_id]
            );
            $success = 'Added to cart!';
        }
    }
}

// Handle Buy Now
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!isLoggedIn()) {
        $error = 'Please login to purchase';
    } elseif ($product['stock'] <= 0) {
        $error = 'Product out of stock';
    } elseif ($user_balance < $product['price']) {
        $error = 'Insufficient balance. Please deposit more funds.';
    } else {
        // Start transaction
        $db->getConnection()->beginTransaction();
        
        try {
            // Get available account
            $account = $db->fetchOne(
                "SELECT * FROM accounts WHERE product_id = ? AND status = 'available' LIMIT 1",
                [$product_id]
            );
            
            if (!$account) {
                throw new Exception('No accounts available');
            }
            
            // Update account status
            $db->query(
                "UPDATE accounts SET status = 'sold', sold_to = ?, sold_at = NOW() WHERE id = ?",
                [$_SESSION['user_id'], $account['id']]
            );
            
            // Deduct user balance
            $db->query(
                "UPDATE users SET balance = balance - ? WHERE id = ?",
                [$product['price'], $_SESSION['user_id']]
            );
            
            // Update product stock
            $db->query(
                "UPDATE products SET stock = stock - 1 WHERE id = ?",
                [$product_id]
            );
            
            // Create order
            $db->query(
                "INSERT INTO orders (user_id, product_id, account_id, price, status) VALUES (?, ?, ?, ?, 'completed')",
                [$_SESSION['user_id'], $product_id, $account['id'], $product['price']]
            );
            
            // Log transaction
            $db->query(
                "INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'purchase', ?, ?)",
                [$_SESSION['user_id'], $product['price'], "Purchased: " . $product['name']]
            );
            
            $db->getConnection()->commit();
            
            // Redirect to orders
            header('Location: /imperium-shop/user/dashboard.php');
            exit;
            
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            $error = 'Purchase failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="/imperium-shop/" class="sidebar-item">HOME</a>
        
        <?php if (isLoggedIn()): ?>
            <a href="/imperium-shop/user/dashboard.php" class="sidebar-item">ORDERS</a>
            <a href="/imperium-shop/user/deposit.php" class="sidebar-item">DEPOSIT</a>
            <a href="/imperium-shop/user/profile.php" class="sidebar-item">PROFILE</a>
            <a href="/imperium-shop/user/logout.php" class="sidebar-item" style="margin-top: auto;">LOGOUT</a>
        <?php else: ?>
            <a href="/imperium-shop/user/login.php" class="sidebar-item" style="margin-top: auto;">LOGIN</a>
        <?php endif; ?>
        
        <?php if (isAdmin()): ?>
            <a href="/imperium-shop/admin/" class="sidebar-item" style="border-top: 1px solid var(--border); margin-top: 20px; padding-top: 20px;">ADMIN</a>
        <?php endif; ?>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HEADER -->
        <header class="page-header" style="padding: 40px 20px 30px; position: relative;">
            <h1 style="font-size: 48px;">PRODUCT DETAILS</h1>
            
            <?php if (isLoggedIn()): ?>
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
            <?php endif; ?>
        </header>


        <div class="container">
            <div style="margin-bottom: 30px;">
                <a href="/imperium-shop/" style="color: var(--text-secondary); text-decoration: none; font-size: 14px; font-weight: 600;">
                    ← BACK TO PRODUCTS
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- PRODUCT DETAIL -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-top: 40px;">
                <!-- PRODUCT IMAGE -->
                <div style="background: var(--bg-secondary); border-radius: 16px; overflow: hidden; height: 500px; display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($product['image'])): ?>
                        <img src="assets/images/products/<?php echo $product['image']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="font-size: 80px; color: var(--text-secondary);">PRODUCT</div>
                    <?php endif; ?>
                </div>

                <!-- PRODUCT INFO -->
                <div>
                    <div style="margin-bottom: 20px;">
                        <span style="display: inline-block; padding: 8px 16px; background: rgba(212, 175, 55, 0.2); border: 1px solid var(--primary); border-radius: 8px; color: var(--primary); font-size: 12px; font-weight: 700; letter-spacing: 1px;">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'PRODUCT'); ?>
                        </span>
                    </div>

                    <h1 style="font-size: 42px; font-weight: 900; margin-bottom: 20px; letter-spacing: 2px;">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h1>

                    <?php if (!empty($product['description'])): ?>
                        <p style="color: var(--text-secondary); font-size: 16px; line-height: 1.8; margin-bottom: 30px;">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </p>
                    <?php endif; ?>

                    <div style="margin: 30px 0;">
                        <div style="font-size: 14px; color: var(--text-secondary); letter-spacing: 1px; margin-bottom: 10px;">PRICE</div>
                        <div style="font-size: 48px; font-weight: 900; color: var(--primary);">
                            <?php echo formatCurrency($product['price']); ?>
                        </div>
                    </div>

                    <div style="margin: 30px 0;">
                        <div style="font-size: 14px; color: var(--text-secondary); letter-spacing: 1px; margin-bottom: 10px;">STOCK</div>
                        <div style="font-size: 24px; font-weight: 700;">
                            <?php echo $product['stock']; ?> 
                            <span style="font-size: 16px; color: var(--text-secondary);">available</span>
                        </div>
                    </div>

                    <?php if ($product['stock'] > 0): ?>
                        <?php if (isLoggedIn()): ?>
                            <div style="display: flex; gap: 15px; margin-top: 40px;">
                                <form method="POST" style="flex: 1;">
                                    <button type="submit" name="add_to_cart" class="btn btn-secondary" style="width: 100%; padding: 22px; font-size: 16px;">
                                        ADD TO CART
                                    </button>
                                </form>
                                <form method="POST" style="flex: 1;">
                                    <button type="submit" name="buy_now" class="btn" style="width: 100%; padding: 22px; font-size: 16px;">
                                        BUY NOW
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <a href="/imperium-shop/user/login.php" class="btn" style="width: 100%; padding: 22px; font-size: 18px; display: block; text-align: center; margin-top: 40px;">
                                LOGIN TO PURCHASE
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn" disabled style="width: 100%; padding: 22px; font-size: 18px; opacity: 0.5; cursor: not-allowed; margin-top: 40px;">
                            OUT OF STOCK
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
