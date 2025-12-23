<?php
/**
 * PRODUCT DETAIL PAGE
 * Displays full product information with images, colors, sizes, and add-to-cart functionality
 * URL: /pages/product.php?id={product_id}
 */

// Include database configuration and helper functions
require '../includes/config.php';

// ============================================
// SECTION 1: VALIDATE PRODUCT ID
// ============================================
// Get product ID from URL query parameter and convert to integer (prevents SQL injection)
$product_id = intval($_GET['id'] ?? 0);

// Redirect if no valid product ID provided
if ($product_id <= 0) {
    header('Location: ' . SITE_URL . 'pages/shop.php', true, 302);
    exit;
}

// ============================================
// SECTION 2: FETCH BASIC PRODUCT DATA
// ============================================
// Prepare SQL query to get core product information
$query = "SELECT id, name, description, price, category, quantity, image_url FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    // Redirect if database connection fails
    header('Location: ' . SITE_URL . 'pages/shop.php', true, 302);
    exit;
}

// Bind product ID and execute query
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// Redirect if product not found in database
if (!$product) {
    header('Location: ' . SITE_URL . 'pages/shop.php', true, 302);
    exit;
}

// ============================================
// SECTION 3: FETCH PRODUCT VARIANTS
// ============================================
// Get all images associated with this product
$images = getProductImages($product_id, $conn);
// Get all color options for this product
$colors = getProductColors($product_id, $conn);
// Get all size options for this product
$sizes = getProductSizes($product_id, $conn);

