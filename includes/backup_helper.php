<?php
/**
 * BACKUP HELPER FUNCTIONS
 * Functions for managing backups, database tables, and admin actions
 */

// ============================================
// DATABASE SETUP FUNCTIONS
// ============================================

/**
 * Create necessary database tables for admin features
 */
function createAdminTables($conn) {
    // Backups table
    $conn->query("
        CREATE TABLE IF NOT EXISTS backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(100) UNIQUE,
            size BIGINT,
            backup_type VARCHAR(50) DEFAULT 'full',
            admin_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
        )
    ");

    // Admin logs table
    $conn->query("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            action VARCHAR(100),
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
        )
    ");

    // Admin permissions table
    $conn->query("
        CREATE TABLE IF NOT EXISTS admin_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNIQUE,
            can_manage_products TINYINT DEFAULT 1,
            can_manage_orders TINYINT DEFAULT 0,
            can_view_analytics TINYINT DEFAULT 0,
            can_manage_users TINYINT DEFAULT 0,
            can_edit_settings TINYINT DEFAULT 0,
            can_manage_admins TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
        )
    ");

    // Settings table
    $conn->query("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_name VARCHAR(100) UNIQUE,
            setting_value LONGTEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Sliders table
    $conn->query("
        CREATE TABLE IF NOT EXISTS sliders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_url VARCHAR(255),
            title VARCHAR(255),
            description TEXT,
            link VARCHAR(255),
            position INT DEFAULT 0,
            active TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // FAQ table
    $conn->query("
        CREATE TABLE IF NOT EXISTS faqs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question VARCHAR(255),
            answer LONGTEXT,
            position INT DEFAULT 0,
            active TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Testimonials table
    $conn->query("
        CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100),
            customer_email VARCHAR(100),
            rating INT,
            message LONGTEXT,
            image_url VARCHAR(255),
            approved TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

// ============================================
// BACKUP FUNCTIONS
// ============================================

/**
 * Create a complete backup of database and files
 */
function createBackup($conn, $admin_id) {
    global $_SERVER;
    
    $backups_dir = __DIR__ . '/../backups';
    if (!is_dir($backups_dir)) {
        mkdir($backups_dir, 0755, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    $backup_folder = $backups_dir . '/backup_' . $timestamp;
    @mkdir($backup_folder, 0755, true);

    // Backup database
    $db_file = $backup_folder . '/database.sql';
    $db_backup = generateDatabaseBackup($conn);
    
    if (!file_put_contents($db_file, $db_backup)) {
        return ['success' => false, 'message' => 'Failed to create database backup'];
    }

    // Backup files
    $zip_file = $backup_folder . '/files.zip';
    if (!createZipBackup(__DIR__ . '/../', $zip_file)) {
        return ['success' => false, 'message' => 'Failed to create file backup'];
    }

    // Get total size
    $total_size = filesize($db_file) + filesize($zip_file);

    // Save backup info to database
    $filename = 'backup_' . $timestamp;
    $stmt = $conn->prepare("
        INSERT INTO backups (filename, size, admin_id, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param('sii', $filename, $total_size, $admin_id);
    $stmt->execute();

    return [
        'success' => true,
        'filename' => $filename,
        'size' => $total_size
    ];
}

/**
 * Generate SQL backup of entire database
 */
function generateDatabaseBackup($conn) {
    $backup = "-- Database Backup\n";
    $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- MySQL Version: " . mysqli_get_server_info($conn) . "\n";
    $backup .= "-- PHP Version: " . phpversion() . "\n\n";
    $backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Get all tables
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    // Backup each table
    foreach ($tables as $table) {
        $backup .= "\n-- Table: `$table`\n";
        $backup .= "DROP TABLE IF EXISTS `$table`;\n";

        // Create table structure
        $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
        $backup .= $create[1] . ";\n\n";

        // Insert data
        $data = $conn->query("SELECT * FROM `$table`");
        $num_fields = $data->field_count;

        while ($row = $data->fetch_row()) {
            $backup .= "INSERT INTO `$table` VALUES (";
            for ($i = 0; $i < $num_fields; $i++) {
                $row[$i] = addslashes($row[$i]);
                $backup .= ($row[$i] !== null) ? "'" . $row[$i] . "'" : "NULL";
                if ($i < $num_fields - 1) {
                    $backup .= ",";
                }
            }
            $backup .= ");\n";
        }
        $backup .= "\n";
    }

    $backup .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $backup;
}

/**
 * Create ZIP file of website files
 */
function createZipBackup($source_dir, $zip_file, $exclude = []) {
    if (!extension_loaded('zip')) {
        return false;
    }

    $default_exclude = ['backups', 'uploads/temp', '.git', 'node_modules', '.DS_Store'];
    $exclude = array_merge($default_exclude, $exclude);

    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (is_dir($file)) {
            continue;
        }

        $file_path = $file->getRealPath();
        $relative_path = substr($file_path, strlen($source_dir) + 1);

        $skip = false;
        foreach ($exclude as $pattern) {
            if (strpos($relative_path, $pattern) === 0) {
                $skip = true;
                break;
            }
        }

        if (!$skip && is_file($file_path)) {
            $zip->addFile($file_path, $relative_path);
        }
    }

    $zip->close();
    return true;
}

/**
 * Delete a backup
 */
function deleteBackup($conn, $backup_id, $admin_id) {
    // Get backup info
    $stmt = $conn->prepare("SELECT filename FROM backups WHERE id = ?");
    $stmt->bind_param('i', $backup_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        return ['success' => false, 'message' => 'Backup not found'];
    }

    $backup_folder = __DIR__ . '/../backups/' . $result['filename'];
    
    // Delete from filesystem
    if (is_dir($backup_folder)) {
        deleteDirectory($backup_folder);
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM backups WHERE id = ?");
    $stmt->bind_param('i', $backup_id);
    $stmt->execute();

    return ['success' => true];
}

/**
 * Restore a backup
 */
function restoreBackup($conn, $backup_id, $admin_id) {
    // Get backup info
    $stmt = $conn->prepare("SELECT filename FROM backups WHERE id = ?");
    $stmt->bind_param('i', $backup_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        return ['success' => false, 'message' => 'Backup not found'];
    }

    $backup_folder = __DIR__ . '/../backups/' . $result['filename'];
    $db_file = $backup_folder . '/database.sql';

    // Restore database
    if (file_exists($db_file)) {
        $sql = file_get_contents($db_file);
        
        // Split queries and execute
        $queries = array_filter(array_map('trim', preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql)));
        foreach ($queries as $query) {
            if (strlen($query) > 5) {
                $conn->query($query);
            }
        }
    }

    // TODO: Restore files from zip if needed

    return ['success' => true];
}

/**
 * Get list of all backups
 */
function getBackupList($conn) {
    $result = $conn->query("
        SELECT b.*, a.username as admin_name
        FROM backups b
        LEFT JOIN admins a ON b.admin_id = a.id
        ORDER BY b.created_at DESC
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get total size of all backups
 */
function getTotalBackupSize($backups) {
    $total = 0;
    foreach ($backups as $backup) {
        $total += $backup['size'];
    }
    return $total;
}

/**
 * Get total storage used by backups directory
 */
function getTotalStorageUsed() {
    $backups_dir = __DIR__ . '/../backups';
    $size = getDirectorySize($backups_dir);
    return formatBytes($size);
}

/**
 * Get directory size recursively
 */
function getDirectorySize($dir) {
    $size = 0;
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . '/' . $file;
                if (is_file($path)) {
                    $size += filesize($path);
                } elseif (is_dir($path)) {
                    $size += getDirectorySize($path);
                }
            }
        }
    }
    return $size;
}

/**
 * Delete directory recursively
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($dir);
}

// ============================================
// ADMIN ACTIVITY LOGGING
// ============================================

/**
 * Log admin action for audit trail
 */
function logAdminAction($conn, $admin_id, $action, $details = '') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('isss', $admin_id, $action, $details, $ip_address);
    return $stmt->execute();
}

/**
 * Get admin activity logs
 */
function getAdminLogs($conn, $limit = 100) {
    $result = $conn->query("
        SELECT l.*, a.username
        FROM admin_logs l
        LEFT JOIN admins a ON l.admin_id = a.id
        ORDER BY l.created_at DESC
        LIMIT $limit
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Format bytes to human-readable size
 */
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

/**
 * Format time ago (e.g., "2 hours ago")
 */
function timeAgo($datetime) {
    $time_ago = strtotime($datetime);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    
    if ($time_difference < 1) return 'Just now';
    
    $condition = [
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
}

// ============================================
// SETTINGS MANAGEMENT FUNCTIONS
// ============================================

/**
 * Update or create a site setting in database
 */
function updateDbSetting($conn, $setting_name, $setting_value) {
    $stmt = $conn->prepare("
        INSERT INTO site_settings (setting_name, setting_value, updated_at) 
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    $stmt->bind_param('sss', $setting_name, $setting_value, $setting_value);
    return $stmt->execute();
}

/**
 * Get a specific site setting from database
 */
function getDbSetting($conn, $setting_name, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_name = ?");
    $stmt->bind_param('s', $setting_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    return $default;
}

/**
 * Get all site settings from database as associative array
 */
function getAllDbSettings($conn) {
    $result = $conn->query("SELECT setting_name, setting_value FROM site_settings");
    $settings = [];
    
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
    
    return $settings;
}

/**
 * Delete a site setting from database
 */
function deleteDbSetting($conn, $setting_name) {
    $stmt = $conn->prepare("DELETE FROM site_settings WHERE setting_name = ?");
    $stmt->bind_param('s', $setting_name);
    return $stmt->execute();
}

?>
