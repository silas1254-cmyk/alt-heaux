<!-- Product Detail Modal -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="productTitle">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Images Section -->
                    <div class="col-md-6">
                        <div class="product-image-carousel">
                            <div class="main-image mb-3">
                                <img id="mainProductImage" src="" alt="Product" class="img-fluid rounded" style="max-width: 100%; height: 400px; object-fit: cover;">
                            </div>
                            <div class="thumbnail-gallery" id="thumbnailGallery">
                                <!-- Thumbnails will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Product Info Section -->
                    <div class="col-md-6">
                        <h3 id="productName"></h3>
                        <div class="mb-3">
                            <span class="badge bg-success" id="productCategory"></span>
                        </div>

                        <div class="mb-4">
                            <h4 id="productPrice" class="text-success fw-bold"></h4>
                            <p id="productDescription"></p>
                        </div>

                        <form id="addToCartForm">
                            <input type="hidden" id="productIdInput" name="product_id">

                            <!-- Color Selection -->
                            <div class="mb-3" id="colorSection" style="display: none;">
                                <label class="form-label">Color</label>
                                <div id="colorOptions" class="btn-group-vertical w-100" role="group">
                                    <!-- Color buttons will be populated here -->
                                </div>
                            </div>

                            <!-- Size Selection -->
                            <div class="mb-3" id="sizeSection" style="display: none;">
                                <label class="form-label">Size</label>
                                <div id="sizeOptions" class="btn-group-vertical w-100" role="group">
                                    <!-- Size buttons will be populated here -->
                                </div>
                            </div>

                            <!-- Quantity Selection -->
                            <div class="mb-4">
                                <label class="form-label">Quantity</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary" type="button" id="decreaseQty">−</button>
                                    <input type="number" class="form-control text-center" id="quantityInput" name="quantity" value="1" min="1" max="999">
                                    <button class="btn btn-outline-secondary" type="button" id="increaseQty">+</button>
                                </div>
                            </div>

                            <!-- Add to Cart Button -->
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </form>

                        <!-- Stock Status -->
                        <div class="mt-3">
                            <small id="stockStatus" class="text-muted"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.thumbnail-gallery {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 0;
}

.thumbnail-gallery img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color 0.3s ease;
}

.thumbnail-gallery img.active {
    border-color: var(--accent);
}

.btn-group-vertical .btn {
    border-radius: 4px;
    text-align: left;
    border: 1px solid #ddd;
    margin-bottom: 8px;
    background-color: #f9f9f9;
    color: #333;
    transition: all 0.3s ease;
}

.btn-group-vertical .btn.active {
    background-color: var(--accent);
    border-color: var(--accent);
    color: white;
    font-weight: 600;
}

.btn-group-vertical .btn:hover {
    background-color: #f0f0f0;
}

.btn-group-vertical .btn.active:hover {
    background-color: #e8d5b5;
}

.product-image-carousel {
    position: sticky;
    top: 20px;
}

#productName {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

#productPrice {
    font-size: 1.5rem;
    font-weight: 700;
}

#productDescription {
    color: #666;
    line-height: 1.6;
}

#addToCartForm .form-label {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.input-group {
    max-width: 150px;
}

.input-group .btn {
    background-color: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
    font-weight: 600;
    padding: 0.5rem 1rem;
}

.input-group .btn:hover {
    background-color: #e8e8e8;
}

#quantityInput {
    border: 1px solid #ddd;
    font-weight: 600;
}

.modal-content {
    border-radius: 8px;
}

.modal-header {
    border-bottom: 2px solid var(--accent);
}

.modal-title {
    font-weight: 700;
    color: var(--text-dark);
}
</style>
