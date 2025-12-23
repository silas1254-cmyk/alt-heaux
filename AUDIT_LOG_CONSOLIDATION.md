# Audit Log Consolidation - Implementation Summary

## Overview
This migration consolidates the separate `admin_logs` and `website_updates` tables into a single unified `audit_log` table for centralized auditing and better data management.

## What Changed

### 1. New Files Created
- **includes/audit_helper.php** - Unified audit logging helper functions
  - `logAuditEvent()` - Central function for all audit logging
  - `getAuditLog()` - Unified query function with filtering
  - `getAuditLogCount()` - Count audit records
  - `getAuditStatistics()` - Statistics and summaries
  - `logAdminAction()` - Legacy wrapper for backward compatibility
  - `logWebsiteUpdate()` - Legacy wrapper for backward compatibility

- **admin/audit_log.php** - Central audit log viewing page
  - Unified display of all audit events (admin actions + data changes)
  - Advanced filtering: by type, category, admin, date range
  - Statistics dashboard showing event counts and trends
  - Pagination and responsive table layout
  - Dark admin theme styling

- **migrations_consolidate_audit_log.sql** - SQL migration script
  - Creates `audit_log` table with all necessary columns
  - Includes data migration queries for both legacy tables

- **run_consolidate_audit.php** - Migration runner script
  - Safely migrates data from old tables to new unified table
  - Verifies migration success
  - Can be run multiple times safely (idempotent)

### 2. Updated Files

#### includes/backup_helper.php
- `logAdminAction()` now delegates to `logAuditEvent()` from audit_helper
- `getAdminLogs()` now queries unified `audit_log` table
- Marked as deprecated with notes to use audit_helper instead

#### includes/updates_helper.php
- `logWebsiteUpdate()` now delegates to `logAuditEvent()` 
- `getWebsiteUpdates()` queries unified audit_log (with fallback to legacy table)
- All functions marked as deprecated for backward compatibility
- Requires audit_helper.php

#### admin/_sidebar.php
- Changed audit log link from `updates.php` to `audit_log.php`
- Unified single "Audit Log" link instead of separate logs

## Database Schema

### New audit_log Table
```sql
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    log_type ENUM('ACTION', 'CHANGE', 'SYSTEM') DEFAULT 'ACTION',
    category VARCHAR(100) NOT NULL,
    action_type VARCHAR(50),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    entity_id INT,
    entity_name VARCHAR(255),
    ip_address VARCHAR(45),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_admin (admin_id),
    INDEX idx_category (category),
    INDEX idx_log_type (log_type),
    INDEX idx_action_type (action_type),
    INDEX idx_entity (category, entity_id),
    INDEX idx_created (created_at DESC)
)
```

### Column Mapping
| New Column | Old admin_logs | Old website_updates | Purpose |
|-----------|----------------|-------------------|---------|
| admin_id | admin_id | admin_id | Who performed the action |
| log_type | 'ACTION' | 'CHANGE' | Type of audit event |
| category | 'Admin' | category | Category/section affected |
| action_type | first part of action | update_type | Action performed |
| title | action | title | Summary of change |
| description | details | description | Detailed change info |
| entity_id | NULL | entity_id | ID of affected entity |
| entity_name | NULL | NULL | Name of affected entity |
| ip_address | ip_address | NULL | Client IP address |
| details | details | NULL | Legacy details field |
| created_at | created_at | created_at | When event occurred |

## Migration Process

### Step 1: Run Migration
```bash
# From command line
php run_consolidate_audit.php

# Or access via web browser
http://localhost/alt-heaux/run_consolidate_audit.php
```

The script will:
1. Create `audit_log` table (if not exists)
2. Migrate data from `admin_logs`
3. Migrate data from `website_updates`
4. Verify data integrity
5. Display summary of what was done

### Step 2: Test New System
1. Visit admin panel: http://localhost/alt-heaux/admin/
2. Click "Audit Log" in sidebar
3. Verify all events display correctly
4. Test filters (by type, category, admin, date)
5. Check pagination and statistics

