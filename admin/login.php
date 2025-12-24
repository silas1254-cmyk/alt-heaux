<?php
require '../includes/config.php';

// Check if already logged in
if (isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . 'admin/dashboard.php');
    exit;
}

$error = '';
$success = $_GET['message'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $admin = validateAdminLogin($username, $password, $conn);
        
        if ($admin) {
            // Check if admin account is active
            if ($admin['status'] !== 'active') {
                $error = 'Your admin account has been deactivated. Please contact the administrator.';
            } else {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_name'] = $admin['username'];  // Use username instead of name
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['is_admin'] = true;
                
                header('Location: ' . SITE_URL . 'admin/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Admin Panel</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-dark w-100 btn-lg">Login</button>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center text-muted mb-0">
                    <a href="<?php echo SITE_URL; ?>index.php" class="text-decoration-none">Back to Home</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin.js"></script>
</body>
</html>
