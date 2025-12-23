<?php
/**
 * Product Visibility Feature Verification
 * Checks that all components are properly installed and configured
 */

require_once 'includes/config.php';

$checks = [
    'database_column' => false,
    'migration_file' => false,
    'setup_file' => false,
    'products_helper_updated' => false,
    'admin_products_updated' => false,
    'index_updated' => false,
    'audit_logging' => false
];

// Check 1: Database column
$result = $conn->query("SHOW COLUMNS FROM products LIKE 'is_hidden'");
$checks['database_column'] = $result && $result->num_rows > 0;

// Check 2: Migration file exists
$checks['migration_file'] = file_exists('migrations_add_product_visibility.sql');

// Check 3: Setup file exists
$checks['setup_file'] = file_exists('setup_product_visibility.php');

// Check 4: Check products_helper.php for is_hidden in queries
$helper_content = file_get_contents('includes/products_helper.php');
$checks['products_helper_updated'] = strpos($helper_content, 'is_hidden = 0') !== false;

// Check 5: Check admin/products.php for toggle functionality
$admin_content = file_get_contents('admin/products.php');
$checks['admin_products_updated'] = strpos($admin_content, 'toggle_visibility') !== false && 
                                    strpos($admin_content, 'toggleProductVisibility') !== false;

// Check 6: Check index.php for is_hidden filter
$index_content = file_get_contents('index.php');
$checks['index_updated'] = strpos($index_content, 'WHERE is_hidden = 0') !== false;

// Check 7: Verify audit logging capability
$checks['audit_logging'] = function_exists('logWebsiteUpdate') || function_exists('logAuditEvent');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Visibility Feature - Verification Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
        }
        .verification-container {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        .verification-container h1 {
            color: #c9a961;
            margin-bottom: 30px;
            text-align: center;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #333;
            border-radius: 6px;
            border-left: 4px solid #666;
        }
        .check-item.pass {
            border-left-color: #28a745;
            background: #1a3a1a;
        }
        .check-item.fail {
            border-left-color: #dc3545;
            background: #3a1a1a;
        }
        .check-item.warning {
            border-left-color: #ffc107;
            background: #3a3a1a;
        }
        .check-icon {
            font-size: 20px;
            width: 30px;
            text-align: center;
            margin-right: 15px;
        }
        .check-item.pass .check-icon {
            color: #28a745;
        }
        .check-item.fail .check-icon {
            color: #dc3545;
        }
        .check-item.warning .check-icon {
            color: #ffc107;
        }
        .check-label {
            flex: 1;
        }
        .check-label strong {
            display: block;
            margin-bottom: 5px;
        }
        .check-label small {
            color: #a0a0a0;
        }
        .summary-box {
            background: #333;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
            text-align: center;
            border: 2px solid #c9a961;
        }
        .summary-box.all-pass {
            background: #1a3a1a;
            border-color: #28a745;
        }
        .summary-box.has-failures {
            background: #3a1a1a;
            border-color: #dc3545;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-action {
            flex: 1;
            padding: 12px;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            color: white;
            background: #c9a961;
            transition: background 0.3s;
        }
        .btn-action:hover {
            background: #d4b374;
            text-decoration: none;
            color: white;
        }
        .btn-action.secondary {
            background: #555;
        }
        .btn-action.secondary:hover {
            background: #666;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h1><i class="fas fa-eye-slash"></i> Product Visibility Feature</h1>
        <h5 style="text-align: center; color: #a0a0a0; margin-bottom: 30px;">Verification Report</h5>

        <?php 
        $pass_count = 0;
        $fail_count = 0;
        $checks_data = [
            'database_column' => [
                'label' => 'Database Column (is_hidden)',
                'description' => 'is_hidden column exists in products table'
            ],
            'migration_file' => [
                'label' => 'Migration File',
                'description' => 'migrations_add_product_visibility.sql exists'
            ],
            'setup_file' => [
                'label' => 'Setup Script',
                'description' => 'setup_product_visibility.php exists'
            ],
            'products_helper_updated' => [
                'label' => 'Products Helper Updated',
                'description' => 'getProductsByCategory() and getFilteredProducts() exclude hidden products'
            ],
            'admin_products_updated' => [
                'label' => 'Admin Products Page',
                'description' => 'UI controls and toggle handler implemented'
            ],
            'index_updated' => [
                'label' => 'Homepage Query',
                'description' => 'Featured products query filters hidden products'
            ],
            'audit_logging' => [
                'label' => 'Audit Logging',
                'description' => 'Hide/unhide actions will be logged'
            ]
        ];

        foreach ($checks_data as $check_key => $check_info): 
            $status = $checks[$check_key];
            if ($status) {
                $pass_count++;
                $class = 'pass';
                $icon = 'fa-check-circle';
            } else {
                $fail_count++;
                $class = 'fail';
                $icon = 'fa-times-circle';
            }
        ?>
            <div class="check-item <?php echo $class; ?>">
                <div class="check-icon">
                    <i class="fas <?php echo $icon; ?>"></i>
                </div>
                <div class="check-label">
                    <strong><?php echo $check_info['label']; ?></strong>
                    <small><?php echo $check_info['description']; ?></small>
                </div>
                <div style="color: <?php echo $status ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                    <?php echo $status ? 'OK' : 'MISSING'; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="summary-box <?php echo $fail_count === 0 ? 'all-pass' : 'has-failures'; ?>">
            <h5>
                <?php if ($fail_count === 0): ?>
                    <i class="fas fa-check-circle"></i> All Checks Passed!
                <?php else: ?>
                    <i class="fas fa-exclamation-triangle"></i> Setup Incomplete
                <?php endif; ?>
            </h5>
            <p style="margin: 10px 0; font-size: 16px;">
                <strong><?php echo $pass_count; ?>/<?php echo count($checks_data); ?></strong> components verified
            </p>
            <p style="margin: 0; font-size: 14px; color: #a0a0a0;">
                <?php if ($fail_count === 0): ?>
                    The product visibility feature is fully implemented and ready to use.
                <?php else: ?>
                    <?php echo $fail_count; ?> component(s) need attention. Run the setup page to complete installation.
                <?php endif; ?>
            </p>
        </div>

        <div class="action-buttons">
            <a href="setup_product_visibility.php" class="btn-action">
                <i class="fas fa-cog"></i> Setup / Check Status
            </a>
            <a href="admin/products.php" class="btn-action secondary">
                <i class="fas fa-cube"></i> Go to Products
            </a>
        </div>
    </div>
</body>
</html>
