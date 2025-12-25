# Database Migrations Security Guide

## Overview

Database migrations allow you to update your database schema in a controlled and tracked manner. All migrations in this project are secured with authentication, authorization, and comprehensive audit logging.

## Security Features

### ✅ Authentication Required
All migration scripts require an active admin session. Unauthenticated access is blocked with HTTP 403 Forbidden.

### ✅ Super User Only
Only admins with the `super_user` role can execute migrations. Regular admins and users are blocked.

### ✅ Audit Logging
Every migration execution is logged in the `audit_log` table with:
- Admin ID and email address
- Migration name and status (START/COMPLETED/FAILED)
- Timestamp of execution
- IP address of the admin
- Query results and any errors

### ✅ Explicit Confirmation
Users must confirm before executing any migration via browser prompt.

### ✅ Backup Reminder
The UI reminds admins to backup before executing destructive migrations.

## Available Migrations

### Auto-Executable Migrations (Via Admin Panel)

#### 1. Add Category Display Order
- **File**: `migrations_add_category_order.sql`
- **Runner**: `/admin/migrations.php` → Execute button
- **Purpose**: Adds `display_order` column to categories table
- **Status**: ✅ Completed & Ready
- **Access**: Admin panel at `/admin/migrations.php`

#### 2. Page Builder Schema
- **File**: `migrations_page_builder.sql`
- **Runner**: `/admin/migrations.php` → Execute button
- **Purpose**: Creates `pages` and `page_blocks` tables
- **Status**: ✅ Completed & Ready
- **Access**: Admin panel at `/admin/migrations.php`

### Manual-Only Migrations (Requires cPanel)

These migrations must be executed manually via cPanel phpMyAdmin for additional safety:

#### 3. Add Product Visibility
- **File**: `migrations_add_product_visibility.sql`
- **Method**: Copy & paste into phpMyAdmin SQL tab
- **Purpose**: Adds product hiding feature for release scheduling

#### 4. Guest Cart Persistence
- **File**: `migrations_guest_cart.sql`
- **Method**: Copy & paste into phpMyAdmin SQL tab
- **Purpose**: Creates guest cart persistence system

#### 5. Consolidate Audit Log
- **File**: `migrations_consolidate_audit_log.sql`
- **Method**: Copy & paste into phpMyAdmin SQL tab
- **Purpose**: Merges multiple audit tables into unified log

### Destructive Migrations (High Risk)

These migrations remove database tables and must ONLY be executed after explicit backup:

#### Drop Pages Table
- **File**: `migrations_drop_pages_table.sql`
- **Method**: cPanel phpMyAdmin only
- **Risk**: HIGH - Removes entire pages table
- **Backup**: REQUIRED before execution
- **Confirmation**: Double-check table name in file before executing

#### Consolidate Audit Log (Destructive Phase)
- **File**: Included in `migrations_consolidate_audit_log.sql`
- **Method**: cPanel phpMyAdmin only
- **Risk**: HIGH - Consolidates and may drop old tables
- **Backup**: REQUIRED before execution

## How to Execute Migrations

### Method 1: Admin Panel (Auto Runners)

1. Log in as super_user admin
2. Navigate to `/admin/migrations.php`
3. Click "Execute Migration" button
4. Review audit log for completion status
5. Check results in modal dialog

**URL**: `https://alt-heaux.site/admin/migrations.php`

### Method 2: cPanel phpMyAdmin (Manual)

1. Log in to cPanel
2. Click "phpMyAdmin"
3. Select database `altheaux_website`
4. Click "SQL" tab
5. Copy & paste SQL from migration file
6. Click "Go" to execute
7. Verify success message

### Method 3: Direct Script Execution (DEPRECATED)

The old direct URLs still work but require admin login:
- `https://alt-heaux.site/run_migration_category_order.php`
- `https://alt-heaux.site/run_migration_page_builder.php`

These require being logged in as super_user. Use the admin panel instead.

## Monitoring Migrations

### View Audit Log
All migrations are logged in the `audit_log` table:

```sql
SELECT * FROM audit_log 
WHERE category = 'Database' AND action_type = 'Migration' 
ORDER BY created_at DESC;
```

### Check Migration Status
In admin panel at `/admin/migrations.php`, the "Recent Activity" section shows:
- Migration name
- Execution date/time
- Admin who executed it

### Troubleshoot Failed Migrations
If a migration fails:

1. Check error message in migration output modal
2. Review audit log for SQL error details
3. Verify no duplicate columns/tables
4. Check database permissions
5. Backup and manually review SQL file
6. Contact hosting support if needed

## Best Practices

### Before Any Migration
- [ ] Backup entire database via cPanel Backups
- [ ] Test on staging environment first
- [ ] Review SQL file to understand changes
- [ ] Inform team of planned changes
- [ ] Schedule during low-traffic hours

### During Migration
- [ ] Execute one migration at a time
- [ ] Monitor admin panel for completion
- [ ] Check audit log for success status
- [ ] Don't refresh browser during execution
- [ ] Note timestamps for reference

### After Migration
- [ ] Verify new columns/tables created
- [ ] Test affected functionality
- [ ] Review audit log entry
- [ ] Check performance impact
- [ ] Document changes in changelog

## Troubleshooting

### Migration Blocked - Access Denied
**Cause**: Not a super_user admin
**Solution**: Only super_user role can execute migrations

### Migration Blocked - Not Authenticated
**Cause**: Not logged in to admin panel
**Solution**: Log in first at `/admin/login.php`

### Column Already Exists
**Cause**: Migration was already executed
**Solution**: Safe to ignore (using `IF NOT EXISTS` or `ADD COLUMN IF`)

### Table Not Found
**Cause**: Prerequisites not met
**Solution**: Check if earlier migrations were skipped

### Database Connection Error
**Cause**: Config.php not loaded
**Solution**: Check that `includes/config.php` is readable

## Security Considerations

### Why Authentication is Required
- Prevents unauthorized schema changes
- Protects against CSRF attacks (must be logged in)
- Maintains audit trail of who changed what

### Why Super User Only
- Schema changes are high-risk operations
- Regular admins should not alter database structure
- Limits exposure if regular admin account is compromised

### Why Audit Logging
- Tracks who executed migrations and when
- Provides recovery information if problems occur
- Required for compliance and security audits
- Enables investigation if unauthorized changes occur

## Emergency Procedures

### If Migration Goes Wrong
1. **Stop execution** - Don't refresh browser
2. **Check error** - Read error message carefully
3. **Restore backup** - If critical data lost
4. **Review logs** - Check audit log for details
5. **Contact support** - If unable to resolve

### Rollback Procedure
If a migration must be rolled back:

1. Restore database backup via cPanel Backups
2. Document issue in audit log with note
3. Review SQL file for what went wrong
4. Fix and re-test before re-executing
5. Get super_user approval before retry

## Related Documentation

- [SECURITY.md](../SECURITY.md) - Overall security policy
- [DEPLOYMENT_CHECKLIST.md](../DEPLOYMENT_CHECKLIST.md) - Deployment process
- [SECURITY_SETUP.md](../SECURITY_SETUP.md) - Initial setup instructions
- Database backup instructions in cPanel

## Changelog

- **Dec 25, 2025**: Secured migration runners with admin auth, created admin panel interface
- **Dec 23, 2025**: Initial migration files created
