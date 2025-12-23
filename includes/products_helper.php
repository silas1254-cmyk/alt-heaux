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
    $query = "SELECT id, name, slug, description, image_url, status FROM categories ORDER BY name ASC";
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
    $query = "SELECT id, name, slug, description, image_url FROM categories WHERE status = 'active' ORDER BY name ASC";
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
    $query = "SELECT id, name, slug, description, image_url, status FROM categories WHERE id = ?";
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
 * @param string $slug Category slug
 * @param string $description Category description
 * @param string $image_url Category image
 * @param mysqli $conn Database connection
 * @return bool
 */
function createCategory($name, $slug, $description, $image_url, $conn) {
    $query = "INSERT INTO categories (name, slug, description, image_url, status) VALUES (?, ?, ?, ?, 'active')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssss', $name, $slug, $description, $image_url);
    return $stmt->execute();
}

/**
 * Update category
 * @param int $id Category ID
 * @param string $name Category name
 * @param string $description Category description
 * @param string $image_url Category image
 * @param string $status Category status
 * @param mysqli $conn Database connection
 * @return bool
 */
function updateCategory($id, $name, $description, $image_url, $status, $conn) {
    $query = "UPDATE categories SET name = ?, description = ?, image_url = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssi', $name, $description, $image_url, $status, $id);
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
 * Get products by category
 * @param int $category_id Category ID
 * @param mysqli $conn Database connection
 * @return array Products in category
 */
function getProductsByCategory($category_id, $conn) {
    $query = "SELECT id, name, description, price, image_url, quantity FROM products WHERE category_id = ? ORDER BY name ASC";
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
    $query = "SELECT id, name, description, price, image_url, quantity FROM products WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($filters['category_id'])) {
        $query .= " AND category_id = ?";
        $params[] = $filters['category_id'];
        $types .= 'i';
    }

    if (!empty($filters['min_price'])) {
        $query .= " AND price >= ?";
        $params[] = $filters['min_price'];
        $types .= 'd';
    }

    if (!empty($filters['max_price'])) {
        $query .= " AND price <= ?";
        $params[] = $filters['max_price'];
        $types .= 'd';
    }

    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= 'ss';
    }

    $query .= " ORDER BY name ASC";

    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
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
