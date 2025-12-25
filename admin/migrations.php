<?php
/**
 * Database Migrations Manager
 * Only accessible to super user admins
 */

require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

// Verify super user access
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super_user') {
    header('Location: ' . SITE_URL . 'admin/login.php');
    exit;
}

// Available migrations with descriptions
$migrations = [
    [
        'file' => 'migrations_add_category_order.sql',
        'name' => 'Add Category Display Order',
        'description' => 'Adds display_order column to categories table for custom sorting',
        'runner' => '../run_migration_category_order.php',
        'status' => 'completed'
    ],
    [
        'file' => 'migrations_add_product_visibility.sql',
        'name' => 'Add Product Visibility',
        'description' => 'Adds is_hidden column to products table for release scheduling',
        'runner' => 'N/A',
        'status' => 'manual_only'
    ],
    [
        'file' => 'migrations_guest_cart.sql',
        'name' => 'Guest Cart Persistence',
        'description' => 'Creates guest_carts table for persistent guest shopping carts',
        'runner' => 'N/A',
        'status' => 'manual_only'
    ],
    [
        'file' => 'migrations_page_builder.sql',
        'name' => 'Page Builder Schema',
        'description' => 'Creates pages and page_blocks tables for page builder functionality',
        'runner' => '../run_migration_page_builder.php',
        'status' => 'completed'
    ],
    [
        'file' => 'migrations_consolidate_audit_log.sql',
        'name' => 'Consolidate Audit Log',
        'description' => 'Merges admin_logs and website_updates into unified audit_log table',
        'runner' => 'N/A',
        'status' => 'manual_only'
    ]
];

// Get migration history
$history = [];
$history_query = $conn->prepare(
    'SELECT admin_id, title, description, created_at FROM audit_log 
     WHERE category = "Database" AND action_type = "Migration" 
     ORDER BY created_at DESC LIMIT 50'
);
if ($history_query) {
    $history_query->execute();
    $history_result = $history_query->get_result();
    while ($row = $history_result->fetch_assoc()) {
        $history[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migrations - Alt Heaux Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .migration-card { background: white; border-left: 4px solid #007bff; margin-bottom: 20px; }
        .migration-card.manual_only { border-left-color: #ffc107; }
        .migration-card.completed { border-left-color: #28a745; }
        .danger-zone { background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 30px; }
        .audit-log { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-9">
                <h1>Database Migrations Manager</h1>
                <p class="text-muted">Only super user administrators can execute database migrations. All actions are logged in the audit trail.</p>
                
                <div class="alert alert-warning">
                    <strong>⚠️ Important:</strong> Database migrations can alter your database structure. Ensure you have a backup before executing any migration.
                </div>

                <?php foreach ($migrations as $migration): ?>
                <div class="card migration-card <?php echo $migration['status']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($migration['name']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($migration['description']); ?></p>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <small class="d-block">
                                    <strong>SQL File:</strong> <?php echo htmlspecialchars($migration['file']); ?><br>
                                    <strong>Status:</strong> 
                                    <?php if ($migration['status'] === 'completed'): ?>
                                        <span class="badge bg-success">Auto Runner Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Manual Execution Only</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($migration['status'] === 'completed'): ?>
                                    <button class="btn btn-sm btn-primary" onclick="runMigration('<?php echo htmlspecialchars($migration['runner']); ?>', '<?php echo htmlspecialchars($migration['name']); ?>')">
                                        Execute Migration
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        Manual Only
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="showSQL('<?php echo htmlspecialchars($migration['file']); ?>')">
                                        View SQL
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">Recent Activity</h6>
                    </div>
                    <div class="card-body p-0 audit-log">
                        <?php if (empty($history)): ?>
                            <p class="p-3 text-muted mb-0">No migration history yet</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($history as $entry): ?>
                                <li class="p-3 border-bottom">
                                    <small>
                                        <strong><?php echo htmlspecialchars($entry['title']); ?></strong><br>
                                        <span class="text-muted"><?php echo date('M d, Y H:i', strtotime($entry['created_at'])); ?></span>
                                    </small>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">Safe Practices</h6>
                    </div>
                    <div class="card-body">
                        <ul class="small">
                            <li>Always backup before migrating</li>
                            <li>Test on staging first</li>
                            <li>Run one migration at a time</li>
                            <li>Monitor audit logs</li>
                            <li>Allow migration time to complete</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="danger-zone">
            <h5>⚠️ Danger Zone</h5>
            <p class="mb-2">These are destructive migrations that remove database tables:</p>
            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete()">
                View Destructive Migrations
            </button>
        </div>
    </div>

    <!-- Output Modal -->
    <div class="modal fade" id="outputModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="outputTitle">Migration Output</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="outputBody">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function runMigration(runner, name) {
            if (!confirm('This will execute the migration: ' + name + '\n\nMake sure you have a backup. Continue?')) {
                return;
            }

            const modal = new bootstrap.Modal(document.getElementById('outputModal'));
            document.getElementById('outputTitle').textContent = 'Executing: ' + name;
            document.getElementById('outputBody').innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';
            modal.show();

            fetch(runner)
                .then(response => response.json())
                .then(data => {
                    let html = '<pre style="max-height: 400px; overflow-y: auto;">';
                    html += 'Status: ' + (data.success ? '<span class="text-success">SUCCESS</span>' : '<span class="text-danger">FAILED</span>') + '\n\n';
                    html += 'Statements Executed: ' + data.executed + '\n';
                    html += 'Admin: ' + data.admin_email + '\n';
                    html += 'Time: ' + data.timestamp + '\n';
                    html += 'IP: ' + data.ip_address + '\n\n';
                    
                    if (data.errors && data.errors.length > 0) {
                        html += 'ERRORS:\n';
                        data.errors.forEach(err => {
                            html += '- ' + err + '\n';
                        });
                    }
                    
                    html += '</pre>';
                    document.getElementById('outputBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('outputBody').innerHTML = '<p class="text-danger">Error: ' + error + '</p>';
                });
        }

        function showSQL(file) {
            alert('To view the SQL file, check: ../' + file);
        }

        function confirmDelete() {
            alert('Destructive migrations:\n\n1. migrations_drop_pages_table.sql - Drops the pages table\n2. migrations_consolidate_audit_log.sql - Consolidates logging\n\nThese must be run manually via cPanel phpMyAdmin for safety.');
        }
    </script>
</body>
</html>
