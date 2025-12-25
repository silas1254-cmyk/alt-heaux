# Database Deployment Strategy

## Overview

Since migration files are kept locally (not in git) for security, this document defines how to deploy database changes to production safely.

## Deployment Methods

### Method 1: Admin Panel Interface (RECOMMENDED PRIMARY)
**Best for**: Normal production deployments, team deployments
**Risk Level**: LOW (super_user auth required, auto-logged)
**Audit Trail**: Automatically logged in audit_log table

**Access**: https://alt-heaux.site/admin/migrations.php

**Steps**:
1. Log in as super_user admin
2. Navigate to `/admin/migrations.php`
3. Select migration to execute
4. Click "Execute Migration" button
5. Confirm in dialog
6. View results and status
7. Changes automatically logged in audit_log

**Advantages**:
- ✅ Automatic audit logging (who/when/IP)
- ✅ Super_user role enforcement
- ✅ Integrated authentication
- ✅ Compliance-ready audit trail
- ✅ Team visibility (everyone sees who deployed what)
- ✅ Lower risk (only pre-defined migrations)
- ✅ Better security posture

---

### Method 2: Manual cPanel phpMyAdmin (EMERGENCY/DEBUG ONLY)
**Best for**: Emergency fixes, arbitrary SQL, app debugging
**Risk Level**: MEDIUM (full database access)
**Audit Trail**: Manual logging required

**Steps**:
1. Connect to cPanel
2. Open phpMyAdmin
3. Select `altheaux_website` database
4. Click "SQL" tab
5. Paste SQL from migration file
6. Click "Go" to execute
7. Manually document in DEPLOYMENT_LOG.md

**When to use**:
- ✅ Application is broken/down
- ✅ Need to run arbitrary SQL
- ✅ Emergency database access needed
- ✅ Testing SQL before creating migration
- ✅ Direct database debugging

---

### Method 3: SSH Command Line (FUTURE - IF NEEDED)
**Best for**: Batch operations, future automation
**Risk Level**: MEDIUM (requires SSH access)
**Audit Trail**: Server-level logging

**Status**: NOT CURRENTLY RECOMMENDED
- SSH port 22 blocked on hosting
- Contact ifastnet to enable if needed
- Consider only after improving password security (SSH keys)

---

---

## Comparison: Method 1 (cPanel phpMyAdmin) vs Method 3 (Admin Panel)

### Why Method 1 (cPanel phpMyAdmin) vs Method 3 (Admin Panel)

**Actually - You Make a Good Point!** The Admin Panel might be BETTER. Here's the honest comparison:

#### phpMyAdmin Advantages:
✅ **Works When App is Broken**: If PHP/application is down, phpMyAdmin still works
✅ **No Dependencies**: Doesn't depend on migration runner scripts working
✅ **More Powerful**: Can execute ANY SQL, not just migrations
✅ **Direct Database Access**: Full control at database level
✅ **Better for Debugging**: Can query data directly, test queries interactively
✅ **Already Available**: Requires no additional code

#### phpMyAdmin Disadvantages:
❌ **No Automatic Audit Trail**: Changes aren't logged in audit_log (unless you log them manually)
❌ **No Role Enforcement**: Anyone with cPanel access can make ANY changes
❌ **Outside Application**: Requires separate cPanel login, leaves the app ecosystem
❌ **Less Secure**: Bypasses application-level permission checks
❌ **Higher Privilege**: Full database access (riskier)
❌ **Manual Documentation**: You have to manually document changes

---

#### Admin Panel (/admin/migrations.php) Advantages:
✅ **Automatic Audit Logging**: All changes logged in audit_log table with timestamp, admin ID, IP
✅ **Super User Only**: Only admins with super_user role can execute (role enforcement)
✅ **Integrated Authentication**: Uses app's session system (logged in as specific user)
✅ **Branded Experience**: Looks/feels like your application
✅ **Audit Trail**: Built-in record of who did what when
✅ **Lower Privilege**: Only executes planned migrations (not arbitrary SQL)
✅ **Team Tracking**: Clear record for compliance/security reviews
✅ **Future Automation**: Can be automated in deployment pipeline
✅ **Better Security Posture**: App-level controls, not raw database access
✅ **Less Mistake Risk**: Can only execute pre-defined migrations

