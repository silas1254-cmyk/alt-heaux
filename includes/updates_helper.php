<?php
/**
 * Website Updates/Changelog Management Functions
 * Tracks all changes made to the website
 * 
 * NOTE: This file provides legacy wrapper functions for backward compatibility.
 * New code should use audit_helper.php and the logAuditEvent() function instead.
 */

if (defined('UPDATES_HELPER_LOADED')) {
    return;
}
define('UPDATES_HELPER_LOADED', true);

require_once __DIR__ . '/audit_helper.php';

/**
 * Log a website update (DEPRECATED - Use logAuditEvent instead)
 * @deprecated Use logAuditEvent() from audit_helper.php instead
 * @param string $category Update category (e.g., 'Product', 'Page', 'Settings', 'User', 'Design')
 * @param string $title Title of the update
 * @param string $description Detailed description of what was changed
 * @param string $update_type Type of update (Create, Update, Delete, etc.)
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function logWebsiteUpdate($category, $title, $description, $update_type = 'Update', $conn = null) {
    global $conn;
    
    $admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;
    
    // Delegate to new unified audit system
    return logAuditEvent($admin_id, 'CHANGE', $category, $update_type, $title, $description);
}

/**
 * Get all website updates with pagination (DEPRECATED)
 * @deprecated Use getAuditLog() from audit_helper.php instead
 * @param int $limit Number of updates per page
 * @param int $offset Offset for pagination
 * @param mysqli $conn Database connection
 * @return array Updates list
 */
function getWebsiteUpdates($limit = 20, $offset = 0, $conn = null) {
    global $conn;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'website_updates'");
    $legacy_exists = $table_check && $table_check->num_rows > 0;
    
    $limit = intval($limit);
    $offset = intval($offset);
    
    // If audit_log table exists, use it (new unified system)
    $table_check = $conn->query("SHOW TABLES LIKE 'audit_log'");
    if ($table_check && $table_check->num_rows > 0) {
        // Return from unified audit_log
        return getAuditLog($limit, $offset, 'CHANGE');
    }
    
    // Fallback to legacy website_updates table if it still exists
    if ($legacy_exists) {
        $query = "SELECT 
                    wu.id,
                    wu.category,
                    wu.title,
                    wu.description,
                    wu.update_type,
                    wu.created_at,
                    au.username as admin_name,
                    au.email as admin_email
                  FROM website_updates wu
                  LEFT JOIN admin_users au ON wu.admin_id = au.id
                  ORDER BY wu.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $updates = [];
        while ($row = $result->fetch_assoc()) {
            $updates[] = $row;
        }
        $stmt->close();
        return $updates;
    }
    
    return [];
}

/**
 * Get updates by category (DEPRECATED)
 * @deprecated Use getAuditLog() with category filter instead
 * @param string $category Category to filter
 * @param int $limit Number of updates
 * @param mysqli $conn Database connection
 * @return array Updates list
 */
function getUpdatesByCategory($category, $limit = 20, $conn = null) {
    global $conn;
    
    $category = trim($category);
    $limit = intval($limit);
    
    $query = "SELECT 
                wu.id,
                wu.category,
                wu.title,
                wu.description,
                wu.update_type,
                wu.created_at,
                au.username as admin_name
              FROM website_updates wu
              LEFT JOIN admin_users au ON wu.admin_id = au.id
              WHERE wu.category = ?
              ORDER BY wu.created_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('si', $category, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $updates = [];
    while ($row = $result->fetch_assoc()) {
        $updates[] = $row;
    }
    $stmt->close();
    return $updates;
}

/**
 * Get total number of updates
 * @param mysqli $conn Database connection
 * @return int Total updates count
 */
function getTotalUpdatesCount($conn = null) {
    global $conn;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'website_updates'");
    if ($table_check->num_rows === 0) {
        return 0;
    }
    
    $query = "SELECT COUNT(*) as count FROM website_updates";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

/**
 * Get update categories
 * @param mysqli $conn Database connection
 * @return array List of categories with counts
 */
function getUpdateCategories($conn = null) {
    global $conn;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'website_updates'");
    if ($table_check->num_rows === 0) {
        return [];
    }
    
    $query = "SELECT 
                category,
                COUNT(*) as count
              FROM website_updates
              GROUP BY category
              ORDER BY category ASC";
    
    $result = $conn->query($query);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

/**
 * Delete an update record
 * @param int $update_id Update ID to delete
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function deleteWebsiteUpdate($update_id, $conn = null) {
    global $conn;
    
    $update_id = intval($update_id);
    
    $query = "DELETE FROM website_updates WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $update_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Clear all updates (use with caution)
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function clearAllUpdates($conn = null) {
    global $conn;
    
    $query = "TRUNCATE TABLE website_updates";
    return $conn->query($query);
}

/**
 * Get recent updates (last N)
 * @param int $count Number of recent updates
 * @param mysqli $conn Database connection
 * @return array Recent updates
 */
function getRecentUpdates($count = 10, $conn = null) {
    global $conn;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'website_updates'");
    if ($table_check->num_rows === 0) {
        return [];
    }
    
    $count = intval($count);
    
    $query = "SELECT 
                wu.id,
                wu.category,
                wu.title,
                wu.description,
                wu.update_type,
                wu.created_at,
                au.username as admin_name
              FROM website_updates wu
              LEFT JOIN admin_users au ON wu.admin_id = au.id
              ORDER BY wu.created_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('i', $count);
    $stmt->execute();
    $result = $stmt->get_result();
    $updates = [];
    while ($row = $result->fetch_assoc()) {
        $updates[] = $row;
    }
    $stmt->close();
    return $updates;
}
?>
