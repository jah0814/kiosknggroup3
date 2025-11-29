<?php
// get_current_queue.php
// Returns the latest queue number currently being served in JSON format
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

try {
    $pdo = get_pdo();
    $windowNumber = isset($_GET['window']) ? (int)$_GET['window'] : 0;
    
    if ($windowNumber > 0) {
        // Get the currently serving queue number for specific window
        $stmt = $pdo->prepare("SELECT queue_number FROM students_queue WHERE window_number = ? AND status = 'serving' ORDER BY served_at DESC, created_at ASC LIMIT 1");
        $stmt->execute([$windowNumber]);
    } else {
        // Get the currently serving queue number (any window)
        $stmt = $pdo->query("SELECT queue_number FROM students_queue WHERE status = 'serving' ORDER BY served_at DESC, created_at ASC LIMIT 1");
    }
    $result = $stmt->fetch();
    
    $queueNumber = $result ? $result['queue_number'] : null;
    
    json_response([
        'ok' => true,
        'queue_number' => $queueNumber
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => 'Failed to get current queue: ' . $e->getMessage()
    ], 500);
}
?>

