<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireAdmin();

$db = new Database();

$success = '';
$error = '';

// Get edit category if exists
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_category = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$edit_id]);
}

// Handle add/edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);

    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        // Handle image upload
        $banner_image = '';
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $banner_image = uniqid() . '.' . $ext;
                
                // Create directory if not exists
                if (!file_exists('../assets/images/categories/')) {
                    mkdir('../assets/images/categories/', 0777, true);
                }
                
                move_uploaded_file($_FILES['banner_image']['tmp_name'], '../assets/images/categories/' . $banner_image);
            }
        }

        if ($id > 0) {
            // Update
            if (!empty($banner_image)) {
                $db->query("UPDATE categories SET name = ?, description = ?, banner_image = ?, display_order = ? WHERE id = ?",
                    [$name, $description, $banner_image, $display_order, $id]);
            } else {
                $db->query("UPDATE categories SET name = ?, description = ?, display_order = ? WHERE id = ?",
                    [$name, $description, $display_order, $id]);
            }
            $success = 'Category updated!';
            header('Location: categories.php?success=1');
            exit;
        } else {
            // Insert
            $db->query("INSERT INTO categories (name, description, banner_image, display_order) VALUES (?, ?, ?, ?)",
                [$name, $description, $banner_image, $display_order]);
            $success = 'Category added!';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $db->query("DELETE FROM categories WHERE id = ?", [(int)$_GET['delete']]);
    header('Location: categories.php?deleted=1');
    exit;
}

$categories = $db->fetchAll("SELECT * FROM categories ORDER BY display_order ASC");

// Success messages from redirects
if (isset($_GET['success'])) {
    $success = 'Category updated successfully!';
}
if (isset($_GET['deleted'])) {
    $success = 'Category deleted!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 30px;
            color: var(--primary);
        }
        .modal-close {
            float: right;
            font-size: 32px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .modal-close:hover {
            color: var(--danger);
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="/imperium-shop/admin/" class="sidebar-item">DASH</a>
        <a href="/imperium-shop/admin/products.php" class="sidebar-item">PRODUCTS</a>
        <a href="/imperium-shop/admin/categories.php" class="sidebar-item active">CATEGORY</a>
        <a href="/imperium-shop/admin/accounts.php" class="sidebar-item">ACCOUNTS</a>
        <a href="/imperium-shop/admin/orders.php" class="sidebar-item">ORDERS</a>
        <a href="/imperium-shop/admin/deposits.php" class="sidebar-item">DEPOSITS</a>
        <a href="/imperium-shop/admin/users.php" class="sidebar-item">USERS</a>
        <a href="/imperium-shop/admin/settings.php" class="sidebar-item">SETTINGS</a>
        <a href="/imperium-shop/" class="sidebar-item" style="margin-top: auto;">SHOP</a>
    </aside>

    <main class="main-content">
        <header class="page-header" style="padding: 40px 20px 30px;">
            <h1 style="font-size: 48px;">MANAGE CATEGORIES</h1>
        </header>

        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 40px;">
                <div class="card-header">ADD NEW CATEGORY</div>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>CATEGORY NAME</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>DESCRIPTION</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <div class="form-group">
                        <label>BANNER IMAGE</label>
                        <input type="file" name="banner_image" class="form-control" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label>DISPLAY ORDER</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                    </div>

                    <button type="submit" class="btn">ADD CATEGORY</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">ALL CATEGORIES</div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>BANNER</th>
                            <th>NAME</th>
                            <th>DESCRIPTION</th>
                            <th>ORDER</th>
                            <th>PRODUCTS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <?php
                            $product_count = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$cat['id']])['count'];
                            ?>
                            <tr>
                                <td style="font-weight: 700;">#<?php echo $cat['id']; ?></td>
                                <td>
                                    <?php if (!empty($cat['banner_image'])): ?>
                                        <img src="../assets/images/categories/<?php echo $cat['banner_image']; ?>" style="width: 80px; height: 50px; border-radius: 8px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 80px; height: 50px; background: var(--bg-card); border-radius: 8px;"></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 700;"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td style="color: var(--text-secondary);"><?php echo htmlspecialchars(substr($cat['description'] ?? '', 0, 50)); ?></td>
                                <td><?php echo $cat['display_order']; ?></td>
                                <td><span style="padding: 6px 12px; background: rgba(212, 175, 55, 0.2); border-radius: 6px; font-weight: 700;"><?php echo $product_count; ?></span></td>
                                <td>
                                    <button onclick="openEditModal(<?php echo $cat['id']; ?>)" class="btn btn-secondary" style="padding: 8px 16px; font-size: 12px; margin-right: 5px;">EDIT</button>
                                    <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-danger" style="padding: 8px 16px; font-size: 12px;" onclick="return confirm('Delete this category?')">DELETE</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <div class="modal-header">EDIT CATEGORY</div>
            
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>CATEGORY NAME</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>DESCRIPTION</label>
                    <textarea name="description" id="edit_description" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label>BANNER IMAGE (Leave empty to keep current)</label>
                    <input type="file" name="banner_image" class="form-control" accept="image/*">
                    <div id="current_image" style="margin-top: 15px;"></div>
                </div>

                <div class="form-group">
                    <label>DISPLAY ORDER</label>
                    <input type="number" name="display_order" id="edit_display_order" class="form-control" value="0">
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn" style="flex: 1;">SAVE CHANGES</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="flex: 1;">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const categories = <?php echo json_encode($categories); ?>;

        function openEditModal(id) {
            const category = categories.find(c => c.id == id);
            if (!category) return;

            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_display_order').value = category.display_order;
            
            // Show current image
            const currentImageDiv = document.getElementById('current_image');
            if (category.banner_image) {
                currentImageDiv.innerHTML = `
                    <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 10px;">Current Image:</div>
                    <img src="../assets/images/categories/${category.banner_image}" style="width: 200px; border-radius: 10px; border: 2px solid var(--border);">
                `;
            } else {
                currentImageDiv.innerHTML = '';
            }

            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
