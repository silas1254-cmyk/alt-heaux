# Production Deployment Checklist

## Pre-Deployment (Local Machine)
- [ ] Commit all changes to Git
- [ ] Export WAMP database to SQL file
- [ ] Test site locally one final time
- [ ] Verify admin panel works
- [ ] Check all image/file uploads work locally
- [ ] Review database for test data to remove

## Server Setup (SSH to ifastnet)
- [ ] Create bare Git repository at `/home/altheaux/alt-heaux.git`
- [ ] Set up post-receive hook for auto-deployment
- [ ] Create logs directory: `/home/altheaux/logs/`
- [ ] Create backups directory: `/home/altheaux/backups/`
- [ ] Set permissions on uploads/files/images directories (755)

## Database Migration
- [ ] Create database `altheaux_website` on ifastnet
- [ ] Import `altheaux_website_backup.sql` via PHP MyAdmin
- [ ] Verify all tables imported correctly
- [ ] Test database connection

## Code Deployment
- [ ] Add production remote: `git remote add production ...`
- [ ] Copy `config.production.php` to `config.php` on server
- [ ] Verify `config.php` has correct production credentials
- [ ] Push code to production: `git push production main`
- [ ] Verify files deployed to `/home/altheaux/public_html/`

## Post-Deployment Testing
- [ ] Visit https://alt-heaux.com in browser
- [ ] Check homepage loads correctly
- [ ] Visit https://alt-heaux.com/admin/
- [ ] Log in with admin credentials
- [ ] Test creating a new product
- [ ] Test uploading product images
- [ ] Test uploading product files
- [ ] Test adding colors/sizes to products
- [ ] Check SSL certificate is valid
- [ ] Verify images load correctly

## Security & Performance
- [ ] Verify error logging is working
- [ ] Check PHP error log for warnings
- [ ] Disable debug mode (display_errors = 0)
- [ ] Test HTTPS redirects work
- [ ] Verify session security settings
- [ ] Set up database backup schedule

## Monitoring & Maintenance
- [ ] Set up regular database backups
- [ ] Monitor error logs
- [ ] Test file uploads periodically
- [ ] Keep admin password secure
- [ ] Document any configuration changes
- [ ] Create backup of entire site

## DNS & Domain (Already Active)
- [ ] SSL certificate is active ✅
- [ ] Domain points to hosting ✅
- [ ] HTTPS working ✅

---

## Deployment Commands Reference

### Export Local Database:
```
mysqldump -h localhost:3308 -u root alt_heaux > altheaux_website_backup.sql
```

### SSH to Server:
```
ssh altheaux@alt-heaux.com
```

### Create Bare Repo:
```
mkdir alt-heaux.git && cd alt-heaux.git && git init --bare
```

### Add Remote:
```
git remote add production ssh://altheaux@alt-heaux.com:/home/altheaux/alt-heaux.git
```

### Deploy Code:
```
git push production main
```

### Import Database:
```
mysql -h localhost:3306 -u altheaux_yevty -p'swegman123-' altheaux_website < backup.sql
```

---

**Status**: Ready for Production Deployment 🚀
**Date**: December 24, 2025
**Domain**: alt-heaux.com
