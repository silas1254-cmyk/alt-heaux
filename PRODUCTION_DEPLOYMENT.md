# ALT HEAUX - Production Deployment Guide

## Hosting Details
- **Host**: ifastnet
- **Domain**: alt-heaux.com
- **Home Directory**: /home/altheaux
- **Public Directory**: /home/altheaux/public_html/
- **Database**: altheaux_website
- **SSL**: Already Active (Let's Encrypt)

## Prerequisites
- ✅ SSH Access via ifastnet
- ✅ Git installed on server
- ✅ PHP MyAdmin access
- ✅ cPanel access

---

## STEP 1: Export WAMP Database

### On Your Local Machine (Windows):

1. **Open phpMyAdmin** (http://localhost/phpmyadmin)
2. **Select Database**: Click on `alt_heaux`
3. **Export**:
   - Click "Export" tab
   - Choose "Quick" export
   - Format: SQL
   - Click "Go"
   - Save file as: `altheaux_website_backup.sql`

### Alternative - Command Line (Windows):
```powershell
# In PowerShell
C:\wamp64\bin\mysql\mysql8.0.32\bin\mysqldump -h localhost:3308 -u root alt_heaux > "C:\Users\silas\Desktop\altheaux_website_backup.sql"
```

---

## STEP 2: Set Up Git Repository

### On Your Local Machine:

```powershell
# Navigate to project directory
cd c:\Users\silas\Desktop\alt-heaux

# Initialize git (if not already done)
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial production deployment"
```

### On the Hosting Server (via SSH):

```bash
# Connect via SSH
ssh altheaux@alt-heaux.com

# Navigate to home directory
cd /home/altheaux

# Create a bare git repository (for deployment)
mkdir alt-heaux.git
cd alt-heaux.git
git init --bare

# Create post-receive hook for automatic deployment
cat > hooks/post-receive << 'EOF'
#!/bin/bash
TARGET="/home/altheaux/public_html"
GIT_DIR="/home/altheaux/alt-heaux.git"
WORK_TREE=$TARGET

cd $GIT_DIR
git --work-tree=$WORK_TREE --git-dir=$GIT_DIR checkout -f main

# Set correct permissions
chmod -R 755 $WORK_TREE
chmod -R 755 $WORK_TREE/uploads
chmod -R 755 $WORK_TREE/files
chmod -R 755 $WORK_TREE/images

# Restart any necessary services (if needed)
echo "Deployment complete!"
EOF

# Make hook executable
chmod +x hooks/post-receive

# Exit server
exit
```

### Add Remote to Local Repository:

```powershell
# On your local machine
git remote add production ssh://altheaux@alt-heaux.com:/home/altheaux/alt-heaux.git

# Verify
git remote -v
```

---

## STEP 3: Create Production Config

### On the Hosting Server (via SSH):

```bash
# Connect via SSH
ssh altheaux@alt-heaux.com

# Create config.php from production config
cd /home/altheaux/public_html
cp includes/config.production.php includes/config.php

# Verify config
cat includes/config.php

# Exit
exit
```

### Alternative - Upload via cPanel File Manager:
1. Go to cPanel → File Manager
2. Navigate to `/home/altheaux/public_html/includes/`
3. Upload `config.production.php`
4. Rename it to `config.php`

---

## STEP 4: Import Database to Production

### Via PHP MyAdmin on ifastnet:

1. **Access PHP MyAdmin**: Log in via ifastnet cPanel
2. **Create Database** (if not exists):
   - Database name: `altheaux_website`
   - Click "Create"
3. **Import Database**:
   - Click on the `altheaux_website` database
   - Click "Import" tab
   - Choose file: `altheaux_website_backup.sql`
   - Click "Go"
   - Wait for import to complete

### Alternative - Command Line via SSH:

```bash
# Connect via SSH
ssh altheaux@alt-heaux.com

# Import database
mysql -h localhost:3306 -u altheaux_yevty -p'swegman123-' altheaux_website < ~/altheaux_website_backup.sql

# Verify tables
mysql -h localhost:3306 -u altheaux_yevty -p'swegman123-' altheaux_website -e "SHOW TABLES;"

# Exit
exit
```

---

## STEP 5: Deploy Code via Git

### Push Code to Production:

```powershell
# On your local machine
# Make sure all changes are committed
git add .
git commit -m "Production deployment setup"

# Push to production
git push production main

# If you're on master branch instead
git push production master
```

### Verify Deployment:

```bash
# SSH into server
ssh altheaux@alt-heaux.com

# Check if files are in public_html
ls -la /home/altheaux/public_html/

# Check if config.php exists
cat /home/altheaux/public_html/includes/config.php

# Exit
exit
```

---

## STEP 6: Final Setup & Testing

### Create Required Directories (via SSH):

```bash
ssh altheaux@alt-heaux.com

# Create logs directory for error logging
mkdir -p /home/altheaux/logs
chmod 755 /home/altheaux/logs

# Create backup directory
mkdir -p /home/altheaux/backups
chmod 755 /home/altheaux/backups

# Verify permissions on upload directories
chmod -R 755 /home/altheaux/public_html/uploads
chmod -R 755 /home/altheaux/public_html/files
chmod -R 755 /home/altheaux/public_html/images

exit
```

### Test the Website:

1. **Frontend**: Visit https://alt-heaux.com
2. **Admin Panel**: Visit https://alt-heaux.com/admin/
3. **Test Login**: Use your admin credentials
4. **Test Uploads**: Try uploading a product image
5. **Check Logs**: View error logs in /home/altheaux/logs/

---

## STEP 7: Set Up Continuous Deployment

### For Future Updates:

Whenever you push code to production:

```powershell
# Local machine - make changes
git add .
git commit -m "Update feature"
git push production main
```

The server will automatically deploy the changes via the Git hook.

---

## Troubleshooting

### Database Connection Error:
- Verify DB credentials in config.php
- Check that database was imported correctly
- Ensure MySQL port is correct (3306 for production)

### File Upload Not Working:
- Check upload directory permissions: `chmod 755 /home/altheaux/public_html/uploads/`
- Verify disk space: `df -h`
- Check error logs: `/home/altheaux/logs/php_errors.log`

### Git Push Fails:
- Verify SSH key is added to ifastnet
- Check that bare repo exists: `/home/altheaux/alt-heaux.git`
- Check hook permissions: `chmod +x /home/altheaux/alt-heaux.git/hooks/post-receive`

### Admin Panel Shows Blank:
- Check PHP errors: Check `/home/altheaux/logs/php_errors.log`
- Verify config.php has correct database credentials
- Check that index.php exists in admin folder

---

## Important Notes

⚠️ **Security**:
- Never commit passwords to Git
- Use SSH keys for authentication
- Keep config.php secure
- Regularly backup database

⚠️ **Backups**:
- Download database backups regularly
- Keep copies of uploaded files
- Test restoration process

⚠️ **Updates**:
- Test changes locally first
- Always have a backup before deploying
- Use descriptive commit messages

---

## Quick Reference Commands

```bash
# SSH into server
ssh altheaux@alt-heaux.com

# View latest deployments
cd /home/altheaux/alt-heaux.git && git log --oneline -n 10

# Check system resources
df -h          # Disk space
ps aux | grep php  # Running processes

# View error log
tail -f /home/altheaux/logs/php_errors.log

# Database backup
mysqldump -h localhost:3306 -u altheaux_yevty -p'swegman123-' altheaux_website > ~/backup_$(date +%Y%m%d).sql

# Exit SSH
exit
```

---

## Next Steps

1. Export WAMP database locally
2. SSH into ifastnet server
3. Set up Git repository and hooks
4. Create config.php on server
5. Import database
6. Deploy code via Git push
7. Test website and admin panel
8. Set up monitoring/backups

**Good luck! Your site will be live soon! 🚀**
