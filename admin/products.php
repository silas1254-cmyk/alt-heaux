<?php
require '../includes/config.php';
requireAdmin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        
        if (empty($name) || empty($price)) {
            $error = 'Product name and price are required.';
        } else {
            $query = "INSERT INTO products (name, description, price, category, quantity) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssdsi', $name, $description, $price, $category, $quantity);
            
            if ($stmt->execute()) {
                $new_product_id = $stmt->insert_id;
                $stmt->close();
                // Log the update
                logWebsiteUpdate('Product', "Created product: $name", "New product added with ID $new_product_id, Price: $$price", 'Create', $conn);
                // Redirect to edit page to add images, colors, sizes
                header("Location: products.php?edit=" . $new_product_id . "&new=1");
                exit();
            } else {
                $error = 'Error adding product: ' . $conn->error;
            }
        }
    } elseif ($action === 'edit') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        
        if (empty($name) || empty($price)) {
            $error = 'Product name and price are required.';
        } else {
            $query = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, quantity = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssdsii', $name, $description, $price, $category, $quantity, $product_id);
            
            if ($stmt->execute()) {
                $success = 'Product updated successfully!';
                // Log the update
                logWebsiteUpdate('Product', "Updated product: $name", "Modified product details including price, category, and quantity", 'Update', $conn);
            } else {
                $error = 'Error updating product: ' . $conn->error;
            }
        }
    } elseif ($action === 'delete') {
        $product_id = intval($_POST['product_id'] ?? 0);
        
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $product_id);
        
        if ($stmt->execute()) {
            $success = 'Product deleted successfully!';
            // Log the update
            logWebsiteUpdate('Product', "Deleted product", "Removed product from inventory", 'Delete', $conn);
        } else {
            $error = 'Error deleting product: ' . $conn->error;
        }
    } elseif ($action === 'upload_images') {
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id > 0 && !empty($_FILES['images']['name'][0])) {
            $uploaded = uploadProductImages($_FILES, $product_id);
            
            if (isset($uploaded['error'])) {
                $error = $uploaded['error'];
            } else {
                // Add each uploaded image to database
                foreach ($uploaded as $file) {
                    $display_order = 0;
                    $is_primary = false;
                    addProductImage($product_id, $file['path'], $file['name'], $display_order, $is_primary, $conn);
                }
                $success = count($uploaded) . ' image(s) uploaded successfully!';
            }
        } else {
            $error = 'Product ID required and at least one image must be selected.';
        }
    } elseif ($action === 'delete_image') {
        $image_id = intval($_POST['image_id'] ?? 0);
        
        if ($image_id > 0) {
            // Get image path for deletion
            $query = "SELECT image_path, product_id FROM product_images WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $image_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $image = $result->fetch_assoc();
            
            if ($image) {
                // Delete file from disk
                $file_path = SITE_ROOT . $image['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Delete from database
                deleteProductImage($image_id, $conn);
                $success = 'Image deleted successfully!';
            }
        }
    } elseif ($action === 'set_primary_image') {
        $image_id = intval($_POST['image_id'] ?? 0);
        
        if ($image_id > 0) {
            $query = "SELECT product_id FROM product_images WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $image_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $image = $result->fetch_assoc();
            
            if ($image) {
                // Unset all primary images for this product
                $update_query = "UPDATE product_images SET is_primary = FALSE WHERE product_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('i', $image['product_id']);
                $update_stmt->execute();
                
                // Set this as primary
                $set_primary = "UPDATE product_images SET is_primary = TRUE WHERE id = ?";
                $primary_stmt = $conn->prepare($set_primary);
                $primary_stmt->bind_param('i', $image_id);
                $primary_stmt->execute();
                
                $success = 'Primary image updated!';
            }
        }
    } elseif ($action === 'add_color') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $color_name = trim($_POST['color_name'] ?? '');
        $color_code = trim($_POST['color_code'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        if ($product_id > 0 && !empty($color_name)) {
            if (addProductColor($product_id, $color_name, $color_code, $display_order, $conn)) {
                $success = 'Color added successfully!';
            } else {
                $error = 'Error adding color.';
            }
        } else {
            $error = 'Color name required.';
        }
    } elseif ($action === 'delete_color') {
        $color_id = intval($_POST['color_id'] ?? 0);
        
        if ($color_id > 0) {
            if (deleteProductColor($color_id, $conn)) {
                $success = 'Color deleted successfully!';
            } else {
                $error = 'Error deleting color.';
            }
        }
    } elseif ($action === 'add_size') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $size_name = trim($_POST['size_name'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        if ($product_id > 0 && !empty($size_name)) {
            if (addProductSize($product_id, $size_name, $display_order, $conn)) {
                $success = 'Size added successfully!';
            } else {
                $error = 'Error adding size.';
            }
        } else {
            $error = 'Size name required.';
        }
    } elseif ($action === 'delete_size') {
        $size_id = intval($_POST['size_id'] ?? 0);
        
        if ($size_id > 0) {
            if (deleteProductSize($size_id, $conn)) {
                $success = 'Size deleted successfully!';
            } else {
                $error = 'Error deleting size.';
            }
        }
    }
}

// Fetch all categories
$all_categories = getAllCategories($conn);

// Fetch all products
$products_query = "SELECT * FROM products ORDER BY created_at DESC";
$products_result = $conn->query($products_query);
$products = $products_result->fetch_all(MYSQLI_ASSOC);

// Get product to edit if editing
$edit_product = null;
$product_images = [];
$product_colors = [];
$product_sizes = [];
$is_new_product = false;

if (isset($_GET['edit'])) {
    $product_id = intval($_GET['edit']);
    $edit_product = getProductDetails($product_id, $conn);
    $is_new_product = isset($_GET['new']) && $_GET['new'] == 1;
    
    if ($edit_product) {
        $product_images = $edit_product['images'] ?? [];
        $product_colors = $edit_product['colors'] ?? [];
        $product_sizes = $edit_product['sizes'] ?? [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin_file_manager.css">
    <style>
        .image-thumbnail {
            position: relative;
            display: inline-block;
            margin: 5px;
        }
        
        .image-thumbnail img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
        }
        
        .image-thumbnail.primary img {
            border-color: var(--accent-gold);
            box-shadow: 0 0 10px rgba(201, 169, 97, 0.5);
        }
        
        .image-thumbnail .badge-primary {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: var(--accent-gold);
        }
        
        .image-actions {
            position: absolute;
            bottom: 5px;
            left: 5px;
            right: 5px;
            display: none;
            gap: 5px;
        }
        
        .image-thumbnail:hover .image-actions {
            display: flex;
        }
        
        .image-actions .btn {
            flex: 1;
            padding: 4px 6px;
            font-size: 0.75rem;
        }
        
        .color-preview {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid #ccc;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .variant-item {
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include('_sidebar.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="page-header">
                    <h1><i class="fas fa-box"></i> Product Management</h1>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- If editing a product, show detailed edit panel -->
                <?php if ($edit_product): ?>
                    <?php if ($is_new_product): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Product created successfully! Now add images, colors, and sizes.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <!-- Product Basic Info -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Product Information</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">

                                        <div class="mb-3">
                                            <label for="name" class="form-label">Product Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($edit_product['name']); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="price" class="form-label">Price *</label>
                                                <input type="number" class="form-control" id="price" name="price" step="0.01" required value="<?php echo $edit_product['price']; ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="quantity" class="form-label">Quantity</label>
                                                <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $edit_product['quantity']; ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-control" id="category" name="category">
                                                <option value="">Select Category</option>
                                                <?php foreach ($all_categories as $cat): ?>
                                                    <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo ($edit_product['category'] === $cat['name']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-0">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Changes
                                            </button>
                                            <a href="products.php" class="btn btn-secondary">Back to Products</a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Product Images -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-images"></i> Product Images</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($product_images)): ?>
                                        <div class="mb-4">
                                            <p class="text-muted mb-3">Click images to set as primary (gold border = primary)</p>
                                            <div class="d-flex flex-wrap">
                                                <?php foreach ($product_images as $image): ?>
                                                    <div class="image-thumbnail <?php echo $image['is_primary'] ? 'primary' : ''; ?>">
                                                        <img src="<?php echo SITE_URL . htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['image_name']); ?>" title="<?php echo htmlspecialchars($image['image_name']); ?>">
                                                        <?php if ($image['is_primary']): ?>
                                                            <span class="badge badge-primary" style="background-color: var(--accent-gold);">PRIMARY</span>
                                                        <?php endif; ?>
                                                        <div class="image-actions">
                                                            <form method="POST" style="flex: 1;">
                                                                <input type="hidden" name="action" value="set_primary_image">
                                                                <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-warning w-100" title="Set as primary">
                                                                    <i class="fas fa-star"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="flex: 1;" onsubmit="return confirm('Delete this image?');">
                                                                <input type="hidden" name="action" value="delete_image">
                                                                <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger w-100" title="Delete image">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center py-4">No images yet. Upload some below.</p>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label for="images" class="form-label">Upload Images</label>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="input-group">
                                                <input type="hidden" name="action" value="upload_images">
                                                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                                <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple required>
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="fas fa-upload"></i> Upload
                                                </button>
                                            </div>
                                            <small class="text-muted">Supports: JPEG, PNG, GIF, WebP. Max 5MB per image.</small>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Files for Download -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-file-download"></i> Download Files</h5>
                                </div>
                                <div class="card-body">
                                    <div class="file-manager-section">
                                        <div class="file-upload-form">
                                            <form id="fileUploadForm" enctype="multipart/form-data">
                                                <input type="hidden" id="product_id" value="<?php echo $edit_product['id']; ?>">
                                                
                                                <div class="form-group mb-3">
                                                    <label for="fileInput" class="form-label">Select File to Upload</label>
                                                    <input type="file" id="fileInput" name="file" class="form-control" required>
                                                    <small class="text-muted d-block mt-2">
                                                        Supported: zip, pdf, exe, dmg, rar, txt, doc, docx, xls, xlsx, ppt, pptx, 7z, iso (Max 500MB)
                                                    </small>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="displayName" class="form-label">Display Name *</label>
                                                    <input type="text" id="displayName" name="display_name" class="form-control" placeholder="e.g., Software Setup" required>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="version" class="form-label">Version</label>
                                                    <input type="text" id="version" name="version" class="form-control" placeholder="e.g., 1.0.0">
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="fileDescription" class="form-label">Description</label>
                                                    <textarea id="fileDescription" name="description" class="form-control" rows="2" placeholder="What does this file contain?"></textarea>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="sizeVariant" class="form-label">Size Variant <span class="text-danger">*</span></label>
                                                    <select id="sizeVariant" name="size_variant" class="form-control" required>
                                                        <option value="">-- Select Size --</option>
                                                    </select>
                                                    <small class="text-muted d-block mt-1">Sizes defined for this product below</small>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="colorVariant" class="form-label">Color Variant <span class="text-danger">*</span></label>
                                                    <select id="colorVariant" name="color_variant" class="form-control" required>
                                                        <option value="">-- Select Color --</option>
                                                    </select>
                                                    <small class="text-muted d-block mt-1">Colors defined for this product below</small>
                                                </div>

                                                <div class="file-info-display mb-3">
                                                    <div id="fileInfo" style="display: none;">
                                                        <strong>Selected File:</strong>
                                                        <div id="fileName"></div>
                                                        <div id="fileSize"></div>
                                                        <div id="fileType"></div>
                                                    </div>
                                                </div>

                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-upload"></i> Upload File
                                                </button>
                                            </form>
                                        </div>

                                        <div id="filesList" class="files-list mt-4"></div>
                                    </div>
                                </div>
                            </div>

                            <script>
                            // Product sizes and colors data from PHP
                            const productSizes = <?php echo json_encode($product_sizes); ?>;
                            const productColors = <?php echo json_encode($product_colors); ?>;
                            
                            // Load files list on page load
                            document.addEventListener('DOMContentLoaded', function() {
                                // Populate size dropdown
                                const sizeSelect = document.getElementById('sizeVariant');
                                productSizes.forEach(size => {
                                    const option = document.createElement('option');
                                    option.value = size.size_name;
                                    option.textContent = size.size_name;
                                    sizeSelect.appendChild(option);
                                });
                                
                                // Populate color dropdown
                                const colorSelect = document.getElementById('colorVariant');
                                productColors.forEach(color => {
                                    const option = document.createElement('option');
                                    option.value = color.color_name;
                                    option.textContent = color.color_name;
                                    colorSelect.appendChild(option);
                                });
                                
                                loadFilesList();
                                
                                // Handle form submission
                                const form = document.getElementById('fileUploadForm');
                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    uploadFile();
                                });
                                
                                // Show file info on selection
                                document.getElementById('fileInput').addEventListener('change', function() {
                                    const file = this.files[0];
                                    if (file) {
                                        const size = (file.size / 1024 / 1024).toFixed(2);
                                        document.getElementById('fileName').textContent = file.name;
                                        document.getElementById('fileSize').textContent = 'Size: ' + size + ' MB';
                                        document.getElementById('fileType').textContent = 'Type: ' + file.type;
                                        document.getElementById('fileInfo').style.display = 'block';
                                    }
                                });
                            });
                            
                            function loadFilesList() {
                                const productId = document.getElementById('product_id').value;
                                const filesList = document.getElementById('filesList');
                                
                                fetch(`../admin/files_api.php?action=list&product_id=${productId}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.files && data.files.length > 0) {
                                            let html = '<h6>Uploaded Files:</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>File Name</th><th>Display Name</th><th>Size</th><th>Variant</th><th>Action</th></tr></thead><tbody>';
                                            
                                            data.files.forEach(file => {
                                                const variant = file.size_variant || file.color_variant 
                                                    ? `${file.size_variant || '-'} / ${file.color_variant || '-'}`
                                                    : 'Generic';
                                                
                                                html += `<tr>
                                                    <td><small>${file.original_filename}</small></td>
                                                    <td><strong>${file.display_name}</strong></td>
                                                    <td><small>${(file.file_size / 1024 / 1024).toFixed(2)} MB</small></td>
                                                    <td><span class="badge bg-info">${variant}</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteFile(${file.id})">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>`;
                                            });
                                            
                                            html += '</tbody></table></div>';
                                            filesList.innerHTML = html;
                                        } else {
                                            filesList.innerHTML = '<p class="text-muted">No files uploaded yet.</p>';
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error loading files:', error);
                                        filesList.innerHTML = '<p class="text-danger">Error loading files.</p>';
                                    });
                            }
                            
                            function uploadFile() {
                                const productId = document.getElementById('product_id').value;
                                const fileInput = document.getElementById('fileInput');
                                const displayName = document.getElementById('displayName').value;
                                const version = document.getElementById('version').value;
                                const description = document.getElementById('fileDescription').value;
                                const sizeVariant = document.getElementById('sizeVariant').value;
                                const colorVariant = document.getElementById('colorVariant').value;
                                const file = fileInput.files[0];
                                
                                // Validation: File required
                                if (!file || !displayName) {
                                    alert('Please select a file and enter a display name.');
                                    return;
                                }
                                
                                // Validation: Size variant required
                                if (!sizeVariant) {
                                    alert('Size variant is required. Please select a size.');
                                    return;
                                }
                                
                                // Validation: Color variant required
                                if (!colorVariant) {
                                    alert('Color variant is required. Please select a color.');
                                    return;
                                }
                                
                                // Validation: Check for .exe files
                                const filename = file.name.toLowerCase();
                                if (filename.endsWith('.exe')) {
                                    alert('ERROR: .exe files are not allowed for security reasons.');
                                    return;
                                }
                                
                                // Additional check: scan filename for suspicious patterns
                                const suspiciousExtensions = ['.exe', '.bat', '.cmd', '.com', '.scr', '.vbs', '.js'];
                                const hasSuspicious = suspiciousExtensions.some(ext => filename.endsWith(ext));
                                if (hasSuspicious) {
                                    alert('ERROR: Executable files are not allowed. Please upload a safe file type.');
                                    return;
                                }
                                
                                const formData = new FormData();
                                formData.append('action', 'upload');
                                formData.append('product_id', productId);
                                formData.append('file', fileInput.files[0]);
                                formData.append('display_name', displayName);
                                formData.append('version', version);
                                formData.append('description', description);
                                formData.append('size_variant', sizeVariant);
                                formData.append('color_variant', colorVariant);
                                
                                fetch('../admin/files_api.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('File uploaded successfully!');
                                        document.getElementById('fileUploadForm').reset();
                                        document.getElementById('fileInfo').style.display = 'none';
                                        loadFilesList();
                                    } else {
                                        alert('Error: ' + (data.error || 'Upload failed'));
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Error uploading file: ' + error.message);
                                });
                            }
                            
                            function deleteFile(fileId) {
                                if (!confirm('Delete this file?')) return;
                                
                                const formData = new FormData();
                                formData.append('action', 'delete');
                                formData.append('file_id', fileId);
                                
                                fetch('../admin/files_api.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('File deleted successfully!');
                                        loadFilesList();
                                    } else {
                                        alert('Error: ' + (data.error || 'Delete failed'));
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Error deleting file: ' + error.message);
                                });
                            }
                            </script>
                        </div>

                        <!-- Product Variants (Colors & Sizes) -->
                        <div class="col-lg-4">
                            <!-- Colors -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-palette"></i> Colors</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($product_colors)): ?>
                                        <div class="mb-3">
                                            <?php foreach ($product_colors as $color): ?>
                                                <div class="variant-item">
                                                    <div style="flex: 1;">
                                                        <span class="color-preview" style="background-color: <?php echo htmlspecialchars($color['color_code'] ?? '#ccc'); ?>"></span>
                                                        <strong><?php echo htmlspecialchars($color['color_name']); ?></strong>
                                                        <?php if ($color['color_code']): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($color['color_code']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_color">
                                                        <input type="hidden" name="color_id" value="<?php echo $color['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this color?');">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" class="mb-3">
                                        <input type="hidden" name="action" value="add_color">
                                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                        <input type="hidden" name="display_order" value="<?php echo count($product_colors); ?>">

                                        <div class="mb-2">
                                            <input type="text" name="color_name" class="form-control form-control-sm" placeholder="Color name" required>
                                        </div>
                                        <div class="mb-2">
                                            <input type="color" name="color_code" class="form-control form-control-sm" value="#000000">
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-success w-100">
                                            <i class="fas fa-plus"></i> Add Color
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Sizes -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-ruler"></i> Sizes</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($product_sizes)): ?>
                                        <div class="mb-3">
                                            <?php foreach ($product_sizes as $size): ?>
                                                <div class="variant-item">
                                                    <div style="flex: 1;">
                                                        <strong><?php echo htmlspecialchars($size['size_name']); ?></strong>
                                                    </div>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_size">
                                                        <input type="hidden" name="size_id" value="<?php echo $size['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this size?');">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" class="mb-3">
                                        <input type="hidden" name="action" value="add_size">
                                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                        <input type="hidden" name="display_order" value="<?php echo count($product_sizes); ?>">

                                        <div class="mb-2">
                                            <input type="text" name="size_name" class="form-control form-control-sm" placeholder="e.g. S, M, L, XL" required>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-success w-100">
                                            <i class="fas fa-plus"></i> Add Size
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Products List View -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Products</h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fas fa-plus"></i> Add New Product
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($products)): ?>
                                <p class="text-muted text-center py-4">No products yet. Click "Add New Product" to create one.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Images</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($products as $product): 
                                                $prod_images = getProductImages($product['id'], $conn);
                                                $img_count = count($prod_images);
                                            ?>
                                                <tr>
                                                    <td><strong>#<?php echo $product['id']; ?></strong></td>
                                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></span></td>
                                                    <td><strong>$<?php echo number_format($product['price'], 2); ?></strong></td>
                                                    <td><?php echo $product['quantity']; ?></td>
                                                    <td>
                                                        <?php if ($img_count > 0): ?>
                                                            <span class="badge bg-success"><i class="fas fa-image"></i> <?php echo $img_count; ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">0 images</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><small><?php echo date('M d, Y', strtotime($product['created_at'])); ?></small></td>
                                                    <td>
                                                        <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-pencil"></i> Edit
                                                        </a>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label for="new_name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="new_name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_description" class="form-label">Description</label>
                            <textarea class="form-control" id="new_description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_price" class="form-label">Price *</label>
                                <input type="number" class="form-control" id="new_price" name="price" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="new_quantity" name="quantity" value="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_category" class="form-label">Category</label>
                            <select class="form-control" id="new_category" name="category">
                                <option value="">Select Category</option>
                                <option value="T-Shirts">T-Shirts</option>
                                <option value="Hoodies">Hoodies</option>
                                <option value="Pants">Pants</option>
                                <option value="Jackets">Jackets</option>
                                <option value="Accessories">Accessories</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" id="deleteProductId" value="">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin_file_manager.js"></script>
    <script>
        // Declare fileManager globally (avoid duplicate declarations)
        var fileManager = null;

        function deleteProduct(productId) {
            document.getElementById('deleteProductId').value = productId;
            new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
        }

        // Initialize file manager if editing a product
        <?php if (isset($_GET['edit'])): ?>
        document.addEventListener('DOMContentLoaded', () => {
            const productId = document.getElementById('product_id')?.value;
            if (productId) {
                fileManager = new ProductFileManager(productId);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