#### Admin Panel (/admin/migrations.php) Disadvantages:
❌ **Depends on Application**: If PHP/app is broken, can't access panel
❌ **Limited to Migrations**: Can't run arbitrary SQL (intentional safety feature)
❌ **Requires Runner Scripts**: Migration runner PHP files must exist and work
❌ **Harder for Emergency**: If something is wrong with the app, can't bypass to fix DB

---

## Decision Matrix: phpMyAdmin vs Admin Panel

| Factor | phpMyAdmin | Admin Panel | Winner |
|--------|-----------|------------|--------|
| **Automatic Audit Log** | ❌ No | ✅ Yes | Admin Panel |
| **Role Enforcement** | ❌ No | ✅ Yes (super_user) | Admin Panel |
| **Security** | Medium | High | Admin Panel |
| **Works When App Down** | ✅ Yes | ❌ No | phpMyAdmin |
| **Integrated Auth** | ❌ No | ✅ Yes | Admin Panel |
| **Can Run Any SQL** | ✅ Yes | ❌ No (migrations only) | phpMyAdmin |
| **Team Visibility** | ❌ Manual | ✅ Automatic | Admin Panel |
| **Compliance Ready** | ❌ No | ✅ Yes | Admin Panel |
| **Emergency Access** | ✅ Yes | ❌ No | phpMyAdmin |
| **Ease of Use** | Very Easy | Easy | phpMyAdmin |
| **Audit Trail** | ❌ No | ✅ Yes | Admin Panel |
| **Less Risky** | Medium | High | Admin Panel |

---

## Revised Best Practice

### Use Admin Panel (/admin/migrations.php) for Normal Deployments:
- ✅ Application is running normally
- ✅ You want automatic audit logging
- ✅ You need role-based access control
- ✅ You want compliance/audit trail
- ✅ You're deploying planned migrations
- ✅ Team members need to see who changed what
- ✅ You want app-level security controls
- ✅ **This is actually the BETTER choice for regular deployments**

### Use cPanel phpMyAdmin for Emergencies Only:
- ✅ Application is broken/down
- ✅ You need to run arbitrary SQL (debugging)
- ✅ You need emergency database access
- ✅ Migration runner scripts aren't working
- ✅ You need to query data directly
- ✅ You're testing SQL before using in migrations
- ✅ **This is the backup method for emergencies**

---

## Preferred Workflow

### Normal Deployments (Use Admin Panel):
```
1. Log in to /admin/migrations.php as super_user
2. Click "Execute Migration"
3. Confirm in dialog
4. Results logged automatically in audit_log
5. Team can see who deployed what when
```

### Emergency/Debugging (Use phpMyAdmin):
```
1. Go to cPanel → phpMyAdmin
2. Select database
3. Run diagnostic queries
4. Fix issues
5. Return to Admin Panel once app is stable
```

---

## Security Comparison

### phpMyAdmin (Lower Security):
```
Risks:
- Anyone with cPanel access can make ANY database change
- No audit trail in the application
- Bypasses app-level permission checks
- No record of who made changes through app
- Could silently corrupt data without app knowing
```

### Admin Panel (Higher Security):
```
Protections:
- Only super_user admins can execute migrations
- Every change logged in audit_log with admin ID
- Timestamp and IP address recorded
- Clear record: "John deployed migration at 3:45 PM"
- Limited to pre-defined, tested migrations
- App-level authorization enforcement
```

---

## Revised Recommendation

**Use the Admin Panel as your PRIMARY method.** It's more secure, has better auditing, and is integrated with your application. Only fall back to phpMyAdmin if:
- The application is completely broken
- You need to debug raw database issues
- The migration runner isn't working properly