// Include header with navigation and global scripts
require '../includes/header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav class="mb-5" style="--bs-breadcrumb-divider: '/';">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>pages/shop.php" class="text-decoration-none" style="color: var(--accent);">Shop</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Images Section -->
        <div class="col-lg-6">
            <div class="product-gallery">
                <!-- Main Image -->
                <div class="main-image mb-4 rounded overflow-hidden" style="background-color: #f5f5f5;">
                    <?php if (!empty($images)): ?>
                        <img id="mainImage" src="<?php echo htmlspecialchars($images[0]['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="img-fluid" style="width: 100%; height: 500px; object-fit: cover;">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center" style="height: 500px; background: linear-gradient(135deg, #f5f5f5, #e0e0e0);">
                            <div class="text-center">
                                <i class="fas fa-image fa-5x text-muted mb-3"></i>
                                <p class="text-muted">No image available</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thumbnails -->
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-gallery">
                        <div class="d-flex gap-3 flex-nowrap overflow-auto pb-2">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="thumbnail-item flex-shrink-0 cursor-pointer" 
                                     onclick="switchImage(this)"
                                     style="width: 80px; height: 80px; border: 2px solid <?php echo $index === 0 ? 'var(--accent)' : '#ddd'; ?>; border-radius: 8px; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         alt="Thumbnail" 
                                         class="img-fluid" 
                                         style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info Section -->
        <div class="col-lg-6">
            <!-- Product Name & Category -->
            <div class="mb-4">
                <h1 class="mb-3" style="font-size: 2.5rem; font-weight: 700; color: #333;">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                <?php if (!empty($product['category'])): ?>
                    <div class="mb-3">
                        <span class="badge" style="background-color: var(--accent); font-size: 0.9rem; padding: 0.5rem 1rem;">
                            <?php echo htmlspecialchars($product['category']); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Price -->
            <div class="mb-4">
                <h2 style="color: var(--accent); font-weight: 700; font-size: 2rem;">
                    $<?php echo number_format($product['price'], 2); ?>
                </h2>
            </div>

            <!-- Availability Status -->
            <div class="mb-4">
                <span class="badge bg-success" style="padding: 0.5rem 1rem; font-size: 0.95rem;">
                    <i class="fas fa-check-circle me-2"></i>Available
                </span>
            </div>

            <!-- Description -->
            <?php if (!empty($product['description'])): ?>
                <div class="mb-4">
                    <h5 style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #666; font-size: 0.9rem;">Description</h5>
                    <p style="color: #666; line-height: 1.6; font-size: 1rem;">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Divider -->
            <div class="divider my-4"></div>

            <!-- Add to Cart Form -->
            <form id="addToCartForm">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                <!-- Color Selection -->
                <?php if (!empty($colors)): ?>
                    <div class="mb-4">
                        <label style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #666; font-size: 0.9rem; display: block; margin-bottom: 1rem;">
                            Select Color
                        </label>
                        <div id="colorOptions" class="d-flex flex-wrap gap-2">
                            <?php foreach ($colors as $color): ?>
                                <button type="button" 
                                        class="color-btn" 
                                        onclick="selectColor(this)" 
                                        style="padding: 0.75rem 1.25rem; border: 2px solid #ddd; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;"
                                        data-color="<?php echo htmlspecialchars($color['color_name']); ?>"
                                        data-color-code="<?php echo htmlspecialchars($color['color_code']); ?>">
                                    <span class="color-swatch" style="width: 20px; height: 20px; border-radius: 50%; border: 1px solid #999; background-color: <?php echo htmlspecialchars($color['color_code']); ?>;"></span>
                                    <span><?php echo htmlspecialchars($color['color_name']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="selectedColor" name="color" value="">
                    </div>
                <?php endif; ?>

                <!-- Size Selection -->
                <?php if (!empty($sizes)): ?>
                    <div class="mb-4">
                        <label style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #666; font-size: 0.9rem; display: block; margin-bottom: 1rem;">
                            Select Size
                        </label>
                        <div id="sizeOptions" class="d-flex flex-wrap gap-2">
                            <?php foreach ($sizes as $size): ?>
                                <button type="button" 
                                        class="size-btn" 
                                        onclick="selectSize(this)" 
                                        style="padding: 0.75rem 1.25rem; border: 2px solid #ddd; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; font-weight: 600;"
                                        data-size="<?php echo htmlspecialchars($size['size_name']); ?>">
                                    <?php echo htmlspecialchars($size['size_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="selectedSize" name="size" value="">
                    </div>
                <?php endif; ?>

                <!-- Quantity Selection -->
                <div class="mb-4">
                    <label style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #666; font-size: 0.9rem; display: block; margin-bottom: 1rem;">
                        Quantity
                    </label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="decreaseQty" style="width: 40px; height: 40px; padding: 0; font-size: 1.2rem;">−</button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" 
                               style="width: 60px; text-align: center; border: 2px solid #ddd; border-radius: 8px; padding: 0.5rem; font-weight: 600;">
                        <button type="button" class="btn btn-outline-secondary" id="increaseQty" style="width: 40px; height: 40px; padding: 0; font-size: 1.2rem;">+</button>
                    </div>
                </div>

                <!-- Divider -->
                <div class="divider my-4"></div>

                <!-- Add to Cart Button -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-lg" style="background-color: var(--accent); color: white; font-weight: 700; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s ease;">
                        <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <div class="divider my-4"></div>

            <!-- Product Details -->
            <div class="product-details">
                <h5 style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #666; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    <i class="fas fa-info-circle me-2"></i>Product Details
                </h5>
                <ul style="list-style: none; padding: 0; color: #666; line-height: 1.8;">
                    <li><strong>SKU:</strong> <?php echo htmlspecialchars($product['id']); ?></li>
                    <?php if (!empty($colors)): ?>
                        <li><strong>Available Colors:</strong> <?php echo count($colors); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($sizes)): ?>
                        <li><strong>Available Sizes:</strong> <?php echo count($sizes); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .thumbnail-item:hover {
        border-color: var(--accent) !important;
        transform: scale(1.05);
    }

    .color-btn:hover {
        border-color: var(--accent) !important;
        background-color: #f9f9f9 !important;
    }

    .color-btn.active {
        border-color: var(--accent) !important;
        background-color: var(--accent) !important;
        color: white !important;
    }

    .size-btn:hover {
        border-color: var(--accent) !important;
        background-color: #f9f9f9 !important;
    }

    .size-btn.active {
        border-color: var(--accent) !important;
        background-color: var(--accent) !important;
        color: white !important;
    }

    #addToCartForm button[type="submit"]:hover {
        background-color: #b8961e !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(201, 169, 97, 0.3);
    }

    .btn-outline-secondary:hover {
        background-color: #f9f9f9;
    }

    .divider {
        height: 1px;
        background: linear-gradient(to right, transparent, #ddd, transparent);
    }
</style>

<script>
    // Image switching
    function switchImage(element) {
        const imagePath = element.querySelector('img').src;
        document.getElementById('mainImage').src = imagePath;
        
        // Update thumbnail styling
        document.querySelectorAll('.thumbnail-item').forEach(item => {
            item.style.borderColor = '#ddd';
        });
        element.style.borderColor = 'var(--accent)';
    }

    // Color selection
    function selectColor(button) {
        document.querySelectorAll('.color-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
        document.getElementById('selectedColor').value = button.dataset.color;
    }

    // Size selection
    function selectSize(button) {
        document.querySelectorAll('.size-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
        document.getElementById('selectedSize').value = button.dataset.size;
    }

    // Quantity controls
    document.getElementById('decreaseQty')?.addEventListener('click', function() {
        const input = document.getElementById('quantity');
        if (input.value > 1) {
            input.value = parseInt(input.value) - 1;
        }
    });

    document.getElementById('increaseQty')?.addEventListener('click', function() {
        const input = document.getElementById('quantity');
        const max = parseInt(input.max);
        if (parseInt(input.value) < max) {
            input.value = parseInt(input.value) + 1;
        }
    });

    // Form submission
    document.getElementById('addToCartForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const productId = document.querySelector('input[name="product_id"]').value;
        const quantity = parseInt(document.getElementById('quantity').value);
        const color = document.getElementById('selectedColor')?.value || '';
        const size = document.getElementById('selectedSize')?.value || '';

        // Validation: Check if color is required and not selected
        const hasColors = document.querySelectorAll('.color-btn').length > 0;
        const hasSizes = document.querySelectorAll('.size-btn').length > 0;

        if (hasColors && !color) {
            showErrorModal('Please select a color before adding to cart');
            return;
        }

        if (hasSizes && !size) {
            showErrorModal('Please select a size before adding to cart');
            return;
        }

        try {
            const response = await fetch('<?php echo SITE_URL; ?>pages/cart_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&quantity=${quantity}&color=${encodeURIComponent(color)}&size=${encodeURIComponent(size)}`
            });

            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            console.log('Cart API raw response:', text);
            
            const result = JSON.parse(text);
            console.log('Cart API response:', result);

            if (result && result.success) {
                showSuccessModal('Product added to cart!');
                // Reset form
                document.getElementById('addToCartForm').reset();
                const colorInput = document.getElementById('selectedColor');
                const sizeInput = document.getElementById('selectedSize');
                if (colorInput) colorInput.value = '';
                if (sizeInput) sizeInput.value = '';
                document.querySelectorAll('.color-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.size-btn').forEach(btn => btn.classList.remove('active'));
                
                // Update cart UI
                setTimeout(() => {
                    // Update cart badge
                    const cartBadge = document.querySelector('.cart-count-badge');
                    if (cartBadge && result.cart_count !== undefined) {
                        cartBadge.textContent = result.cart_count;
                        cartBadge.style.display = result.cart_count > 0 ? 'inline' : 'none';
                    }
                    // Reload cart preview
                    if (window.loadCartPreview) {
                        loadCartPreview();
                    }
                }, 300);
            } else {
                showErrorModal((result && result.message) || 'Error adding to cart');
            }
        } catch (error) {
            showErrorModal('Error adding to cart: ' + error.message);
            console.error('Error:', error);
        }
    });

    // Error Modal
    function showErrorModal(message) {
        const modal = document.createElement('div');
        modal.className = 'modal show';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        modal.innerHTML = `
            <div class="modal-dialog" style="animation: slideDown 0.3s ease;">
                <div class="modal-content border-danger">
                    <div class="modal-header border-danger" style="background-color: #fff5f5;">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>Oops!
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p style="margin: 0; color: #333;">${message}</p>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        setTimeout(() => {
            modal.remove();
        }, 2000);
    }

    // Success Modal
    function showSuccessModal(message) {
        const modal = document.createElement('div');
        modal.className = 'modal show';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        modal.innerHTML = `
            <div class="modal-dialog" style="animation: slideDown 0.3s ease;">
                <div class="modal-content border-success">
                    <div class="modal-header border-success" style="background-color: #f0fdf4;">
                        <h5 class="modal-title text-success">
                            <i class="fas fa-check-circle me-2"></i>Success!
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p style="margin: 0; color: #333;">${message}</p>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        setTimeout(() => {
            modal.remove();
        }, 2000);
    }

    // Animation for modals
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);

</script>

<?php require '../includes/footer.php'; ?>
