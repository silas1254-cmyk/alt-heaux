<?php
/**
 * SHOPPING CART PAGE
 * Displays all items in the user's shopping cart with quantities, colors, sizes, and totals
 * URL: /pages/pages.php?section=cart
 * Supports both logged-in users (database cart) and guests (session cart)
 */

// Redirect direct access to pages.php
if (basename($_SERVER['PHP_SELF']) === 'cart.php') {
    header('Location: pages.php?section=cart');
    exit;
}

// Include database configuration and helper functions
require '../includes/config.php';

// Get custom cart page content
$cart_content = getSetting('cart_content', '');
$cart_content_published = getSetting('cart_content_published', '1');

// ============================================
// SECTION 1: DETERMINE USER TYPE & GET CART DATA
// ============================================
// Check if user is logged in
$user_id = isUserLoggedIn() ? $_SESSION['user_id'] : null;

// Fetch cart items based on user status
if ($user_id) {
    // Logged-in users: get cart from database
    $cart_items = getCartItems($user_id, $conn);
} else {
    // Guest users: get cart from database (guest_carts table)
    $cart_items = getGuestCart($conn);
}

// ============================================
// SECTION 3: CALCULATE CART TOTALS
// ============================================
// Helper function calculates subtotal, tax, and grand total for all items
$cart_totals = calculateCartTotals($cart_items);
?>

<!-- Custom Cart Content -->
<?php if (!empty($cart_content) && $cart_content_published): ?>
    <div class="section-padding" style="background-color: var(--white);">
        <div class="container">
            <?php echo $cart_content; ?>
        </div>
    </div>
<?php endif; ?>

