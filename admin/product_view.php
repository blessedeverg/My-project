<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// Get product data with category
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get all images for this product
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, created_at ASC");
$stmt->execute([$product_id]);
$product_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product - Admin</title>
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
                <h1 class="page-title">Product Details</h1>
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?>
                    </div>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="btn-group">
                            <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                                Edit Product
                            </a>
                            <a href="../product.php?id=<?php echo $product['id']; ?>" class="btn btn-info btn-sm" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                View on Site
                            </a>
                            <a href="products.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i>
                                Back to Products
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <!-- Product Image -->
                            <div style="flex: 1; max-width: 400px;">
                                <h4 style="margin-bottom: 1rem; color: #333;">
                                    <i class="fas fa-image"></i>
                                    Product Image
                                </h4>
                                <?php if ($product['image_url']): ?>
                                    <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: #f8f9fa;">
                                        <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="width: 100%; height: auto; display: block;">
                                    </div>
                                <?php else: ?>
                                    <div style="border: 2px dashed #ddd; border-radius: 8px; padding: 3rem; text-align: center; color: #999;">
                                        <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                        <p>No image uploaded</p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($product_images)): ?>
                                    <div style="margin-top: 1rem;">
                                        <h5>All Images:</h5>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <?php foreach ($product_images as $img): ?>
                                                <img src="../<?php echo htmlspecialchars($img['image_url']); ?>" 
                                                     alt="Product image"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 2px solid <?php echo $img['is_primary'] ? '#ffd700' : '#ddd'; ?>;">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Product Details -->
                            <div style="flex: 1; padding-left: 2rem;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-tag"></i>
                                            Product ID
                                        </label>
                                        <div class="form-control" style="background: #f8f9fa; border: none; font-weight: bold;">
                                            #<?php echo $product['id']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-dollar-sign"></i>
                                            Price
                                        </label>
                                        <div class="form-control" style="background: #f8f9fa; border: none; font-weight: bold; color: #28a745;">
                                            $<?php echo number_format($product['price'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-list"></i>
                                            Category
                                        </label>
                                        <div class="form-control" style="background: #f8f9fa; border: none;">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-map-marker-alt"></i>
                                            Display Location
                                        </label>
                                        <div class="form-control" style="background: #f8f9fa; border: none;">
                                            <span class="badge badge-info">
                                                <?php echo ucfirst(str_replace('_', ' ', $product['display_location'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-toggle-on"></i>
                                            Status
                                        </label>
                                        <div class="form-control" style="background: #f8f9fa; border: none;">
                                            <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($product['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar"></i>
                                            Created Date
                                        </label>
                                        <div class="form-control" style="background: #f8f9fa; border: none;">
                                            <?php echo date('F j, Y g:i A', strtotime($product['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($product['updated_at'] && $product['updated_at'] !== $product['created_at']): ?>
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-clock"></i>
                                            Last Updated
                                        </label>
                                        <div class="form-control" style="background: #f8f9fa; border: none;">
                                            <?php echo date('F j, Y g:i A', strtotime($product['updated_at'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Product Description -->
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e9ecef;">
                            <h4 style="margin-bottom: 1rem; color: #333;">
                                <i class="fas fa-align-left"></i>
                                Product Description
                            </h4>
                            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border: 1px solid #e9ecef; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e9ecef; text-align: center;">
                            <div class="btn-group">
                                <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i>
                                    Edit Product
                                </a>
                                <a href="../product.php?id=<?php echo $product['id']; ?>" class="btn btn-info" target="_blank">
                                    <i class="fas fa-external-link-alt"></i>
                                    View on Website
                                </a>
                                <a href="products.php" class="btn btn-secondary">
                                    <i class="fas fa-list"></i>
                                    All Products
                                </a>
                                <button type="button" class="btn btn-danger" 
                                        onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                    <i class="fas fa-trash"></i>
                                    Delete Product
                                </button>
                            </div>
                        </div>
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
                <form method="POST" action="products.php" style="display: inline;">
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
