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
    
    // Check if this is a JSON request (from AJAX drag-drop)
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($content_type, 'application/json') !== false) {
        $json = json_decode(file_get_contents('php://input'), true);
        $action = $json['action'] ?? '';
        $_POST = $json; // Merge JSON into $_POST for consistency
    }
    
    try {
        if ($action === 'reorder') {
            // Handle AJAX reorder request
            $items = $_POST['items'] ?? [];
            if (!empty($items)) {
                foreach ($items as $item) {
                    $menu_id = intval($item['id'] ?? 0);
                    $position = intval($item['position'] ?? 0);
                    $parent_id = isset($item['parent_id']) && $item['parent_id'] !== 'null' ? intval($item['parent_id']) : null;
                    if ($menu_id > 0) {
                        $update_query = "UPDATE menu_items SET position = ?, parent_id = ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bind_param('iii', $position, $parent_id, $menu_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
                // Log the update
                logWebsiteUpdate('Menu', "Reordered menu items", "Updated menu item order and hierarchy", 'Update', $conn);
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'No order data provided']);
                exit;
            }
        } elseif ($action === 'add') {
            $label = $_POST['label'] ?? '';
            $url = $_POST['url'] ?? '';
            $parent_id = $_POST['parent_id'] && $_POST['parent_id'] !== 'none' ? (int)$_POST['parent_id'] : null;
            
            if (empty($label) || empty($url)) {
                $error = 'Label and URL are required!';
            } else {
                // Get the next position for this item
                $position_query = "SELECT MAX(position) as max_pos FROM menu_items";
                $pos_result = $conn->query($position_query);
                $pos_row = $pos_result->fetch_assoc();
                $position = ($pos_row['max_pos'] ?? -1) + 1;
                createMenuItem($label, $url, $position, $parent_id);
                $message = 'Menu item added successfully!';
                // Log the update
                logWebsiteUpdate('Menu', "Added menu item: $label", "New navigation menu item created", 'Create', $conn);
            }
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $label = $_POST['label'] ?? '';
            $url = $_POST['url'] ?? '';
            $active = isset($_POST['active']);
            
            if (empty($label) || empty($url)) {
                $error = 'Label and URL are required!';
            } else {
                // Get current position from database
                $curr_query = "SELECT position FROM menu_items WHERE id = ?";
                $curr_stmt = $conn->prepare($curr_query);
                $curr_stmt->bind_param('i', $id);
                $curr_stmt->execute();
                $curr_result = $curr_stmt->get_result();
                $curr_row = $curr_result->fetch_assoc();
                $position = $curr_row['position'] ?? 0;
                $curr_stmt->close();
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
        } elseif ($action === 'toggle_visibility') {
            // Handle AJAX toggle visibility request
            $menu_id = intval($_POST['menu_id'] ?? 0);
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
            $new_active = $is_active ? 0 : 1;
            
            if ($menu_id > 0) {
                $update_query = "UPDATE menu_items SET active = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('ii', $new_active, $menu_id);
                if ($update_stmt->execute()) {
                    // Log the update
                    logWebsiteUpdate('Menu', "Toggled menu item visibility", "Menu item " . ($new_active ? 'shown' : 'hidden'), 'Update', $conn);
                    $update_stmt->close();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'new_active' => $new_active]);
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $conn->error]);
                    exit;
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid menu ID']);
                exit;
            }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
