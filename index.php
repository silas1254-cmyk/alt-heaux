<?php
// TEST DEPLOYMENT: Updated Dec 25, 2025 - 12:00 AM
require 'includes/config.php';
require 'includes/compression.php';

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdnjs.cloudflare.com cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com cdn.jsdelivr.net fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' cdnjs.cloudflare.com cdn.jsdelivr.net fonts.gstatic.com; connect-src 'self' cdnjs.cloudflare.com cdn.jsdelivr.net");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

require 'includes/header.php';

// Get featured products (limit 4)
$featured_query = "SELECT id, name, description, price, image_url, quantity FROM products WHERE is_hidden = 0 ORDER BY display_order ASC, name ASC LIMIT 4";
$featured_result = $conn->query($featured_query);
$featured_products = $featured_result->fetch_all(MYSQLI_ASSOC);

// Get active sliders
$sliders = getSliders();

// Get custom home page content (if set in admin)
$home_content = getSetting('home_content', '');
$home_content_published = getSetting('home_content_published', '1');
?>

<!-- Dynamic Hero Slider Section -->
<?php if (!empty($sliders)): ?>
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" style="height: 600px;">
        <div class="carousel-indicators">
            <?php foreach ($sliders as $index => $slider): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner" style="height: 100%;">
            <?php foreach ($sliders as $index => $slider): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" style="height: 100%; background: url('<?php echo htmlspecialchars($slider['image_url']); ?>') center/cover no-repeat;">
                    <div class="carousel-caption d-none d-md-block" style="bottom: auto; top: 50%; transform: translateY(-50%);">
                        <h1 class="display-3 fw-bold mb-4" style="text-shadow: 0 2px 10px rgba(0,0,0,0.5);"><?php echo htmlspecialchars($slider['title']); ?></h1>
                        <?php if (!empty($slider['description'])): ?>
                            <p class="lead mb-4" style="font-size: 1.3rem; text-shadow: 0 1px 5px rgba(0,0,0,0.5);"><?php echo htmlspecialchars($slider['description']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($slider['button_text']) && !empty($slider['button_url'])): ?>
                            <a href="<?php echo htmlspecialchars($slider['button_url']); ?>" class="btn btn-accent btn-lg">
                                <?php echo htmlspecialchars($slider['button_text']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
<?php else: ?>
    <div class="hero-section">
        <div class="container py-5 position-relative" style="z-index: 2;">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-section h1"><?php echo SITE_NAME; ?></h1>
                    <p class="hero-section lead"><?php echo getSetting('site_tagline', 'Elevate Your Style. Express Your Individuality.'); ?></p>
                    <a href="pages/shop.php" class="btn btn-accent btn-lg">Explore Collection</a>
                </div>
                <div class="col-lg-6">
                    <div style="height: 400px; background: linear-gradient(135deg, rgba(201,169,97,0.2) 0%, rgba(201,169,97,0.05) 100%); display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                        <i class="fas fa-shopping-bags" style="font-size: 8rem; color: rgba(201,169,97,0.3);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Featured Products Section -->
<div class="section-padding" style="background-color: var(--white);">
    <div class="container">
        <h2 class="section-title">Featured Collection</h2>
        <div class="section-divider"></div>
        <p class="section-subtitle">Discover our handpicked selection of premium pieces</p>
        
        <div class="row g-4">
            <?php if (empty($featured_products)): ?>
                <div class="col-12">
                    <p class="text-center text-muted py-5">No products available yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($featured_products as $product): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="product-card">
                            <div class="product-image" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-image fa-3x mb-2"></i>
                                        <p>No Image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="product-description"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)); ?></p>
                                    <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                                    <button class="btn btn-dark w-100" 
                                            data-add-to-cart
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-quantity="1">
                                        <i class="fas fa-shopping-bag"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="pages/shop.php" class="btn btn-outline-dark btn-lg">View All Collections</a>
        </div>
    </div>
</div>

<!-- Custom Home Content -->
<?php if (!empty($home_content) && $home_content_published): ?>
    <div class="section-padding" style="background-color: var(--white);">
        <div class="container">
            <?php echo $home_content; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Why Choose Us Section -->
<?php 
    $why_choose = getSection('why-choose-us');
    if ($why_choose && !empty($why_choose['content'])): 
?>
    <div class="section-padding" style="background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);">
        <div class="container">
            <h2 class="section-title"><?php echo htmlspecialchars($why_choose['title']); ?></h2>
            <div class="section-divider"></div>
            <div class="row">
                <div class="col-md-12">
                    <?php echo $why_choose['content']; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
