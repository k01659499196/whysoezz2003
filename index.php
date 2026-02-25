<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = new Database();

// Get user balance if logged in
$user_balance = 0;
if (isLoggedIn()) {
    $user = $db->fetchOne("SELECT balance FROM users WHERE id = ?", [$_SESSION['user_id']]);
    $user_balance = $user['balance'] ?? 0;
}

// Get categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY display_order ASC");

// Get filter
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where = ["p.stock > 0"];
$params = [];

if ($category_id > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE " . implode(" AND ", $where) . "
        ORDER BY p.created_at DESC";

$products = $db->fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_TAGLINE; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="/imperium-shop/" class="sidebar-item active">HOME</a>
        
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
        <header class="page-header">
            <h1><?php echo SITE_NAME; ?></h1>
            <p class="subtitle"><?php echo SITE_TAGLINE; ?></p>
            
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
            <!-- SEARCH & FILTER -->
            <div class="search-filter">
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search for a product" value="<?php echo htmlspecialchars($search); ?>">
                </form>
                
                <form method="GET" class="filter-dropdown">
                    <select name="category" onchange="this.form.submit()">
                        <option value="0">All</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- PRODUCTS SECTION -->
            <h2 class="section-title">HOT PRODUCTS</h2>
            
            <div class="products-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" onclick="window.location.href='/imperium-shop/product.php?id=<?php echo $product['id']; ?>'">
                            <div class="product-image">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div style="font-size: 48px; color: var(--text-secondary);">PRODUCT</div>
                                <?php endif; ?>
                                
                                <?php if ($product['stock'] > 0 && $product['stock'] < 10): ?>
                                    <span class="product-badge" style="background: var(--warning);">LOW STOCK</span>
                                <?php elseif ($product['stock'] >= 50): ?>
                                    <span class="product-badge">HOT</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-content">
                                <div class="product-brand">CROWNED WITH QUALITY</div>
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-stock">Stock: <strong><?php echo $product['stock']; ?></strong></p>
                                <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                                <button class="btn" style="width: 100%; margin-top: 15px;">PURCHASE</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 100px 20px; color: var(--text-secondary);">
                        <h3 style="font-size: 32px; margin-bottom: 15px; font-weight: 800;">NO PRODUCTS FOUND</h3>
                        <p style="font-size: 16px; margin-bottom: 30px;">Add some products from admin panel</p>
                        <?php if (isAdmin()): ?>
                            <a href="/imperium-shop/admin/products.php" class="btn">ADD PRODUCTS</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
