<?php
/**
 * Product and Category management helper functions
 */

// Prevent multiple inclusions
if (defined('PRODUCTS_HELPER_LOADED')) {
    return;
}
define('PRODUCTS_HELPER_LOADED', true);

/**
 * Get all categories
 * @param mysqli $conn Database connection
 * @return array Categories
 */
function getAllCategories($conn) {
    $query = "SELECT id, name, description, status, display_order FROM categories ORDER BY display_order ASC, name ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get active categories
 * @param mysqli $conn Database connection
 * @return array Active categories
 */
function getActiveCategories($conn) {
    $query = "SELECT id, name, description, display_order FROM categories WHERE status = 'active' ORDER BY display_order ASC, name ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get category by ID
 * @param int $id Category ID
 * @param mysqli $conn Database connection
 * @return array|null Category data
 */
function getCategoryById($id, $conn) {
    $query = "SELECT id, name, description, status, display_order FROM categories WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

/**
 * Get category by slug
 * @param string $slug Category slug
 * @param mysqli $conn Database connection
 * @return array|null Category data
 */
function getCategoryBySlug($slug, $conn) {
    $query = "SELECT id, name, slug, description, image_url FROM categories WHERE slug = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

/**
 * Create category
 * @param string $name Category name
 * @param string $description Category description
 * @param mysqli $conn Database connection
 * @return bool
 */
function createCategory($name, $description, $conn) {
    // Get the next display order
    $order_result = $conn->query("SELECT COALESCE(MAX(display_order), -1) + 1 as next_order FROM categories");
    $order_row = $order_result->fetch_assoc();
    $display_order = $order_row['next_order'];
    
    $query = "INSERT INTO categories (name, description, status, display_order) VALUES (?, ?, 'active', ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssi', $name, $description, $display_order);
    return $stmt->execute();
}

/**
 * Update category
 * @param int $id Category ID
 * @param string $name Category name
 * @param string $description Category description
 * @param string $status Category status
 * @param mysqli $conn Database connection
 * @return bool
 */
function updateCategory($id, $name, $description, $status, $conn) {
    $query = "UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssi', $name, $description, $status, $id);
    return $stmt->execute();
}

/**
 * Delete category
 * @param int $id Category ID
 * @param mysqli $conn Database connection
 * @return bool
 */
function deleteCategory($id, $conn) {
    $query = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

/**
 * Update category display order
 * @param int $id Category ID
 * @param int $display_order Display order position
 * @param mysqli $conn Database connection
 * @return bool
 */
function updateCategoryOrder($id, $display_order, $conn) {
    $query = "UPDATE categories SET display_order = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $display_order, $id);
    return $stmt->execute();
}

/**
 * Get products by category
 * @param int $category_id Category ID
 * @param mysqli $conn Database connection
 * @return array Products in category
 */
function getProductsByCategory($category_id, $conn) {
    $query = "SELECT id, name, description, price, image_url, quantity FROM products WHERE category_id = ? AND is_hidden = 0 ORDER BY display_order ASC, name ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get filtered products
 * @param array $filters Filters array (category_id, min_price, max_price, search)
 * @param mysqli $conn Database connection
 * @return array Filtered products
 */
function getFilteredProducts($filters, $conn) {
    // Modified query to join with product_images table and get primary image
    $query = "SELECT 
                p.id, 
                p.name, 
                p.description, 
                p.price, 
                p.quantity,
                COALESCE(pi.image_path, '') as image_url
              FROM products p
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
              WHERE p.is_hidden = 0";
    $params = [];
    $types = '';

    if (!empty($filters['category_id'])) {
        $query .= " AND p.category_id = ?";
        $params[] = $filters['category_id'];
        $types .= 'i';
    }

    if (!empty($filters['min_price'])) {
        $query .= " AND p.price >= ?";
        $params[] = $filters['min_price'];
        $types .= 'd';
    }

    if (!empty($filters['max_price'])) {
        $query .= " AND p.price <= ?";
        $params[] = $filters['max_price'];
        $types .= 'd';
    }

    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= 'ss';
    }

    $query .= " ORDER BY p.display_order ASC, p.name ASC";

    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    // Add SITE_URL to image paths for proper display
    foreach ($products as &$product) {
        if (!empty($product['image_url'])) {
            $product['image_url'] = SITE_URL . $product['image_url'];
        }
    }
    
    return $products;
}

/**
 * Update product category
 * @param int $product_id Product ID
 * @param int $category_id Category ID
 * @param mysqli $conn Database connection
 * @return bool
 */
function updateProductCategory($product_id, $category_id, $conn) {
    $query = "UPDATE products SET category_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $category_id, $product_id);
    return $stmt->execute();
}
?>
