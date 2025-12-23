<?php
/**
 * Product Visibility Feature - Advanced Diagnostics
 * Detailed error reporting and alternative migration methods
 */

require_once 'includes/config.php';

$diagnostics = [
    'database_connected' => false,
    'database_name' => '',
    'products_table_exists' => false,
    'is_hidden_column_exists' => false,
    'column_details' => null,
    'migration_sql' => '',
    'errors' => []
];

// Test connection
$diagnostics['database_connected'] = $conn && !$conn->connect_error;
$diagnostics['database_name'] = DB_NAME;

if ($conn) {
    // Check if products table exists
    $result = $conn->query("SHOW TABLES LIKE 'products'");
    $diagnostics['products_table_exists'] = $result && $result->num_rows > 0;
    
    // Check if is_hidden column exists
    $result = $conn->query("SHOW COLUMNS FROM products LIKE 'is_hidden'");
    $diagnostics['is_hidden_column_exists'] = $result && $result->num_rows > 0;
    
    if ($diagnostics['is_hidden_column_exists']) {
        $col = $result->fetch_assoc();
        $diagnostics['column_details'] = $col;
    }
}

// Load migration SQL
if (file_exists('migrations_add_product_visibility.sql')) {
    $diagnostics['migration_sql'] = file_get_contents('migrations_add_product_visibility.sql');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Visibility - Advanced Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
            font-family: 'Courier New', monospace;
        }
        .diag-container {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .diag-container h1 {
            color: #c9a961;
            margin-bottom: 10px;
        }
        .diag-subtitle {
            color: #a0a0a0;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .diagnostic-section {
            background: #333;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #444;
        }
        .diagnostic-section h3 {
            color: #c9a961;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-icon {
            font-size: 18px;
        }
        .status-icon.pass {
            color: #28a745;
        }
        .status-icon.fail {
            color: #dc3545;
        }
        .status-icon.warning {
            color: #ffc107;
        }
        .diagnostic-item {
            background: #1a1a1a;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 4px;
            border-left: 3px solid #666;
            font-size: 13px;
        }
        .diagnostic-item.pass {
            border-left-color: #28a745;
            background: #1a3a1a;
        }
        .diagnostic-item.fail {
            border-left-color: #dc3545;
            background: #3a1a1a;
        }
        .diagnostic-item strong {
            color: #c9a961;
        }
        .code-block {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #444;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 10px;
        }
        .btn-manual {
            background: #c9a961;
            border: none;
            color: #1a1a1a;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
        }
        .btn-manual:hover {
            background: #d4b374;
            text-decoration: none;
            color: #1a1a1a;
        }
        .action-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #444;
        }
        .table-structure {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table-structure td, .table-structure th {
            padding: 8px;
            border: 1px solid #444;
            text-align: left;
            font-size: 12px;
        }
        .table-structure th {
            background: #444;
            color: #c9a961;
        }
        .table-structure td {
            background: #1a1a1a;
        }
    </style>
</head>
<body>
    <div class="diag-container">
        <h1><i class="fas fa-stethoscope"></i> Product Visibility - Diagnostics</h1>
        <div class="diag-subtitle">System analysis and troubleshooting tools</div>

        <!-- Database Connection -->
        <div class="diagnostic-section">
            <h3>
                <span class="status-icon <?php echo $diagnostics['database_connected'] ? 'pass' : 'fail'; ?>">
                    <i class="fas fa-<?php echo $diagnostics['database_connected'] ? 'check-circle' : 'times-circle'; ?>"></i>
                </span>
                Database Connection
            </h3>
            <div class="diagnostic-item <?php echo $diagnostics['database_connected'] ? 'pass' : 'fail'; ?>">
                <strong>Status:</strong> <?php echo $diagnostics['database_connected'] ? 'Connected' : 'Failed'; ?><br>
                <strong>Database:</strong> <?php echo htmlspecialchars($diagnostics['database_name']); ?><br>
                <strong>Host:</strong> <?php echo htmlspecialchars(DB_HOST); ?>
            </div>
        </div>

        <!-- Products Table -->
        <div class="diagnostic-section">
            <h3>
                <span class="status-icon <?php echo $diagnostics['products_table_exists'] ? 'pass' : 'fail'; ?>">
                    <i class="fas fa-<?php echo $diagnostics['products_table_exists'] ? 'check-circle' : 'times-circle'; ?>"></i>
                </span>
                Products Table
            </h3>
            <div class="diagnostic-item <?php echo $diagnostics['products_table_exists'] ? 'pass' : 'fail'; ?>">
                <strong>Exists:</strong> <?php echo $diagnostics['products_table_exists'] ? 'Yes' : 'No'; ?>
                <?php if ($diagnostics['products_table_exists']): ?>
                    <br><strong>Status:</strong> Ready for modifications
                <?php else: ?>
                    <br><strong>Issue:</strong> Products table not found in database
                <?php endif; ?>
            </div>
        </div>

        <!-- is_hidden Column -->
        <div class="diagnostic-section">
            <h3>
                <span class="status-icon <?php echo $diagnostics['is_hidden_column_exists'] ? 'pass' : 'warning'; ?>">
                    <i class="fas fa-<?php echo $diagnostics['is_hidden_column_exists'] ? 'check-circle' : 'circle'; ?>"></i>
                </span>
                is_hidden Column
            </h3>
            <?php if ($diagnostics['is_hidden_column_exists']): ?>
                <div class="diagnostic-item pass">
                    <strong>Status:</strong> EXISTS ✓<br>
                    <strong>Type:</strong> <?php echo htmlspecialchars($diagnostics['column_details']['Type']); ?><br>
                    <strong>Null:</strong> <?php echo $diagnostics['column_details']['Null']; ?><br>
                    <strong>Default:</strong> <?php echo $diagnostics['column_details']['Default']; ?><br>
                    <br><em>The column has been successfully added! The setup is complete.</em>
                </div>
            <?php else: ?>
                <div class="diagnostic-item">
                    <strong>Status:</strong> NOT FOUND<br>
                    <strong>Issue:</strong> The is_hidden column needs to be added<br>
                    <br><strong>Available Options:</strong>
                    <ol style="margin: 10px 0;">
                        <li>Use the <strong>Manual Migration</strong> button below to add the column directly</li>
                        <li>Or use the <strong>PHPMyAdmin/Database Tool</strong> to execute the SQL</li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>

        <!-- Migration SQL -->
        <div class="diagnostic-section">
            <h3>
                <span class="status-icon warning">
                    <i class="fas fa-code"></i>
                </span>
                Migration SQL
            </h3>
            <div class="code-block"><?php echo htmlspecialchars($diagnostics['migration_sql']); ?></div>
        </div>

        <!-- Manual Actions -->
        <div class="diagnostic-section action-section">
            <h3><i class="fas fa-tools"></i> Manual Actions</h3>
            
            <?php if (!$diagnostics['is_hidden_column_exists'] && $diagnostics['products_table_exists']): ?>
                <p style="margin-bottom: 15px;">The is_hidden column is missing. Choose one of these options:</p>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="migrate_direct">
                    <button type="submit" class="btn-manual">
                        <i class="fas fa-play-circle"></i> Run Migration Now
                    </button>
                </form>

                <a href="#copy-sql" class="btn-manual" onclick="copySQLToClipboard(); return false;">
                    <i class="fas fa-copy"></i> Copy SQL for PHPMyAdmin
                </a>

                <?php 
                // Process direct migration if requested
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'migrate_direct'):
                    try {
                        // Execute each statement from migration
                        $statements = array_filter(
                            array_map('trim', preg_split('/;/', $diagnostics['migration_sql'])),
                            fn($s) => !empty($s) && !str_starts_with(trim($s), '--') && !str_starts_with(trim($s), 'USE')
                        );
                        
                        echo "<div style='margin-top: 20px; background: #1a3a1a; padding: 15px; border-left: 3px solid #28a745; border-radius: 4px;'>";
                        echo "<strong style='color: #28a745;'>Migration Execution Report:</strong><br><br>";
                        
                        $success = 0;
                        $failed = 0;
                        
                        foreach ($statements as $statement) {
                            $stmt_clean = trim($statement);
                            if (!empty($stmt_clean)) {
                                if ($conn->query($stmt_clean)) {
                                    echo "✓ " . substr($stmt_clean, 0, 60) . "...<br>";
                                    $success++;
                                } else {
                                    echo "✗ Error: " . $conn->error . "<br>";
                                    echo "  SQL: " . htmlspecialchars(substr($stmt_clean, 0, 80)) . "...<br>";
                                    $failed++;
                                }
                            }
                        }
                        
                        // Verify
                        $verify = $conn->query("SHOW COLUMNS FROM products LIKE 'is_hidden'");
                        if ($verify && $verify->num_rows > 0) {
                            echo "<br><strong style='color: #28a745;'>✓ Verification: Column successfully added!</strong>";
                            echo "<br>Executed: " . $success . " statements";
                            if ($failed > 0) echo " (with " . $failed . " warnings)";
                            echo "<br><br><a href='setup_product_visibility.php' class='btn-manual' style='display: inline-block;'>✓ Go to Setup Page</a>";
                        } else {
                            echo "<br><strong style='color: #dc3545;'>✗ Verification failed - column not found</strong>";
                        }
                        
                        echo "</div>";
                    } catch (Exception $e) {
                        echo "<div style='margin-top: 20px; background: #3a1a1a; padding: 15px; border-left: 3px solid #dc3545; border-radius: 4px;'>";
                        echo "<strong style='color: #dc3545;'>Error:</strong> " . htmlspecialchars($e->getMessage());
                        echo "</div>";
                    }
                endif;
                ?>
            <?php elseif ($diagnostics['is_hidden_column_exists']): ?>
                <div class="diagnostic-item pass" style="margin-bottom: 0;">
                    <strong><i class="fas fa-check-circle"></i> Setup Complete!</strong><br>
                    The is_hidden column has been successfully added to your database.<br><br>
                    <a href="admin/products.php" class="btn-manual" style="display: inline-block;">
                        <i class="fas fa-cube"></i> Go to Products
                    </a>
                    <a href="setup_product_visibility.php" class="btn-manual" style="display: inline-block;">
                        <i class="fas fa-check"></i> Verify Setup
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Troubleshooting -->
        <div class="diagnostic-section">
            <h3><i class="fas fa-question-circle"></i> Troubleshooting</h3>
            <div style="font-size: 13px; line-height: 1.8;">
                <strong>Problem: "Run Migration Now" button doesn't work</strong><br>
                <span style="color: #a0a0a0;">
                    Your hosting may have restrictions on PHP executing SQL. Use the Copy SQL button and paste it into PHPMyAdmin instead.
                </span>
                <br><br>
                
                <strong>Problem: Can't find PHPMyAdmin</strong><br>
                <span style="color: #a0a0a0;">
                    If you're using XAMPP, access it at: <code style="background: #1a1a1a; padding: 2px 6px; border-radius: 3px;">http://localhost/phpmyadmin</code>
                    <br>Then: Select database "alt_heaux" → Click "SQL" tab → Paste the SQL above
                </span>
                <br><br>
                
                <strong>Problem: Still not working after migration</strong><br>
                <span style="color: #a0a0a0;">
                    1. Refresh this page to check if column was added<br>
                    2. Clear your browser cache<br>
                    3. Contact your hosting provider if database operations are restricted
                </span>
            </div>
        </div>
    </div>

    <script>
        function copySQLToClipboard() {
            const sql = `<?php echo addslashes($diagnostics['migration_sql']); ?>`;
            navigator.clipboard.writeText(sql).then(() => {
                alert('SQL copied to clipboard! You can now paste it into PHPMyAdmin.');
            }).catch(() => {
                alert('Failed to copy. Please manually copy the SQL from the box above.');
            });
        }
    </script>
</body>
</html>
