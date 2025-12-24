<?php
require '../includes/config.php';

// Require admin access
requireAdmin();

$message = '';
$error = '';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register_admin') {
        // Register new admin
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = 'Administrator';  // Fixed role for all admins

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'All fields are required.';
        } elseif (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters long.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check if username or email already exists
            $check_query = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Create admin user as active (use username as name)
                if (createAdminUser($username, $password, $email, $username, $conn, $role, 'active')) {
                    $message = "Admin '$username' has been created successfully!";
                    $_POST = [];
                } else {
                    $error = 'Error creating admin account. Please try again.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $admin_id = $_POST['admin_id'] ?? '';
        if (!empty($admin_id)) {
            if (deleteAdmin($admin_id, $conn)) {
                $message = 'Admin account has been deleted.';
            } else {
                $error = 'Error deleting admin account.';
            }
        }
    } elseif ($action === 'change_role') {
        // Only Owner can change roles
        if ($_SESSION['admin_role'] !== 'Owner') {
            $error = 'Only Owner accounts can change admin roles.';
        } else {
            $admin_id = intval($_POST['admin_id'] ?? 0);
            $new_role = $_POST['new_role'] ?? '';
            
            if (empty($admin_id) || empty($new_role)) {
                $error = 'Invalid request.';
            } else {
                if (updateAdminRole($admin_id, $new_role, $conn)) {
                    $message = "Admin role has been updated to '$new_role'.";
                    // Log the update
                    logWebsiteUpdate('Admin', "Changed admin role to $new_role", "Admin role updated", 'Update', $conn);
                } else {
                    $error = 'Error updating admin role.';
                }
            }
        }
    }
}

$approved_admins = getApprovedAdmins($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include('_sidebar.php'); ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Admin Management</h1>
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

                <!-- Register New Admin -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-plus"></i> Register New Admin</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="register_admin">
                            
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" minlength="3" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Create Admin Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Approved Admins -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Active Admins (<?php echo count($approved_admins); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($approved_admins)): ?>
                            <p class="text-muted">No approved admins.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_admins as $admin): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                                <td>
                                                    <span class="admin-badge badge-<?php echo $admin['role']; ?>">
                                                        <?php echo $admin['role'] === 'Owner' ? '👑 Owner' : ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                                        <!-- Current user - no actions -->
                                                        <span class="badge bg-secondary">Your Account</span>
                                                    <?php elseif ($admin['username'] === 'Yevty'): ?>
                                                        <!-- Yevty account - undeletable by anyone -->
                                                        <span class="badge bg-dark"><i class="fas fa-crown"></i> Undeletable</span>
                                                    <?php elseif ($admin['role'] === 'Owner'): ?>
                                                        <!-- Owner account - only deletable by Yevty -->
                                                        <?php if ($_SESSION['admin_name'] === 'Yevty'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this Owner account? This action cannot be undone.');">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="badge bg-dark"><i class="fas fa-crown"></i> Undeletable</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <!-- Administrator account -->
                                                        <div class="d-flex gap-2">
                                                            <?php if ($_SESSION['admin_role'] === 'Owner'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                    <input type="hidden" name="action" value="change_role">
                                                                    <input type="hidden" name="new_role" value="Owner">
                                                                    <button type="submit" class="btn btn-sm btn-warning" title="Make Owner" onclick="return confirm('Make this admin an Owner?');">
                                                                        <i class="fas fa-crown"></i> Make Owner
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this admin account? This action cannot be undone.');">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
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
