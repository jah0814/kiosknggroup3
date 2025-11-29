<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

$pdo = get_pdo();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(64) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        window_label VARCHAR(32) DEFAULT NULL,
        role VARCHAR(32) NOT NULL DEFAULT "window",
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$defaults = [
    ['username' => 'window1', 'password' => 'window1@123', 'window_label' => 'Window 1'],
    ['username' => 'window2', 'password' => 'window2@123', 'window_label' => 'Window 2'],
    ['username' => 'window3', 'password' => 'window3@123', 'window_label' => 'Window 3'],
    ['username' => 'window4', 'password' => 'window4@123', 'window_label' => 'Window 4'],
];

$inserted = 0; $skipped = 0;
foreach ($defaults as $u){
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $check->execute([$u['username']]);
    if ($check->fetch()) { $skipped++; continue; }
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, window_label, role) VALUES (?, ?, ?, "window")');
    $stmt->execute([$u['username'], password_hash($u['password'], PASSWORD_DEFAULT), $u['window_label']]);
    $inserted++;
}

header('Content-Type: text/plain');
echo "Users table ensured. Inserted: {$inserted}, Skipped existing: {$skipped}\n";
echo "Default accounts:\n";
foreach ($defaults as $u){
    echo $u['username'] . ' / ' . $u['password'] . ' (' . $u['window_label'] . ")\n";
}
