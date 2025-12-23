<?php
/**
 * SHOP PAGE - Product Listing and Filtering
 * 
 * This page displays all available products with filtering options.
 * Users can filter by category, search, and price range.
 * Each product shows as a card with image, name, price, and quick view button.
 */

require '../includes/config.php';

// Get custom shop page content
$shop_content = getSetting('shop_content', '');
$shop_content_published = getSetting('shop_content_published', '1');

require '../includes/header.php';

/**
 * FETCH ACTIVE CATEGORIES
 * Gets all product categories for the filter sidebar
 */
$categories = getActiveCategories($conn);
$filters = [];

/**
 * BUILD FILTER PARAMETERS FROM GET REQUESTS
 * Validates and converts user input for database queries
 */
// Category filter - convert to integer for safety
if (!empty($_GET['category'])) {
    $filters['category_id'] = intval($_GET['category']);
}
// Minimum price filter - convert to float
if (!empty($_GET['min_price'])) {
    $filters['min_price'] = floatval($_GET['min_price']);
}
// Maximum price filter - convert to float
if (!empty($_GET['max_price'])) {
    $filters['max_price'] = floatval($_GET['max_price']);
}
// Search query - trim whitespace for consistency
if (!empty($_GET['search'])) {
    $filters['search'] = trim($_GET['search']);
}

/**
 * FETCH FILTERED PRODUCTS
 * Gets products matching all active filters from database
 */
$products = getFilteredProducts($filters, $conn);
?>

<!-- Custom Shop Content -->
<?php if (!empty($shop_content) && $shop_content_published): ?>
    <div class="section-padding" style="background-color: var(--white);">
        <div class="container">
            <?php echo $shop_content; ?>
        </div>
    </div>
<?php endif; ?>

<!-- PAGE BACKGROUND -->
<div class="container-fluid py-5" style="background-color: #fafafa;">
    <!-- PAGE HEADER SECTION -->
    <div class="container mb-5">
        <div class="text-center mb-5">
            <!-- Page title -->
            <h1 class="section-title">Our Collection</h1>
            <!-- Decorative divider -->
            <div class="section-divider"></div>
            <!-- Page subtitle describing the collection -->
            <p class="section-subtitle">Discover our carefully curated selection of premium fashion</p>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- LEFT SIDEBAR: FILTER CONTROLS -->
            <!-- Sticky positioned sidebar for filtering products -->
            <div class="col-lg-3 mb-4">
                <div class="card border-0 shadow-light" style="position: sticky; top: 20px;">
                    <div class="card-body">
                        <!-- FILTER HEADER -->
                        <h5 class="card-title mb-3" style="color: var(--accent); font-weight: 700;">
                            <i class="fas fa-filter"></i> Refine
                        </h5>
                        <!-- Visual divider -->
                        <div class="divider"></div>
                        
                        <!-- CATEGORY FILTER SECTION -->
                        <h6 class="mb-3 fw-700" style="text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.5px;">Categories</h6>
                        <div class="d-flex flex-column gap-2">
                            <!-- "All Categories" option - shows all products -->
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" id="all_cat" value="" 
                                       <?php echo empty($filters['category_id']) ? 'checked' : ''; ?> 
                                       onchange="location.href='<?php echo SITE_URL; ?>pages/shop.php';">
                                <label class="form-check-label" for="all_cat">All Categories</label>
                            </div>
                            <!-- Dynamic category radio buttons -->
                            <?php foreach ($categories as $cat): ?>
                                <div class="form-check">
                                    <!-- Radio button for this category - onChange filters products -->
                                    <input class="form-check-input" type="radio" name="category" id="cat_<?php echo $cat['id']; ?>" 
                                           value="<?php echo $cat['id']; ?>"
                                           <?php echo ($filters['category_id'] ?? 0) == $cat['id'] ? 'checked' : ''; ?>
                                           onchange="window.location.href='<?php echo SITE_URL; ?>pages/shop.php?category=' + this.value;">
                                    <!-- Category label with name -->
                                    <label class="form-check-label" for="cat_<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT SECTION: PRODUCTS GRID -->
            <!-- Responsive grid layout showing product cards -->
            <div class="col-lg-9">
                <div class="row g-4">
                    <!-- EMPTY STATE MESSAGE -->
                    <!-- Shown when no products match the selected filters -->
                    <?php if (empty($products)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <!-- Large search icon indicating no results -->
                                <i class="fas fa-search fa-5x mb-3 text-accent"></i>
                                <h4 class="fw-bold">No Products Found</h4>
                                <p class="text-muted">Try adjusting your filters to discover more pieces</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- PRODUCT CARDS LOOP -->
                        <!-- Iterates through filtered products array and creates card for each -->
                        <?php foreach ($products as $product): ?>
                            <!-- PRODUCT CARD COLUMN -->
                            <!-- Each card is responsive: 6 cols on tablet, 4 cols on desktop -->
                            <div class="col-md-6 col-lg-4">
                                <div class="product-card">
                                    <!-- PRODUCT IMAGE CONTAINER -->
                                    <!-- Gradient background with product image centered -->
                                    <div class="product-image" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                                        <!-- Display primary product image if available -->
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid p-3">
                                        <?php else: ?>
                                            <!-- Placeholder shown when no image is available -->
                                            <div class="text-center text-muted">
                                                <i class="fas fa-image fa-3x mb-2"></i>
                                                <p>No Image</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- PRODUCT INFO CARD -->
                                    <div class="card">
                                        <div class="card-body">
                                            <!-- PRODUCT NAME -->
                                            <h5 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <!-- PRODUCT DESCRIPTION (truncated to 60 characters) -->
                                            <p class="product-description"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 60)); ?></p>
                                            
                                            <!-- PRICE AND AVAILABILITY SECTION -->
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <!-- Product price formatted to 2 decimal places -->
                                                <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                                                <!-- Availability badge -->
                                                <small>
                                                    <span class="badge badge-accent">Available</span>
                                                </small>
                                            </div>
                                            
                                            <!-- VIEW PRODUCT BUTTON -->
                                            <!-- Links to detailed product page with ID parameter -->
                                            <a href="<?php echo SITE_URL; ?>pages/product.php?id=<?php echo $product['id']; ?>" class="btn btn-dark w-100">
                                                <i class="fas fa-eye"></i> View Product
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
