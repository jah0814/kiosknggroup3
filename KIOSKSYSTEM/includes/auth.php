<?php
// includes/auth.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function auth_login(string $username, string $password): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, window_label, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user) { return false; }
    if (!password_verify($password, (string)$user['password_hash'])) { return false; }
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => (string)$user['username'],
        'window_label' => (string)($user['window_label'] ?? ''),
        'role' => (string)($user['role'] ?? ''),
    ];
    return true;
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')){
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_user(): array|null {
    return $_SESSION['user'] ?? null;
}

function auth_require(): void {
    if (!auth_user()){
        header('Location: login.php');
        exit;
    }
}
