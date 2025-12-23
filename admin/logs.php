<?php
/**
 * ADMIN ACTIVITY LOG
 * View all admin actions for auditing and troubleshooting
 */

require '../../includes/config.php';
require '../../includes/backup_helper.php';

// Check if admin has permission to view logs
if (!isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . 'admin/login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Get filter parameters
$filter_admin = $_GET['admin'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Build query
$query = "SELECT l.*, a.username FROM admin_logs l LEFT JOIN admins a ON l.admin_id = a.id WHERE 1=1";
$params = [];
$param_types = '';

if (!empty($filter_admin)) {
    $query .= " AND l.admin_id = ?";
    $params[] = intval($filter_admin);
    $param_types .= 'i';
}

if (!empty($filter_action)) {
    $query .= " AND l.action LIKE ?";
    $params[] = '%' . $filter_action . '%';
    $param_types .= 's';
}

if (!empty($filter_date)) {
    $query .= " AND DATE(l.created_at) = ?";
    $params[] = $filter_date;
    $param_types .= 's';
}

$query .= " ORDER BY l.created_at DESC LIMIT 1000";

// Prepare and execute
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique admins for filter
$admins_result = $conn->query("SELECT id, username FROM admins ORDER BY username");
$admins = $admins_result->fetch_all(MYSQLI_ASSOC);

// Get unique actions for filter
$actions_result = $conn->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
$actions = $actions_result->fetch_all(MYSQLI_ASSOC);

require '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-history"></i> Activity Log</h2>
            <p class="text-muted">View all administrator actions and system events</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-outline-secondary" onclick="exportCSV()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Admin User</label>
                    <select name="admin" class="form-select">
                        <option value="">All Admins</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?php echo $a['id']; ?>" <?php echo $filter_admin == $a['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Action Type</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $filter_action == $act['action'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($act['action']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="<?php echo SITE_URL; ?>admin/logs.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- STATISTICS -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Events</h6>
                    <h2><?php echo count($logs); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Today's Events</h6>
                    <?php
                    $today_count = count(array_filter($logs, function($l) {
                        return date('Y-m-d') === date('Y-m-d', strtotime($l['created_at']));
                    }));
                    ?>
                    <h2><?php echo $today_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Active Admins</h6>
                    <?php
                    $unique_admins = count(array_unique(array_column($logs, 'admin_id')));
                    ?>
                    <h2><?php echo $unique_admins; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Most Common Action</h6>
                    <?php
                    $action_counts = array_count_values(array_column($logs, 'action'));
                    $most_common = key($action_counts);
                    ?>
                    <p class="mb-0"><small><?php echo htmlspecialchars($most_common); ?></small></p>
                </div>
            </div>
        </div>
    </div>

    <!-- LOGS TABLE -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Activity Records</h5>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No activity logs found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="logsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></strong>
                                        <br><small class="text-muted"><?php echo timeAgo($log['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['username'])): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($log['username']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Deleted User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['action']); ?></code>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($log['details'], 0, 100)); ?>
                                        <?php if (strlen($log['details']) > 100): ?>
                                            <button type="button" class="btn btn-sm btn-link p-0" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($log['details']); ?>">
                                                ...
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
});

// Export logs as CSV
function exportCSV() {
    const table = document.getElementById('logsTable');
    let csv = 'Date,Admin,Action,Details,IP Address\n';
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [
            cells[0].textContent.split('\n')[0],
            cells[1].textContent.trim(),
            cells[2].textContent.trim(),
            '"' + cells[3].textContent.trim().replace(/"/g, '""') + '"',
            cells[4].textContent.trim()
        ];
        csv += rowData.join(',') + '\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'activity_log_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
}
</script>

<?php require '../../includes/footer.php'; ?>
