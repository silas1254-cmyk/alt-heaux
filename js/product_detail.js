/**
 * Product Detail Modal Handler
 * Handles image gallery, color/size selection, and add to cart
 */

let currentProduct = null;
let selectedColor = null;
let selectedSize = null;

// Open product detail modal
function openProductDetail(productId) {
    // Fetch product details
    fetch(`${SITE_URL}pages/product_api.php?action=get_product&id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProductDetail(data.product);
                const modal = new bootstrap.Modal(document.getElementById('productDetailModal'));
                modal.show();
            } else {
                alert('Error loading product details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading product details');
        });
}

// Display product details in modal
function displayProductDetail(product) {
    currentProduct = product;
    selectedColor = null;
    selectedSize = null;

    // Set product info
    document.getElementById('productName').textContent = product.name;
    document.getElementById('productPrice').textContent = '$' + parseFloat(product.price).toFixed(2);
    document.getElementById('productDescription').textContent = product.description || 'No description available';
    document.getElementById('productCategory').textContent = product.category;
    document.getElementById('productIdInput').value = product.id;
    document.getElementById('stockStatus').textContent = `In stock: ${product.quantity} items available`;

    // Populate images
    populateImages(product.images || []);

    // Populate colors
    const colors = product.colors || [];
    if (colors.length > 0) {
        document.getElementById('colorSection').style.display = 'block';
        populateColors(colors);
    } else {
        document.getElementById('colorSection').style.display = 'none';
        selectedColor = null;
    }

    // Populate sizes
    const sizes = product.sizes || [];
    if (sizes.length > 0) {
        document.getElementById('sizeSection').style.display = 'block';
        populateSizes(sizes);
    } else {
        document.getElementById('sizeSection').style.display = 'none';
        selectedSize = null;
    }

    // Reset quantity
    document.getElementById('quantityInput').value = 1;
    document.getElementById('quantityInput').max = product.quantity;
}

// Populate images in carousel
function populateImages(images) {
    const mainImage = document.getElementById('mainProductImage');
    const thumbnailGallery = document.getElementById('thumbnailGallery');

    thumbnailGallery.innerHTML = '';

    if (images.length === 0) {
        mainImage.src = `${SITE_URL}images/placeholder.png`;
        return;
    }

    // Set first image as main
    const firstImage = images[0];
    mainImage.src = `${SITE_URL}${firstImage.image_path}`;
    mainImage.dataset.imageId = firstImage.id;

    // Create thumbnails
    images.forEach((image, index) => {
        const thumb = document.createElement('img');
        thumb.src = `${SITE_URL}${image.image_path}`;
        thumb.alt = image.image_name;
        thumb.dataset.imageId = image.id;
        thumb.dataset.imagePath = image.image_path;

        if (index === 0) {
            thumb.classList.add('active');
        }

        thumb.addEventListener('click', () => switchImage(image));
        thumbnailGallery.appendChild(thumb);
    });
}

// Switch main image
function switchImage(image) {
    document.getElementById('mainProductImage').src = `${SITE_URL}${image.image_path}`;
    document.getElementById('mainProductImage').dataset.imageId = image.id;

    // Update thumbnail active state
    document.querySelectorAll('#thumbnailGallery img').forEach(thumb => {
        thumb.classList.remove('active');
        if (thumb.dataset.imageId == image.id) {
            thumb.classList.add('active');
        }
    });
}

// Populate color options
function populateColors(colors) {
    const colorOptions = document.getElementById('colorOptions');
    colorOptions.innerHTML = '';

    colors.forEach(color => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary';
        btn.textContent = color.color_name;
        btn.dataset.colorId = color.id;
        btn.dataset.colorName = color.color_name;

        if (color.color_code) {
            btn.style.borderLeftColor = color.color_code;
            btn.style.borderLeftWidth = '4px';
        }

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            selectColor(color, btn);
        });

        colorOptions.appendChild(btn);
    });
}

// Select color
function selectColor(color, button) {
    // Remove active class from all color buttons
    document.querySelectorAll('#colorOptions .btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Add active class to selected button
    button.classList.add('active');
    selectedColor = color.color_name;
}

// Populate size options
function populateSizes(sizes) {
    const sizeOptions = document.getElementById('sizeOptions');
    sizeOptions.innerHTML = '';

    sizes.forEach(size => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary';
        btn.textContent = size.size_name;
        btn.dataset.sizeId = size.id;
        btn.dataset.sizeName = size.size_name;

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            selectSize(size, btn);
        });

        sizeOptions.appendChild(btn);
    });
}

// Select size
function selectSize(size, button) {
    // Remove active class from all size buttons
    document.querySelectorAll('#sizeOptions .btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Add active class to selected button
    button.classList.add('active');
    selectedSize = size.size_name;
}

// Quantity controls
document.addEventListener('DOMContentLoaded', () => {
    const quantityInput = document.getElementById('quantityInput');
    const increaseBtn = document.getElementById('increaseQty');
    const decreaseBtn = document.getElementById('decreaseQty');

    if (increaseBtn) {
        increaseBtn.addEventListener('click', () => {
            const currentValue = parseInt(quantityInput.value);
            const maxValue = parseInt(quantityInput.max);
            if (currentValue < maxValue) {
                quantityInput.value = currentValue + 1;
            }
        });
    }

    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', () => {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
    }

    // Handle form submission
    const addToCartForm = document.getElementById('addToCartForm');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', (e) => {
            e.preventDefault();
            addToCartFromModal();
        });
    }
});

// Add to cart from modal
function addToCartFromModal() {
    const productId = document.getElementById('productIdInput').value;
    const quantity = parseInt(document.getElementById('quantityInput').value);
    const color = selectedColor;
    const size = selectedSize;

    if (!productId || quantity < 1) {
        showToast('danger', 'Please enter a valid quantity');
        return;
    }

    // Check if color is required but not selected
    if (document.getElementById('colorSection').style.display !== 'none' && !color) {
        showToast('danger', 'Please select a color');
        return;
    }

    // Check if size is required but not selected
    if (document.getElementById('sizeSection').style.display !== 'none' && !size) {
        showToast('danger', 'Please select a size');
        return;
    }

    // Add to cart via main.js function
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    if (color) formData.append('color', color);
    if (size) formData.append('size', size);

    fetch(`${SITE_URL}pages/cart_api.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('productDetailModal'));
            modal.hide();

            // Update cart badge
            updateCartBadge();

            // Show success message - using main.js showToast
            showToast('success', 'Product added to cart!');
        } else {
            showToast('danger', data.message || 'Error adding to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('danger', 'Error adding to cart');
    });
}

// Show notification (you can enhance this with a toast library later)
// Note: showToast() is defined in main.js and available globally for notifications
