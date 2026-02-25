<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

$success = '';
$error = '';

// Handle add/edit product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = sanitize($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name) || $category_id <= 0 || $price <= 0) {
        $error = 'Please fill in all required fields';
    } else {
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $image = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $image);
            }
        }

        if ($id > 0) {
            // Update
            if (!empty($image)) {
                $db->query("UPDATE products SET name = ?, category_id = ?, price = ?, description = ?, image = ? WHERE id = ?",
                    [$name, $category_id, $price, $description, $image, $id]);
            } else {
                $db->query("UPDATE products SET name = ?, category_id = ?, price = ?, description = ? WHERE id = ?",
                    [$name, $category_id, $price, $description, $id]);
            }
            $success = 'Product updated successfully!';
        } else {
            // Insert
            $db->query("INSERT INTO products (name, category_id, price, description, image, stock) VALUES (?, ?, ?, ?, ?, 0)",
                [$name, $category_id, $price, $description, $image]);
            $success = 'Product added successfully!';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM products WHERE id = ?", [$id]);
    $success = 'Product deleted!';
}

// Get products
$products = $db->fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     ORDER BY p.created_at DESC"
);

// Get categories for dropdown
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <aside class="sidebar">
        <a href="/imperium-shop/admin/" class="sidebar-item">DASH</a>
        <a href="/imperium-shop/admin/products.php" class="sidebar-item active">PRODUCTS</a>
        <a href="/imperium-shop/admin/categories.php" class="sidebar-item">CATEGORY</a>
        <a href="/imperium-shop/admin/accounts.php" class="sidebar-item">ACCOUNTS</a>
        <a href="/imperium-shop/admin/orders.php" class="sidebar-item">ORDERS</a>
        <a href="/imperium-shop/admin/deposits.php" class="sidebar-item">DEPOSITS</a>
        <a href="/imperium-shop/admin/users.php" class="sidebar-item">USERS</a>
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item">SETTINGS</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
    </aside>


    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">MANAGE PRODUCTS</h1>
        </header>

        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- ADD PRODUCT FORM -->
            <div class="card" style="margin-bottom: 40px;">
                <div class="card-header">ADD NEW PRODUCT</div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>PRODUCT NAME</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>CATEGORY</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>PRICE (VNĐ)</label>
                        <input type="number" name="price" class="form-control" min="0" step="1000" required>
                    </div>

                    <div class="form-group">
                        <label>DESCRIPTION</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <div class="form-group">
                        <label>IMAGE</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>

                    <button type="submit" class="btn">ADD PRODUCT</button>
                </form>
            </div>

            <!-- PRODUCTS TABLE -->
            <div class="card">
                <div class="card-header">ALL PRODUCTS</div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>IMAGE</th>
                            <th>NAME</th>
                            <th>CATEGORY</th>
                            <th>PRICE</th>
                            <th>STOCK</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td style="font-weight: 700;">#<?php echo $product['id']; ?></td>
                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="../assets/images/products/<?php echo $product['image']; ?>" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--bg-card); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px;"></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 700;"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td style="color: var(--primary); font-weight: 700;"><?php echo formatCurrency($product['price']); ?></td>
                                <td>
                                    <span style="padding: 6px 12px; background: <?php echo $product['stock'] < 10 ? 'rgba(255, 152, 0, 0.2)' : 'rgba(76, 175, 80, 0.2)'; ?>; border-radius: 6px; font-weight: 700;">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="accounts.php?product_id=<?php echo $product['id']; ?>" class="btn btn-secondary" style="padding: 8px 16px; font-size: 12px; margin-right: 5px;">ACCOUNTS</a>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger" style="padding: 8px 16px; font-size: 12px;" onclick="return confirm('Delete this product?')">DELETE</a>
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
