<?php
/**
 * Product Images and Variants Helper Functions
 * Handles product images, colors, and sizes
 */

/**
 * Get all images for a product
 */
function getProductImages($product_id, $conn) {
    $query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC, created_at ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get primary image for a product (for listings)
 */
function getProductPrimaryImage($product_id, $conn) {
    $query = "SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = TRUE LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['image_path'] : null;
}

/**
 * Add image to product
 */
function addProductImage($product_id, $image_path, $image_name, $display_order, $is_primary = false, $conn) {
    // If this is primary, unset any existing primary images
    if ($is_primary) {
        $update_query = "UPDATE product_images SET is_primary = FALSE WHERE product_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
    }
    
    $query = "INSERT INTO product_images (product_id, image_path, image_name, display_order, is_primary) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('issis', $product_id, $image_path, $image_name, $display_order, $is_primary);
    return $stmt->execute();
}

/**
 * Delete product image
 */
function deleteProductImage($image_id, $conn) {
    $query = "DELETE FROM product_images WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $image_id);
    return $stmt->execute();
}

/**
 * Get all colors for a product
 */
function getProductColors($product_id, $conn) {
    $query = "SELECT * FROM product_colors WHERE product_id = ? ORDER BY display_order ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Add color to product
 */
function addProductColor($product_id, $color_name, $color_code, $display_order, $conn) {
    $query = "INSERT INTO product_colors (product_id, color_name, color_code, display_order) 
              VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('issi', $product_id, $color_name, $color_code, $display_order);
    return $stmt->execute();
}

/**
 * Delete product color
 */
function deleteProductColor($color_id, $conn) {
    $query = "DELETE FROM product_colors WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $color_id);
    return $stmt->execute();
}

/**
 * Get all sizes for a product
 */
function getProductSizes($product_id, $conn) {
    $query = "SELECT * FROM product_sizes WHERE product_id = ? ORDER BY display_order ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Add size to product
 */
function addProductSize($product_id, $size_name, $display_order, $conn) {
    $query = "INSERT INTO product_sizes (product_id, size_name, display_order) 
              VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isi', $product_id, $size_name, $display_order);
    return $stmt->execute();
}

/**
 * Delete product size
 */
function deleteProductSize($size_id, $conn) {
    $query = "DELETE FROM product_sizes WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $size_id);
    return $stmt->execute();
}

/**
 * Handle file uploads for product images
 * Returns array of uploaded file paths or error
 */
function uploadProductImages($files, $product_id) {
    $upload_dir = SITE_ROOT . 'uploads/products/' . $product_id . '/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded_files = [];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    
    if (!isset($files['images'])) {
        return ['error' => 'No files provided'];
    }
    
    $file_count = count($files['images']['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['images']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $file_tmp = $files['images']['tmp_name'][$i];
        $file_name = $files['images']['name'][$i];
        $file_type = mime_content_type($file_tmp);
        $file_size = $files['images']['size'][$i];
        
        // Validation
        if (!in_array($file_type, $allowed_types)) {
            continue;
        }
        
        if ($file_size > $max_file_size) {
            continue;
        }
        
        // Generate unique filename
        $unique_name = time() . '_' . uniqid() . '_' . basename($file_name);
        $destination = $upload_dir . $unique_name;
        
        // Resize and optimize image
        if (move_uploaded_file($file_tmp, $destination)) {
            // Optimize image size
            optimizeProductImage($destination);
            
            $uploaded_files[] = [
                'path' => 'uploads/products/' . $product_id . '/' . $unique_name,
                'name' => $file_name
            ];
        }
    }
    
    return $uploaded_files;
}

/**
 * Optimize product image for web
 */
function optimizeProductImage($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Get image info
    $image_info = getimagesize($file_path);
    
    if (!$image_info) {
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $mime = $image_info['mime'];
    
    // Max width for product images
    $max_width = 1200;
    
    if ($width > $max_width) {
        $ratio = $max_width / $width;
        $new_width = $max_width;
        $new_height = round($height * $ratio);
        
        $source = null;
        
        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $source = imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($file_path);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($file_path);
                break;
        }
        
        if ($source) {
            $resized = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            // Save resized image
            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($resized, $file_path, 85);
                    break;
                case 'image/png':
                    imagepng($resized, $file_path, 9);
                    break;
                case 'image/gif':
                    imagegif($resized, $file_path);
                    break;
                case 'image/webp':
                    imagewebp($resized, $file_path, 85);
                    break;
            }
            
            imagedestroy($source);
            imagedestroy($resized);
        }
    }
    
    return true;
}

/**
 * Get product details including images, colors, sizes
 */
function getProductDetails($product_id, $conn) {
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        return null;
    }
    
    // Add images, colors, and sizes
    $product['images'] = getProductImages($product_id, $conn);
    $product['colors'] = getProductColors($product_id, $conn);
    $product['sizes'] = getProductSizes($product_id, $conn);
    $product['primary_image'] = getProductPrimaryImage($product_id, $conn);
    
    return $product;
}
