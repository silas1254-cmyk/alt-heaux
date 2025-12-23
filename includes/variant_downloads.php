<?php
/**
 * Variant Download Helper
 * Get files based on product variant (size, color)
 */

/**
 * Get download files for a specific product variant
 * 
 * @param int $product_id
 * @param string $size (optional) - Size variant (S, M, L, XL, etc)
 * @param string $color (optional) - Color variant (Red, Blue, etc)
 * @return array - Array of matching files
 */
function getVariantFiles($product_id, $size = null, $color = null) {
    global $conn;
    
    $product_id = (int)$product_id;
    
    // Build query to find matching files
    $sql = "SELECT id, product_id, file_path, original_filename, display_name, 
                   description, version, file_type, file_size, size_variant, color_variant
            FROM product_files 
            WHERE product_id = $product_id 
            AND is_active = 1";
    
    // If size/color specified, find exact matches or generic files
    if (!empty($size) || !empty($color)) {
        // First try to find exact variant match
        $exactMatch = true;
        
        if (!empty($size) && !empty($color)) {
            // Both size and color specified - find exact match
            $size_safe = $conn->real_escape_string($size);
            $color_safe = $conn->real_escape_string($color);
            $sql .= " AND (
                (size_variant = '$size_safe' AND color_variant = '$color_safe') 
                OR (size_variant IS NULL AND color_variant IS NULL)
            )";
        } elseif (!empty($size)) {
            // Only size specified
            $size_safe = $conn->real_escape_string($size);
            $sql .= " AND (
                (size_variant = '$size_safe' AND color_variant IS NULL) 
                OR (size_variant IS NULL AND color_variant IS NULL)
            )";
        } else {
            // Only color specified
            $color_safe = $conn->real_escape_string($color);
            $sql .= " AND (
                (size_variant IS NULL AND color_variant = '$color_safe') 
                OR (size_variant IS NULL AND color_variant IS NULL)
            )";
        }
    }
    
    $sql .= " ORDER BY size_variant, color_variant, upload_date DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        return [];
    }
    
    return $result->fetch_all(MYSQLI_ASSOC) ?: [];
}

/**
 * Get all available variants for a product
 * Useful for displaying variant options to admin when uploading
 * 
 * @param int $product_id
 * @return array - Array of unique variant combinations
 */
function getProductVariants($product_id) {
    global $conn;
    
    $product_id = (int)$product_id;
    
    $sql = "SELECT DISTINCT 
                CONCAT_WS(' / ', size_variant, color_variant) as variant_combo,
                size_variant, 
                color_variant,
                COUNT(*) as file_count
            FROM product_files
            WHERE product_id = $product_id
            AND is_active = 1
            GROUP BY size_variant, color_variant
            ORDER BY size_variant, color_variant";
    
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get files for a specific user purchase (by order ID)
 * This ensures the user gets files matching their ordered size/color
 * 
 * @param int $purchase_id - From user_purchases table
 * @return array - Files matching the purchase variant
 */
function getPurchaseFiles($purchase_id) {
    global $conn;
    
    $purchase_id = (int)$purchase_id;
    
    // Get purchase details with variants
    $sql = "SELECT product_id, purchased_size, purchased_color 
            FROM user_purchases 
            WHERE id = $purchase_id";
    
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        return [];
    }
    
    $purchase = $result->fetch_assoc();
    
    // Get files matching the purchased variants
    return getVariantFiles(
        $purchase['product_id'],
        $purchase['purchased_size'],
        $purchase['purchased_color']
    );
}

/**
 * Get download links for a purchase
 * 
 * @param int $purchase_id
 * @param string $download_token (optional) - For secure downloads
 * @return array - Array with file_id, display_name, download_url
 */
function getPurchaseDownloadLinks($purchase_id, $download_token = null) {
    global $conn;
    
    $files = getPurchaseFiles($purchase_id);
    $download_links = [];
    
    foreach ($files as $file) {
        $download_links[] = [
            'file_id' => $file['id'],
            'name' => $file['display_name'] ?: $file['original_filename'],
            'description' => $file['description'],
            'size' => $file['file_size'],
            'variant_info' => buildVariantLabel($file['size_variant'], $file['color_variant']),
            'download_url' => buildDownloadUrl($file['id'], $download_token)
        ];
    }
    
    return $download_links;
}

/**
 * Build human-readable variant label
 * 
 * @param string $size
 * @param string $color
 * @return string
 */
function buildVariantLabel($size, $color) {
    $parts = [];
    if (!empty($size)) $parts[] = "Size: " . htmlspecialchars($size);
    if (!empty($color)) $parts[] = "Color: " . htmlspecialchars($color);
    
    return !empty($parts) ? '(' . implode(', ', $parts) . ')' : '(Generic/All variants)';
}

/**
 * Build secure download URL
 * 
 * @param int $file_id
 * @param string $token (optional)
 * @return string
 */
function buildDownloadUrl($file_id, $token = null) {
    $base_url = 'pages/download.php?file_id=' . urlencode($file_id);
    
    if (!empty($token)) {
        $base_url .= '&token=' . urlencode($token);
    }
    
    return SITE_URL . $base_url;
}

?>
