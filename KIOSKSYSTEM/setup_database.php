<?php
// setup_database.php
// Run this script once to initialize the database and tables
declare(strict_types=1);

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = ''; // Your MySQL password (empty for XAMPP default)

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - Kiosk System</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px; 
            background: url('assets/images/university-entrance.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            color: #e7f8f1;
        }
        .success { color: #10b981; padding: 10px; background: rgba(232, 245, 233, 0.95); border-left: 4px solid #10b981; margin: 10px 0; border-radius: 4px; }
        .error { color: #ef4444; padding: 10px; background: rgba(255, 235, 238, 0.95); border-left: 4px solid #ef4444; margin: 10px 0; border-radius: 4px; }
        .info { color: #3b82f6; padding: 10px; background: rgba(227, 242, 253, 0.95); border-left: 4px solid #3b82f6; margin: 10px 0; border-radius: 4px; }
        pre { background: rgba(245, 245, 245, 0.95); padding: 10px; overflow-x: auto; border-radius: 4px; color: #0f172a; }
        h1 { color: #e7f8f1; }
        a { color: #34d399; }
        a:hover { color: #10b981; }
    </style>
</head>
<body>
    <h1>Database Setup</h1>
    
<?php
try {
    // Connect to MySQL server (without database)
    $dsn = "mysql:host={$DB_HOST};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    
    echo '<div class="info">Connected to MySQL server successfully.</div>';
    
    // Read the schema file
    $schemaFile = __DIR__ . '/kiosk_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: {$schemaFile}");
    }
    
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new Exception("Failed to read schema file");
    }
    
    echo '<div class="info">Schema file loaded successfully.</div>';
    
    // Split SQL into individual statements
    // Remove comments and split by semicolons
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt); }
    );
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignore "table already exists" errors for CREATE TABLE IF NOT EXISTS
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    echo '<div class="success">Database setup completed!</div>';
    echo '<div class="info">Executed ' . $executed . ' SQL statements.</div>';
    
    if (!empty($errors)) {
        echo '<div class="error"><strong>Some errors occurred (non-critical):</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    
    // Ensure students table exists (for user registration)
    try {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS `students` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `student_id` VARCHAR(64) NOT NULL UNIQUE,
                `name` VARCHAR(255) NOT NULL,
                `department` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) DEFAULT NULL,
                `password_hash` VARCHAR(255) DEFAULT NULL,
                `facebook_id` VARCHAR(255) DEFAULT NULL,
                `login_type` ENUM("student","email","facebook") NOT NULL DEFAULT "student",
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_student_id` (`student_id`),
                UNIQUE KEY `idx_email` (`email`),
                INDEX `idx_login_type` (`login_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    } catch (PDOException $e) {
        // Table might already exist
    }
    
    // Verify tables exist and update students_queue if needed
    $pdo->exec('USE `kiosksystem`');
    
    // Check if students_queue needs updating - add department column
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM students_queue")->fetchAll(PDO::FETCH_COLUMN);
        $hasDepartment = in_array('department', $columns);
        $hasWindowNumber = in_array('window_number', $columns);
        
        if (!$hasDepartment) {
            $pdo->exec('ALTER TABLE students_queue ADD COLUMN department VARCHAR(255) NOT NULL DEFAULT "" AFTER name');
            echo '<div class="info">Added department column to students_queue table.</div>';
        }
        
        if (!$hasWindowNumber) {
            $pdo->exec('ALTER TABLE students_queue ADD COLUMN window_number INT UNSIGNED NOT NULL DEFAULT 1 AFTER purpose');
            echo '<div class="info">Added window_number column to students_queue table.</div>';
            
            // Check if index exists
            $indexes = $pdo->query("SHOW INDEXES FROM students_queue")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('idx_window_number', $indexes)) {
                $pdo->exec('ALTER TABLE students_queue ADD INDEX idx_window_number (window_number)');
                echo '<div class="info">Added index on window_number column.</div>';
            }
        }
    } catch (PDOException $e) {
        echo '<div class="error">Error updating students_queue table: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo '<div class="info"><strong>Tables created:</strong><ul>';
    foreach ($tables as $table) {
        echo '<li>' . htmlspecialchars($table) . '</li>';
    }
    echo '</ul></div>';
    
    // Ensure default window users exist with known passwords
    $defaults = [
        ['username' => 'window1', 'password' => 'window1@123', 'window_label' => 'Window 1 - Ms Jonalyn Marie M. Cuenca'],
        ['username' => 'window2', 'password' => 'window2@123', 'window_label' => 'Window 2 - Ms Emelinda H. Cosico'],
        ['username' => 'window3', 'password' => 'window3@123', 'window_label' => 'Window 3 - Ms Ellen E. Dejaresco'],
        ['username' => 'window4', 'password' => 'window4@123', 'window_label' => 'Window 4 - Ms. Amalia M. Bobadilla'],
        ['username' => 'window5', 'password' => 'window5@123', 'window_label' => 'Window 5 - Ms Ronieliza Sansebuche'],
        ['username' => 'window6', 'password' => 'window6@123', 'window_label' => 'Window 6 - Ms Arsenia E. Lumalang & Ms Marife De Castro'],
    ];
    
    $inserted = 0;
    $updated = 0;
    foreach ($defaults as $u) {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $check->execute([$u['username']]);
        if ($check->fetch()) {
            // Update existing user with known password
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, window_label = ?, role = "window" WHERE username = ?');
            $stmt->execute([password_hash($u['password'], PASSWORD_DEFAULT), $u['window_label'], $u['username']]);
            $updated++;
        } else {
            // Insert new user
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, window_label, role) VALUES (?, ?, ?, "window")');
            $stmt->execute([$u['username'], password_hash($u['password'], PASSWORD_DEFAULT), $u['window_label']]);
            $inserted++;
        }
    }
    
    if ($inserted > 0 || $updated > 0) {
        echo '<div class="success">Window users configured: ' . $inserted . ' inserted, ' . $updated . ' updated.</div>';
    }
    
    // Check if users table has data
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo '<div class="info">Users table contains ' . $userCount . ' user(s).</div>';
    
    if ($userCount > 0) {
        echo '<div class="success"><strong>Default window accounts (username / password):</strong><pre>';
        foreach ($defaults as $u) {
            echo htmlspecialchars($u['username']) . ' / ' . htmlspecialchars($u['password']) . ' (' . htmlspecialchars($u['window_label']) . ")\n";
        }
        echo '</pre></div>';
    }
    
    echo '<div class="success"><strong>Setup complete! You can now use the application.</strong></div>';
    echo '<p><a href="login.php">Go to Login Page</a></p>';
    
} catch (Exception $e) {
    echo '<div class="error"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="info">Make sure MySQL is running in XAMPP and the credentials in this file match your setup.</div>';
}
?>
</body>
</html>