**The Admin Panel gives you the best of both worlds** - ease of use + automatic security/auditing that phpMyAdmin doesn't provide.

---

## Initial Database Setup

The `altheaux_website` database already exists on the production server with all current tables.

### Backup Current Schema
To preserve current database state:

1. Go to cPanel
2. Click "Backups"
3. Click "Download a Full Backup" or "Download a MySQL Database Backup"
4. Save as `altheaux_website_backup_[date].sql`
5. Keep locally for reference

### If Rebuilding Database
Never delete the production database! Instead:

1. Create test database first: `altheaux_website_test`
2. Import current backup to test database
3. Apply migrations to test database
4. Verify functionality
5. Back up production
6. Apply migrations to production

---

## Deployment Workflow

### For Small Schema Changes (1-2 migrations)

```
1. Create SQL file locally (e.g., migrations_new_feature.sql)
2. Test on local development database
3. Create staging environment copy
4. Test migration on staging
5. Back up production database (cPanel Backups)
6. Log in to /admin/migrations.php as super_user
7. Click "Execute Migration" for the migration
8. Confirm execution
9. Wait for completion and verify success
10. Check audit_log to confirm deployment logged
11. Test affected application features
12. Update DEPLOYMENT_LOG.md
13. Inform team of changes
```

### For Major Schema Changes (Multiple migrations)

```
1. Create comprehensive migration SQL file
2. Test on local database multiple times
3. Create detailed testing document
4. Schedule maintenance window (notify users)
5. Back up entire production database
6. Test backup restore (verify backup integrity)
7. Execute migration in staging first via admin panel
8. Get approval from team lead
9. Execute migration on production via /admin/migrations.php
10. Verify audit_log shows execution
11. Monitor error logs for issues
12. Test all affected features
13. Update documentation
14. Announce completion to team
15. Keep backup for 1+ month
```

---

## Migration Files Storage

### Local Storage Structure
Keep migration files in local directory (NOT committed to git):

```
alt-heaux/
├── database/
│   ├── migrations/
│   │   ├── 2025-12-23_add_category_order.sql
│   │   ├── 2025-12-23_add_product_visibility.sql
│   │   ├── 2025-12-23_guest_cart.sql
│   │   └── 2025-12-24_new_feature.sql
│   ├── backups/
│   │   ├── altheaux_website_backup_2025-12-25.sql
│   │   └── altheaux_website_backup_2025-12-20.sql
│   └── schemas/
│       └── altheaux_website_current_schema.sql
```

### File Naming Convention
Use format: `YYYY-MM-DD_description.sql`

Examples:
- `2025-12-25_add_category_order.sql`
- `2025-12-26_fix_audit_log_indexes.sql`
- `2025-12-27_remove_deprecated_column.sql`

---

## Production Database Backup Strategy

### Regular Backups (CRITICAL)
- **Frequency**: Weekly via cPanel Backups
- **Location**: Download & store locally
- **Format**: Full MySQL dump (`.sql` files)
- **Retention**: Keep last 4 weeks

### Backup Before Migrations
- Back up immediately before any migration
- Name with date: `altheaux_website_backup_pre_2025-12-25.sql`
- Keep for at least 1 month after successful migration

### How to Backup via cPanel
1. Log in to cPanel
2. Click "Backups"
3. Under "Backup" section, click "Full Backup" or "Database Backup"
4. Follow prompts to download
5. Save locally with descriptive name

### How to Restore from Backup
1. Log in to cPanel
2. Click "Backups"
3. Click "Restore" next to full backup or database backup
4. Confirm restoration (overwrites current database)
5. Verify restoration succeeded

---

## Deployment Checklist

Use this checklist before any migration:

