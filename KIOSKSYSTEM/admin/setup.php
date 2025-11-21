<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../includes/db.php';

$pdo = get_pdo();

// If an admin already exists, block setup and redirect to login
$exists = (int)$pdo->query('SELECT COUNT(*) AS c FROM admin_users')->fetch()['c'];
if ($exists > 0) { header('Location: /KIOSKSYSTEM/admin/login.php'); exit; }

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');
    if ($username === '' || $password === '' || $confirm === '') {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $success = 'Admin account created. You can now login.';
        } catch (Throwable $e) {
            $error = 'Failed to create admin. Username may already exist.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Initial Setup - Create Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>.setup{ max-width:460px; margin: 10vh auto; } .msg{ margin:8px 0; } .err{ color:#b91c1c;} .ok{ color:#16a34a; }</style>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';">
    <meta http-equiv="Referrer-Policy" content="no-referrer" />
</head>
<body>
    <div class="container setup">
        <div class="card">
            <h2>Initial Setup: Create Admin</h2>
            <p class="msg">This page is only available because there are no admin users yet.</p>
            <?php if ($error): ?><div class="msg err"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>
            <?php if ($success): ?>
                <div class="msg ok"><?php echo htmlspecialchars($success, ENT_QUOTES); ?></div>
                <div style="margin-top:8px;"><a class="btn" href="/KIOSKSYSTEM/admin/login.php">Go to Login</a></div>
            <?php else: ?>
            <form method="post">
                <div class="form-row">
                    <label>Username</label>
                    <input type="text" name="username" required />
                </div>
                <div class="form-row">
                    <label>Password</label>
                    <input type="password" name="password" required />
                </div>
                <div class="form-row">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm" required />
                </div>
                <button class="btn primary" type="submit">Create Admin</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
