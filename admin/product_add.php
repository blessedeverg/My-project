<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $display_location = $_POST['display_location'] ?? 'homepage';
    $status = $_POST['status'] ?? 'active';
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Product name is required.';
    }
    if (empty($description)) {
        $errors[] = 'Product description is required.';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than 0.';
    }
    if ($category_id <= 0) {
        $errors[] = 'Please select a category.';
    }
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = 'uploads/products/' . $filename;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        } else {
            $errors[] = 'Invalid image format. Allowed: JPG, JPEG, PNG, GIF, WebP.';
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO products (name, description, price, category_id, image_url, display_location, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $description, $price, $category_id, $image_url, $display_location, $status])) {
                $product_id = $db->lastInsertId();
                
                // If image was uploaded, also add to product_images table
                if ($image_url) {
                    $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 1)");
                    $stmt->execute([$product_id, $image_url]);
                }
                
                $message = 'Product added successfully!';
                $message_type = 'success';
                
                // Clear form data
                $name = $description = $image_url = '';
                $price = $category_id = 0;
                $display_location = 'homepage';
                $status = 'active';
            } else {
                $errors[] = 'Failed to add product to database.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

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
    <title>Add Product - Admin</title>
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
                <h1 class="page-title">Add New Product</h1>
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
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Product Information</h3>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Products
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="name">
                                        <i class="fas fa-tag"></i>
                                        Product Name *
                                    </label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                                           placeholder="Enter product name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="price">
                                        <i class="fas fa-dollar-sign"></i>
                                        Price *
                                    </label>
                                    <input type="number" id="price" name="price" class="form-control" 
                                           value="<?php echo $price ?? ''; ?>" 
                                           placeholder="0.00" step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="category_id">
                                        <i class="fas fa-list"></i>
                                        Category *
                                    </label>
                                    <select id="category_id" name="category_id" class="form-control" required>
                                        <option value="">Select a category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="display_location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Display Location
                                    </label>
                                    <select id="display_location" name="display_location" class="form-control">
                                        <option value="homepage" <?php echo (isset($display_location) && $display_location === 'homepage') ? 'selected' : ''; ?>>Homepage</option>
                                        <option value="featured" <?php echo (isset($display_location) && $display_location === 'featured') ? 'selected' : ''; ?>>Featured Products</option>
                                        <option value="category_page" <?php echo (isset($display_location) && $display_location === 'category_page') ? 'selected' : ''; ?>>Category Page Only</option>
                                        <option value="all_products" <?php echo (isset($display_location) && $display_location === 'all_products') ? 'selected' : ''; ?>>All Products Page</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="description">
                                    <i class="fas fa-align-left"></i>
                                    Description *
                                </label>
                                <textarea id="description" name="description" class="form-control" rows="5" 
                                          placeholder="Enter product description" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="image">
                                        <i class="fas fa-image"></i>
                                        Product Image
                                    </label>
                                    <div class="file-upload">
                                        <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                                        <div class="file-upload-btn">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            Choose Image File
                                        </div>
                                    </div>
                                    <small class="text-muted">Supported formats: JPG, JPEG, PNG, GIF, WebP (Max: 5MB)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="status">
                                        <i class="fas fa-toggle-on"></i>
                                        Status
                                    </label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="active" <?php echo (isset($status) && $status === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo (isset($status) && $status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Image Preview -->
                            <div class="form-group">
                                <div id="image-preview" style="display: none;">
                                    <label class="form-label">Image Preview:</label>
                                    <div style="border: 2px dashed #ddd; padding: 1rem; border-radius: 8px; text-align: center;">
                                        <img id="preview-img" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e9ecef;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Add Product
                                </button>
                                <a href="products.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const category = document.getElementById('category_id').value;
            const description = document.getElementById('description').value.trim();
            
            if (!name || !price || !category || !description) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (price <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0.');
                return false;
            }
        });
    </script>

    <script src="assets/js/admin.js"></script>
</body>
</html>
