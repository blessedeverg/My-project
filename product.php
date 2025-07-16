<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// Get product data
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.id = ? AND p.status = 'active'");
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

// Get related products from same category
$stmt = $db->prepare("SELECT p.*, pi.image_url 
                     FROM products p 
                     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
                     WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
                     ORDER BY RAND() LIMIT 4");
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get site settings
$stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get all categories for navigation
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo htmlspecialchars($settings['site_name'] ?? 'TechStore Pro'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($product['description'], 0, 160)); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .product-detail {
            padding: 2rem 0;
            background: white;
        }
        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        .product-gallery {
            position: relative;
        }
        .main-image {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e9ecef;
            margin-bottom: 1rem;
        }
        .main-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        .image-thumbnails {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
        }
        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid transparent;
            cursor: pointer;
            transition: border-color 0.3s ease;
            flex-shrink: 0;
        }
        .thumbnail.active {
            border-color: #667eea;
        }
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-details h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 1rem;
        }
        .product-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .product-price {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .product-category {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: transform 0.3s ease;
        }
        .product-category:hover {
            transform: translateY(-1px);
            color: white;
        }
        .product-description {
            line-height: 1.8;
            color: #555;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .product-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-contact {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-contact:hover {
            background: linear-gradient(135deg, #218838, #1ea98b);
            transform: translateY(-2px);
            color: white;
        }
        .related-products {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 2px solid #e9ecef;
        }
        @media (max-width: 768px) {
            .product-main {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .product-details h1 {
                font-size: 2rem;
            }
            .product-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            .product-actions {
                flex-direction: column;
            }
        }
    </style>
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
                    <li class="nav-item"><a href="products.php" class="nav-link">All Products</a></li>
                    <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                </ul>

                <div class="nav-search">
                    <form action="products.php" method="GET">
                        <input type="text" name="q" placeholder="Search products..." class="search-input">
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

    <!-- Breadcrumb -->
    <div style="background: #f8f9fa; padding: 1rem 0; margin-top: 80px;">
        <div class="container">
            <nav style="color: #666; font-size: 0.9rem;">
                <a href="index.php" style="color: #667eea; text-decoration: none;">Home</a>
                <span style="margin: 0 0.5rem;">/</span>
                <a href="products.php" style="color: #667eea; text-decoration: none;">Products</a>
                <?php if ($product['category_name']): ?>
                    <span style="margin: 0 0.5rem;">/</span>
                    <a href="products.php?category=<?php echo $product['category_id']; ?>" style="color: #667eea; text-decoration: none;">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                <?php endif; ?>
                <span style="margin: 0 0.5rem;">/</span>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </nav>
        </div>
    </div>

    <!-- Product Detail -->
    <section class="product-detail">
        <div class="container">
            <div class="product-main">
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="main-image">
                        <?php if ($product['image_url']): ?>
                            <img id="mainImage" src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <div style="height: 400px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                <i class="fas fa-image" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($product_images)): ?>
                        <div class="image-thumbnails">
                            <!-- Main product image thumbnail -->
                            <?php if ($product['image_url']): ?>
                                <div class="thumbnail active" onclick="changeMainImage('<?php echo htmlspecialchars($product['image_url']); ?>')">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Main image">
                                </div>
                            <?php endif; ?>
                            
                            <!-- Additional images -->
                            <?php foreach ($product_images as $img): ?>
                                <?php if ($img['image_url'] !== $product['image_url']): ?>
                                    <div class="thumbnail" onclick="changeMainImage('<?php echo htmlspecialchars($img['image_url']); ?>')">
                                        <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="Product image">
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-details">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-meta">
                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                        <?php if ($product['category_name']): ?>
                            <a href="products.php?category=<?php echo $product['category_id']; ?>" class="product-category">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <div class="product-actions">
                        <a href="contact.php?product=<?php echo $product['id']; ?>" class="btn-large btn-contact">
                            <i class="fas fa-envelope"></i>
                            Contact for Purchase
                        </a>
                        <button type="button" class="btn-large btn-primary" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                            <i class="fas fa-heart"></i>
                            Add to Wishlist
                        </button>
                        <button type="button" class="btn-large" style="background: #6c757d; color: white;" onclick="shareProduct()">
                            <i class="fas fa-share-alt"></i>
                            Share
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if (!empty($related_products)): ?>
                <div class="related-products">
                    <h2 class="section-title">Related Products</h2>
                    <div class="products-grid">
                        <?php foreach ($related_products as $related): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if ($related['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                                    <p class="product-description"><?php echo htmlspecialchars(substr($related['description'], 0, 80)); ?>...</p>
                                    <div class="product-price">$<?php echo number_format($related['price'], 2); ?></div>
                                    <a href="product.php?id=<?php echo $related['id']; ?>" class="btn-primary">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

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

    <script>
        function changeMainImage(imageUrl) {
            document.getElementById('mainImage').src = imageUrl;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
        
        function addToWishlist(productId) {
            // This would typically save to localStorage or send to server
            alert('Product added to wishlist! (This is a demo)');
        }
        
        function shareProduct() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars($product['name']); ?>',
                    text: '<?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>',
                    url: window.location.href
                });
            } else {
                // Fallback to copy URL
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Product URL copied to clipboard!');
                });
            }
        }
    </script>

    <script src="assets/js/script.js"></script>
</body>
</html>
