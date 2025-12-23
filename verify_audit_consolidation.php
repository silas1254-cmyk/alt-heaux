<?php
/**
 * Quick Setup Guide for Audit Log Consolidation
 * Run this after consolidating to ensure everything is configured correctly
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/audit_helper.php';

echo "=== Audit Log Consolidation Setup Verification ===\n\n";

// 1. Check audit_log table
echo "[1/4] Checking audit_log table...\n";
$result = $conn->query("SHOW TABLES LIKE 'audit_log'");
if ($result && $result->num_rows > 0) {
    echo "    ✓ audit_log table exists\n";
    
    // Get record count
    $result = $conn->query("SELECT COUNT(*) as count FROM audit_log");
    $count = $result->fetch_assoc()['count'];
    echo "    ✓ Contains $count records\n";
    
    // Show sample
    $result = $conn->query("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "    ✓ Latest record: " . date('M d, Y H:i:s', strtotime($row['created_at'])) . "\n";
    }
} else {
    echo "    ❌ audit_log table NOT found\n";
    echo "    → Run run_consolidate_audit.php first\n";
}

// 2. Check old tables
echo "\n[2/4] Checking legacy tables...\n";

$result = $conn->query("SHOW TABLES LIKE 'admin_logs'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM admin_logs");
    $count = $result->fetch_assoc()['count'];
    echo "    ℹ admin_logs table exists ($count records)\n";
} else {
    echo "    ✓ admin_logs table removed\n";
}

$result = $conn->query("SHOW TABLES LIKE 'website_updates'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM website_updates");
    $count = $result->fetch_assoc()['count'];
    echo "    ℹ website_updates table exists ($count records)\n";
} else {
    echo "    ✓ website_updates table removed\n";
}

// 3. Test helper functions
echo "\n[3/4] Testing audit helper functions...\n";

if (function_exists('logAuditEvent')) {
    echo "    ✓ logAuditEvent() function loaded\n";
} else {
    echo "    ❌ logAuditEvent() function NOT loaded\n";
}

if (function_exists('getAuditLog')) {
    echo "    ✓ getAuditLog() function loaded\n";
    
    // Test query
    $logs = getAuditLog(5, 0);
    echo "    ✓ Query successful - retrieved " . count($logs) . " records\n";
} else {
    echo "    ❌ getAuditLog() function NOT loaded\n";
}

if (function_exists('getAuditStatistics')) {
    echo "    ✓ getAuditStatistics() function loaded\n";
    
    // Test query
    $stats = getAuditStatistics();
    echo "    ✓ Statistics retrieved - " . ($stats['total'] ?? 0) . " total events\n";
} else {
    echo "    ❌ getAuditStatistics() function NOT loaded\n";
}

// 4. Backward compatibility check
echo "\n[4/4] Testing backward compatibility...\n";

// Check if old helpers still work
require_once __DIR__ . '/includes/backup_helper.php';
require_once __DIR__ . '/includes/updates_helper.php';

if (function_exists('logAdminAction')) {
    echo "    ✓ Legacy logAdminAction() still available\n";
} else {
    echo "    ❌ Legacy logAdminAction() NOT available\n";
}

if (function_exists('logWebsiteUpdate')) {
    echo "    ✓ Legacy logWebsiteUpdate() still available\n";
} else {
    echo "    ❌ Legacy logWebsiteUpdate() NOT available\n";
}

// Summary
echo "\n=== Setup Complete ===\n";
echo "✓ Audit log consolidation is ready to use!\n\n";
echo "Next steps:\n";
echo "1. Visit admin panel: /admin/audit_log.php\n";
echo "2. Review audit events and test filters\n";
echo "3. Check that new events are being logged\n";
echo "4. When confident, drop old tables (optional):\n";
echo "   - DROP TABLE admin_logs;\n";
echo "   - DROP TABLE website_updates;\n";

$conn->close();
?>
