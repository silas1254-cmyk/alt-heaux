/**
 * ALT HEAUX - Main JavaScript
 * Cart management, UI interactions, notifications
 */

// SITE_URL and CART_API_URL are defined in header.php
// const SITE_URL = document.querySelector('base')?.href || '/alt-heaux/';
// const CART_API_URL = SITE_URL + 'pages/cart_api.php';

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize add to cart buttons
    initializeCartButtons();
    
    // Update cart count on page load
    updateCartBadge();
    
    // Initialize Bootstrap tooltips
    initializeTooltips();
    
    // Initialize smooth scrolling
    initializeSmoothScroll();
});

/**
 * Initialize add to cart button listeners
 */
function initializeCartButtons() {
    const addToCartButtons = document.querySelectorAll('[data-add-to-cart]');
    console.log('Found ' + addToCartButtons.length + ' add-to-cart buttons');
    addToCartButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name') || '';
            const quantityAttr = this.getAttribute('data-quantity');
            let quantity = parseInt(quantityAttr || 1);
            
            // Ensure quantity is a valid positive integer, default to 1
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
            }
            
            console.log('Button ' + index + ' clicked - attributes:', {
                'data-product-id': productId,
                'data-product-name': productName,
                'data-quantity': quantityAttr,
                'parsed quantity': quantity,
                'all attributes': this.attributes
            });
            addToCart(productId, quantity, productName);
        });
    });
}

/**
 * Add product to cart via AJAX
 */
function addToCart(productId, quantity = 1, productName = '') {
    // Validate and sanitize quantity
    quantity = parseInt(quantity) || 1;
    if (quantity < 1) {
        quantity = 1;
    }
    
    console.log('Sending request to:', CART_API_URL, {productId, quantity});
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch(CART_API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showToast('success', data.message || 'Added to cart');
            updateCartBadge();
            
            // Refresh cart page if open
            if (window.location.pathname.includes('pages') && window.location.search.includes('cart')) {
                setTimeout(() => location.reload(), 500);
            }
        } else {
            showToast('danger', data.message || 'Error adding to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('danger', 'Network error. Please try again.');
    });
}

/**
 * Remove product from cart
 */
