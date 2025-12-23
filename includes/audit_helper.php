<?php
/**
 * Unified Audit Logging Helper
 * Consolidates admin action logging and website update tracking
 * Replaces: logAdminAction() and logWebsiteUpdate()
 */

if (defined('AUDIT_HELPER_LOADED')) return;
define('AUDIT_HELPER_LOADED', true);

require_once __DIR__ . '/config.php';

/**
 * Log an audit event to the unified audit_log table
 * 
 * @param int $admin_id The ID of the admin who performed the action
 * @param string $log_type 'ACTION' (admin action), 'CHANGE' (data change), or 'SYSTEM' (system event)
 * @param string $category Category of the event (Product, Category, Settings, Admin, etc.)
 * @param string $action_type Type of action (Create, Update, Delete, Login, etc.)
 * @param string $title Brief title/summary
 * @param string|null $description Detailed description
 * @param int|null $entity_id ID of affected entity
 * @param string|null $entity_name Name of affected entity
 * @param string|null $ip_address Client IP address (auto-detected if not provided)
 * @param string|null $details Legacy details field (optional)
 * @return bool True on success, false on failure
 */
function logAuditEvent($admin_id, $log_type, $category, $action_type, $title, $description = null, $entity_id = null, $entity_name = null, $ip_address = null, $details = null) {
    global $conn;
    
    // Auto-detect IP if not provided
    if (!$ip_address) {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_log (
                admin_id, log_type, category, action_type, title, description, 
                entity_id, entity_name, ip_address, details
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            error_log("Audit log prepare error: " . $conn->error);
            return false;
        }
        
        $success = $stmt->execute([
            $admin_id,
            strtoupper($log_type),
            $category,
            $action_type,
            $title,
            $description,
            $entity_id,
            $entity_name,
            $ip_address,
            $details ?? $description
        ]);
        
        $stmt->close();
        return $success;
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

// Legacy wrappers are defined in backup_helper.php and updates_helper.php
// for backward compatibility. They delegate to logAuditEvent().

/**
 * Get audit log entries with optional filtering
 * 
 * @param int|null $limit Number of records to retrieve
 * @param int|null $offset Starting record
 * @param string|null $log_type Filter by 'ACTION', 'CHANGE', or 'SYSTEM'
 * @param int|null $admin_id Filter by admin ID
 * @param string|null $category Filter by category
 * @param string|null $date_from Filter from date (YYYY-MM-DD)
 * @param string|null $date_to Filter to date (YYYY-MM-DD)
 * @return array Array of audit log entries
 */
function getAuditLog($limit = 50, $offset = 0, $log_type = null, $admin_id = null, $category = null, $date_from = null, $date_to = null) {
    global $conn;
    
    $query = "SELECT * FROM audit_log WHERE 1=1";
    $params = [];
    $types = "";
    
    // Filter by log type
    if ($log_type) {
        $query .= " AND log_type = ?";
        $params[] = strtoupper($log_type);
        $types .= "s";
    }
    
    // Filter by admin
    if ($admin_id) {
        $query .= " AND admin_id = ?";
        $params[] = (int)$admin_id;
        $types .= "i";
    }
    
    // Filter by category
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    // Filter by date range
    if ($date_from) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    
    if ($date_to) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    // Order and limit
    $query .= " ORDER BY created_at DESC";
    
    if ($limit) {
        $query .= " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        $types .= "ii";
    }
    
    try {
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Audit log query prepare error: " . $conn->error);
            return [];
        }
        
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $logs ?? [];
    } catch (Exception $e) {
        error_log("Audit log query error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total count of audit log entries
 * 
 * @param string|null $log_type Filter by type
 * @param int|null $admin_id Filter by admin
 * @param string|null $category Filter by category
 * @return int Total count
 */
function getAuditLogCount($log_type = null, $admin_id = null, $category = null) {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM audit_log WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($log_type) {
        $query .= " AND log_type = ?";
        $params[] = strtoupper($log_type);
        $types .= "s";
    }
    
    if ($admin_id) {
        $query .= " AND admin_id = ?";
        $params[] = (int)$admin_id;
        $types .= "i";
    }
    
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    try {
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Audit log count prepare error: " . $conn->error);
            return 0;
        }
        
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['count'] ?? 0);
    } catch (Exception $e) {
        error_log("Audit log count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get audit statistics
 * 
 * @return array Statistics data
 */
function getAuditStatistics() {
    global $conn;
    
    $stats = [];
    
    try {
        // Count by type
        $query = "SELECT log_type, COUNT(*) as count FROM audit_log GROUP BY log_type";
        $result = $conn->query($query);
        $stats['by_type'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Count by category
        $query = "SELECT category, COUNT(*) as count FROM audit_log GROUP BY category ORDER BY count DESC LIMIT 10";
        $result = $conn->query($query);
        $stats['by_category'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Count by admin
        $query = "SELECT a.id, a.username, COUNT(al.id) as count FROM audit_log al
                  LEFT JOIN admin_users a ON al.admin_id = a.id
                  GROUP BY a.id ORDER BY count DESC LIMIT 10";
        $result = $conn->query($query);
        $stats['by_admin'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Total counts
        $result = $conn->query("SELECT COUNT(*) as total FROM audit_log");
        $stats['total'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        $result = $conn->query("SELECT COUNT(*) as today FROM audit_log WHERE DATE(created_at) = CURDATE()");
        $stats['today'] = $result ? $result->fetch_assoc()['today'] : 0;
        
        return $stats;
    } catch (Exception $e) {
        error_log("Audit statistics error: " . $e->getMessage());
        return [];
    }
}
