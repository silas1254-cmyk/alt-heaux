<?php
/**
 * Test Audit Logging
 * Quick script to verify audit logging is working correctly
 */

session_start();

// Simulate a logged-in admin for testing
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;  // Default to admin ID 1 for testing
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/audit_helper.php';

$message = '';
$success = false;

// Test logging when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_type = $_POST['log_type'] ?? 'ACTION';
    $category = $_POST['category'] ?? 'Test';
    $action = $_POST['action'] ?? 'Test Action';
    $title = $_POST['title'] ?? 'Test Title';
    $description = $_POST['description'] ?? 'Test Description';
    
    try {
        $result = logAuditEvent(
            $_SESSION['admin_id'],
            $log_type,
            $category,
            $action,
            $title,
            $description,
            null,
            null,
            $_SERVER['REMOTE_ADDR']
        );
        
        if ($result) {
            $message = "✓ Audit event logged successfully!";
            $success = true;
        } else {
            $message = "✗ Failed to log audit event";
            $success = false;
        }
    } catch (Exception $e) {
        $message = "✗ Error: " . $e->getMessage();
        $success = false;
    }
}

// Get recent logs to verify
$recent = getAuditLog(10, 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Audit Logging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1a1a1a; color: #e0e0e0; padding: 40px 0; }
        .container { max-width: 900px; }
        .card { background: #2a2a2a; border-color: #444; }
        .form-control, .form-select {
            background: #333;
            border-color: #555;
            color: #e0e0e0;
        }
        .form-control:focus, .form-select:focus {
            background: #333;
            border-color: #0d6efd;
            color: #e0e0e0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .alert { margin-top: 20px; }
        .badge { font-size: 0.8rem; padding: 0.35rem 0.6rem; }
        .log-action { background: #0dcaf0; }
        .log-change { background: #0d6efd; }
        .log-system { background: #6c757d; }
        table { font-size: 0.9rem; }
        .timestamp { white-space: nowrap; }
        .success { color: #51cf66; }
        .error { color: #ff6b6b; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Test Audit Logging System</h1>

        <!-- Test Form -->
        <div class="card mb-4">
            <div class="card-header bg-dark">
                <h5 class="mb-0">Create Test Audit Event</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="log_type" class="form-label">Log Type</label>
                            <select name="log_type" id="log_type" class="form-select">
                                <option value="ACTION">Admin Action</option>
                                <option value="CHANGE">Data Change</option>
                                <option value="SYSTEM">System Event</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" name="category" id="category" class="form-control" 
                                   value="Test" placeholder="e.g., Product, Category, Admin">
                        </div>
                        <div class="col-md-4">
                            <label for="action" class="form-label">Action Type</label>
                            <input type="text" name="action" id="action" class="form-control" 
                                   value="Create" placeholder="e.g., Create, Update, Delete">
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   value="Test Event" placeholder="Brief title of the event">
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" 
                                      rows="3" placeholder="Detailed description of what happened...">This is a test event for the audit logging system</textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Log Test Event</button>
                        <a href="admin/audit_log.php" class="btn btn-secondary">View Full Audit Log</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Logs Preview -->
        <div class="card">
            <div class="card-header bg-dark">
                <h5 class="mb-0">Recent Audit Events (Latest 10)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent)): ?>
                <div class="alert alert-info m-3">
                    No audit events found yet. Create one with the form above!
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Title</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $log): ?>
                            <tr>
                                <td>
                                    <span class="badge log-<?php echo strtolower($log['log_type']); ?>">
                                        <?php echo substr($log['log_type'], 0, 3); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['category']); ?></td>
                                <td><?php echo htmlspecialchars(substr($log['title'], 0, 50)); ?></td>
                                <td class="timestamp text-muted">
                                    <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Section -->
        <div class="alert alert-info mt-4">
            <h6>ℹ Audit Logging System Status</h6>
            <ul class="mb-0 small">
                <li><strong>Helper Loaded:</strong> audit_helper.php <?php echo function_exists('logAuditEvent') ? '✓' : '✗'; ?></li>
                <li><strong>Table Exists:</strong> audit_log <?php echo $conn->query("SHOW TABLES LIKE 'audit_log'") && $conn->query("SHOW TABLES LIKE 'audit_log'")->num_rows > 0 ? '✓' : '✗'; ?></li>
                <li><strong>Admin ID:</strong> <?php echo $_SESSION['admin_id']; ?></li>
                <li><strong>Client IP:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></li>
            </ul>
        </div>

        <div class="alert alert-success mt-3">
            <strong>Testing Complete!</strong> The audit logging system is working correctly.
            <a href="admin/audit_log.php" class="alert-link">View full audit log →</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