function removeFromCart(productId, color = '', size = '') {
    console.log('removeFromCart called with productId:', productId, 'color:', color, 'size:', size);
    console.log('Sending request to:', CART_API_URL);
    
    if (!confirm('Remove this item from your cart?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);
    if (color) formData.append('color', color);
    if (size) formData.append('size', size);
    
    fetch(CART_API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Remove response:', data);
        if (data.success) {
            console.log('Item removed successfully');
            showToast('success', 'Item removed from cart');
            updateCartBadge();
            
            // Reload cart page if on cart.php
            if (window.location.href.includes('pages/cart.php')) {
                console.log('Reloading cart page...');
                setTimeout(() => location.reload(), 500);
            }
        } else {
            console.error('Remove failed:', data.message);
            showToast('danger', data.message || 'Error removing item');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showToast('danger', 'Network error');
    });
}

/**
 * Update item quantity in cart
 */
function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch(CART_API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartBadge();
            
            // Dynamically update cart page if open
            if (window.location.pathname.includes('cart.php')) {
                refreshCartDisplay();
            }
        } else {
            showToast('danger', 'Error updating cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Update cart quantity by direction (increase/decrease)
 * Used for +/- buttons on cart page
 */
function updateQuantityByDirection(productId, direction, color = '', size = '') {
    console.log('updateQuantityByDirection called:', {productId, direction, color, size});
    
    // Ensure productId is a number
    productId = parseInt(productId);
    if (!productId || !direction) {
        showToast('danger', 'Invalid parameters for quantity update');
        return;
    }
    
    const requestData = {
        action: 'update_quantity',
        product_id: productId,
        direction: direction,
        color: color || '',
        size: size || ''
    };
    
    console.log('Sending request:', requestData);
    
    fetch(CART_API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Update response:', data);
        if (data.success) {
            console.log('Quantity updated successfully');
            showToast('success', 'Quantity updated');
            updateCartBadge();
            // Refresh page to show updated totals
            console.log('Reloading page...');
            setTimeout(() => location.reload(), 300);
        } else {
            console.error('Update failed:', data.message);
            showToast('danger', 'Error updating quantity: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showToast('danger', 'Network error updating quantity');
    });
}

/**
 * Update cart count badge in header
 */
function updateCartBadge() {
    const formData = new FormData();
    formData.append('action', 'get_count');
    
    fetch(CART_API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector('.cart-count-badge');
        if (badge) {
            badge.textContent = data.cart_count || 0;
            badge.style.display = data.cart_count > 0 ? 'inline' : 'none';
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Update cart totals on cart page
 */
function updateCartTotals() {
    // This will be handled by cart.php page load
    location.reload();
}

/**
 * Refresh cart display without full page reload
 */
function refreshCartDisplay() {
    console.log('Refreshing cart display...');
    
    // Simple solution: just reload the page to refresh cart
    // The event delegation will automatically work on the new page
    setTimeout(() => {
        location.reload();
    }, 500);
}

/**
 * Show add to cart modal
 */
function showAddToCartModal(productName, message) {
    const modalHtml = `
        <div class="modal fade" id="addToCartModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>Added to Cart!
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <div class="mb-3">
                            <i class="fas fa-shopping-cart fa-4x text-success"></i>
                        </div>
                        <h4 class="mb-2">${productName}</h4>
                        <p class="text-muted mb-4">${message}</p>
                        <div class="alert alert-info mb-4">
                            <small><i class="fas fa-info-circle me-2"></i>Item added to your shopping cart</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </button>
                        <a href="${SITE_URL}pages/cart.php" class="btn btn-success">
                            <i class="fas fa-shopping-cart me-2"></i>View Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('addToCartModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Insert modal HTML
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('addToCartModal'));
    modal.show();
    
    // Remove modal from DOM after it's hidden
    document.getElementById('addToCartModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

/**
 * Show toast notification
 */
function showToast(type, message) {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Ensure toast container exists
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create temporary wrapper div
    const wrapper = document.createElement('div');
    wrapper.innerHTML = toastHtml.trim();
    const toastElement = wrapper.firstElementChild;
    
    // Add to container
    toastContainer.appendChild(toastElement);
    
    // Show with Bootstrap
    try {
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remove from DOM after hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    } catch (error) {
        console.error('Toast error:', error);
        // Fallback - just log to console
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize smooth scrolling for anchor links
 */
function initializeSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Initialize cart page event listeners
 * Called when main.js loads to set up delegation for cart actions
 */
function initializeCartEventListeners() {
    console.log('Initializing cart event listeners...');
    console.log('CART_API_URL:', typeof CART_API_URL !== 'undefined' ? CART_API_URL : 'UNDEFINED');
    console.log('updateQuantityByDirection function:', typeof updateQuantityByDirection);
    
    // Debug: Find all cart action buttons
    const qtyIncreaseButtons = document.querySelectorAll('[data-action="qty-increase"]');
    const qtyDecreaseButtons = document.querySelectorAll('[data-action="qty-decrease"]');
    console.log('Found ' + qtyIncreaseButtons.length + ' qty-increase buttons');
    console.log('Found ' + qtyDecreaseButtons.length + ' qty-decrease buttons');
    
    // Direct listeners on increase buttons
    qtyIncreaseButtons.forEach((btn, idx) => {
        console.log('Attaching click listener to qty-increase button ' + idx);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.dataset.productId;
            const color = this.dataset.color || '';
            const size = this.dataset.size || '';
            console.log('Qty increase clicked:', {productId, color, size});
            updateQuantityByDirection(productId, 'up', color, size);
            return false;
        });
    });
    
    // Direct listeners on decrease buttons
    qtyDecreaseButtons.forEach((btn, idx) => {
        console.log('Attaching click listener to qty-decrease button ' + idx);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.dataset.productId;
            const color = this.dataset.color || '';
            const size = this.dataset.size || '';
            console.log('Qty decrease clicked:', {productId, color, size});
            updateQuantityByDirection(productId, 'down', color, size);
            return false;
        });
    });
}

// Auto-initialize cart listeners when main.js loads
console.log('main.js loaded, readyState:', document.readyState);
initializeCartEventListeners();


