<?php
/**
 * Admin Sidebar Navigation
 */
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h5><?php echo SITE_NAME; ?></h5>
        <small>Admin Panel</small>
    </div>
    
    <div class="nav-section">
        <h6>Main</h6>
        <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </div>
    
    <div class="nav-section">
        <h6>Content Management</h6>
        <a href="<?php echo SITE_URL; ?>admin/pages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'pages.php' ? 'active' : ''; ?>">
            <i class="bi bi-file-text"></i> Pages
        </a>
        <a href="<?php echo SITE_URL; ?>admin/sections.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sections.php' ? 'active' : ''; ?>">
            <i class="bi bi-layout-text-sidebar"></i> Sections
        </a>
        <a href="<?php echo SITE_URL; ?>admin/sliders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sliders.php' ? 'active' : ''; ?>">
            <i class="bi bi-images"></i> Hero Sliders
        </a>
        <a href="<?php echo SITE_URL; ?>admin/menus.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'menus.php' ? 'active' : ''; ?>">
            <i class="bi bi-list"></i> Navigation Menu
        </a>
    </div>
    
    <div class="nav-section">
        <h6>Store</h6>
        <a href="<?php echo SITE_URL; ?>admin/products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>">
            <i class="bi bi-box"></i> Products
        </a>
        <a href="<?php echo SITE_URL; ?>admin/categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="<?php echo SITE_URL; ?>admin/sales_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sales_dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-graph-up"></i> Sales Dashboard
        </a>
    </div>
    
    <div class="nav-section">
        <h6>Settings</h6>
        <a href="<?php echo SITE_URL; ?>admin/settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i> Site Settings
        </a>
        <?php if (isAdmin()): ?>
            <a href="<?php echo SITE_URL; ?>admin/manage_admins.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_admins.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Manage Admins
            </a>
            <a href="<?php echo SITE_URL; ?>admin/audit_log.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'audit_log.php' ? 'active' : ''; ?>">
                <i class="bi bi-clock-history"></i> Audit Log
            </a>
        <?php endif; ?>
    </div>
    
    <div class="logout-section">
        <a href="<?php echo SITE_URL; ?>" class="nav-link">
            <i class="bi bi-globe"></i> View Site
        </a>
        <a href="<?php echo SITE_URL; ?>admin/logout.php" class="nav-link logout-btn">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
