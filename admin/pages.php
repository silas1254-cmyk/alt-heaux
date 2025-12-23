<?php
require '../includes/config.php';
requireAdmin();

$success = '';
$error = '';
$page = null;
$page_id = $_GET['edit'] ?? null;

// Handle JSON requests (AJAX)
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($content_type, 'application/json') !== false) {
    $json = json_decode(file_get_contents('php://input'), true);
    $action = $json['action'] ?? '';
    $_POST = $json;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save_content') {
        // Save page builder content
        $page_id = intval($_POST['page_id'] ?? 0);
        $blocks = $_POST['blocks'] ?? '[]';
        $content = json_encode($blocks);
        
        if ($page_id > 0) {
            $admin_id = $_SESSION['admin_id'] ?? 1;
            if (updatePageContent($page_id, $content, $admin_id)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Page content saved']);
                exit;
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to save']);
        exit;
    } elseif ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if (empty($title) || empty($slug)) {
            $error = 'Title and slug are required';
        } elseif (!isSlugAvailable($slug)) {
            $error = 'Slug already in use';
        } else {
            $admin_id = $_SESSION['admin_id'] ?? 1;
            $new_id = createPage($title, $slug, '[]', $admin_id);
            if ($new_id) {
                header("Location: pages.php?edit=$new_id&new=1");
                exit;
            } else {
                $error = 'Failed to create page';
            }
        }
    } elseif ($action === 'update') {
        $page_id = intval($_POST['page_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $meta_keywords = trim($_POST['meta_keywords'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $is_hidden = isset($_POST['is_hidden']) ? 1 : 0;
        $featured_image = trim($_POST['featured_image'] ?? '');
        
        if (empty($title) || empty($slug)) {
            $error = 'Title and slug are required';
        } elseif (!isSlugAvailable($slug, $page_id)) {
            $error = 'Slug already in use';
        } else {
            $admin_id = $_SESSION['admin_id'] ?? 1;
            if (updatePage($page_id, $title, $slug, $meta_description, $meta_keywords, $featured_image, $is_published, $is_hidden, $admin_id)) {
                $success = 'Page updated successfully';
                $page = getPageById($page_id);
            } else {
                $error = 'Failed to update page';
            }
        }
    } elseif ($action === 'delete') {
        $page_id = intval($_POST['page_id'] ?? 0);
        if ($page_id > 0) {
            if (deletePage($page_id)) {
                header('Location: pages.php?deleted=1');
                exit;
            } else {
                $error = 'Failed to delete page';
            }
        }
    }
}

if ($page_id) {
    $page = getPageById($page_id);
    if (!$page) {
        header('HTTP/1.1 404 Not Found');
        die('Page not found');
    }
}

$all_pages = getAllPages();
$templates = getAllPageTemplates();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Builder - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
    <style>
        .page-builder-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
        }

        .page-builder-canvas {
            background-color: var(--primary-light);
            border-radius: 8px;
            padding: 2rem;
            min-height: 600px;
            border: 2px dashed var(--border-color);
        }

        .builder-block {
            background-color: var(--primary-medium);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: move;
            transition: all 0.2s ease;
            position: relative;
        }

        .builder-block:hover {
            border-color: var(--accent-gold);
            box-shadow: 0 4px 12px rgba(201, 169, 97, 0.2);
        }

        .builder-block.sortable-ghost {
            opacity: 0.5;
            background-color: var(--accent-gold);
        }

        .builder-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .builder-block-type {
            font-weight: 600;
            color: var(--accent-gold);
            font-size: 0.9rem;
        }

        .builder-block-actions {
            display: flex;
            gap: 0.5rem;
        }

        .builder-block-actions button {
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
        }

        .blocks-panel {
            background-color: var(--primary-medium);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .blocks-panel h6 {
            color: var(--accent-gold);
            margin-bottom: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .block-template {
            background-color: var(--primary-light);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            cursor: grab;
            transition: all 0.2s ease;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .block-template:hover {
            border-color: var(--accent-gold);
            background-color: rgba(201, 169, 97, 0.1);
        }

        .block-template i {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .builder-preview {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            color: #333;
            min-height: 400px;
            margin-top: 2rem;
        }

        @media (max-width: 1200px) {
            .page-builder-wrapper {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include('_sidebar.php'); ?>
        
        <div class="col-md-9 main-content">
            <div class="page-header-extended">
                <h1><i class="fas fa-file-alt"></i> Page Builder</h1>
                <p class="page-header-subtitle">Create and manage pages with drag-and-drop builder</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Page List View -->
            <?php if (!$page): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-plus"></i> Create New Page
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="col-md-6">
                                <label for="title" class="form-label">Page Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="col-md-6">
                                <label for="slug" class="form-label">Page Slug *</label>
                                <input type="text" class="form-control" id="slug" name="slug" placeholder="auto-generated" required>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Create Page
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Pages List -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list"></i> All Pages
                    </div>
                    <div class="card-body">
                        <?php if (empty($all_pages)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fas fa-file"></i></div>
                                <h3>No pages yet</h3>
                                <p>Create your first page using the form above</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Slug</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_pages as $p): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($p['title']); ?></td>
                                                <td><code><?php echo htmlspecialchars($p['slug']); ?></code></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $p['is_published'] ? 'success' : 'warning'; ?>">
                                                        <?php echo $p['is_published'] ? 'Published' : 'Draft'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="page_id" value="<?php echo $p['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this page?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- Page Editor View -->
            <?php else: ?>
                <div class="quick-actions">
                    <div class="quick-actions-left">
                        <a href="?edit=<?php echo $page['id']; ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    <div class="quick-actions-right">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="savePage()">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="savePageContent()">
                            <i class="fas fa-check"></i> Save Content
                        </button>
                    </div>
                </div>

                <!-- Page Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-cog"></i> Page Settings
                    </div>
                    <div class="card-body">
                        <form id="pageSettingsForm" method="POST">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($page['title']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($page['slug']); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($page['meta_keywords'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-row full">
                                <div class="form-group">
                                    <label for="featured_image" class="form-label">Featured Image URL</label>
                                    <input type="text" class="form-control" id="featured_image" name="featured_image" value="<?php echo htmlspecialchars($page['featured_image'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" <?php echo $page['is_published'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_published">
                                            Publish Page
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_hidden" name="is_hidden" <?php echo $page['is_hidden'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_hidden">
                                            Hide from Frontend
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Page Builder -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-cube"></i> Page Content Builder
                    </div>
                    <div class="card-body">
                        <div class="page-builder-wrapper">
                            <!-- Canvas -->
                            <div>
                                <h6 style="color: var(--accent-gold); margin-bottom: 1rem; font-size: 0.9rem;">Page Preview & Editor</h6>
                                <div id="builderCanvas" class="page-builder-canvas">
                                    <!-- Blocks will be rendered here -->
                                </div>
                            </div>

                            <!-- Blocks Panel -->
                            <div class="blocks-panel">
                                <h6><i class="fas fa-shapes"></i> Add Blocks</h6>

                                <div class="block-template" onclick="addBlock('heading')">
                                    <i class="fas fa-heading"></i>
                                    Heading
                                </div>

                                <div class="block-template" onclick="addBlock('text')">
                                    <i class="fas fa-paragraph"></i>
                                    Text
                                </div>

                                <div class="block-template" onclick="addBlock('button')">
                                    <i class="fas fa-square"></i>
                                    Button
                                </div>

                                <div class="block-template" onclick="addBlock('image')">
                                    <i class="fas fa-image"></i>
                                    Image
                                </div>

                                <div class="block-template" onclick="addBlock('gallery')">
                                    <i class="fas fa-images"></i>
                                    Gallery
                                </div>

                                <div class="block-template" onclick="addBlock('divider')">
                                    <i class="fas fa-minus"></i>
                                    Divider
                                </div>

                                <div class="block-template" onclick="addBlock('feature')">
                                    <i class="fas fa-lightbulb"></i>
                                    Feature
                                </div>

                                <div class="block-template" onclick="addBlock('testimonial')">
                                    <i class="fas fa-quote-left"></i>
                                    Testimonial
                                </div>

                                <div class="block-template" onclick="addBlock('cta')">
                                    <i class="fas fa-bullhorn"></i>
                                    Call to Action
                                </div>

                                <div class="block-template" onclick="addBlock('columns')">
                                    <i class="fas fa-columns"></i>
                                    Two Columns
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="<?php echo SITE_URL; ?>js/admin.js"></script>
<script>
    let pageContent = <?php echo json_encode($page ? json_decode($page['content'], true) ?? [] : []); ?>;
    const pageId = <?php echo json_encode($page ? $page['id'] : null); ?>;

    function renderBlocks() {
        const canvas = document.getElementById('builderCanvas');
        canvas.innerHTML = '';

        pageContent.forEach((block, index) => {
            const blockEl = createBlockElement(block, index);
            canvas.appendChild(blockEl);
        });

        initSortable();
    }

    function createBlockElement(block, index) {
        const div = document.createElement('div');
        div.className = 'builder-block';
        div.id = `block-${index}`;
        div.dataset.index = index;

        let preview = getBlockPreview(block);

        div.innerHTML = `
            <div class="builder-block-header">
                <span class="builder-block-type"><i class="fas fa-grip-vertical"></i> ${block.type}</span>
                <div class="builder-block-actions">
                    <button class="btn btn-sm btn-warning" onclick="editBlock(${index})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="removeBlock(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="builder-block-preview">${preview}</div>
        `;

        return div;
    }

    function getBlockPreview(block) {
        switch (block.type) {
            case 'heading':
                return `<strong>${block.content || 'Heading'}</strong>`;
            case 'text':
                return `<small>${(block.content || 'Text content...').substring(0, 50)}...</small>`;
            case 'image':
                return `<small><i class="fas fa-image"></i> ${block.image || 'Image'}</small>`;
            case 'button':
                return `<small><i class="fas fa-square"></i> ${block.text || 'Button'}</small>`;
            default:
                return `<small><i class="fas fa-cube"></i> ${block.type} block</small>`;
        }
    }

    function addBlock(type) {
        const block = {
            type: type,
            content: '',
            title: ''
        };

        switch (type) {
            case 'heading':
                block.content = 'New Heading';
                block.level = 2;
                break;
            case 'text':
                block.content = 'Edit your text here...';
                break;
            case 'image':
                block.image = '';
                block.alt = '';
                break;
            case 'button':
                block.text = 'Click Me';
                block.url = '#';
                break;
        }

        pageContent.push(block);
        renderBlocks();
        window.ToastManager.success('Block added');
    }

    function removeBlock(index) {
        pageContent.splice(index, 1);
        renderBlocks();
        window.ToastManager.success('Block removed');
    }

    function editBlock(index) {
        const block = pageContent[index];
        const modal = new bootstrap.Modal(document.createElement('div'));
        alert('Edit functionality coming soon');
    }

    function initSortable() {
        const canvas = document.getElementById('builderCanvas');
        Sortable.create(canvas, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: (evt) => {
                const newContent = [];
                document.querySelectorAll('#builderCanvas .builder-block').forEach(el => {
                    const index = parseInt(el.dataset.index);
                    newContent.push(pageContent[index]);
                });
                pageContent = newContent;
                renderBlocks();
            }
        });
    }

    function savePageContent() {
        if (!pageId) return;

        window.SpinnerManager.show('Saving content...');

        fetch('pages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'save_content',
                page_id: pageId,
                blocks: pageContent
            })
        })
        .then(r => r.json())
        .then(data => {
            window.SpinnerManager.hide();
            if (data.success) {
                window.ToastManager.success('Content saved successfully');
            } else {
                window.ToastManager.error(data.error || 'Failed to save');
            }
        });
    }

    function savePage() {
        const form = document.getElementById('pageSettingsForm');
        if (form) {
            form.submit();
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        if (pageId) {
            renderBlocks();
        }
    });
</script>
</body>
</html>
