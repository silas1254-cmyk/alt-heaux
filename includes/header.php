<?php
// Session started in config.php
// All helpers loaded in config.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo SITE_URL; ?>">
    <title><?php echo SITE_NAME; ?> - <?php echo getSetting('site_tagline', 'Premium Fashion & Clothing'); ?></title>
    <meta name="description" content="<?php echo getSetting('site_description', ''); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/style.css">
    <style>
        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .cart-badge { position: absolute; top: -8px; right: -8px; }
        .cart-link { position: relative; display: inline-block; }
        
        /* Cart Preview Dropdown */
        .cart-dropdown-wrapper {
            position: relative;
        }
        
        .cart-preview-panel {
            position: absolute;
            top: 100%;
            right: -10px;
            margin-top: 5px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            width: 320px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 0;
            z-index: 999;
            pointer-events: none;
        }
        
        .cart-dropdown-wrapper:hover .cart-preview-panel {
            max-height: 500px;
            opacity: 1;
            pointer-events: auto;
        }
        
        .cart-preview-content {
            display: flex;
            flex-direction: column;
            padding: 15px;
        }
        
        .cart-preview-title {
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }
        
        .cart-preview-items {
            max-height: 350px;
            overflow-y: auto;
            margin-bottom: 10px;
        }
        
        .cart-preview-item {
            display: flex;
            gap: 10px;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9rem;
        }
        
        .cart-preview-item:last-child {
            border-bottom: none;
        }
        
        .cart-preview-item-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            flex-shrink: 0;
        }
        
        .cart-preview-item-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .cart-preview-item-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 3px;
        }
        
        .cart-preview-item-price {
            color: #667eea;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .cart-preview-item-qty {
            color: #999;
            font-size: 0.8rem;
        }
        
        .cart-preview-footer {
            border-top: 2px solid #f8f9fa;
            padding-top: 10px;
            text-align: center;
        }
        
        .cart-preview-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 0 5px;
            font-weight: 600;
            color: #333;
        }
        
        .cart-preview-empty {
            text-align: center;
            padding: 30px 15px;
            color: #999;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <?php 
                $site_logo = getSetting('site_logo', '');
                if (!empty($site_logo)): 
            ?>
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>index.php">
                    <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo SITE_NAME; ?>" style="height: 40px; margin-right: 10px;">
                    <span class="fw-bold"><?php echo SITE_NAME; ?></span>
                </a>
            <?php else: ?>
                <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>index.php"><?php echo SITE_NAME; ?></a>
            <?php endif; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php 
                        // Load dynamic menu items
                        $menu_items = getMenuItems(null);
                        if (!empty($menu_items)):
                            foreach ($menu_items as $item):
                    ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($item['url']); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
                        </li>
                    <?php 
                            endforeach;
                        else:
                            // No menu items: show default pages
                            $reserved_slugs = ['shop', 'cart', 'page', 'product', 'dashboard', 'orders', 'profile', 'downloads'];
                    ?>
                        <!-- Always show core pages when no menu items exist -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>pages/shop.php">Shop</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>pages/contact.php">Contact</a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Cart and Auth: Always show these regardless of menu items -->
                    <li class="nav-item cart-dropdown-wrapper">
                        <a class="nav-link cart-link" href="<?php echo SITE_URL; ?>pages/cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="cart-count-badge badge bg-danger" style="display: none;"></span>
                        </a>
                        <!-- Cart Hover Preview -->
                        <div class="cart-preview-panel">
                            <div class="cart-preview-content">
                                <h6 class="cart-preview-title">
                                    <i class="fas fa-shopping-bag"></i> Shopping Cart
                                </h6>
                                <div id="cart-preview-items" class="cart-preview-items">
                                    <p class="text-muted text-center py-3">Loading...</p>
                                </div>
                                <div class="cart-preview-footer">
                                    <a href="<?php echo SITE_URL; ?>pages/cart.php" class="btn btn-dark btn-sm w-100">
                                        <i class="fas fa-arrow-right me-2"></i>View Full Cart
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pages/dashboard.php"><i class="fas fa-th-large me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pages/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pages/orders.php"><i class="fas fa-receipt me-2"></i>Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        <?php else: ?>
                            <a class="nav-link" href="<?php echo SITE_URL; ?>auth/login.php">Sign In</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Toast Container for Notifications -->
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    
    <script>
        // Get SITE_URL from base tag (set by config.php)
        const SITE_URL = document.querySelector('base')?.href || '/alt-heaux/';
        const CART_API_URL = SITE_URL + 'pages/cart_api.php';
        
        /**
         * Fetch and display cart preview
         */
        function loadCartPreview() {
            const formData = new FormData();
            formData.append('action', 'get_items');
            
            fetch(CART_API_URL, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const previewContent = document.getElementById('cart-preview-items');
                
                if (data.success && data.items && data.items.length > 0) {
                    let html = '';
                    
                    data.items.forEach(item => {
                        const itemTotal = (item.price * item.quantity).toFixed(2);
                        const img = item.image_url ? `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name)}" class="cart-preview-item-img">` : '';
                        
                        html += `
                            <div class="cart-preview-item">
                                ${img}
                                <div class="cart-preview-item-info">
                                    <div class="cart-preview-item-name">${escapeHtml(item.name)}</div>
                                    <div class="cart-preview-item-qty">Qty: ${item.quantity}</div>
                                    <div class="cart-preview-item-price">$${itemTotal}</div>
                                </div>
                            </div>
                        `;
                    });
                    
                    previewContent.innerHTML = html;
                    
                    // Add total to footer
                    const footer = document.querySelector('.cart-preview-footer');
                    const totalHtml = `
                        <div class="cart-preview-total">
                            <span>Total:</span>
                            <span>$${data.total.toFixed(2)}</span>
                        </div>
                    `;
                    
                    const existingTotal = footer.querySelector('.cart-preview-total');
                    if (existingTotal) {
                        existingTotal.remove();
                    }
                    footer.insertAdjacentHTML('beforeend', totalHtml);
                } else {
                    previewContent.innerHTML = '<div class="cart-preview-empty"><p>Cart is empty</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading cart preview:', error);
                document.getElementById('cart-preview-items').innerHTML = '<p class="text-muted text-center py-3">Error loading cart</p>';
            });
        }
        
        /**
         * Escape HTML special characters to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Load cart preview on hover
         */
        document.addEventListener('DOMContentLoaded', function() {
            const cartWrapper = document.querySelector('.cart-dropdown-wrapper');
            if (cartWrapper) {
                cartWrapper.addEventListener('mouseenter', loadCartPreview);
            }
        });
    </script>

    <script>
        /**
         * Global event delegation for cart button interactions
         * This listener works on current AND dynamically added elements
         */
        document.addEventListener('click', function(e) {
            // Handle remove item buttons in dropdown - call main.js removeFromCart
            if (e.target.closest('button[data-action="remove-item"]')) {
                const btn = e.target.closest('button[data-action="remove-item"]');
                const productId = btn.dataset.productId;
                const color = btn.dataset.color || '';
                const size = btn.dataset.size || '';
                
                e.preventDefault();
                e.stopPropagation();
                
                // Call main.js removeFromCart function which handles the API call and page reload
                if (typeof removeFromCart === 'function') {
                    removeFromCart(productId, color, size);
                } else {
                    alert('System is loading. Please try again in a moment.');
                }
                return;
            }
        });

    </script>