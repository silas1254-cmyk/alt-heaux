<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/audit_helper.php';

// Check admin permission
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get current admin info
$stmt = $conn->prepare("SELECT id, role FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check permission - Owners and Administrators can view full audit log
$can_view_all = isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['Owner', 'Administrator']);

// Pagination
$limit = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filters
$log_type = $_GET['type'] ?? null;
$category = $_GET['category'] ?? null;
$admin_id = $_GET['admin'] ?? null;
$date_from = $_GET['from'] ?? null;
$date_to = $_GET['to'] ?? null;

// Get audit logs
$logs = getAuditLog($limit, $offset, $log_type, $admin_id, $category, $date_from, $date_to);
$total = getAuditLogCount($log_type, $admin_id, $category);
$pages = ceil($total / $limit);

// Get statistics
$stats = getAuditStatistics();

// Get list of admins for filter dropdown
$admins = [];
$result = $conn->query("SELECT id, username FROM admin_users ORDER BY username");
if ($result) {
    $admins = $result->fetch_all(MYSQLI_ASSOC);
}

// Get categories list
$categories = [];
$result = $conn->query("SELECT DISTINCT category FROM audit_log ORDER BY category");
if ($result) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <style>
        body {
            background: #1a1a1a;
            color: #e0e0e0;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #2a2a2a;
            border-right: 1px solid #444;
            flex-shrink: 0;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            background: #1a1a1a;
        }

        .log-type-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.6rem;
        }

        .log-type-action {
            background-color: #0dcaf0 !important;
        }

        .log-type-change {
            background-color: #0d6efd !important;
        }

        .log-type-system {
            background-color: #6c757d !important;
        }

        .category-cell {
            font-weight: 500;
            color: #0d6efd;
        }

        .timestamp-cell {
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .ip-cell {
            font-family: monospace;
            font-size: 0.85rem;
        }

        .filter-card {
            background: #2a2a2a;
            border-color: #444;
            margin-bottom: 20px;
        }

        .filter-card .form-label {
            color: #e0e0e0;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stats-row {
            margin-bottom: 20px;
        }

        .stat-card {
            background: #2a2a2a;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 5px;
        }

        .stat-card h6 {
            color: #a0a0a0;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-card .stat-number {
            color: #fff;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .stat-card.action-type .stat-number {
            color: #0dcaf0;
        }

        .stat-card.change-type .stat-number {
            color: #0d6efd;
        }

        .stat-card.system-type .stat-number {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="wrapper d-flex">
        <?php include '_sidebar.php'; ?>

        <div class="main-content flex-grow-1">
            <div class="container-fluid p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Audit Log</h1>
                    <div>
                        <a href="logs.php" class="btn btn-sm btn-outline-secondary me-2">Admin Activity</a>
                        <a href="updates.php" class="btn btn-sm btn-outline-secondary">Changes</a>
                    </div>
                </div>

                <!-- Statistics -->
                <?php if (!empty($stats)): ?>
                <div class="row stats-row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h6>Total Events</h6>
                            <div class="stat-number"><?php echo number_format($stats['total'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card action-type">
                            <h6>Actions (Today)</h6>
                            <div class="stat-number"><?php echo $stats['today'] ?? 0; ?></div>
                        </div>
                    </div>
                    <?php
                    $action_count = 0;
                    $change_count = 0;
                    foreach ($stats['by_type'] ?? [] as $t):
                        if ($t['log_type'] == 'ACTION') $action_count = $t['count'];
                        if ($t['log_type'] == 'CHANGE') $change_count = $t['count'];
                    endforeach;
                    ?>
                    <div class="col-md-3">
                        <div class="stat-card action-type">
                            <h6>Admin Actions</h6>
                            <div class="stat-number"><?php echo number_format($action_count); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card change-type">
                            <h6>Data Changes</h6>
                            <div class="stat-number"><?php echo number_format($change_count); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card filter-card">
                    <div class="card-body">
                        <h5 class="card-title text-light mb-3">Filters</h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label for="type" class="form-label">Log Type</label>
                                <select name="type" id="type" class="form-select form-select-sm bg-dark text-light border-dark">
                                    <option value="">All Types</option>
                                    <option value="ACTION" <?php echo $log_type === 'ACTION' ? 'selected' : ''; ?>>Admin Actions</option>
                                    <option value="CHANGE" <?php echo $log_type === 'CHANGE' ? 'selected' : ''; ?>>Data Changes</option>
                                    <option value="SYSTEM" <?php echo $log_type === 'SYSTEM' ? 'selected' : ''; ?>>System Events</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="category" class="form-label">Category</label>
                                <select name="category" id="category" class="form-select form-select-sm bg-dark text-light border-dark">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="admin" class="form-label">Admin</label>
                                <select name="admin" id="admin" class="form-select form-select-sm bg-dark text-light border-dark">
                                    <option value="">All Admins</option>
                                    <?php foreach ($admins as $a): ?>
                                    <option value="<?php echo $a['id']; ?>" <?php echo $admin_id == $a['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($a['username']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="from" class="form-label">From Date</label>
                                <input type="date" name="from" id="from" class="form-control form-control-sm bg-dark text-light border-dark"
                                       value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                            </div>

                            <div class="col-md-2">
                                <label for="to" class="form-label">To Date</label>
                                <input type="date" name="to" id="to" class="form-control form-control-sm bg-dark text-light border-dark"
                                       value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                                <a href="audit_log.php" class="btn btn-sm btn-outline-secondary w-100 mt-1">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card bg-dark border-secondary">
                    <div class="card-body p-0">
                        <?php if (empty($logs)): ?>
                        <div class="alert alert-info m-3">
                            No audit log entries found.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th style="width: 80px;">Type</th>
                                        <th style="width: 100px;">Category</th>
                                        <th style="width: 120px;">Action</th>
                                        <th>Title / Description</th>
                                        <th style="width: 100px;">Admin</th>
                                        <th style="width: 150px;">Timestamp</th>
                                        <th style="width: 100px;">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="badge log-type-badge log-type-<?php echo strtolower($log['log_type']); ?>">
                                                <?php echo htmlspecialchars(substr($log['log_type'], 0, 3)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="category-cell"><?php echo htmlspecialchars($log['category']); ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['action_type'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars(substr($log['title'], 0, 50)); ?></strong>
                                            </div>
                                            <?php if ($log['description']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($log['description'], 0, 80)); ?></small>
                                            <?php endif; ?>
                                            <?php if ($log['entity_name']): ?>
                                            <div><small class="text-info">Entity: <?php echo htmlspecialchars($log['entity_name']); ?></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $admin_name = 'Unknown';
                                            foreach ($admins as $a) {
                                                if ($a['id'] == $log['admin_id']) {
                                                    $admin_name = htmlspecialchars($a['username']);
                                                    break;
                                                }
                                            }
                                            echo $admin_name;
                                            ?>
                                        </td>
                                        <td class="timestamp-cell text-muted">
                                            <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td class="ip-cell text-muted">
                                            <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">Previous</a>
                        </li>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $pages; ?><?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">Last</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <div class="text-center text-muted mt-3">
                    <small>Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total); ?> of <?php echo number_format($total); ?> records</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
