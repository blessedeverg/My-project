<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $product_id = (int)$_POST['product_id'];
                $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
                if ($stmt->execute([$product_id])) {
                    $message = 'Product deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting product.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get all products with categories
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>TechStore Pro</h3>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="products.php" class="nav-link active">
                    <i class="fas fa-box"></i>
                    Products
                </a>
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    Categories
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Orders
                </a>
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    Users
                </a>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    Messages
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Products Management</h1>
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?>
                    </div>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check' : 'exclamation-triangle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Products</h3>
                        <a href="product_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add New Product
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <p>No products found. <a href="product_add.php">Add your first product</a>.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Display Location</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($product['image_url']): ?>
                                                        <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                    <?php else: ?>
                                                        <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-image" style="color: #ccc;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo ucfirst(str_replace('_', ' ', $product['display_location'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($product['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="product_view.php?id=<?php echo $product['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="product_edit.php?id=<?php echo $product['id']; ?>" 
                                                           class="btn btn-sm btn-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 10px; max-width: 400px; width: 90%;">
            <h3 style="margin-bottom: 1rem; color: #dc3545;">
                <i class="fas fa-exclamation-triangle"></i>
                Confirm Delete
            </h3>
            <p id="deleteMessage">Are you sure you want to delete this product?</p>
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(productId, productName) {
            document.getElementById('deleteProductId').value = productId;
            document.getElementById('deleteMessage').textContent = `Are you sure you want to delete "${productName}"? This action cannot be undone.`;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>

    <script src="assets/js/admin.js"></script>
</body>
</html>
