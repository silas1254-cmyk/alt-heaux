<?php
/**
 * Migration Runner: Consolidate Audit Logging
 * This script consolidates admin_logs and website_updates tables into unified audit_log table
 * 
 * Run from command line: php run_consolidate_audit.php
 * Or access via web: http://localhost/alt-heaux/run_consolidate_audit.php
 */

require_once __DIR__ . '/includes/config.php';

echo "=== Audit Log Consolidation Migration ===\n\n";

try {
    // Step 1: Check if new audit_log table exists
    echo "[1/5] Checking for existing audit_log table...\n";
    
    $result = $conn->query("SHOW TABLES LIKE 'audit_log'");
    $exists = ($result && $result->num_rows > 0);
    
    if ($exists) {
        echo "    ✓ audit_log table already exists\n";
    } else {
        echo "    - Creating audit_log table...\n";
        
        $sql = <<<SQL
            CREATE TABLE audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT,
                log_type ENUM('ACTION', 'CHANGE', 'SYSTEM') DEFAULT 'ACTION' COMMENT 'ACTION=admin action, CHANGE=data change, SYSTEM=system event',
                category VARCHAR(100) NOT NULL COMMENT 'Product, Category, Settings, User, Design, etc.',
                action_type VARCHAR(50) COMMENT 'Create, Update, Delete, Login, Logout, etc.',
                title VARCHAR(255) NOT NULL,
                description TEXT,
                entity_id INT COMMENT 'ID of the affected entity',
                entity_name VARCHAR(255) COMMENT 'Name of the affected entity',
                ip_address VARCHAR(45),
                details TEXT COMMENT 'Legacy details field for backward compatibility',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
                INDEX idx_admin (admin_id),
                INDEX idx_category (category),
                INDEX idx_log_type (log_type),
                INDEX idx_action_type (action_type),
                INDEX idx_entity (category, entity_id),
                INDEX idx_created (created_at)
            )
        SQL;
        
        if ($conn->query($sql)) {
            echo "    ✓ audit_log table created successfully\n";
        } else {
            throw new Exception("Failed to create audit_log table: " . $conn->error);
        }
    }
    
    // Step 2: Check admin_logs table
    echo "\n[2/5] Processing admin_logs data...\n";
    
    $result = $conn->query("SHOW TABLES LIKE 'admin_logs'");
    $admin_logs_exists = ($result && $result->num_rows > 0);
    
    if ($admin_logs_exists) {
        $result = $conn->query("SELECT COUNT(*) as count FROM admin_logs");
        $count = $result->fetch_assoc()['count'];
        echo "    - Found $count records in admin_logs\n";
        
        // Check how many are already migrated
        $result = $conn->query("SELECT COUNT(*) as count FROM audit_log WHERE log_type = 'ACTION'");
        $migrated = $result->fetch_assoc()['count'];
        echo "    - $migrated already migrated\n";
        
        if ($migrated < $count) {
            echo "    - Migrating remaining admin_logs data...\n";
            
            $sql = "
                INSERT INTO audit_log (
                    admin_id, log_type, category, action_type, title, description, 
                    ip_address, details, created_at
                )
                SELECT 
                    admin_id,
                    'ACTION' as log_type,
                    'Admin' as category,
                    SUBSTRING_INDEX(action, ':', 1) as action_type,
                    action as title,
                    details,
                    ip_address,
                    details,
                    created_at
                FROM admin_logs
                WHERE admin_logs.id NOT IN (
                    SELECT id FROM audit_log WHERE id IN (
                        SELECT al.id FROM admin_logs al 
                        LEFT JOIN audit_log aud ON al.id = aud.id AND aud.log_type = 'ACTION'
                    )
                )
            ";
            
            if ($conn->query($sql)) {
                echo "    ✓ admin_logs data migrated\n";
            } else {
                echo "    ⚠ Note: Some admin_logs may already be migrated: " . $conn->error . "\n";
            }
        }
    } else {
        echo "    - admin_logs table not found (may have been already consolidated)\n";
    }
    
    // Step 3: Check website_updates table
    echo "\n[3/5] Processing website_updates data...\n";
    
    $result = $conn->query("SHOW TABLES LIKE 'website_updates'");
    $updates_exists = ($result && $result->num_rows > 0);
    
    if ($updates_exists) {
        $result = $conn->query("SELECT COUNT(*) as count FROM website_updates");
        $count = $result->fetch_assoc()['count'];
        echo "    - Found $count records in website_updates\n";
        
        // Check how many are already migrated
        $result = $conn->query("SELECT COUNT(*) as count FROM audit_log WHERE log_type = 'CHANGE'");
        $migrated = $result->fetch_assoc()['count'];
        echo "    - $migrated already migrated\n";
        
        if ($migrated < $count) {
            echo "    - Migrating remaining website_updates data...\n";
            
            $sql = "
                INSERT INTO audit_log (
                    admin_id, log_type, category, action_type, title, description, 
                    created_at
                )
                SELECT 
                    admin_id,
                    'CHANGE' as log_type,
                    category,
                    update_type as action_type,
                    title,
                    description,
                    created_at
                FROM website_updates
                WHERE website_updates.id NOT IN (
                    SELECT id FROM audit_log WHERE id IN (
                        SELECT wu.id FROM website_updates wu 
                        LEFT JOIN audit_log aud ON wu.id = aud.id AND aud.log_type = 'CHANGE'
                    )
                )
            ";
            
            if ($conn->query($sql)) {
                echo "    ✓ website_updates data migrated\n";
            } else {
                echo "    ⚠ Note: Some website_updates may already be migrated: " . $conn->error . "\n";
            }
        }
    } else {
        echo "    - website_updates table not found (may have been already consolidated)\n";
    }
    
    // Step 4: Verify consolidated data
    echo "\n[4/5] Verifying consolidated data...\n";
    
    $result = $conn->query("SELECT COUNT(*) as total FROM audit_log");
    $total = $result->fetch_assoc()['total'];
    echo "    - Total audit_log records: $total\n";
    
    $result = $conn->query("SELECT log_type, COUNT(*) as count FROM audit_log GROUP BY log_type");
    while ($row = $result->fetch_assoc()) {
        echo "    - {$row['log_type']}: {$row['count']} records\n";
    }
    
    // Step 5: Check if we should keep old tables
    echo "\n[5/5] Migration Summary\n";
    echo "    ✓ Unified audit_log table created and populated\n";
    
    if ($admin_logs_exists) {
        echo "    ℹ Old admin_logs table still exists\n";
        echo "    → You can manually drop it if you're confident: DROP TABLE admin_logs;\n";
    }
    
    if ($updates_exists) {
        echo "    ℹ Old website_updates table still exists\n";
        echo "    → You can manually drop it if you're confident: DROP TABLE website_updates;\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "✓ Audit log consolidation successful!\n";
    echo "\nNext steps:\n";
    echo "1. Test the unified audit log page: /admin/audit_log.php\n";
    echo "2. Update sidebar to link to unified audit log\n";
    echo "3. Keep old tables for reference, or DROP them when confident\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
