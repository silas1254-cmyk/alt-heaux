<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';
require_once '../includes/content_helper.php';

requireAdmin();

// Define pages
$pages = [
    'home' => ['title' => 'Home', 'key' => 'home_content'],
    'shop' => ['title' => 'Shop', 'key' => 'shop_content'],
    'contact' => ['title' => 'Contact', 'key' => 'contact_content'],
    'cart' => ['title' => 'Cart', 'key' => 'cart_content']
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $page = $_POST['page'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (!isset($pages[$page])) {
        echo json_encode(['success' => false, 'message' => 'Invalid page']);
        exit;
    }
    
    $key = $pages[$page]['key'];
    $published_key = $key . '_published';
    
    if ($action === 'save') {
        $content = $_POST['content'] ?? '';
        updateDbSetting($conn, $key, $content);
        echo json_encode(['success' => true, 'message' => 'Content saved']);
        exit;
    } elseif ($action === 'publish') {
        updateDbSetting($conn, $published_key, '1');
        $status = getSetting($published_key);
        echo json_encode(['success' => true, 'message' => 'Published', 'published' => $status]);
        exit;
    } elseif ($action === 'unpublish') {
        updateDbSetting($conn, $published_key, '0');
        $status = getSetting($published_key);
        echo json_encode(['success' => true, 'message' => 'Unpublished', 'published' => $status]);
        exit;
    } elseif ($action === 'load') {
        $content = getSetting($key, '');
        $published = getSetting($published_key, '1');
        echo json_encode(['success' => true, 'content' => $content, 'published' => $published]);
        exit;
    }
}

$first_page = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Editor - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
    <style>
        .tab-buttons { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab-btn { 
            padding: 10px 20px; 
            border: none; 
            background: #2a2a2a; 
            color: #b0b0b0; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: all 0.3s;
            font-weight: 500;
        }
        .tab-btn:hover { background: #3a3a3a; color: #e8e8e8; }
        .tab-btn.active { background: #007bff; color: #fff; }
        .editor-section { display: none; }
        .editor-section.active { display: block; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h3 { margin: 0; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .status-published { background: #28a745; color: #fff; }
        .status-unpublished { background: #ffc107; color: #000; }
        textarea { background: #2a2a2a; color: #e8e8e8; border: 1px solid #444; padding: 15px; font-family: monospace; min-height: 500px; }
        textarea:focus { background: #2a2a2a; color: #e8e8e8; border-color: #666; }
        .button-group { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .btn-save { background: #28a745; border: none; color: #fff; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-save:hover { background: #218838; }
        .btn-publish { background: #007bff; border: none; color: #fff; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-publish:hover { background: #0056b3; }
        .btn-unpublish { background: #ffc107; border: none; color: #000; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-unpublish:hover { background: #ffb600; }
        .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; display: none; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-show { display: block; }
        .help-box { background: #2a2a2a; border-left: 4px solid #007bff; padding: 15px; margin-top: 20px; border-radius: 4px; color: #b0b0b0; font-size: 13px; }
        .help-box h6 { color: #e8e8e8; margin-bottom: 10px; }
        .help-box code { background: #1a1a1a; padding: 2px 6px; border-radius: 3px; color: #a8d5ff; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include('_sidebar.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="page-header">
                    <h1><i class="fas fa-edit"></i> Edit Pages</h1>
                </div>

                <!-- Alert Message -->
                <div class="alert" id="alertBox"></div>

                <!-- Tab Buttons -->
                <div class="tab-buttons">
                    <?php foreach ($pages as $slug => $page_info): ?>
                        <button class="tab-btn <?php echo $slug === 'home' ? 'active' : ''; ?>" 
                                onclick="switchTab('<?php echo $slug; ?>', event)">
                            <i class="fas fa-file"></i> <?php echo $page_info['title']; ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Editor Sections -->
                <?php foreach ($pages as $slug => $page_info): 
                    $content = getSetting($page_info['key'], '');
                    $published = getSetting($page_info['key'] . '_published', '1');
                ?>
                    <div class="editor-section <?php echo $slug === 'home' ? 'active' : ''; ?>" data-page="<?php echo $slug; ?>">
                        <div class="section-header">
                            <h3><i class="fas fa-file"></i> <?php echo $page_info['title']; ?> Page Content</h3>
                            <span class="status-badge status-<?php echo $published ? 'published' : 'unpublished'; ?>" id="status-<?php echo $slug; ?>">
                                <i class="fas fa-<?php echo $published ? 'check-circle' : 'times-circle'; ?>"></i>
                                <span><?php echo $published ? 'Published' : 'Unpublished'; ?></span>
                            </span>
                        </div>
                        
                        <textarea id="editor-<?php echo $slug; ?>"><?php echo htmlspecialchars($content); ?></textarea>

                        <div class="button-group">
                            <button type="button" class="btn-save" onclick="saveContent('<?php echo $slug; ?>')">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" 
                                    class="btn-<?php echo $published ? 'unpublish' : 'publish'; ?>" 
                                    id="publish-btn-<?php echo $slug; ?>"
                                    onclick="togglePublish('<?php echo $slug; ?>')">
                                <i class="fas fa-<?php echo $published ? 'times-circle' : 'check-circle'; ?>"></i>
                                <span><?php echo $published ? 'Unpublish' : 'Publish'; ?></span>
                            </button>
                        </div>

                        <div class="help-box">
                            <h6><i class="fas fa-info-circle"></i> Bootstrap Classes</h6>
                            <p style="margin: 0; font-size: 12px;">
                                Use: <code>container</code> | <code>row</code> | <code>col</code> | <code>btn btn-primary</code> | <code>card</code> | <code>mt-3</code>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function loadPage(page) {
            // Hide all sections
            document.querySelectorAll('.editor-section').forEach(el => el.classList.remove('active'));
            
            // Show selected section
            document.querySelector(`.editor-section[data-page="${page}"]`).classList.add('active');
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.tab-btn').classList.add('active');
        }

        function showAlert(message, type = 'success') {
            const alertBox = document.getElementById('alertBox');
            alertBox.textContent = message;
            alertBox.className = `alert alert-${type} alert-show`;
            setTimeout(() => alertBox.classList.remove('alert-show'), 3000);
        }

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin.js"></script>
    <script>
        function switchTab(page, event) {
            event.preventDefault();
            document.querySelectorAll('.editor-section').forEach(el => el.classList.remove('active'));
            document.querySelector(`.editor-section[data-page="${page}"]`).classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.tab-btn').classList.add('active');
        }
        function showAlert(msg, type = 'success') {
            const box = document.getElementById('alertBox');
            box.textContent = msg;
            box.className = `alert alert-${type} alert-show`;
            setTimeout(() => box.classList.remove('alert-show'), 3000);
        }
        function saveContent(page) {
            const content = document.getElementById(`editor-${page}`).value;
            fetch('<?php echo SITE_URL; ?>admin/page_editor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&page=${page}&action=save&content=${encodeURIComponent(content)}`
            })
            .then(r => r.json())
            .then(d => showAlert(d.message || 'Saved', d.success ? 'success' : 'error'))
            .catch(e => { showAlert('Error saving', 'error'); console.error(e); });
        }
        function togglePublish(page) {
            const statusEl = document.getElementById(`status-${page}`);
            const isPublished = statusEl.classList.contains('status-published');
            const action = isPublished ? 'unpublish' : 'publish';
            const button = event.target.closest('button');
            const msg = isPublished ? 'Unpublish? It will hide from visitors.' : 'Publish? It will be visible.';
            if (!confirm(msg)) return;
            fetch('<?php echo SITE_URL; ?>admin/page_editor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&page=${page}&action=${action}`
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    const pub = d.published == 1 || d.published === '1';
                    statusEl.className = `status-badge status-${pub ? 'published' : 'unpublished'}`;
                    statusEl.innerHTML = `<i class="fas fa-${pub ? 'check-circle' : 'times-circle'}"></i><span>${pub ? 'Published' : 'Unpublished'}</span>`;
                    button.className = `btn-${pub ? 'unpublish' : 'publish'}`;
                    button.innerHTML = `<i class="fas fa-${pub ? 'times-circle' : 'check-circle'}"></i><span>${pub ? 'Unpublish' : 'Publish'}</span>`;
                    showAlert(pub ? 'Published!' : 'Unpublished!', 'success');
                }
            })
            .catch(e => { showAlert('Error updating', 'error'); console.error(e); });
        }
    </script>
</body>
</html>
