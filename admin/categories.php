<?php
require '../includes/config.php';

requireAdmin();

$message = '';
$error = '';
$categories = getAllCategories($conn);

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a JSON request (from AJAX drag-drop)
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($content_type, 'application/json') !== false) {
        $json = json_decode(file_get_contents('php://input'), true);
        $action = $json['action'] ?? '';
        $_POST = $json; // Merge JSON into $_POST for consistency
    } else {
        $action = $_POST['action'] ?? '';
    }
    
    if ($action === 'reorder') {
        // Handle AJAX reorder request
        $order = $_POST['order'] ?? [];
        if (!empty($order)) {
            foreach ($order as $item) {
                $category_id = intval($item['id'] ?? 0);
                $position = intval($item['position'] ?? 0);
                if ($category_id > 0) {
                    updateCategoryOrder($category_id, $position, $conn);
                }
            }
            // Log the update
            logWebsiteUpdate('Category', "Reordered categories", "Updated category display order", 'Update', $conn);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No order data provided']);
            exit;
        }
    } elseif ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Name is required.';
        } else {
            if (createCategory($name, $description, $conn)) {
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
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            $error = 'Name is required.';
        } else {
            if (updateCategory($id, $name, $description, $status, $conn)) {
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

                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($edit_category['name'] ?? $_POST['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? $_POST['description'] ?? ''); ?></textarea>
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
                        <h5 class="mb-0"><i class="fas fa-arrows-alt"></i> All Categories (<?php echo count($categories); ?>) - Drag to reorder</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <p class="text-muted">No categories created yet.</p>
                        <?php else: ?>
                            <div id="sortable-categories" class="list-group">
                                <?php foreach ($categories as $cat): ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between sortable-item" data-category-id="<?php echo $cat['id']; ?>" style="cursor: grab; background: var(--primary-light); color: var(--text-primary); border: 1px solid var(--border-color); margin-bottom: 8px; padding: 15px; border-radius: 6px;">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <i class="fas fa-grip-vertical me-3" style="cursor: grab; color: var(--text-secondary);"></i>
                                            <div>
                                                <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($cat['name']); ?></strong>
                                                <?php if (!empty($cat['description'])): ?>
                                                <br>
                                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($cat['description']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-<?php echo $cat['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($cat['status']); ?>
                                            </span>
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
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin.js"></script>
    <script>
        // Initialize drag-and-drop sorting
        const sortableContainer = document.getElementById('sortable-categories');
        
        if (sortableContainer) {
            const sortable = Sortable.create(sortableContainer, {
                animation: 150,
                handle: '.fa-grip-vertical',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    saveNewOrder();
                }
            });
        }
        
        // Save the new order to the server
        function saveNewOrder() {
            const items = document.querySelectorAll('.sortable-item');
            const order = [];
            let position = 0;
            
            items.forEach(item => {
                const categoryId = item.getAttribute('data-category-id');
                order.push({
                    id: categoryId,
                    position: position
                });
                position++;
            });
            
            // Send AJAX request to save order
            fetch('<?php echo SITE_URL; ?>admin/categories.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reorder',
                    order: order
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Optional: Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        <strong>Order saved!</strong> Category order has been updated.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.main-content').insertBefore(alert, document.querySelector('.card:first-child'));
                    setTimeout(() => alert.remove(), 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    <strong>Error!</strong> Failed to save order. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.card:first-child'));
            });
        }
        
        // Handle delete buttons with modal confirmation
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-action="delete"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const form = btn.closest('form');
                    const categoryName = btn.closest('.list-group-item')?.querySelector('strong')?.textContent || 'this category';
                    
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
        
        // Add CSS for drag feedback
        const style = document.createElement('style');
        style.textContent = `
            :root {
                --primary-dark: #1a1a1a;
                --primary-light: #3a3a3a;
                --accent-blue: #4a90e2;
                --text-secondary: #b0b0b0;
                --border-color: #404040;
            }
            
            .sortable-ghost {
                opacity: 0.5;
                background-color: var(--primary-dark) !important;
                border: 2px dashed var(--border-color) !important;
            }
            
            .sortable-drag {
                opacity: 1;
                background-color: var(--primary-light) !important;
                border-color: var(--accent-blue) !important;
                border: 2px solid var(--accent-blue) !important;
                box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
            }
            
            .sortable-item {
                transition: all 0.2s ease;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
