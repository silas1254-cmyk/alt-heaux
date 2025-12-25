# Security Setup & Configuration Guide

## ✅ Completed Security Measures

### 1. Credentials Protection
- ✅ `.gitignore` configured to exclude `config.php` and `config.production.php`
- ✅ Database credentials removed from git history using `git filter-branch`
- ✅ Pre-commit hook installed to catch secret commits
- ✅ Webhook secret configured for safe deployments

### 2. Secure Deployment
- ✅ GitHub webhook set up at `https://alt-heaux.site/deploy.php`
- ✅ Webhook signature verification enabled
- ✅ Only pushed code is deployed (config files stay on server)
- ✅ Automatic git pull on push events

### 3. Dependency Security
- ✅ .gitignore includes `vendor/` and `composer.lock` handling

---

## 🔧 Manual Setup Required (GitHub Web Interface)

### 1. Enable Branch Protection on `main`
1. Go to https://github.com/silas1254-cmyk/alt-heaux
2. Settings → Branches
3. Click "Add rule"
4. Branch name pattern: `main`
5. Enable:
   - ✅ "Require a pull request before merging"
   - ✅ "Require approvals" (set to 1)
   - ✅ "Dismiss stale pull request approvals"
   - ✅ "Require status checks to pass before merging" (optional)
6. Click "Create"

**Effect:** Prevents accidental pushes to main; all changes must go through PR review

### 2. Enable Dependabot (Vulnerability Scanning)
1. Go to https://github.com/silas1254-cmyk/alt-heaux
2. Settings → Code security and analysis
3. Enable:
   - ✅ "Dependabot alerts"
   - ✅ "Dependabot security updates"
   - ✅ "Dependency graph" (should be auto-enabled)
4. GitHub will now automatically scan for vulnerable dependencies

**Effect:** You'll get alerts for outdated/vulnerable packages

### 3. Enable Two-Factor Authentication (2FA) on Your Account
1. Go to https://github.com/settings/security
2. Click "Enable two-factor authentication"
3. Choose between:
   - SMS (less secure)
   - Authenticator app like Google Authenticator (recommended)
   - Security keys (most secure)
4. Follow the setup wizard
5. Save backup codes in a safe place!

**Effect:** Your account is protected even if your password is compromised

### 4. Set Up Signed Commits (Optional but Recommended)
1. Generate a GPG key:
   ```powershell
   gpg --full-generate-key
   ```
2. Follow the prompts (4096-bit RSA, 4 year expiration recommended)
3. Get your key ID:
   ```powershell
   gpg --list-secret-keys --keyid-format LONG
   ```
4. Configure git:
   ```powershell
   git config --global user.signingkey YOUR_KEY_ID
   git config --global commit.gpgsign true
   ```
5. Add public key to GitHub:
   - Go to https://github.com/settings/keys
   - Click "New GPG key"
   - Export your key: `gpg --armor --export YOUR_KEY_ID`
   - Paste and save

**Effect:** Your commits are cryptographically signed, proving they came from you

---

## 📋 Pre-Commit Hook

The `.git/hooks/pre-commit` script is installed and will:
- Check staged files for common secret patterns
- Block commits if secrets are detected
- Warn about password, API key, token patterns

**To test:**
```powershell
git commit -m "test" --no-verify  # Bypasses hook (not recommended)
```

**To disable temporarily:**
```powershell
git commit -m "test" --no-verify
```

---

## 🔐 Additional Recommendations

### Regular Updates
- Check for updates: `composer outdated` (if using Composer)
- Update regularly to patch vulnerabilities

### Access Control
- Only add trusted collaborators
- Use minimal permissions (no unnecessary admin access)
- Review who has access regularly

### Monitoring
- Watch your GitHub security tab for alerts
- Enable email notifications for security events

---

## 📚 Resources

- [GitHub Security Best Practices](https://docs.github.com/en/code-security)
- [OWASP Security Guidelines](https://owasp.org/)
- [Dependabot Documentation](https://docs.github.com/en/code-security/dependabot)

---

## Emergency: Account Compromised?

If you suspect your GitHub account is compromised:
1. Change your password immediately
2. Review login activity: https://github.com/settings/security-log
3. Revoke suspicious personal access tokens
4. Enable 2FA if not already enabled
5. Check authorized OAuth apps: https://github.com/settings/applications

Contact GitHub support if needed.
