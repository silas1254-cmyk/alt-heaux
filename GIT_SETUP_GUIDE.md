# Git & GitHub Desktop Setup Guide

**Status:** ✅ Git repository initialized and ready!

## Current Setup

Your Alt-Heaux project is now fully set up with Git version control:

```
Repository: c:\Users\silas\Desktop\alt-heaux
Branch: main
Initial Commit: 5745f4f - "Clean, optimized production codebase"
Files Tracked: 65 production files
Status: Working tree clean ✅
```

## Using GitHub Desktop

### Step 1: Open GitHub Desktop
1. Download and install [GitHub Desktop](https://desktop.github.com/) if you haven't already
2. Launch GitHub Desktop

### Step 2: Add Your Repository
1. Click **File** → **Add Local Repository**
2. Choose the folder: `C:\Users\silas\Desktop\alt-heaux`
3. Click **Add Repository**

Your repository should now appear in GitHub Desktop!

### Step 3: Making Changes & Commits

**When you modify code:**
1. GitHub Desktop will automatically detect changes
2. You'll see modified files listed on the left
3. Write a descriptive commit message (summary + description)
4. Click **Commit to main**

**Example workflow:**
```
Summary: Add new admin feature for reports
Description: 
- Created reports.php page
- Added reports helper functions
- Updated sidebar navigation
- All tests passing
```

### Step 4: Syncing to Cloud Storage or Remote

#### Option A: Push to GitHub (Recommended)
1. Create a [GitHub](https://github.com) account if you don't have one
2. Create a new private repository on GitHub
3. In GitHub Desktop: **File** → **Push repository**
4. Follow the prompts to connect your GitHub account
5. Your repository will sync to the cloud

#### Option B: Use OneDrive/Backup Service
1. Keep the local git folder where it is
2. Set up automatic backups of `c:\Users\silas\Desktop\alt-heaux`
3. Use GitHub Desktop locally only

#### Option C: Both (Recommended for Production)
- Push to GitHub for remote backup
- Also backup entire folder to cloud storage
- Maximum redundancy and safety

## Common Tasks

### Making a Commit
```
1. Make your code changes
2. GitHub Desktop shows the changes
3. Add a clear commit message
4. Click "Commit to main"
5. Commit is saved in your local history
```

### Viewing History
- Click **History** tab in GitHub Desktop
- See all your commits with descriptions
- Click any commit to see exactly what changed

### Reverting Changes
If you made a mistake:
1. Right-click the commit you want to revert
2. Select "Revert This Commit"
3. The changes are undone (creates a new commit)

### Creating Branches (For experiments)
1. Click **Current Branch** at the top
2. Click **New Branch**
3. Name it (e.g., "feature/reports" or "bugfix/cart")
4. Work on the feature without affecting main
5. Merge back when ready

## Best Practices

### ✅ DO
- **Commit frequently** - Small, logical changes are easier to track
- **Write clear messages** - Describe WHAT you changed and WHY
- **One feature per commit** - If adding reports, don't also update settings
- **Test before committing** - Make sure everything works
- **Pull before pushing** - Get latest changes from team (if collaborating)
- **Backup to cloud** - Push to GitHub or backup folder regularly

### ❌ DON'T
- **Commit sensitive data** - Never commit passwords, API keys, or .env files
- **Huge commits** - Breaking changes into smaller commits
- **Vague messages** - "Fixed stuff" doesn't help future you
- **Ignore merge conflicts** - Resolve them properly
- **Force push to main** - Only for fixing mistakes, use carefully

## File Handling in Git

### What's Tracked (In Version Control)
✅ All `.php` files (code)
✅ `.js` files (JavaScript)
✅ `.css` files (stylesheets)
✅ `.md` files (documentation)
✅ `.sql` files (migrations)
✅ `.gitignore` (git configuration)

### What's Ignored (Not Tracked)
❌ `files/` folder (user uploads - varies per installation)
❌ `images/` folder (product images)
❌ `.env` file (your local database password)
❌ `*.log` files (logs)
❌ `*.tmp` files (temporary)
❌ `vendor/` folder (composer packages)
❌ Minified CSS/JS (generated, not needed)

### Adding New Files
When you create a new file:
1. If it's code/config → It will be tracked automatically
2. If it's generated → It might be ignored (check .gitignore)
3. To override ignoring: `git add -f filename`

## Backup Strategy Going Forward

### Daily/Weekly Workflow
1. **Work in VS Code** - Write code normally
2. **Test locally** - Verify everything works
3. **Commit in GitHub Desktop** - Save your work
4. **Push to GitHub** (if using remote) - Backup to cloud

### Monthly/Release
1. **Tag the release** - Create a version tag
2. **Document changes** - Update CHANGELOG.md
3. **Full system backup** - Backup database + files folder
4. **Deploy to production** - Push changes to live server

## Recovery Options

### If you need to go back
1. **Revert a single commit** - Right-click in history, select Revert
2. **Go back to a previous version** - Check out the commit hash
3. **Restore deleted file** - Find it in git history, restore
4. **Undo local changes** - Discard in GitHub Desktop (can't recover!)

## Connecting to GitHub Desktop

### Authenticating with GitHub
1. In GitHub Desktop: **File** → **Options**
2. Go to **Accounts** tab
3. Click **Sign in** to GitHub
4. Authorize GitHub Desktop in your browser
5. Done! Now you can push/pull from GitHub

### First Push to GitHub
```
1. Create repository on GitHub.com
2. GitHub Desktop → Current Branch → Publish Branch
3. Select your GitHub account
4. Push! Your code is now on GitHub
```

## Tips for Your Use Case

Since you're using GitHub Desktop:

1. **Regular commits** - Save your work every 30-60 minutes
2. **Clear messages** - Include ticket/feature numbers if applicable
3. **Merge releases** - When code is stable, tag it as a release
4. **Keep backups** - Use 3-2-1 rule: 3 copies, 2 different media, 1 offsite
5. **Review before push** - Always check what you're committing

---

## Quick Reference

| Task | How-To |
|------|--------|
| Save changes | Make edits → Write message → Commit to main |
| View history | Click History tab |
| Go back | Right-click commit → Revert |
| Create branch | Current Branch → New Branch |
| Upload to cloud | File → Push repository |
| Update from GitHub | File → Pull repository |
| Compare versions | Click any commit to see changes |

---

**Last Updated:** December 23, 2025  
**Repository:** Alt-Heaux E-Commerce Platform  
**Ready for:** Production version control with GitHub Desktop

Start committing your changes today! 🚀
