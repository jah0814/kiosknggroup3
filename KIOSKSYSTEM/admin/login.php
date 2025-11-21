<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../includes/db.php';

$pdo = get_pdo();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Enter username and password';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = (int)$user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: /KIOSKSYSTEM/admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - Registrar</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style> .login{ max-width:420px; margin: 10vh auto; } .error{ color:#b91c1c; margin-top:8px; }</style>
</head>
<body class="admin">
    <div class="container login">
        <div class="card">
            <div class="brand" style="justify-content:flex-start; margin-bottom:8px;">
                <img src="../assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
                <div class="title">
                    <h2 style="margin:0;">Admin Login</h2>
                    <p style="margin:0;color:#64748b">Registrar • DLSP</p>
                </div>
            </div>
            <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>
            <form method="post">
                <div class="form-row">
                    <label>Username</label>
                    <input type="text" name="username" required />
                </div>
                <div class="form-row">
                    <label>Password</label>
                    <input type="password" name="password" required />
                </div>
                <button class="btn primary" type="submit">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
