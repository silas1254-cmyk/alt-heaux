<?php
// Prevent multiple inclusions - MUST BE FIRST
if (defined('PAGES_HELPER_LOADED')) {
    return;
}
define('PAGES_HELPER_LOADED', true);

/**
 * Page management helper functions
 */

/**
 * Get all pages
 * @param mysqli $conn Database connection
 * @return array Pages
 */
function getAllPages($conn) {
    $query = "SELECT id, slug, title, status, created_at FROM pages ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get published pages
 * @param mysqli $conn Database connection
 * @return array Published pages
 */
function getPublishedPages($conn) {
    $query = "SELECT id, slug, title, content, meta_description, created_at FROM pages WHERE status = 'published' ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get page by slug
 * @param string $slug Page slug
 * @param mysqli $conn Database connection
 * @return array|null Page data
 */
function getPageBySlug($slug, $conn) {
    $query = "SELECT id, slug, title, content, meta_description, status FROM pages WHERE slug = ? AND status = 'published'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Get page by ID
 * @param int $id Page ID
 * @param mysqli $conn Database connection
 * @return array|null Page data
 */
function getPageById($id, $conn) {
    $query = "SELECT id, slug, title, content, meta_description, status FROM pages WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Create page
 * @param string $slug Page slug
 * @param string $title Page title
 * @param string $content Page content
 * @param string $meta_description Meta description
 * @param int $created_by Admin ID
 * @param mysqli $conn Database connection
 * @return bool
 */
function createPage($slug, $title, $content, $meta_description, $created_by, $conn) {
    $query = "INSERT INTO pages (slug, title, content, meta_description, created_by, status) VALUES (?, ?, ?, ?, ?, 'draft')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssi', $slug, $title, $content, $meta_description, $created_by);
    return $stmt->execute();
}

/**
 * Update page
 * @param int $id Page ID
 * @param string $title Page title
 * @param string $content Page content
 * @param string $meta_description Meta description
 * @param string $status Page status
 * @param mysqli $conn Database connection
 * @return bool
 */
function updatePage($id, $title, $content, $meta_description, $status, $conn) {
    $query = "UPDATE pages SET title = ?, content = ?, meta_description = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssi', $title, $content, $meta_description, $status, $id);
    return $stmt->execute();
}

/**
 * Delete page
 * @param int $id Page ID
 * @param mysqli $conn Database connection
 * @return bool
 */
function deletePage($id, $conn) {
    $query = "DELETE FROM pages WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}
?>
