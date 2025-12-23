<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';
require_once '../includes/content_helper.php';

// Determine which page section to load
$section = $_GET['section'] ?? 'home';
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === '1';

// Map sections to settings keys
$page_map = [
    'home' => ['key' => 'home_content', 'title' => 'Home', 'class_name' => 'home-page'],
    'shop' => ['key' => 'shop_content', 'title' => 'Shop', 'class_name' => 'shop-page'],
    'contact' => ['key' => 'contact_content', 'title' => 'Contact', 'class_name' => 'contact-page'],
    'cart' => ['key' => 'cart_content', 'title' => 'Cart', 'class_name' => 'cart-page']
];

// Validate section
if (!isset($page_map[$section])) {
    $section = 'home';
}

$page_config = $page_map[$section];
$setting_key = $page_config['key'];
$setting_published_key = $setting_key . '_published';

// Handle form submissions (admin only)
if ($edit_mode && $_SERVER['REQUEST_METHOD'] === 'POST' && isAdminLoggedIn()) {
    $action = $_POST['action'] ?? 'save';
    
    if ($action === 'save') {
        $content = $_POST['content'] ?? '';
        updateDbSetting($conn, $setting_key, $content);
        $success_msg = 'Content saved successfully!';
    } elseif ($action === 'publish') {
        updateDbSetting($conn, $setting_published_key, '1');
        $success_msg = 'Content published!';
    } elseif ($action === 'unpublish') {
        updateDbSetting($conn, $setting_published_key, '0');
        $success_msg = 'Content unpublished!';
    }
}

// Redirect to edit mode if not admin
if ($edit_mode && !isAdminLoggedIn()) {
    header("Location: pages.php?section=$section");
    exit;
}

// Get current content
$page_content = getSetting($setting_key, '');
$page_published = getSetting($setting_published_key, '1');

// Determine page title for different sections
$page_title = $page_config['title'];
if ($section === 'home') {
    $page_title = 'Home';
} elseif ($section === 'shop') {
    $page_title = 'Shop';
} elseif ($section === 'contact') {
    $page_title = 'Contact';
} elseif ($section === 'cart') {
    $page_title = 'Cart';
}

// If in edit mode, show the editor
if ($edit_mode && isAdminLoggedIn()):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .editor-container { max-width: 1000px; margin: 40px auto; padding: 20px; background: #1a1a1a; border-radius: 8px; color: #e8e8e8; }
        .editor-header { margin-bottom: 30px; }
        .editor-header h1 { color: #fff; margin-bottom: 10px; }
        .editor-header .status-badge { margin-left: 15px; }
        textarea { background: #2a2a2a; color: #e8e8e8; border: 1px solid #444; padding: 15px; font-family: monospace; min-height: 400px; }
        textarea:focus { background: #2a2a2a; color: #e8e8e8; border-color: #666; box-shadow: 0 0 5px rgba(255,255,255,0.1); }
        .btn { margin-right: 10px; }
        .btn-save { background: #28a745; border: none; }
        .btn-save:hover { background: #218838; }
        .btn-publish { background: #007bff; border: none; }
        .btn-publish:hover { background: #0056b3; }
        .btn-unpublish { background: #ffc107; border: none; color: #000; }
        .btn-unpublish:hover { background: #ffb600; }
        .btn-back { background: #6c757d; border: none; }
        .btn-back:hover { background: #5a6268; }
        .alert { margin-bottom: 20px; }
        .help-text { color: #b0b0b0; font-size: 14px; margin-top: 20px; padding: 15px; background: #2a2a2a; border-radius: 4px; }
        .help-text h5 { color: #e8e8e8; margin-top: 15px; margin-bottom: 10px; }
        .help-text code { background: #1a1a1a; padding: 2px 6px; border-radius: 3px; color: #a8d5ff; }
    </style>
</head>
<body style="background: #111;">
    <div class="editor-container">
        <div class="editor-header">
            <h1>
                <i class="fas fa-edit"></i> Edit <?php echo htmlspecialchars($page_title); ?> Page
                <?php if ($page_published): ?>
                    <span class="badge bg-success ms-2"><i class="fas fa-check-circle"></i> Published</span>
                <?php else: ?>
                    <span class="badge bg-warning ms-2"><i class="fas fa-times-circle"></i> Unpublished</span>
                <?php endif; ?>
            </h1>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <strong><i class="fas fa-check"></i></strong> <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
            
            <textarea name="content" class="form-control" placeholder="Enter HTML/CSS content here..."><?php echo htmlspecialchars($page_content); ?></textarea>

            <div class="mt-4 mb-4">
                <button type="submit" name="action" value="save" class="btn btn-save btn-lg">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <?php if ($page_published): ?>
                    <button type="submit" name="action" value="unpublish" class="btn btn-unpublish btn-lg" onclick="return confirm('Unpublish this page? It will no longer be visible to visitors.');">
                        <i class="fas fa-times-circle"></i> Unpublish
                    </button>
                <?php else: ?>
                    <button type="submit" name="action" value="publish" class="btn btn-publish btn-lg">
                        <i class="fas fa-check-circle"></i> Publish
                    </button>
                <?php endif; ?>
                <a href="pages.php?section=<?php echo htmlspecialchars($section); ?>" class="btn btn-back btn-lg">
                    <i class="fas fa-arrow-left"></i> View Page
                </a>
            </div>
        </form>

        <div class="help-text">
            <h5><i class="fas fa-info-circle"></i> Available Bootstrap Classes</h5>
            <p>Use these Bootstrap 5 classes to style your content:</p>
            <code>container</code> | <code>row</code> | <code>col</code> | <code>btn btn-primary</code> | <code>alert alert-info</code> | 
            <code>card</code> | <code>text-center</code> | <code>mt-3</code> | <code>mb-4</code> | <code>p-4</code>
            <h5><i class="fas fa-code"></i> Publishing Tips</h5>
            <ul style="margin-bottom: 0;">
                <li><strong>Save Changes:</strong> Saves your content without making it live yet</li>
                <li><strong>Publish:</strong> Makes the content visible to visitors</li>
                <li><strong>Unpublish:</strong> Hides the content from visitors (useful for maintenance)</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Otherwise, show the public page
else:
    // Include the page header
    include '../includes/header.php';
    
    // Prepare page-specific content
    $page_class = $page_config['class_name'];
?>

<div class="<?php echo $page_class; ?>">
    <?php 
    // Display custom content if published
    if (!empty($page_content) && $page_published): 
    ?>
        <section class="section-padding bg-light">
            <div class="container">
                <?php echo $page_content; ?>
            </div>
        </section>
    <?php 
    endif; 
    
    // Include section-specific content below custom content
    if ($section === 'home'):
        // Home page default content
    ?>
        <section class="why-choose-us section-padding">
            <div class="container">
                <h2 class="text-center mb-5">Why Choose Us</h2>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-shipping-fast fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">Fast Shipping</h5>
                                <p class="card-text">Quick and reliable delivery to your doorstep</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-lock fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">Secure Payment</h5>
                                <p class="card-text">Your payment information is safe and secure</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-headset fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">24/7 Support</h5>
                                <p class="card-text">Always here to help with any questions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php
    elseif ($section === 'shop'):
        // Shop page - show products
        include 'shop.php';
    elseif ($section === 'contact'):
        // Contact page - show form
        include 'contact.php';
    elseif ($section === 'cart'):
        // Cart page - show cart
        include 'cart.php';
    endif;
    ?>
</div>

<?php
    include '../includes/footer.php';
endif;
?>
