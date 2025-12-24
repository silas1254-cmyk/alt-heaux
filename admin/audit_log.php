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

// Determine active tab
$active_tab = $_GET['tab'] ?? 'audit';

// ============== AUDIT LOG DATA (tab: audit) ==============
// Pagination
$limit = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filters
$log_type = $_GET['type'] ?? null;
$category = $_GET['category'] ?? null;
$audit_admin_id = $_GET['admin'] ?? null;
$date_from = $_GET['from'] ?? null;
$date_to = $_GET['to'] ?? null;

// Get audit logs
$logs = getAuditLog($limit, $offset, $log_type, $audit_admin_id, $category, $date_from, $date_to);
$total = getAuditLogCount($log_type, $audit_admin_id, $category);
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

// ============== ADMIN LOGS DATA (tab: admin) ==============
$filter_admin = $_GET['admin_filter'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Build admin logs query
$admin_query = "SELECT l.*, a.username FROM admin_logs l LEFT JOIN admin_users a ON l.admin_id = a.id WHERE 1=1";
$admin_params = [];
$admin_param_types = '';

if (!empty($filter_admin)) {
    $admin_query .= " AND l.admin_id = ?";
    $admin_params[] = intval($filter_admin);
    $admin_param_types .= 'i';
}

if (!empty($filter_action)) {
    $admin_query .= " AND l.action LIKE ?";
    $admin_params[] = '%' . $filter_action . '%';
    $admin_param_types .= 's';
}

if (!empty($filter_date)) {
    $admin_query .= " AND DATE(l.created_at) = ?";
    $admin_params[] = $filter_date;
    $admin_param_types .= 's';
}

$admin_query .= " ORDER BY l.created_at DESC LIMIT 1000";

// Execute admin logs query
$admin_stmt = $conn->prepare($admin_query);
if (!empty($admin_params)) {
    $admin_stmt->bind_param($admin_param_types, ...$admin_params);
}
$admin_stmt->execute();
$admin_logs = $admin_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique admins for admin logs filter
$admin_admins_result = $conn->query("SELECT id, username FROM admin_users ORDER BY username");
$admin_admins = $admin_admins_result->fetch_all(MYSQLI_ASSOC);

// Get unique actions for admin logs filter
$admin_actions_result = $conn->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
$admin_actions = $admin_actions_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include '_sidebar.php'; ?>

        <div class="main-content flex-grow-1">
            <div class="container-fluid p-4">
                <!-- Header with Tabs -->
                <div class="mb-4">
                    <h1 class="h3 mb-3">Logs & Audit</h1>
                    <ul class="nav nav-tabs" role="tablist" style="border-bottom: 2px solid var(--border);">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?php echo $active_tab === 'audit' ? 'active' : ''; ?>" href="?tab=audit" style="<?php echo $active_tab === 'audit' ? 'border-bottom: 3px solid var(--blue); color: var(--text-primary);' : 'color: var(--text-secondary);'; ?>">
                                <i class="bi bi-list"></i> Audit Log
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?php echo $active_tab === 'admin' ? 'active' : ''; ?>" href="?tab=admin" style="<?php echo $active_tab === 'admin' ? 'border-bottom: 3px solid var(--blue); color: var(--text-primary);' : 'color: var(--text-secondary);'; ?>">
                                <i class="bi bi-clock-history"></i> Admin Activity
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- AUDIT LOG TAB -->
                <?php if ($active_tab === 'audit'): ?>

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
                                <select name="type" id="type" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <option value="ACTION" <?php echo $log_type === 'ACTION' ? 'selected' : ''; ?>>Admin Actions</option>
                                    <option value="CHANGE" <?php echo $log_type === 'CHANGE' ? 'selected' : ''; ?>>Data Changes</option>
                                    <option value="SYSTEM" <?php echo $log_type === 'SYSTEM' ? 'selected' : ''; ?>>System Events</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="category" class="form-label">Category</label>
                                <select name="category" id="category" class="form-select form-select-sm">
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
                                <select name="admin" id="admin" class="form-select form-select-sm">
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
                                <input type="date" name="from" id="from" class="form-control form-control-sm"
                                       value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                            </div>

                            <div class="col-md-2">
                                <label for="to" class="form-label">To Date</label>
                                <input type="date" name="to" id="to" class="form-control form-control-sm"
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
                <div class="card">
                    <div class="card-body p-0">
                        <?php if (empty($logs)): ?>
                        <div class="alert alert-info m-3">
                            No audit log entries found.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
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
                                            <div><small class="text-muted">Entity: <?php echo htmlspecialchars($log['entity_name']); ?></small></div>
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
                            <a class="page-link" href="?tab=audit&page=1<?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?tab=audit&page=<?php echo $page - 1; ?><?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">Previous</a>
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
                            <a class="page-link" href="?tab=audit&page=<?php echo $page + 1; ?><?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?tab=audit&page=<?php echo $pages; ?><?php echo $log_type ? "&type=$log_type" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>">Last</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <div class="text-center text-muted mt-3">
                    <small>Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total); ?> of <?php echo number_format($total); ?> records</small>
                </div>

                <?php endif; /* End AUDIT LOG TAB */ ?>

                <!-- ADMIN LOGS TAB -->
                <?php if ($active_tab === 'admin'): ?>

                <!-- Admin Logs Filters -->
                <div class="card filter-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filters</h5>
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="tab" value="admin">
                            <div class="col-md-3">
                                <label for="admin_filter" class="form-label">Admin</label>
                                <select name="admin_filter" id="admin_filter" class="form-select form-select-sm">
                                    <option value="">All Admins</option>
                                    <?php foreach ($admin_admins as $a): ?>
                                    <option value="<?php echo $a['id']; ?>" <?php echo $filter_admin == $a['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($a['username']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="action" class="form-label">Action</label>
                                <select name="action" id="action" class="form-select form-select-sm">
                                    <option value="">All Actions</option>
                                    <?php foreach ($admin_actions as $act): ?>
                                    <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $filter_action === $act['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($act['action']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" name="date" id="date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filter_date); ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                                <a href="?tab=admin" class="btn btn-sm btn-outline-secondary w-100 mt-1">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Admin Logs Table -->
                <div class="card mt-3">
                    <div class="card-body p-0">
                        <?php if (empty($admin_logs)): ?>
                        <div class="alert alert-info m-3">
                            No admin activity records found.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Admin</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>Date & Time</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admin_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($log['action']); ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 60)); ?></small>
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

                <?php endif; /* End ADMIN LOGS TAB */ ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
