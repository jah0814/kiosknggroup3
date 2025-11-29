<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if ($username === '' || $password === ''){
        $error = 'Please enter username and password.';
    } else if (!auth_login($username, $password)){
        $error = 'Invalid credentials.';
    } else {
        header('Location: window_dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - DLSP Registrar Kiosk</title>
    <link rel="icon" type="image/jpeg" href="favicon.php" />
    <link rel="shortcut icon" type="image/jpeg" href="favicon.php" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        body.kiosk {
            background: url('assets/images/university-entrance.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a1a;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        header {
            margin-bottom: 2rem;
            text-align: center;
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .brand img {
            height: 80px;
            margin-right: 1rem;
        }
        .title h1 {
            margin: 0;
            color: #0A3C2E;
            font-size: 2rem;
            font-weight: 700;
        }
        .title p {
            margin: 0.25rem 0 0;
            color: #2D3748;
            font-size: 1.1rem;
        }
        .card {
            background: #EEE7CA;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 420px;
            width: 100%;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group:last-child {
            margin-bottom: 0;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: rgba(13, 176, 122, 0.1);
            border: 1px solid #0A3C2E;
            color: #0f1f1a;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #0D8A58;
            box-shadow: 0 0 0 3px rgba(13, 138, 88, 0.2);
        }
        input::placeholder {
            color: rgba(15, 31, 26, 0.6);
        }
        .btn {
            background: #0D8A58;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            width: 100%;
        }
        .btn:hover {
            background: #0B6E47;
            transform: translateY(-1px);
        }
        .error-message {
            color: #B91C1C;
            background-color: #FEE2E2;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
            text-align: center;
        }
        .form-title {
            color: #0A3C2E;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1.5rem 0;
            text-align: center;
        }
    </style>
</head>
<body class="kiosk">
    <div class="container">
        <header>
            <div class="brand">
                <img src="assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
                <div class="title">
                    <h1>Registrar Kiosk</h1>
                    <p>Dalubhasaan ng Lungsod ng San Pablo</p>
                </div>
            </div>
        </header>

        <main class="card" role="region" aria-labelledby="loginTitle">
            <h1 id="loginTitle" class="form-title">Window Login</h1>
            
            <?php if ($error !== ''): ?>
                <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            
            <form method="post" class="login-form">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" autocomplete="username" required />
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" autocomplete="current-password" required />
                </div>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <button type="submit" class="btn">Login</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
