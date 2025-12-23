<?php
require '../includes/config.php';

requireAdmin();

$message = '';
$error = '';
$categories = getAllCategories($conn);

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        
        if (empty($name) || empty($slug)) {
            $error = 'Name and slug are required.';
        } else {
            if (createCategory($name, $slug, $description, $image_url, $conn)) {
                $message = 'Category created successfully!';
                // Log the update
                logWebsiteUpdate('Category', "Created category: $name", "New product category added", 'Create', $conn);
                $categories = getAllCategories($conn);
                $_POST = [];
            } else {
                $error = 'Error creating category: ' . $conn->error;
            }
        }
    } elseif ($action === 'update') {
        $id = $_POST['category_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            $error = 'Name is required.';
        } else {
            if (updateCategory($id, $name, $description, $image_url, $status, $conn)) {
                $message = 'Category updated successfully!';
                // Log the update
                logWebsiteUpdate('Category', "Updated category: $name", "Modified category details", 'Update', $conn);
                $categories = getAllCategories($conn);
            } else {
                $error = 'Error updating category.';
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['category_id'] ?? '';
        if (deleteCategory($id, $conn)) {
            $message = 'Category deleted successfully!';
            // Log the update
            logWebsiteUpdate('Category', "Deleted category", "Removed product category", 'Delete', $conn);
            $categories = getAllCategories($conn);
        } else {
            $error = 'Error deleting category.';
        }
    }
}

$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_category = getCategoryById($_GET['edit'], $conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include('_sidebar.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Manage Categories</h1>
                    <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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

                <!-- Category Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $edit_category ? 'Edit Category' : 'Create New Category'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $edit_category ? 'update' : 'create'; ?>">
                            <?php if ($edit_category): ?>
                                <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                            <?php endif; ?>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($edit_category['name'] ?? $_POST['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="slug" class="form-label">URL Slug <?php echo $edit_category ? '(read-only)' : '*'; ?></label>
                                    <input type="text" class="form-control" id="slug" name="slug" 
                                           value="<?php echo htmlspecialchars($edit_category['slug'] ?? $_POST['slug'] ?? ''); ?>" 
                                           <?php echo $edit_category ? 'readonly' : 'required'; ?>>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="image_url" class="form-label">Image URL</label>
                                <input type="text" class="form-control" id="image_url" name="image_url" 
                                       value="<?php echo htmlspecialchars($edit_category['image_url'] ?? $_POST['image_url'] ?? ''); ?>" 
                                       placeholder="https://example.com/image.jpg">
                            </div>

                            <?php if ($edit_category): ?>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $edit_category['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $edit_category['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> <?php echo $edit_category ? 'Update' : 'Create'; ?> Category
                                </button>
                                <?php if ($edit_category): ?>
                                    <a href="<?php echo SITE_URL; ?>admin/categories.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">All Categories (<?php echo count($categories); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <p class="text-muted">No categories created yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Slug</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                                <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $cat['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($cat['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo isset($cat['created_at']) && !empty($cat['created_at']) ? date('M d, Y', strtotime($cat['created_at'])) : 'N/A'; ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <form method="POST" style="display: inline;" class="delete-form">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                        <button type="button" class="btn btn-sm btn-danger" data-action="delete" data-id="<?php echo $cat['id']; ?>">
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
    <script>
        // Handle delete buttons with modal confirmation
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-action="delete"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const form = btn.closest('form');
                    const categoryName = btn.closest('tr').querySelector('td:first-child').textContent;
                    
                    ModalManager.confirm(
                        `Are you sure you want to delete the category "<strong>${categoryName}</strong>"? This action cannot be undone.`,
                        () => {
                            SpinnerManager.show('Deleting category...');
                            form.submit();
                        }
                    );
                });
            });
        });
    </script>
</body>
</html>
