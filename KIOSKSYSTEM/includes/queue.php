<?php
// includes/queue.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

// Utility: format queue number from ID
function format_queue_number(int $id): string {
    return 'REG-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}

$action = $_GET['action'] ?? '';

// Create ticket
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    
    if ($studentId === '' || $name === '' || $purpose === '') {
        json_response(['ok' => false, 'error' => 'All fields are required.'], 400);
    }
    
    $pdo = get_pdo();
    $pdo->beginTransaction();
    try {
        // Temporary placeholder queue_number; will update after insert id known
        $stmt = $pdo->prepare('INSERT INTO students_queue (queue_number, student_id, name, purpose, status) VALUES (?, ?, ?, ?, "waiting")');
        $placeholder = 'PENDING';
        $stmt->execute([$placeholder, $studentId, $name, $purpose]);
        $id = (int)$pdo->lastInsertId();
        $queueNumber = format_queue_number($id);
        
        $upd = $pdo->prepare('UPDATE students_queue SET queue_number = ? WHERE id = ?');
        $upd->execute([$queueNumber, $id]);

        // Estimate waiting time
        $countStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM students_queue WHERE status IN ('waiting','serving') AND id <> ?");
        $countStmt->execute([$id]);
        $row = $countStmt->fetch();
        $queueCount = (int)($row['cnt'] ?? 0);
        $estimatedMinutes = max(0, $queueCount * 3);

        $pdo->commit();
        json_response([
            'ok' => true,
            'queue_number' => $queueNumber,
            'estimated_wait_minutes' => $estimatedMinutes,
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'Failed to create ticket: ' . $e->getMessage()], 500);
    }
}

// List queue (for admin)
if ($action === 'list') {
    $pdo = get_pdo();
    $stmt = $pdo->query("SELECT * FROM students_queue WHERE status <> 'done' ORDER BY FIELD(status,'serving','hold','waiting'), created_at ASC");
    $rows = $stmt->fetchAll();
    json_response(['ok' => true, 'items' => $rows]);
}

// Now serving & next list (for display)
if ($action === 'display') {
    $pdo = get_pdo();
    $servingStmt = $pdo->query("SELECT * FROM students_queue WHERE status = 'serving' ORDER BY served_at DESC, created_at ASC LIMIT 1");
    $serving = $servingStmt->fetch();
    
    $nextStmt = $pdo->query("SELECT * FROM students_queue WHERE status IN ('waiting','hold') ORDER BY FIELD(status,'hold','waiting'), created_at ASC LIMIT 5");
    $next = $nextStmt->fetchAll();
    
    json_response(['ok' => true, 'serving' => $serving, 'next' => $next]);
}

// Stats
if ($action === 'stats') {
    $pdo = get_pdo();
    $today = (new DateTime('today'))->format('Y-m-d');
    
    $servedStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM students_queue WHERE DATE(completed_at) = ?");
    $servedStmt->execute([$today]);
    $servedCount = (int)$servedStmt->fetch()['c'];

    $avgStmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, served_at, completed_at)) AS avg_min FROM students_queue WHERE DATE(completed_at) = ? AND served_at IS NOT NULL AND completed_at IS NOT NULL");
    $avgStmt->execute([$today]);
    $avgResult = $avgStmt->fetch();
    $avgMin = $avgResult['avg_min'] ? (float)$avgResult['avg_min'] : 0;

    json_response(['ok' => true, 'servedToday' => $servedCount, 'avgWaitMinutes' => round($avgMin, 1)]);
}

// Admin actions
if ($action === 'next' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = get_pdo();
    $pdo->beginTransaction();
    try {
        // Put current serving on hold
        $clearStmt = $pdo->prepare("UPDATE students_queue SET status='hold' WHERE status='serving'");
        $clearStmt->execute();
        
        // Pick next (prioritize hold then waiting)
        $pickStmt = $pdo->query("SELECT id FROM students_queue WHERE status IN ('hold','waiting') ORDER BY FIELD(status,'hold','waiting'), created_at ASC LIMIT 1");
        $pick = $pickStmt->fetch();
        
        if ($pick) {
            $updStmt = $pdo->prepare("UPDATE students_queue SET status='serving', served_at=NOW() WHERE id = ?");
            $updStmt->execute([(int)$pick['id']]);
            $pdo->commit();
            json_response(['ok' => true]);
        } else {
            $pdo->commit();
            json_response(['ok' => true, 'message' => 'No waiting students']);
        }
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($action === 'hold' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Invalid id'], 400);
    }
    
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE students_queue SET status='hold' WHERE id = ?");
    $stmt->execute([$id]);
    json_response(['ok' => true]);
}

if ($action === 'complete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Invalid id'], 400);
    }
    
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE students_queue SET status='done', completed_at=NOW() WHERE id = ?");
    $stmt->execute([$id]);
    json_response(['ok' => true]);
}

// Fallback
json_response(['ok' => false, 'error' => 'Unknown action'], 404);
?>