<div class="wrapper">
    <?php include('_sidebar.php'); ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="bi bi-list"></i> Navigation Menu</h1>
        </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add New Menu Item -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-plus"></i> Add New Menu Item</h5>
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
                                <i class="bi bi-plus-circle"></i> Add Menu Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Current Menu Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list"></i> Current Menu Items
                    <span class="text-muted" style="font-size: 0.9em; margin-left: 10px;"><i class="bi bi-arrows-vertical"></i> Drag to reorder items and organize hierarchy</span></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($menu_items)): ?>
                        <p class="text-muted"><i class="bi bi-info-circle"></i> No menu items yet. Add one above!</p>
                    <?php else: ?>
                        <div id="sortable-menu" class="menu-tree">
                            <?php foreach ($menu_items as $item): 
                                $item_class = $item['parent_id'] ? 'menu-item-submenu' : 'menu-item-root';
                            ?>
                                <div class="menu-item-row sortable-menu-item <?php echo $item_class; ?>" data-menu-id="<?php echo $item['id']; ?>" data-parent-id="<?php echo $item['parent_id'] ?? 'null'; ?>" style="cursor: grab; background: var(--primary-light); color: var(--text-primary); border: 1px solid var(--border-color); margin-bottom: 8px; padding: 15px; border-radius: 6px; margin-left: <?php echo $item['parent_id'] ? '30px' : '0'; ?>;">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <i class="bi bi-grip-vertical" style="color: var(--text-muted); cursor: grab;"></i>
                                                <div>
                                                    <div class="menu-item-label">
                                                        <?php if ($item['parent_id']): ?>
                                                            <i class="bi bi-arrow-right text-muted"></i>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($item['label']); ?>
                                                    </div>
                                                    <div class="menu-item-url"><?php echo htmlspecialchars($item['url']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="badge bg-<?php echo $item['active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $item['active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button class="btn btn-sm <?php echo $item['active'] ? 'btn-warning' : 'btn-success'; ?>" onclick="toggleMenuItemVisibility(<?php echo $item['id']; ?>, <?php echo $item['active']; ?>)" title="<?php echo $item['active'] ? 'Hide menu item' : 'Show menu item'; ?>">
                                                <i class="bi <?php echo $item['active'] ? 'bi-eye-slash' : 'bi-eye'; ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning edit-menu-btn" data-menu-id="<?php echo $item['id']; ?>" data-label="<?php echo htmlspecialchars($item['label']); ?>" data-url="<?php echo htmlspecialchars($item['url']); ?>" data-active="<?php echo $item['active']; ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Delete this menu item?');">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Edit Modal (Outside sortable container) -->
            <div class="modal fade" id="editMenuModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Menu Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" id="edit_menu_id" value="">
                                
                                <div class="mb-3">
                                    <label for="edit_menu_label" class="form-label">Label</label>
                                    <input type="text" class="form-control" id="edit_menu_label" 
                                           name="label" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_menu_url" class="form-label">URL</label>
                                    <input type="text" class="form-control" id="edit_menu_url" 
                                           name="url" required>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_menu_active" 
                                           name="active">
                                    <label class="form-check-label" for="edit_menu_active">
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
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="<?php echo SITE_URL; ?>js/admin.js"></script>
<script>
    // Initialize drag-and-drop sorting for menu items
    const sortableMenu = document.getElementById('sortable-menu');
    
    if (sortableMenu) {
        const sortable = Sortable.create(sortableMenu, {
            animation: 150,
            handle: '.fa-grip-vertical',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                saveMenuOrder();
            }
        });
    }
    
    // Handle edit button clicks
    document.querySelectorAll('.edit-menu-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const menuId = this.getAttribute('data-menu-id');
            const label = this.getAttribute('data-label');
            const url = this.getAttribute('data-url');
            const active = this.getAttribute('data-active');
            
            // Populate modal with data
            document.getElementById('edit_menu_id').value = menuId;
            document.getElementById('edit_menu_label').value = label;
            document.getElementById('edit_menu_url').value = url;
            document.getElementById('edit_menu_active').checked = active == 1;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editMenuModal'));
            modal.show();
        });
    });
    
    // Toggle menu item visibility
    function toggleMenuItemVisibility(menuId, isActive) {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

        // Send AJAX request
        fetch('menus.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle_visibility',
                menu_id: menuId,
                is_active: isActive
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newActive = data.new_active;
                // Update button appearance
                btn.innerHTML = newActive ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
                btn.className = newActive ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-success';
                btn.title = newActive ? 'Hide menu item' : 'Show menu item';
                btn.disabled = false;
                
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <strong>Success!</strong> Menu item visibility updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.page-header').parentElement.insertBefore(alert, document.querySelector('.page-header').nextSibling);
                setTimeout(() => alert.remove(), 3000);
            } else {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            console.error('Error:', error);
            alert('Error updating menu item visibility');
        });
    }
    
    // Save the new menu order to the server
    function saveMenuOrder() {
        const items = document.querySelectorAll('.sortable-menu-item');
        const order = [];
        let position = 0;
        
        items.forEach(item => {
            const menuId = item.getAttribute('data-menu-id');
            const parentId = item.getAttribute('data-parent-id');
            order.push({
                id: menuId,
                position: position,
                parent_id: parentId === 'null' ? null : parseInt(parentId)
            });
            position++;
        });
        
        // Send AJAX request to save order
        fetch('<?php echo SITE_URL; ?>admin/menus.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reorder',
                items: order
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <strong>Order saved!</strong> Menu item order has been updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.page-header').parentElement.insertBefore(alert, document.querySelector('.page-header').nextSibling);
                setTimeout(() => alert.remove(), 3000);
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
</head>
    .menu-tree {
        padding: 10px;
    }
    
    .sortable-ghost {
        opacity: 0.5;
        background-color: var(--accent-gold);
    }
    
    .sortable-drag {
        opacity: 1;
        box-shadow: 0 4px 12px rgba(201, 169, 97, 0.3);
    }
    
    .sortable-menu-item {
        transition: all 0.2s ease;
    }
    
    /* Modal Styling */
    .modal-content {
        background: var(--primary-dark) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    }
    
    .modal-header {
        border-bottom: 2px solid var(--accent-gold) !important;
        background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(201, 169, 97, 0.05) 100%);
        padding: 1.5rem;
    }
    
    .modal-title {
        color: var(--accent-gold);
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .modal-header .btn-close {
        filter: invert(1);
        opacity: 0.8;
        transition: opacity 0.2s ease;
    }
    
    .modal-header .btn-close:hover {
        opacity: 1;
    }
    
    .modal-body {
        padding: 2rem;
        color: var(--text-primary);
    }
    
    .modal-body .form-label {
        color: var(--accent-gold);
        font-weight: 500;
        margin-bottom: 0.7rem;
    }
    
    .modal-body .form-control,
    .modal-body .form-select {
        background: var(--primary-light) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        padding: 0.75rem;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .modal-body .form-control:focus,
    .modal-body .form-select:focus {
        background: var(--primary-light) !important;
        border-color: var(--accent-gold) !important;
        box-shadow: 0 0 0 3px rgba(201, 169, 97, 0.1);
        color: var(--text-primary) !important;
    }
    
    .modal-body .form-check-input {
        width: 1.25em;
        height: 1.25em;
        margin-top: 0.3em;
        border: 1px solid var(--border-color);
        background: var(--primary-light);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .modal-body .form-check-input:checked {
        background-color: var(--accent-gold);
        border-color: var(--accent-gold);
    }
    
    .modal-body .form-check-input:focus {
        border-color: var(--accent-gold);
        box-shadow: 0 0 0 3px rgba(201, 169, 97, 0.1);
    }
    
    .modal-body .form-check-label {
        color: var(--text-primary);
        margin-left: 0.5rem;
        cursor: pointer;
    }
    
    .modal-footer {
        border-top: 1px solid var(--border-color) !important;
        padding: 1.5rem;
        background: rgba(201, 169, 97, 0.02);
    }
    
    .modal-footer .btn-secondary {
        background: var(--primary-light) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        transition: all 0.2s ease;
    }
    
    .modal-footer .btn-secondary:hover {
        background: var(--primary-medium) !important;
        border-color: var(--accent-gold) !important;
    }
    
    .modal-footer .btn-primary {
        background: var(--accent-gold) !important;
        border: 1px solid var(--accent-gold) !important;
        color: #000 !important;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    
    .modal-footer .btn-primary:hover {
        background: #e0c087 !important;
        border-color: #e0c087 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(201, 169, 97, 0.3);
    }
    
    /* Modal backdrop */
    .modal-backdrop {
        background: rgba(0, 0, 0, 0.6) !important;
    }
</head>
<body>
</html>
