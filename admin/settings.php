<?php
/**
 * SITE SETTINGS & CONFIGURATION
 * Manage branding, email, and site-wide settings
 */

session_status() === PHP_SESSION_ACTIVE || session_start();
require_once('../includes/config.php');
require_once('../includes/admin_auth.php');
require_once('../includes/backup_helper.php');

// Check admin access
if (!isAdminLoggedIn()) {
    // Add error output before redirect
    echo "<!-- Not logged in, redirecting to login -->";
    header('Location: ' . SITE_URL . 'admin/login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section'])) {
    $section = $_POST['section'];
    
    // Basic site settings
    if ($section === 'site') {
        $site_name = trim($_POST['site_name'] ?? '');
        $site_description = trim($_POST['site_description'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        
        if (empty($site_name)) {
            $error = 'Site name is required';
        } else {
            updateDbSetting($conn, 'site_name', $site_name);
            updateDbSetting($conn, 'site_description', $site_description);
            updateDbSetting($conn, 'contact_email', $contact_email);
            updateDbSetting($conn, 'phone_number', $phone_number);
            
            $message = 'Site settings updated successfully';
            if ($admin_id) {
                logAdminAction($conn, $admin_id, 'settings_updated', 'Updated site settings');
            }
        }
    }
    
    // Store settings
    elseif ($section === 'store') {
        $currency = trim($_POST['currency'] ?? 'USD');
        $tax_rate = floatval($_POST['tax_rate'] ?? 0);
        $shipping_cost = floatval($_POST['shipping_cost'] ?? 0);
        
        updateDbSetting($conn, 'currency', $currency);
        updateDbSetting($conn, 'tax_rate', $tax_rate);
        updateDbSetting($conn, 'shipping_cost', $shipping_cost);
        
        $message = 'Store settings updated successfully';
        if ($admin_id) {
            logAdminAction($conn, $admin_id, 'settings_updated', 'Updated store settings');
        }
    }
    
    // Email settings
    elseif ($section === 'email') {
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = trim($_POST['smtp_port'] ?? '587');
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_password = trim($_POST['smtp_password'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');
        
        updateDbSetting($conn, 'smtp_host', $smtp_host);
        updateDbSetting($conn, 'smtp_port', $smtp_port);
        updateDbSetting($conn, 'smtp_user', $smtp_user);
        if (!empty($smtp_password)) {
            updateDbSetting($conn, 'smtp_password', base64_encode($smtp_password));
        }
        updateDbSetting($conn, 'smtp_from_name', $smtp_from_name);
        
        $message = 'Email settings updated successfully';
        if ($admin_id) {
            logAdminAction($conn, $admin_id, 'settings_updated', 'Updated email settings');
        }
    }
}

// Get all settings
$settings = getAllDbSettings($conn) ?? [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
<div class="wrapper">
    <?php include('_sidebar.php'); ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="bi bi-gear"></i> Site Settings</h1>
            <small>Configure site-wide settings, branding, and options</small>
        </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- SETTINGS TABS -->
                <div class="col-md-3 mb-3">
                    <div class="list-group sticky-top" style="background-color: var(--dark-2); border: 1px solid var(--border); border-radius: 8px;">
                        <button type="button" class="list-group-item list-group-item-action active tab-btn" onclick="showTab('site', event)" style="background-color: var(--dark-3); border: none; color: var(--text-primary); padding: 1rem;">
                            <i class="bi bi-globe"></i> Site Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action tab-btn" onclick="showTab('store', event)" style="background-color: var(--dark-2); border: none; color: var(--text-secondary); padding: 1rem; border-top: 1px solid var(--border); transition: all 0.3s ease;">
                            <i class="bi bi-shop"></i> Store Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action tab-btn" onclick="showTab('email', event)" style="background-color: var(--dark-2); border: none; color: var(--text-secondary); padding: 1rem; border-top: 1px solid var(--border); transition: all 0.3s ease;">
                            <i class="bi bi-envelope"></i> Email Settings
                        </button>
                    </div>
                </div>

                <!-- SETTINGS CONTENT -->
                <div class="col-md-9">
                    <!-- SITE SETTINGS -->
                    <div id="site" class="tab-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-globe"></i> Site Settings</h5>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="section" value="site">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="site_name" class="form-label">Site Name</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Alt-Heaux'); ?>" required>
                                        <small class="form-text text-muted">Appears in browser tabs, emails, and throughout the site</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="site_description" class="form-label">Site Description</label>
                                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">Short description for SEO and meta tags</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="contact_email" class="form-label">Contact Email</label>
                                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                                        <small class="form-text text-muted">Used for customer inquiries and notifications</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($settings['phone_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-download"></i> Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- STORE SETTINGS -->
                    <div id="store" class="tab-content" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-shop"></i> Store Settings</h5>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="section" value="store">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="currency" class="form-label">Currency</label>
                                                <select class="form-select" id="currency" name="currency">
                                                    <option value="USD" <?php echo ($settings['currency'] ?? 'USD') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                                    <option value="EUR" <?php echo ($settings['currency'] ?? 'USD') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                                    <option value="GBP" <?php echo ($settings['currency'] ?? 'USD') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                                    <option value="CAD" <?php echo ($settings['currency'] ?? 'USD') === 'CAD' ? 'selected' : ''; ?>>CAD (C$)</option>
                                                    <option value="AUD" <?php echo ($settings['currency'] ?? 'USD') === 'AUD' ? 'selected' : ''; ?>>AUD (A$)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" value="<?php echo floatval($settings['tax_rate'] ?? 0); ?>" step="0.01" min="0" max="100">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="shipping_cost" class="form-label">Flat Shipping Cost</label>
                                        <input type="number" class="form-control" id="shipping_cost" name="shipping_cost" value="<?php echo floatval($settings['shipping_cost'] ?? 0); ?>" step="0.01" min="0">
                                        <small class="form-text text-muted">Default shipping cost for all orders</small>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> <strong>Note:</strong> Advanced shipping methods (zones, weight-based, etc) will be added in a future update.
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- EMAIL SETTINGS -->
                    <div id="email" class="tab-content" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-envelope"></i> Email Configuration</h5>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="section" value="email">
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> <strong>SMTP Configuration</strong> is needed to send emails (order confirmations, password resets, etc).
                                    </div>

                                    <div class="mb-3">
                                        <label for="smtp_host" class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_port" class="form-label">SMTP Port</label>
                                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_user" class="form-label">Username/Email</label>
                                                <input type="email" class="form-control" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="smtp_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="Leave blank to keep current">
                                        <small class="form-text text-muted">Your SMTP password (not your site admin password)</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="smtp_from_name" class="form-label">From Name</label>
                                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" placeholder="<?php echo htmlspecialchars($settings['site_name'] ?? 'Alt-Heaux'); ?>" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? ''); ?>">
                                        <small class="form-text text-muted">Name shown in email "From" field</small>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>js/admin.js"></script>
<script>
function showTab(tabName, event) {
    event.preventDefault();
    
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.style.display = 'none');
    
    // Show selected tab
    document.getElementById(tabName).style.display = 'block';
    
    // Update active button styling
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
        btn.style.backgroundColor = '#2a2a2a';
        btn.style.color = '#b0b0b0';
    });
    
    const activeBtn = event.target.closest('.tab-btn');
    activeBtn.classList.add('active');
    activeBtn.style.backgroundColor = '#3a3a3a';
    activeBtn.style.color = '#e8e8e8';
}

// Sync color input and text display
// (Theme color functionality removed)

</script>

</body>
</html>

