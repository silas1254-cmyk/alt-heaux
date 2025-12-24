<?php
require '../includes/config.php';
requireAdmin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Check if this is a JSON request (from AJAX drag-drop)
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($content_type, 'application/json') !== false) {
        $json = json_decode(file_get_contents('php://input'), true);
        $action = $json['action'] ?? '';
        $_POST = $json; // Merge JSON into $_POST for consistency
    }
    
    if ($action === 'reorder') {
        // Handle AJAX reorder request
        $order = $_POST['order'] ?? [];
        if (!empty($order)) {
            foreach ($order as $item) {
                $product_id = intval($item['id'] ?? 0);
                $position = intval($item['position'] ?? 0);
                if ($product_id > 0) {
                    $update_query = "UPDATE products SET display_order = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param('ii', $position, $product_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
            // Log the update
            logWebsiteUpdate('Product', "Reordered products", "Updated product display order", 'Update', $conn);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No order data provided']);
            exit;
        }
    } elseif ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $is_hidden = isset($_POST['is_hidden']) && $_POST['is_hidden'] === 'on' ? 1 : 0;
        
        if (empty($name) || empty($price)) {
            $error = 'Product name and price are required.';
        } else {
            $query = "INSERT INTO products (name, description, price, category_id, quantity, is_hidden) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssdiii', $name, $description, $price, $category_id, $quantity, $is_hidden);
            
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
        $category_id = intval($_POST['category'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        
        if (empty($name) || empty($price)) {
            $error = 'Product name and price are required.';
        } else {
            $query = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, quantity = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssdiii', $name, $description, $price, $category_id, $quantity, $product_id);
            
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
    } elseif ($action === 'toggle_visibility') {
        header('Content-Type: application/json');
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        
        // Get current hidden status
        $check_query = "SELECT is_hidden FROM products WHERE id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        $product = $result->fetch_assoc();
        $new_hidden_status = $product['is_hidden'] ? 0 : 1;
        
        // Update hidden status
        $update_query = "UPDATE products SET is_hidden = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ii', $new_hidden_status, $product_id);
        
        if ($stmt->execute()) {
            // Get product name for logging
            $name_query = "SELECT name FROM products WHERE id = ?";
            $name_stmt = $conn->prepare($name_query);
            $name_stmt->bind_param('i', $product_id);
            $name_stmt->execute();
            $name_result = $name_stmt->get_result();
            $name_row = $name_result->fetch_assoc();
            $product_name = $name_row['name'] ?? 'Unknown';
            
            // Log the action
            if ($new_hidden_status) {
                logWebsiteUpdate('Product', "Hid product: $product_name", "Product is now hidden from public site", 'Hide', $conn);
            } else {
                logWebsiteUpdate('Product', "Unhid product: $product_name", "Product is now visible on public site", 'Unhide', $conn);
            }
            
            echo json_encode(['success' => true, 'message' => $new_hidden_status ? 'Product hidden' : 'Product unhidden']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating product visibility']);
            exit;
        }
    } elseif ($action === 'upload_showcase_image') {
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id > 0 && !empty($_FILES['showcase_image']['name'])) {
            // Handle single image upload
            $file = $_FILES['showcase_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            // Validate file type
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'File is too large. Maximum size: 5MB';
            } else {
                // Create upload directory if it doesn't exist
                $upload_dir = SITE_ROOT . 'images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'product_' . $product_id . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Store relative path for database
                    $db_path = 'images/products/' . $filename;
                    
                    // Remove any existing primary image for this product
                    $delete_query = "DELETE FROM product_images WHERE product_id = ? AND is_primary = TRUE";
                    $del_stmt = $conn->prepare($delete_query);
                    $del_stmt->bind_param('i', $product_id);
                    $del_stmt->execute();
                    
                    // Add new image as primary
                    addProductImage($product_id, $db_path, $filename, 0, true, $conn);
                    $success = 'Showcase image uploaded and set successfully!';
                } else {
                    $error = 'Failed to upload file. Check directory permissions.';
                }
            }
        } else {
            $error = 'Product ID required and an image must be selected.';
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

// Fetch all products with category names
$products_query = "SELECT p.*, COALESCE(c.name, 'Uncategorized') as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.display_order ASC, p.name ASC";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin_file_manager.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include('_sidebar.php'); ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1><i class="bi bi-box"></i> Product Management</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    ✓ <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ✗ <?php echo $error; ?>
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
                                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Product Information</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">

                                        <!-- Basic Information -->
                                        <div class="form-section mb-4">
                                            <h6 class="text-secondary font-weight-bold mb-3">
                                                <i class="bi bi-file-text"></i> Basic Information
                                            </h6>
                                            <div class="mb-3">
                                                <label for="name" class="form-label"><i class="bi bi-tag"></i> Product Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($edit_product['name']); ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="description" class="form-label"><i class="bi bi-pencil-square"></i> Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter a detailed product description..."><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                                            </div>
                                        </div>

                                        <hr class="my-4" style="border-color: rgba(66, 165, 245, 0.2);">

                                        <!-- Pricing & Inventory -->
                                        <div class="form-section mb-4">
                                            <h6 class="text-secondary font-weight-bold mb-3">
                                                <i class="bi bi-cash-coin"></i> Pricing & Inventory
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="price" class="form-label"><i class="bi bi-currency-dollar"></i> Price *</label>
                                                    <input type="number" class="form-control" id="price" name="price" step="0.01" required value="<?php echo $edit_product['price']; ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="quantity" class="form-label"><i class="bi bi-box-seam"></i> Quantity</label>
                                                    <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $edit_product['quantity']; ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4" style="border-color: rgba(66, 165, 245, 0.2);">

                                        <!-- Category -->
                                        <div class="form-section mb-5">
                                            <h6 class="text-secondary font-weight-bold mb-3">
                                                <i class="bi bi-folder"></i> Organization
                                            </h6>
                                            <div class="mb-3">
                                                <label for="category" class="form-label"><i class="bi bi-collection"></i> Category</label>
                                                <select class="form-select" id="category" name="category">
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($all_categories as $cat): ?>
                                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Form Actions -->
                                        <div class="form-actions pt-3 border-top" style="border-color: rgba(66, 165, 245, 0.2) !important;">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="bi bi-check-circle"></i> Save Changes
                                            </button>
                                            <a href="products.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-arrow-left"></i> Back to Products
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Product Images (Unified) -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-images"></i> Product Images</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-4">Upload and manage product images. The primary image will be the showcase image.</p>
                                    
                                    <?php 
                                    $primary_image = null;
                                    if (!empty($product_images)) {
                                        foreach ($product_images as $img) {
                                            if ($img['is_primary']) {
                                                $primary_image = $img;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <!-- Showcase Image -->
                                    <div class="mb-5">
                                        <h6 class="text-secondary mb-3 font-weight-bold">Showcase Image</h6>
                                        <?php if ($primary_image): ?>
                                            <div class="showcase-image-container mb-3">
                                                <div class="position-relative d-inline-block">
                                                    <img src="<?php echo SITE_URL . htmlspecialchars($primary_image['image_path']); ?>" alt="Primary image" class="showcase-image">
                                                    <span class="badge badge-success position-absolute" style="top: 10px; right: 10px; background-color: #ffc107 !important; padding: 6px 12px; font-size: 0.8rem;">
                                                        <i class="bi bi-star-fill"></i> SHOWCASE
                                                    </span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted text-center py-4">No showcase image selected yet.</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Gallery Images -->
                                    <?php if (!empty($product_images)): ?>
                                        <div class="mb-5">
                                            <h6 class="text-secondary mb-3 font-weight-bold">Gallery (<?php echo count($product_images); ?> images)</h6>
                                            <div class="images-gallery mb-4">
                                                <?php foreach ($product_images as $image): ?>
                                                    <div class="image-item <?php echo $image['is_primary'] ? 'is-primary' : ''; ?>">
                                                        <div class="image-wrapper">
                                                            <img src="<?php echo SITE_URL . htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['image_name']); ?>">
                                                            <?php if ($image['is_primary']): ?>
                                                                <div class="image-badge-primary">
                                                                    <i class="bi bi-star-fill"></i> PRIMARY
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="image-actions-group">
                                                            <form method="POST" style="flex: 1;">
                                                                <input type="hidden" name="action" value="set_primary_image">
                                                                <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-warning w-100" title="Set as primary">
                                                                    <i class="bi bi-star"></i> Primary
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="flex: 1;" onsubmit="return confirm('Delete this image?');">
                                                                <input type="hidden" name="action" value="delete_image">
                                                                <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" title="Delete image">
                                                                    <i class="bi bi-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-5">
                                            <p class="text-muted text-center py-4">No images yet. Upload some below.</p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Upload Section -->
                                    <div class="upload-section p-4" style="background: linear-gradient(135deg, rgba(66, 165, 245, 0.08) 0%, rgba(66, 165, 245, 0.03) 100%); border: 2px dashed rgba(66, 165, 245, 0.4); border-radius: 12px;">
                                        <h6 class="text-secondary mb-3 font-weight-bold"><i class="bi bi-cloud-upload"></i> Upload New Images</h6>
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="upload_images">
                                            <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                            
                                            <div class="input-group mb-2">
                                                <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple required>
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="bi bi-upload"></i> Upload
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
                                    <h5 class="mb-0"><i class="bi bi-cloud-download"></i> Download Files</h5>
                                </div>
                                <div class="card-body">
                                    <div id="filesList" class="files-list-container mb-4">
                                        <!-- Files list will be populated here -->
                                    </div>

                                    <hr>

                                    <div class="upload-section">
                                        <h6 class="text-secondary mb-3">Add Download File</h6>
                                        <form id="fileUploadForm" enctype="multipart/form-data" class="upload-form">
                                            <input type="hidden" id="product_id" value="<?php echo $edit_product['id']; ?>">
                                            
                                            <!-- File Selection -->
                                            <div class="row g-3 mb-4">
                                                <div class="col-12">
                                                    <label for="fileInput" class="form-label">Select File *</label>
                                                    <input type="file" id="fileInput" name="file" class="form-control" required>
                                                    <small class="text-muted d-block mt-2">
                                                        Supports: zip, pdf, exe, dmg, rar, txt, doc, docx, xls, xlsx, ppt, pptx, 7z, iso (Max 500MB)
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- Display Name & Version -->
                                            <div class="row g-3 mb-4">
                                                <div class="col-md-8">
                                                    <label for="displayName" class="form-label">Display Name *</label>
                                                    <input type="text" id="displayName" name="display_name" class="form-control" placeholder="e.g., Software Setup" required>
                                                    <small class="text-muted d-block mt-2">Name shown to customers</small>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="version" class="form-label">Version</label>
                                                    <input type="text" id="version" name="version" class="form-control" placeholder="1.0.0">
                                                    <small class="text-muted d-block mt-2">e.g., 1.0.0</small>
                                                </div>
                                            </div>

                                            <!-- Description -->
                                            <div class="row g-3 mb-4">
                                                <div class="col-12">
                                                    <label for="fileDescription" class="form-label">Description</label>
                                                    <textarea id="fileDescription" name="description" class="form-control" rows="2" placeholder="What does this file contain?"></textarea>
                                                </div>
                                            </div>

                                            <!-- Size & Color Variants -->
                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <label for="sizeVariant" class="form-label">Size Variant *</label>
                                                    <select id="sizeVariant" name="size_variant" class="form-select" required>
                                                        <option value="">-- Select Size --</option>
                                                    </select>
                                                    <small class="text-muted d-block mt-1">Which product size is this for?</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="colorVariant" class="form-label">Color Variant *</label>
                                                    <select id="colorVariant" name="color_variant" class="form-select" required>
                                                        <option value="">-- Select Color --</option>
                                                    </select>
                                                    <small class="text-muted d-block mt-1">Which product color is this for?</small>
                                                </div>
                                            </div>

                                            <!-- File Preview -->
                                            <div id="fileInfo" class="file-preview-box mb-4" style="display: none;">
                                                <div class="preview-success-badge">
                                                    <i class="bi bi-check-circle-fill"></i> File Ready
                                                </div>
                                                <div class="preview-info">
                                                    <div class="info-group">
                                                        <span class="info-label">Name:</span>
                                                        <span class="info-value" id="fileName"></span>
                                                    </div>
                                                    <div class="info-group">
                                                        <span class="info-label">Size:</span>
                                                        <span class="info-value" id="fileSize"></span>
                                                    </div>
                                                    <div class="info-group">
                                                        <span class="info-label">Type:</span>
                                                        <span class="info-value" id="fileType"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="bi bi-cloud-upload"></i> Upload File
                                            </button>
                                        </form>
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
                                        document.getElementById('fileSize').textContent = size + ' MB';
                                        document.getElementById('fileType').textContent = file.type || 'Unknown';
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
                                            let html = '<h6 class="text-secondary mb-3">Uploaded Files (' + data.files.length + ')</h6>';
                                            html += '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>File Name</th><th>Display Name</th><th>Size</th><th>Version</th><th>Variant</th><th class="text-end">Action</th></tr></thead><tbody>';
                                            
                                            data.files.forEach(file => {
                                                const variant = (file.size_variant || '-') + ' / ' + (file.color_variant || '-');
                                                
                                                html += `<tr>
                                                    <td><small><i class="bi bi-file-earmark"></i> ${file.original_filename}</small></td>
                                                    <td><strong>${file.display_name}</strong></td>
                                                    <td><small>${(file.file_size / 1024 / 1024).toFixed(2)} MB</small></td>
                                                    <td><small>${file.version || '-'}</small></td>
                                                    <td><span class="badge bg-info">${variant}</span></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${file.id})" title="Delete file">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>`;
                                            });
                                            
                                            html += '</tbody></table></div>';
                                            filesList.innerHTML = html;
                                        } else {
                                            filesList.innerHTML = '<p class="text-muted text-center py-3"><i class="bi bi-info-circle"></i> No files uploaded yet. Add one below.</p>';
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error loading files:', error);
                                        filesList.innerHTML = '<p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Error loading files.</p>';
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
                                    <h5 class="mb-0"><i class="bi bi-palette"></i> Colors</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($product_colors)): ?>
                                        <div class="mb-4">
                                            <h6 class="text-secondary mb-3">Existing Colors</h6>
                                            <div class="colors-grid">
                                                <?php foreach ($product_colors as $color): ?>
                                                    <div class="color-card">
                                                        <div class="color-swatch" style="background-color: <?php echo htmlspecialchars($color['color_code'] ?? '#ccc'); ?>"></div>
                                                        <div class="color-info">
                                                            <strong><?php echo htmlspecialchars($color['color_name']); ?></strong>
                                                            <?php if ($color['color_code']): ?>
                                                                <small class="text-muted d-block"><?php echo htmlspecialchars($color['color_code']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <form method="POST" class="color-delete-form">
                                                            <input type="hidden" name="action" value="delete_color">
                                                            <input type="hidden" name="color_id" value="<?php echo $color['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this color?');" title="Delete color">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <hr>
                                    <?php endif; ?>

                                    <div>
                                        <h6 class="text-secondary mb-3">Add New Color</h6>
                                        
                                        <!-- Pattern Type Selector -->
                                        <div class="pattern-selector mb-4">
                                            <small class="text-muted d-block mb-2">Pattern Type:</small>
                                            <div class="pattern-buttons">
                                                <button type="button" class="pattern-btn active" data-pattern="solid" onclick="setPatternType('solid')" title="Solid color">
                                                    <i class="bi bi-circle-fill"></i> Solid
                                                </button>
                                                <button type="button" class="pattern-btn" data-pattern="stripes" onclick="setPatternType('stripes')" title="Stripes">
                                                    <i class="bi bi-lines"></i> Stripes
                                                </button>
                                                <button type="button" class="pattern-btn" data-pattern="split" onclick="setPatternType('split')" title="Split colors">
                                                    <i class="bi bi-square-split"></i> Split
                                                </button>
                                                <button type="button" class="pattern-btn" data-pattern="gradient" onclick="setPatternType('gradient')" title="Gradient blend">
                                                    <i class="bi bi-arrow-right"></i> Gradient
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Quick color presets -->
                                        <div class="preset-colors mb-4">
                                            <small class="text-muted d-block mb-2">Quick Presets:</small>
                                            <div class="preset-buttons">
                                                <button type="button" class="preset-btn" style="background-color: #FFFFFF; border: 2px solid var(--border);" onclick="setColorPreset('#FFFFFF', 'White')" title="White"></button>
                                                <button type="button" class="preset-btn" style="background-color: #000000;" onclick="setColorPreset('#000000', 'Black')" title="Black"></button>
                                                <button type="button" class="preset-btn" style="background-color: #FF0000;" onclick="setColorPreset('#FF0000', 'Red')" title="Red"></button>
                                                <button type="button" class="preset-btn" style="background-color: #00FF00;" onclick="setColorPreset('#00FF00', 'Green')" title="Green"></button>
                                                <button type="button" class="preset-btn" style="background-color: #0000FF;" onclick="setColorPreset('#0000FF', 'Blue')" title="Blue"></button>
                                                <button type="button" class="preset-btn" style="background-color: #FFFF00;" onclick="setColorPreset('#FFFF00', 'Yellow')" title="Yellow"></button>
                                                <button type="button" class="preset-btn" style="background-color: #FF8000;" onclick="setColorPreset('#FF8000', 'Orange')" title="Orange"></button>
                                                <button type="button" class="preset-btn" style="background-color: #800080;" onclick="setColorPreset('#800080', 'Purple')" title="Purple"></button>
                                                <button type="button" class="preset-btn" style="background-color: #FFC0CB;" onclick="setColorPreset('#FFC0CB', 'Pink')" title="Pink"></button>
                                                <button type="button" class="preset-btn" style="background-color: #808080;" onclick="setColorPreset('#808080', 'Gray')" title="Gray"></button>
                                                <button type="button" class="preset-btn" style="background-color: #00FFFF;" onclick="setColorPreset('#00FFFF', 'Cyan')" title="Cyan"></button>
                                                <button type="button" class="preset-btn" style="background-color: #800000;" onclick="setColorPreset('#800000', 'Maroon')" title="Maroon"></button>
                                            </div>
                                        </div>

                                        <form method="POST" class="add-color-form">
                                            <input type="hidden" name="action" value="add_color">
                                            <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                            <input type="hidden" name="display_order" value="<?php echo count($product_colors); ?>">
                                            <input type="hidden" id="pattern_type" name="pattern_type" value="solid">

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div>
                                                        <label for="color_name_input" class="form-label">Color Name</label>
                                                        <input type="text" id="color_name_input" name="color_name" class="form-control" placeholder="e.g. Forest Green" required>
                                                        <small class="text-muted">Give your color a descriptive name</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div>
                                                        <label for="color_code_input" class="form-label" id="primary_color_label">Color Code</label>
                                                        <div class="color-picker-wrapper">
                                                            <input type="color" id="color_code_input" name="color_code" class="color-picker-input" value="#000000" onchange="updateColorPreview()" oninput="updateColorPreview()">
                                                            <input type="text" id="color_hex_display" class="form-control form-control-sm" value="#000000" readonly placeholder="Hex code">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Secondary Color (for patterns) -->
                                            <div class="row g-3 mt-2" id="secondary_color_section" style="display: none;">
                                                <div class="col-md-6">
                                                    <label for="color_code_input_2" class="form-label">Secondary Color</label>
                                                    <div class="color-picker-wrapper">
                                                        <input type="color" id="color_code_input_2" name="color_code_2" class="color-picker-input" value="#FFFFFF" onchange="updateColorPreview()" oninput="updateColorPreview()">
                                                        <input type="text" id="color_hex_display_2" class="form-control form-control-sm" value="#FFFFFF" readonly placeholder="Hex code">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="color-preview-box mt-3">
                                                <div id="colorPreviewDisplay" class="color-preview-large" style="background-color: #000000;"></div>
                                                <div class="color-preview-text">
                                                    <strong id="previewColorName">Color Preview</strong>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-success w-100 mt-3">
                                                <i class="bi bi-plus-circle"></i> Add Color
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <script>
                            let currentPattern = 'solid';

                            function setPatternType(pattern) {
                                currentPattern = pattern;
                                document.getElementById('pattern_type').value = pattern;
                                
                                // Update button active state
                                document.querySelectorAll('.pattern-btn').forEach(btn => {
                                    btn.classList.remove('active');
                                });
                                document.querySelector(`[data-pattern="${pattern}"]`).classList.add('active');
                                
                                // Show/hide secondary color input
                                const secondarySection = document.getElementById('secondary_color_section');
                                const primaryLabel = document.getElementById('primary_color_label');
                                
                                if (pattern === 'solid') {
                                    secondarySection.style.display = 'none';
                                    primaryLabel.textContent = 'Color Code';
                                } else {
                                    secondarySection.style.display = 'block';
                                    primaryLabel.textContent = pattern.charAt(0).toUpperCase() + pattern.slice(1) + ' - Primary Color';
                                }
                                
                                updateColorPreview();
                            }

                            function setColorPreset(hexCode, colorName) {
                                document.getElementById('color_code_input').value = hexCode;
                                document.getElementById('color_name_input').value = colorName;
                                updateColorPreview();
                            }

                            function updateColorPreview() {
                                const color1 = document.getElementById('color_code_input').value.toUpperCase();
                                const color2 = document.getElementById('color_code_input_2').value.toUpperCase();
                                const hexDisplay = document.getElementById('color_hex_display');
                                const hexDisplay2 = document.getElementById('color_hex_display_2');
                                const previewBox = document.getElementById('colorPreviewDisplay');
                                const colorName = document.getElementById('color_name_input').value || 'Color Preview';
                                
                                hexDisplay.value = color1;
                                hexDisplay2.value = color2;
                                document.getElementById('previewColorName').textContent = colorName;
                                
                                // Apply preview based on pattern type
                                if (currentPattern === 'solid') {
                                    previewBox.style.background = color1;
                                } else if (currentPattern === 'stripes') {
                                    previewBox.style.background = `repeating-linear-gradient(90deg, ${color1} 0px, ${color1} 10px, ${color2} 10px, ${color2} 20px)`;
                                } else if (currentPattern === 'split') {
                                    previewBox.style.background = `linear-gradient(90deg, ${color1} 0%, ${color1} 50%, ${color2} 50%, ${color2} 100%)`;
                                } else if (currentPattern === 'gradient') {
                                    previewBox.style.background = `linear-gradient(90deg, ${color1}, ${color2})`;
                                }
                            }

                            // Initialize color preview on page load
                            document.addEventListener('DOMContentLoaded', function() {
                                updateColorPreview();
                                document.getElementById('color_name_input').addEventListener('input', updateColorPreview);
                            });
                            </script>

                            <!-- Sizes -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-rulers"></i> Sizes</h5>
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
                                                            <i class="bi bi-trash"></i>
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
                                            <i class="bi bi-plus"></i> Add Size
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
                            <h5 class="mb-0">All Products (Drag to Reorder)</h5>
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="bi bi-plus"></i> Add New Product
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($products)): ?>
                                <p class="text-muted text-center py-4">No products yet. Click "Add New Product" to create one.</p>
                            <?php else: ?>
                                <div id="sortable-products" class="list-group">
                                    <?php foreach ($products as $product):
                                        $prod_images = getProductImages($product['id'], $conn);
                                        $img_count = count($prod_images);
                                    ?>
                                        <div class="list-group-item d-flex align-items-center justify-content-between sortable-item" data-product-id="<?php echo $product['id']; ?>" style="cursor: grab; background: var(--dark-1); color: var(--text-primary); border: 1px solid var(--border); margin-bottom: 8px; padding: 15px; border-radius: 6px;">
                                            <div class="d-flex align-items-center flex-grow-1">
                                                <i class="bi bi-grip-vertical me-3" style="cursor: grab; color: var(--text-secondary);"></i>
                                                <div>
                                                    <strong style="color: var(--text-secondary); font-weight: 600;">
                                                        <?php echo htmlspecialchars($product['name']); ?>
                                                        <?php if ($product['is_hidden']): ?>
                                                            <span class="badge bg-danger" style="margin-left: 8px;"><i class="bi bi-eye-slash"></i> Hidden</span>
                                                        <?php endif; ?>
                                                    </strong>
                                                    <br>
                                                    <small style="color: var(--text-muted);">
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                                        <span class="badge bg-info">$<?php echo number_format($product['price'], 2); ?></span>
                                                        <span class="badge bg-warning text-dark">Stock: <?php echo $product['quantity']; ?></span>
                                                        <?php if ($img_count > 0): ?>
                                                            <span class="badge bg-success"><i class="bi bi-image"></i> <?php echo $img_count; ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">0 images</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button class="btn btn-sm <?php echo $product['is_hidden'] ? 'btn-success' : 'btn-warning'; ?>" onclick="toggleProductVisibility(<?php echo $product['id']; ?>, <?php echo $product['is_hidden']; ?>)" title="<?php echo $product['is_hidden'] ? 'Unhide product' : 'Hide product'; ?>">
                                                    <i class="bi <?php echo $product['is_hidden'] ? 'bi-eye' : 'bi-eye-slash'; ?>"></i>
                                                </button>
                                                <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
                                <?php foreach ($all_categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="new_is_hidden" name="is_hidden">
                                <label class="form-check-label" for="new_is_hidden">
                                    <i class="fas fa-eye-slash"></i> Hide from public site (for releases)
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">Check this to create the product hidden. You can unhide it anytime from the product list.</small>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin_file_manager.js"></script>
    <script>
        // Initialize drag-and-drop sorting for products
        const sortableContainer = document.getElementById('sortable-products');
        
        if (sortableContainer) {
            const sortable = Sortable.create(sortableContainer, {
                animation: 150,
                handle: '.fa-grip-vertical',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    saveNewOrder();
                }
            });
        }
        
        // Save the new order to the server
        function saveNewOrder() {
            const items = document.querySelectorAll('.sortable-item');
            const order = [];
            let position = 0;
            
            items.forEach(item => {
                const productId = item.getAttribute('data-product-id');
                order.push({
                    id: productId,
                    position: position
                });
                position++;
            });
            
            // Send AJAX request to save order
            fetch('<?php echo SITE_URL; ?>admin/products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reorder',
                    order: order
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Optional: Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        <strong>Order saved!</strong> Product order has been updated.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.page-header').parentElement.insertBefore(alert, document.querySelector('.page-header').nextSibling);
                    setTimeout(() => alert.remove(), 3000);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Declare fileManager globally (avoid duplicate declarations)
        var fileManager = null;

        function toggleProductVisibility(productId, isHidden) {
            // Show loading state
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Send AJAX request
            fetch('products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=toggle_visibility&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated state
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to toggle product visibility'));
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error toggling product visibility');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }

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

        // Add CSS for drag-and-drop styling
        const style = document.createElement('style');
        style.innerHTML = `
            .sortable-ghost {
                opacity: 0.4;
                background: #f5f5f5;
            }
            .sortable-drag {
                opacity: 0;
            }
            .sortable-item {
                transition: transform 0.15s ease, opacity 0.15s ease;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
