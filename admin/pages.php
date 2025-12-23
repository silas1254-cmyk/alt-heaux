<?php
require '../includes/config.php';

requireAdmin();

$message = '';
$error = '';
$pages = getAllPages($conn);

// Handle page actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $slug = trim($_POST['slug'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $meta_description = trim($_POST['meta_description'] ?? '');
        
        if (empty($slug) || empty($title)) {
            $error = 'Slug and title are required.';
        } else {
            if (createPage($slug, $title, $content, $meta_description, $_SESSION['admin_id'], $conn)) {
                $message = 'Page created successfully!';
                // Log the update
                logWebsiteUpdate('Page', "Created page: $title", "New page added", 'Create', $conn);
                header('Location: ' . SITE_URL . 'admin/pages.php');
                exit;
            } else {
                $error = 'Error creating page: ' . $conn->error;
            }
        }
    } elseif ($action === 'update') {
        $id = $_POST['page_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $meta_description = trim($_POST['meta_description'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        
        if (empty($title)) {
            $error = 'Title is required.';
        } else {
            if (updatePage($id, $title, $content, $meta_description, $status, $conn)) {
                $message = 'Page updated successfully!';
                // Log the update
                logWebsiteUpdate('Page', "Updated page: $title", "Page content modified", 'Update', $conn);
                $pages = getAllPages($conn);
            } else {
                $error = 'Error updating page.';
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['page_id'] ?? '';
        if (deletePage($id, $conn)) {
            $message = 'Page deleted successfully!';
            // Log the update
            logWebsiteUpdate('Page', "Deleted page", "Page removed from website", 'Delete', $conn);
            $pages = getAllPages($conn);
        } else {
            $error = 'Error deleting page.';
        }
    }
}

$edit_page = null;
if (isset($_GET['edit'])) {
    $edit_page = getPageById($_GET['edit'], $conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pages - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- TinyMCE Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
    <script>
        tinymce.init({
            selector: '#content',
            height: 400,
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code',
        });
    </script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include('_sidebar.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="page-header">
                    <h1>Manage Pages</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Page Editor -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $edit_page ? 'Edit Page' : 'Create New Page'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $edit_page ? 'update' : 'create'; ?>">
                            <?php if ($edit_page): ?>
                                <input type="hidden" name="page_id" value="<?php echo $edit_page['id']; ?>">
                            <?php endif; ?>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Page Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($edit_page['title'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="slug" class="form-label">URL Slug <?php echo $edit_page ? '(read-only)' : '*'; ?></label>
                                    <input type="text" class="form-control" id="slug" name="slug" 
                                           value="<?php echo htmlspecialchars($edit_page['slug'] ?? ''); ?>" 
                                           <?php echo $edit_page ? 'readonly' : 'required'; ?>>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Description</label>
                                <input type="text" class="form-control" id="meta_description" name="meta_description" 
                                       value="<?php echo htmlspecialchars($edit_page['meta_description'] ?? ''); ?>" 
                                       maxlength="160" placeholder="Max 160 characters">
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea id="content" name="content"><?php echo htmlspecialchars($edit_page['content'] ?? ''); ?></textarea>
                            </div>

                            <?php if ($edit_page): ?>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="draft" <?php echo $edit_page['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo $edit_page['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> <?php echo $edit_page ? 'Update' : 'Create'; ?> Page
                                </button>
                                <?php if ($edit_page): ?>
                                    <a href="<?php echo SITE_URL; ?>admin/pages.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Pages List -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">All Pages (<?php echo count($pages); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pages)): ?>
                            <p class="text-muted">No pages created yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
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
                                        <?php foreach ($pages as $page): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($page['title']); ?></td>
                                                <td><code><?php echo htmlspecialchars($page['slug']); ?></code></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $page['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($page['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($page['created_at'])); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this page?');">
                                                            <i class="fas fa-trash"></i> Delete
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin.js"></script>
</body>
</html>