<div class="container py-5">
    <!-- PAGE HEADER -->
    <div class="mb-5">
        <h1 class="display-4 fw-bold">Shopping Cart</h1>
        <p class="text-muted">Review your items before checkout</p>
    </div>
    
    <?php if (empty($cart_items)): ?>
        <!-- EMPTY CART STATE -->
        <!-- Shown when user has no items in cart -->
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm text-center p-5">
                    <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
                    <h4>Your Cart is Empty</h4>
                    <p class="text-muted mb-4">Continue shopping to add items to your cart.</p>
                    <!-- Link back to shop page -->
                    <a href="<?php echo SITE_URL; ?>pages/shop.php" class="btn btn-dark btn-lg">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- CART ITEMS AND SUMMARY -->
        <!-- Main cart layout with items table and order summary -->
        <div class="row g-4">
            <!-- LEFT COLUMN: ITEMS LIST -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <!-- CART ITEMS HEADER -->
                        <h5 class="card-title mb-4">
                            <i class="fas fa-shopping-bag"></i> Cart Items (<?php echo $cart_totals['item_count']; ?>)
                        </h5>
                        
                        <!-- CART ITEMS TABLE -->
                        <!-- Responsive table showing all cart items with variant details -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <!-- CART ITEM ROW -->
                                        <!-- Each row represents one item with its variants (color, size) -->
                                        <tr>
                                            <!-- PRODUCT INFO COLUMN -->
                                            <!-- Shows product image, name, and variant details -->
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <!-- Product thumbnail image -->
                                                    <?php if (!empty($item['image_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <!-- Product name -->
                                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                        <!-- Selected color variant (if applicable) -->
                                                        <?php if (!empty($item['selected_color'])): ?>
                                                            <div class="text-muted small">Color: <?php echo htmlspecialchars($item['selected_color']); ?></div>
                                                        <?php endif; ?>
                                                        <!-- Selected size variant (if applicable) -->
                                                        <?php if (!empty($item['selected_size'])): ?>
                                                            <div class="text-muted small">Size: <?php echo htmlspecialchars($item['selected_size']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- UNIT PRICE COLUMN -->
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <!-- QUANTITY COLUMN -->
                                            <!-- Contains -/+ buttons to adjust quantity via AJAX -->
                                            <td>
                                                <div class="input-group input-group-sm" style="width: 100px;">
                                                    <!-- Decrease quantity button with variant data attributes -->
                                                    <button class="btn btn-outline-secondary" data-action="qty-decrease" data-product-id="<?php echo $item['product_id']; ?>" data-color="<?php echo htmlspecialchars($item['selected_color'] ?? ''); ?>" data-size="<?php echo htmlspecialchars($item['selected_size'] ?? ''); ?>">−</button>
                                                    <!-- Read-only display of current quantity -->
                                                    <input type="text" class="form-control text-center" value="<?php echo intval($item['quantity']); ?>" readonly>
                                                    <!-- Increase quantity button with variant data attributes -->
                                                    <button class="btn btn-outline-secondary" data-action="qty-increase" data-product-id="<?php echo $item['product_id']; ?>" data-color="<?php echo htmlspecialchars($item['selected_color'] ?? ''); ?>" data-size="<?php echo htmlspecialchars($item['selected_size'] ?? ''); ?>">+</button>
                                                </div>
                                            </td>
                                            <!-- LINE TOTAL COLUMN -->
                                            <!-- Price × Quantity for this item -->
                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            <!-- ACTION COLUMN -->
                                            <!-- Remove button with variant data attributes for specific variant deletion -->
                                            <td>
                                                <button class="btn btn-sm btn-danger" data-action="remove-item" data-product-id="<?php echo $item['product_id']; ?>" data-color="<?php echo htmlspecialchars($item['selected_color'] ?? ''); ?>" data-size="<?php echo htmlspecialchars($item['selected_size'] ?? ''); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- CART FOOTER ACTIONS -->
                        <!-- Navigation buttons for continuing shopping -->
                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <!-- Return to shop button -->
                            <a href="<?php echo SITE_URL; ?>pages/shop.php" class="btn btn-outline-dark">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RIGHT COLUMN: ORDER SUMMARY CARD -->
            <!-- Sticky card showing cart totals and checkout button -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm position-sticky" style="top: 20px;">
                    <div class="card-body">
                        <!-- SUMMARY HEADER -->
                        <h5 class="card-title mb-4">
                            <i class="fas fa-receipt"></i> Order Summary
                        </h5>
                        
                        <!-- SUBTOTAL ROW -->
                        <!-- Sum of all item prices × quantities (before any taxes/shipping) -->
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <strong id="subtotal">$<?php echo number_format($cart_totals['subtotal'], 2); ?></strong>
                        </div>
                        
                        <!-- VISUAL DIVIDER -->
                        <hr class="my-3">
                        
                        <!-- TOTAL ROW -->
                        <!-- Final amount customer will pay (updated via JavaScript when quantity changes) -->
                        <div class="summary-row total">
                            <span>Total:</span>
                            <strong id="total" class="h5">$<?php echo number_format($cart_totals['total'], 2); ?></strong>
                        </div>
                        
                        <!-- CHECKOUT SECTION -->
                        <?php if ($user_id): ?>
                            <!-- LOGGED-IN USER: Show checkout button -->
                            <button class="btn btn-dark w-100 mt-4 btn-lg">
                                <i class="fas fa-lock"></i> Proceed to Checkout
                            </button>
                            
                            <!-- SECURITY BADGE -->
                            <div class="mt-3 text-center text-muted small">
                                <i class="fas fa-shield-alt"></i> Secure checkout powered by Stripe
                            </div>
                        <?php else: ?>
                            <!-- GUEST USER: Show login/register prompt instead -->
                            <div class="alert alert-info mt-4" role="alert">
                                <i class="fas fa-info-circle"></i> <strong>Login Required</strong><br>
                                Please log in or create an account to proceed with checkout.
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Login to Your Account
                                </a>
                                <a href="<?php echo SITE_URL; ?>auth/register.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-user-plus"></i> Create New Account
                                </a>
                            </div>
                            
                            <p class="text-center text-muted small mt-3">
                                <i class="fas fa-lock"></i> Your cart will be saved for 30 days
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- CART PAGE STYLING -->
<!-- CSS rules specific to cart page layout and behavior -->
<style>
    /* Summary row container - flex layout for space-between alignment */
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }
    
    /* Total row styling - emphasized for importance */
    .summary-row.total {
        font-size: 1.1rem;
        font-weight: 600;
        color: #667eea;
    }
    
    /* Hover effect on table rows - smooth background color transition */
    .table-hover tbody tr {
        transition: background-color 0.2s ease;
    }
    
    /* Table row highlight on hover - light gray background */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>


<!-- CART FUNCTIONALITY SCRIPTS -->
<script>
/**
 * EVENT DELEGATION FOR CART ACTIONS
 * Listens for clicks on dynamically-generated cart buttons
 * Uses data attributes to pass product ID and variant information
 */
document.addEventListener('click', function(e) {
    // Handle quantity increase button
    if (e.target.closest('[data-action="qty-increase"]')) {
        const btn = e.target.closest('[data-action="qty-increase"]');
        const productId = btn.dataset.productId;
        const color = btn.dataset.color || '';
        const size = btn.dataset.size || '';
        // Call updateQuantityByDirection with direction 'up'
        updateQuantityByDirection(productId, 'up', color, size);
    }
    
    // Handle quantity decrease button
    if (e.target.closest('[data-action="qty-decrease"]')) {
        const btn = e.target.closest('[data-action="qty-decrease"]');
        const productId = btn.dataset.productId;
        const color = btn.dataset.color || '';
        const size = btn.dataset.size || '';
        // Call updateQuantityByDirection with direction 'down'
        updateQuantityByDirection(productId, 'down', color, size);
    }
    
    // Handle remove item button
    if (e.target.closest('[data-action="remove-item"]')) {
        const btn = e.target.closest('[data-action="remove-item"]');
        const productId = btn.dataset.productId;
        const color = btn.dataset.color || '';
        const size = btn.dataset.size || '';
        // Call removeFromCart which handles the confirm dialog
        removeFromCart(productId, color, size);
    }
});

/**
 * UPDATE CART QUANTITY VIA AJAX (Direction-based)
 * @param {string} productId - The product ID
 * @param {string} direction - 'up' to increase, 'down' to decrease quantity
 * @param {string} color - Selected color variant (optional)
 * @param {string} size - Selected size variant (optional)
 */
function updateQuantityByDirection(productId, direction, color = '', size = '') {
    fetch('<?php echo SITE_URL; ?>pages/cart_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_quantity',
            product_id: productId,
            direction: direction,
            color: color,
            size: size
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart badge before reloading
            if (typeof updateCartBadge === 'function') {
                updateCartBadge();
            }
            // Refresh page to show updated totals
            location.reload();
        } else {
            alert('Error updating quantity: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating quantity');
    });
}

/**
 * REMOVE ITEM FROM CART VIA AJAX
 * Sends specific product ID with color/size variants to API
 * Reloads page on success to update display and totals
 * 
 * @param {string} productId - The product ID to remove
 * @param {string} color - Selected color variant (optional)
 * @param {string} size - Selected size variant (optional)
 */
function removeFromCart(productId, color = '', size = '') {
    console.log('Removing item - productId:', productId, 'color:', color, 'size:', size);
    
    if (!confirm('Remove this item from your cart?')) {
        return; // User cancelled
    }
    
    fetch('<?php echo SITE_URL; ?>pages/cart_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            product_id: productId,
            color: color,
            size: size
        })
    })
    .then(response => {
        console.log('Remove response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Remove response data:', data);
        if (data.success) {
            console.log('Item removed successfully, reloading page...');
            // Update cart badge
            if (typeof updateCartBadge === 'function') {
                updateCartBadge();
            }
            // Force reload after brief delay
            setTimeout(() => {
                window.location.href = window.location.href;
            }, 300);
        } else {
            console.error('Remove failed:', data.message);
            alert('Error removing item: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Error removing item: ' + error.message);
    });
}
</script>
<?php require '../includes/footer.php'; ?>