```
PRE-MIGRATION:
[ ] Migration SQL file created and named with date
[ ] SQL syntax verified (no errors)
[ ] Migration tested on local database
[ ] Backup of production database created
[ ] Backup verified (can be restored)
[ ] All affected features identified
[ ] Testing plan documented
[ ] Team notified of planned change
[ ] Maintenance window scheduled (if needed)

DURING-MIGRATION (ADMIN PANEL):
[ ] Logged in to admin dashboard as super_user
[ ] Navigated to /admin/migrations.php
[ ] Selected correct migration
[ ] Reviewed migration details
[ ] Clicked "Execute Migration" button
[ ] Confirmed in dialog
[ ] Waited for execution to complete
[ ] Verified success message in modal
[ ] Noted audit log entry was created

POST-MIGRATION:
[ ] Verified new tables/columns in phpMyAdmin
[ ] Tested affected application features
[ ] Checked error logs (/home/altheaux/logs/php_errors.log)
[ ] Verified no syntax errors
[ ] Confirmed audit_log entry shows deployment
[ ] Updated DEPLOYMENT_LOG.md
[ ] Notified team of completion
[ ] Documented results and any issues
[ ] Kept backup for at least 1 month
```

---

## Deployment Log

Keep a `DEPLOYMENT_LOG.md` file documenting all production migrations:

```markdown
## December 25, 2025
- **Migration**: Add Category Display Order
- **File**: migrations_add_category_order.sql
- **Status**: ✅ Completed
- **Time**: 14:30 UTC
- **Admin**: [Your Name]
- **Changes**: Added display_order column to categories table
- **Issues**: None
- **Backup**: altheaux_website_backup_2025-12-25.sql

## December 24, 2025
- **Migration**: Page Builder Schema
- **File**: migrations_page_builder.sql
- **Status**: ✅ Completed
- **Time**: 10:15 UTC
- ...
```

---

## Emergency Procedures

### If Migration Fails
1. **Stop** - Don't refresh or continue
2. **Check Error** - Read error message carefully
3. **Backup Restore** - Restore from pre-migration backup
4. **Document** - Record what went wrong
5. **Review** - Check SQL syntax and requirements
6. **Retry** - Fix issue and re-execute when ready

### If Production Database Corrupted
1. Immediately restore latest backup
2. Contact hosting support (ifastnet)
3. Document issue in deployment log
4. Prevent further changes until stable
5. Plan careful rollback/recovery

### If You Lose Migration Files Locally
1. Check git for schema version history
2. Export current schema from phpMyAdmin
3. Compare with previous backups
4. Reconstruct missing migrations from backups

---

## Security Considerations

### Why Keep Migrations Local
- **Schema hiding**: Exposes database structure to attackers
- **Attack vectors**: Reveals what tables/columns/indexes exist
- **Supply chain**: Reduces risk if GitHub account compromised
- **Staged deployment**: Manual execution provides review checkpoint

### Keep Password Secure
- Migration SQL files may reference database credentials
- Never commit files containing passwords
- Use .gitignore to protect local files
- Delete old backups that contain unencrypted dumps

### Access Control
- Only super_user admins can execute migrations
- All executions logged in audit_log table
- cPanel account access required
- FTP/SSH credentials protect database

---

## Tracking Database Changes

Since migrations aren't in git, track changes via:

1. **audit_log table** - All admin actions logged
2. **DEPLOYMENT_LOG.md** - Manual deployment record
3. **Backup files** - Historical database states
4. **git history** - Code changes related to migrations
5. **README.md** - Documentation of schema

---

## Related Documentation

- [MIGRATIONS_SECURITY.md](./MIGRATIONS_SECURITY.md) - Migration security guide
- [DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md) - Deployment process
- [SECURITY.md](./SECURITY.md) - Overall security policy
- [SECURITY_SETUP.md](./SECURITY_SETUP.md) - Initial setup

---

## Contact & Support

**For Database Issues**:
- Hosting: ifastnet (ifastnet.com support)
- cPanel: Backups, phpMyAdmin tools
- Local: Review backup files, restore to test database

**For Application Issues**:
- Check error logs: `/home/altheaux/logs/php_errors.log`
- Review audit log: Dashboard > Audit Log
- Test on staging database first

---

**Last Updated**: December 25, 2025
**Next Review**: January 25, 2026
