<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get site settings
$stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get filter parameters
$category_filter = intval($_GET['category'] ?? 0);
$search_query = trim($_GET['q'] ?? '');
$sort_by = $_GET['sort'] ?? 'newest';

// Build SQL query
$where_conditions = ["p.status = 'active'"];
$params = [];

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$where_clause = implode(' AND ', $where_conditions);

// Determine sort order
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'price_low':
        $order_clause .= "p.price ASC";
        break;
    case 'price_high':
        $order_clause .= "p.price DESC";
        break;
    case 'name':
        $order_clause .= "p.name ASC";
        break;
    case 'oldest':
        $order_clause .= "p.created_at ASC";
        break;
    default: // newest
        $order_clause .= "p.created_at DESC";
        break;
}

// Get products
$sql = "SELECT p.*, c.name as category_name, pi.image_url 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
        WHERE $where_clause 
        $order_clause";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current category name for page title
$current_category_name = '';
if ($category_filter > 0) {
    $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_filter]);
    $category_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_category_name = $category_data['name'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_category_name ? htmlspecialchars($current_category_name) . ' - ' : ''; ?>Products - <?php echo htmlspecialchars($settings['site_name'] ?? 'TechStore Pro'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2><a href="index.php" style="color: white; text-decoration: none;"><?php echo htmlspecialchars($settings['site_name'] ?? 'TechStore Pro'); ?></a></h2>
                    <span class="tagline"><?php echo htmlspecialchars($settings['site_tagline'] ?? 'Your Premier Electronics Destination'); ?></span>
                </div>
                
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link">Categories <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $category): ?>
                                <li><a href="products.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a href="products.php" class="nav-link active">All Products</a></li>
                    <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                </ul>

                <div class="nav-search">
                    <form action="products.php" method="GET">
                        <?php if ($category_filter): ?>
                            <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                        <?php endif; ?>
                        <input type="text" name="q" placeholder="Search products..." class="search-input" 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main style="margin-top: 100px; padding: 2rem 0;">
        <div class="container">
            <!-- Page Header -->
            <div style="margin-bottom: 2rem;">
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: #333;">
                    <?php if ($current_category_name): ?>
                        <?php echo htmlspecialchars($current_category_name); ?>
                    <?php elseif ($search_query): ?>
                        Search Results for "<?php echo htmlspecialchars($search_query); ?>"
                    <?php else: ?>
                        All Products
                    <?php endif; ?>
                </h1>
                
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <p style="color: #666; margin: 0;">
                        Showing <?php echo count($products); ?> product<?php echo count($products) !== 1 ? 's' : ''; ?>
                        <?php if ($current_category_name): ?>
                            in <?php echo htmlspecialchars($current_category_name); ?>
                        <?php endif; ?>
                    </p>
                    
                    <!-- Sort Options -->
                    <form method="GET" style="display: flex; align-items: center; gap: 0.5rem;">
                        <?php if ($category_filter): ?>
                            <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                        <?php endif; ?>
                        <?php if ($search_query): ?>
                            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php endif; ?>
                        
                        <label for="sort" style="color: #666; font-weight: 500;">Sort by:</label>
                        <select name="sort" id="sort" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Filters -->
            <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: #333;">
                    <i class="fas fa-filter"></i>
                    Filter Products
                </h3>
                
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
                    <!-- Category Filter -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #555;">Category:</label>
                        <form method="GET" style="display: inline;">
                            <?php if ($search_query): ?>
                                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                            <?php endif; ?>
                            <?php if ($sort_by !== 'newest'): ?>
                                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                            <?php endif; ?>
                            
                            <select name="category" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; min-width: 150px;">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    
                    <!-- Clear Filters -->
                    <?php if ($category_filter || $search_query): ?>
                        <a href="products.php" style="padding: 0.5rem 1rem; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; font-size: 0.9rem;">
                            <i class="fas fa-times"></i>
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <i class="fas fa-search" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <h3 style="color: #666; margin-bottom: 1rem;">No Products Found</h3>
                    <p style="color: #999; margin-bottom: 2rem;">
                        <?php if ($search_query): ?>
                            Try adjusting your search terms or browse our categories.
                        <?php elseif ($category_filter): ?>
                            No products available in this category yet.
                        <?php else: ?>
                            No products available at the moment.
                        <?php endif; ?>
                    </p>
                    <a href="products.php" class="btn-primary">Browse All Products</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <span class="category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-primary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo htmlspecialchars($settings['site_name'] ?? 'TechStore Pro'); ?></h3>
                    <p><?php echo htmlspecialchars($settings['site_tagline'] ?? 'Your Premier Electronics Destination'); ?></p>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['contact_email'] ?? 'contact@techstore.com'); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['contact_phone'] ?? '+1 (555) 123-4567'); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($settings['contact_address'] ?? '123 Tech Street, Digital City'); ?></p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="admin/login.php">Admin</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 <?php echo htmlspecialchars($settings['site_name'] ?? 'TechStore Pro'); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