### Step 3: Cleanup (Optional)
Once confident the migration is successful, you can optionally drop the old tables:
```sql
-- Check that no code references these tables
DROP TABLE admin_logs;
DROP TABLE website_updates;
```

Note: The migration runner will keep the old tables intact to prevent data loss.

## Backward Compatibility

All existing code continues to work without changes:
- Old calls to `logAdminAction()` automatically use new system
- Old calls to `logWebsiteUpdate()` automatically use new system
- Helper functions redirect to unified system
- Legacy table fallback if new table doesn't exist

## New API

### For New Development
Use the new unified functions from `audit_helper.php`:

```php
// Log any type of audit event
logAuditEvent(
    $admin_id,
    'ACTION',               // or 'CHANGE', 'SYSTEM'
    'Product',              // Category
    'Create',               // Action type
    'Added product',        // Title
    'Added product XYZ',    // Description
    null,                   // entity_id (optional)
    'XYZ',                  // entity_name (optional)
    $_SERVER['REMOTE_ADDR'] // IP address (auto-detected if not provided)
);

// Get filtered audit logs
$logs = getAuditLog(
    $limit = 50,
    $offset = 0,
    $log_type = 'ACTION',    // Filter by type
    $admin_id = 5,           // Filter by admin
    $category = 'Product',   // Filter by category
    $date_from = '2025-01-01',
    $date_to = '2025-12-31'
);

// Get statistics
$stats = getAuditStatistics();
```

## Features

### Unified Audit Log Page
- **Type Filtering**: VIEW → ACTION (admin actions), CHANGE (data changes), SYSTEM (system events)
- **Category Filtering**: Filter by affected section (Product, Category, Admin, Settings, etc.)
- **Admin Filtering**: See actions by specific admin
- **Date Range**: Filter events between specific dates
- **Statistics Dashboard**: 
  - Total events
  - Today's actions
  - Count by type
  - Top 10 categories
  - Top 10 admins
- **Responsive Table**:
  - Type badges with color coding
  - Admin username
  - Timestamp (formatted)
  - IP address (if logged)
  - Full description/details with truncation
- **Pagination**: Navigate large audit logs

### Dark Theme
- Matches admin panel dark theme
- Professional audit log interface
- High contrast for readability
- Color-coded event types

## Troubleshooting

### Migration Script Issues
If `run_consolidate_audit.php` fails:
1. Check error messages for specific issues
2. Ensure database connection works
3. Verify `admin_users` table exists
4. Check file permissions on PHP script

### Queries Still Using Old Tables
If you have custom code querying old tables:
1. Update queries to use `audit_log` table
2. Use column mapping above for reference
3. Or use new helper functions instead of raw queries

### Data Missing After Migration
1. Old tables are NOT dropped automatically
2. Check both `audit_log` and old tables for data
3. Run `run_consolidate_audit.php` again to complete migration
4. Check error logs for any warnings

## Performance Impact

**Positive:**
- Single table instead of two = simpler queries
- Better indexed for common queries (by admin, category, date)
- Centralized audit trail
- Easier to query across types

**Migration Impact:**
- One-time data copy operation (few seconds for typical databases)
- No downtime required
- Can be run while site is active

## Rollback Plan

If issues occur:
1. Stop using new audit_log table
2. Update code back to using old helper functions
3. Old `admin_logs` and `website_updates` tables remain intact
4. No data lost during migration

## Next Steps

1. **Run Migration**: Execute `run_consolidate_audit.php`
2. **Test Thoroughly**: Verify all audit events log correctly
3. **Update Custom Code**: Any custom queries should use new `audit_log` table
4. **Monitor**: Check that new logging works as expected
5. **Cleanup**: Once confident, drop old tables

---

**Implementation Date**: 2025-12-23  
**Status**: Ready for deployment  
**Risk Level**: Low (backward compatible, reversible)
