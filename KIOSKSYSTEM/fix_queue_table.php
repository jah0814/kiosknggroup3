<?php
// fix_queue_table.php
// Quick fix to add missing columns to students_queue table
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Queue Table - Kiosk System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e3f2fd; border-left: 4px solid blue; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Fix Queue Table</h1>
    
<?php
try {
    $pdo = get_pdo();
    $pdo->exec('USE `kiosksystem`');
    
    echo '<div class="info">Checking students_queue table structure...</div>';
    
    // Get all columns
    $columns = $pdo->query("SHOW COLUMNS FROM students_queue")->fetchAll(PDO::FETCH_COLUMN);
    $hasDepartment = in_array('department', $columns);
    $hasWindowNumber = in_array('window_number', $columns);
    
    echo '<div class="info">Current columns: ' . implode(', ', $columns) . '</div>';
    
    // Add department column if missing
    if (!$hasDepartment) {
        try {
            $pdo->exec('ALTER TABLE students_queue ADD COLUMN department VARCHAR(255) NOT NULL DEFAULT "" AFTER name');
            echo '<div class="success">✓ Added department column to students_queue table.</div>';
        } catch (PDOException $e) {
            echo '<div class="error">✗ Failed to add department column: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        echo '<div class="info">✓ department column already exists.</div>';
    }
    
    // Add window_number column if missing
    if (!$hasWindowNumber) {
        try {
            $pdo->exec('ALTER TABLE students_queue ADD COLUMN window_number INT UNSIGNED NOT NULL DEFAULT 1 AFTER purpose');
            echo '<div class="success">✓ Added window_number column to students_queue table.</div>';
        } catch (PDOException $e) {
            echo '<div class="error">✗ Failed to add window_number column: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        echo '<div class="info">✓ window_number column already exists.</div>';
    }
    
    // Check and add index
    if ($hasWindowNumber || !$hasWindowNumber) {
        try {
            $indexes = $pdo->query("SHOW INDEXES FROM students_queue")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('idx_window_number', $indexes)) {
                $pdo->exec('ALTER TABLE students_queue ADD INDEX idx_window_number (window_number)');
                echo '<div class="success">✓ Added index on window_number column.</div>';
            } else {
                echo '<div class="info">✓ idx_window_number index already exists.</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">✗ Failed to add index: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    
    // Verify final structure
    $finalColumns = $pdo->query("SHOW COLUMNS FROM students_queue")->fetchAll(PDO::FETCH_COLUMN);
    echo '<div class="success"><strong>Final table structure:</strong><br>';
    echo 'Columns: ' . implode(', ', $finalColumns) . '</div>';
    
    echo '<div class="success"><strong>Fix completed! You can now use the system.</strong></div>';
    echo '<p><a href="index.php">Go to Main Page</a></p>';
    
} catch (Exception $e) {
    echo '<div class="error"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="info">Make sure MySQL is running in XAMPP and the database exists.</div>';
}
?>
</body>
</html>

