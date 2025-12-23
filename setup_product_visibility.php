<?php
/**
 * Product Visibility Feature Setup
 * This script verifies and sets up the product visibility (hide/unhide) feature
 * Navigate to this file in your browser to run the setup
 */

require_once 'includes/config.php';

// Check if is_hidden column exists
$check_query = "SHOW COLUMNS FROM products LIKE 'is_hidden'";
$result = $conn->query($check_query);
$column_exists = $result && $result->num_rows > 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Visibility Feature Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #1a1a1a;
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-container {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        .setup-container h1 {
            color: #c9a961;
            margin-bottom: 30px;
        }
        .status-item {
            background: #333;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border-left: 4px solid #666;
        }
        .status-item.success {
            border-left-color: #28a745;
            background: #1a3a1a;
        }
        .status-item.error {
            border-left-color: #dc3545;
            background: #3a1a1a;
        }
        .status-item.warning {
            border-left-color: #ffc107;
            background: #3a3a1a;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-error {
            background: #dc3545;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        .setup-form {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #444;
        }
        .btn-primary {
            background: #c9a961;
            border: none;
            color: #1a1a1a;
            font-weight: bold;
        }
        .btn-primary:hover {
            background: #d4b374;
            color: #1a1a1a;
        }
        .output {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #444;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1><i class="fas fa-eye-slash"></i> Product Visibility Setup</h1>
        
        <div class="status-item <?php echo $column_exists ? 'success' : 'error'; ?>">
            <strong><i class="fas fa-<?php echo $column_exists ? 'check-circle' : 'times-circle'; ?>"></i> Database Column Check</strong>
            <span class="status-badge badge-<?php echo $column_exists ? 'success' : 'error'; ?>">
                <?php echo $column_exists ? 'EXISTS' : 'MISSING'; ?>
            </span>
            <div style="margin-top: 10px; font-size: 14px;">
                <?php if ($column_exists): ?>
                    ✓ The <code>is_hidden</code> column is present in the products table
                <?php else: ?>
                    ✗ The <code>is_hidden</code> column is missing from the products table and needs to be added
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$column_exists): ?>
            <div class="setup-form">
                <form method="POST">
                    <input type="hidden" name="setup_visibility" value="1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-cog"></i> Setup Product Visibility Feature
                    </button>
                </form>
            </div>

            <?php 
            // Process setup if requested
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['setup_visibility'])):
                $output = '';
                try {
                    // Read migration SQL
                    $migration_sql = file_get_contents('migrations_add_product_visibility.sql');
                    
                    // Split by ; but handle multi-line statements better
                    $lines = explode("\n", $migration_sql);
                    $statements = [];
                    $current_statement = '';
                    
                    foreach ($lines as $line) {
                        $trimmed = trim($line);
                        // Skip comments and empty lines
                        if (empty($trimmed) || strpos($trimmed, '--') === 0) {
                            continue;
                        }
                        // Skip USE statement (already connected to database)
                        if (stripos($trimmed, 'USE') === 0) {
                            continue;
                        }
                        
                        $current_statement .= ' ' . $line;
                        
                        // If statement ends with semicolon, add to array
                        if (strpos($trimmed, ';') !== false) {
                            $current_statement = trim(str_replace(';', '', $current_statement));
                            if (!empty($current_statement)) {
                                $statements[] = $current_statement;
                            }
                            $current_statement = '';
                        }
                    }
                    
                    $success_count = 0;
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if (!empty($statement)) {
                            $output .= "Executing: " . substr($statement, 0, 60) . "...\n";
                            if ($conn->query($statement)) {
                                $success_count++;
                                $output .= "✓ Success\n\n";
                            } else {
                                $output .= "✗ Error: " . $conn->error . "\n\n";
                                throw new Exception("Query failed: " . $conn->error);
                            }
                        }
                    }
                    
                    // Verify column was added
                    $verify = $conn->query("SHOW COLUMNS FROM products LIKE 'is_hidden'");
                    if ($verify && $verify->num_rows > 0) {
                        $output .= "\n✓ Verification: is_hidden column successfully added!\n";
                        $output .= "✓ All " . $success_count . " migration statements executed successfully\n";
                        $output .= "✓ Product visibility feature is now ready to use!\n";
                        $column_exists = true;
                        echo "<div class='status-item success'>";
                        echo "<strong><i class='fas fa-check-circle'></i> Setup Completed Successfully!</strong>";
                        echo "<div style='margin-top: 10px; font-size: 14px;'>";
                        echo "✓ The product visibility feature is now active<br>";
                        echo "✓ You can now hide/unhide products from the admin panel<br>";
                        echo "✓ Hidden products will not appear on the public site<br>";
                        echo "</div>";
                        echo "</div>";
                    } else {
                        throw new Exception("Column verification failed - column was not created");
                    }
                } catch (Exception $e) {
                    $output .= "\n✗ Setup failed: " . $e->getMessage() . "\n";
                    echo "<div class='status-item error'>";
                    echo "<strong><i class='fas fa-times-circle'></i> Setup Failed</strong>";
                    echo "<div style='margin-top: 10px; font-size: 14px;'>";
                    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
                    echo "Please use the diagnostic tool at <code>diagnose_product_visibility.php</code> for more details<br>";
                    echo "</div>";
                    echo "</div>";
                }
                
                if (!empty($output)):
                    echo "<div class='output'>" . htmlspecialchars($output) . "</div>";
                endif;
            endif;
            ?>
        <?php else: ?>
            <div class="status-item success" style="margin-top: 30px;">
                <strong><i class="fas fa-check-circle"></i> Ready to Use</strong>
                <div style="margin-top: 10px; font-size: 14px;">
                    <p>The product visibility feature is fully set up and ready to use:</p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Go to the <strong>Products</strong> page in the admin panel</li>
                        <li>Click the <strong>eye icon</strong> to hide/unhide any product</li>
                        <li>Hidden products will not appear on the public website</li>
                        <li>All hide/unhide actions are logged in the audit log</li>
                    </ul>
                </div>
            </div>

            <div class="alert alert-info" style="margin-top: 20px;">
                <strong><i class="fas fa-info-circle"></i> Next Steps:</strong><br>
                <a href="admin/products.php" class="alert-link">Go to Product Management</a> to start using the hide/unhide feature
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #444;">
            <small style="color: #a0a0a0;">
                Having issues? Use the <a href="diagnose_product_visibility.php" style="color: #c9a961; text-decoration: underline;">Diagnostics Tool</a> to troubleshoot
            </small>
        </div>
    </div>
</body>
</html>
