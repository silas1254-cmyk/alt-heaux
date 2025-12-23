<?php
session_status() === PHP_SESSION_ACTIVE || session_start();
require_once('../includes/config.php');
require_once('../includes/admin_auth.php');
require_once('../includes/content_helper.php');

requireAdmin();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $label = $_POST['label'] ?? '';
            $url = $_POST['url'] ?? '';
            $position = (int)($_POST['position'] ?? 0);
            $parent_id = $_POST['parent_id'] && $_POST['parent_id'] !== 'none' ? (int)$_POST['parent_id'] : null;
            
            if (empty($label) || empty($url)) {
                $error = 'Label and URL are required!';
            } else {
                createMenuItem($label, $url, $position, $parent_id);
                $message = 'Menu item added successfully!';
                // Log the update
                logWebsiteUpdate('Menu', "Added menu item: $label", "New navigation menu item created", 'Create', $conn);
            }
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $label = $_POST['label'] ?? '';
            $url = $_POST['url'] ?? '';
            $position = (int)($_POST['position'] ?? 0);
            $active = isset($_POST['active']);
            
            if (empty($label) || empty($url)) {
                $error = 'Label and URL are required!';
            } else {
                updateMenuItem($id, $label, $url, $position, $active);
                $message = 'Menu item updated successfully!';
                // Log the update
                logWebsiteUpdate('Menu', "Updated menu item: $label", "Menu item modified", 'Update', $conn);
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            deleteMenuItem($id);
            $message = 'Menu item deleted successfully!';
            // Log the update
            logWebsiteUpdate('Menu', "Deleted menu item", "Navigation menu item removed", 'Delete', $conn);
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

$menu_items = getAllMenuItems();
$parent_items = array_filter($menu_items, function($item) { return $item['parent_id'] === null; });
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include('_sidebar.php'); ?>
        
        <div class="col-md-9 main-content">
            <div class="page-header">
                <h1><i class="fas fa-bars"></i> Navigation Menu</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add New Menu Item -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-plus"></i> Add New Menu Item
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="col-md-4">
                            <label for="label" class="form-label">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="label" name="label" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="url" class="form-label">URL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="url" name="url" placeholder="/" required>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="position" class="form-label">Position</label>
                            <input type="number" class="form-control" id="position" name="position" value="0">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="parent_id" class="form-label">Parent Menu</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="none">None (Top Level)</option>
                                <?php foreach ($parent_items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Add Menu Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Current Menu Items -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Current Menu Items
                </div>
                <div class="card-body">
                    <?php if (empty($menu_items)): ?>
                        <p class="text-muted"><i class="fas fa-info-circle"></i> No menu items yet. Add one above!</p>
                    <?php else: ?>
                        <?php foreach ($menu_items as $item): 
                            $item_class = $item['parent_id'] ? 'submenu' : '';
                        ?>
                            <div class="menu-item-row <?php echo $item_class; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="menu-item-label">
                                            <?php if ($item['parent_id']): ?>
                                                <i class="fas fa-arrow-right text-muted"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($item['label']); ?>
                                        </div>
                                        <div class="menu-item-url"><?php echo htmlspecialchars($item['url']); ?></div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class="badge bg-<?php echo $item['active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $item['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $item['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Delete this menu item?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $item['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Menu Item</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="edit_label<?php echo $item['id']; ?>" class="form-label">Label</label>
                                                    <input type="text" class="form-control" id="edit_label<?php echo $item['id']; ?>" 
                                                           name="label" value="<?php echo htmlspecialchars($item['label']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="edit_url<?php echo $item['id']; ?>" class="form-label">URL</label>
                                                    <input type="text" class="form-control" id="edit_url<?php echo $item['id']; ?>" 
                                                           name="url" value="<?php echo htmlspecialchars($item['url']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="edit_position<?php echo $item['id']; ?>" class="form-label">Position</label>
                                                    <input type="number" class="form-control" id="edit_position<?php echo $item['id']; ?>" 
                                                           name="position" value="<?php echo $item['position']; ?>">
                                                </div>
                                                
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input" id="edit_active<?php echo $item['id']; ?>" 
                                                           name="active" <?php echo $item['active'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="edit_active<?php echo $item['id']; ?>">
                                                        Active
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>js/admin.js"></script>
</body>
</html>
