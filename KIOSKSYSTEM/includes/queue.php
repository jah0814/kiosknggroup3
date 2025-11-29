<?php
// includes/queue.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

// Utility: format queue number from per-window sequence (1,2,3...) -> REG-001, REG-002, etc.
function format_queue_number(int $seq): string {
    return 'REG-' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
}

// Map department to window number
function get_window_for_department(string $department): int {
    $dept = strtoupper(trim($department));
    
    // Window 1: Ms Jonalyn Marie M. Cuenca
    if (in_array($dept, ['BSED', 'BTVTED', 'BSNED', 'BEED', 'BPED', 'TCP'])) {
        return 1;
    }
    
    // Window 2: Ms Emelinda H. Cosico
    if (in_array($dept, ['BSIT', 'BSIS', 'MBA', 'MPA', 'MAED'])) {
        return 2;
    }
    
    // Window 3: Ms Ellen E. Dejaresco
    if (in_array($dept, ['BSBA', 'BSAIS', 'BSMA'])) {
        return 3;
    }
    
    // Window 4: Ms. Amalia M. Bobadilla
    if (in_array($dept, ['BS HOSPITALITY MANAGEMENT', 'BSHM', 'HOSPITALITY MANAGEMENT'])) {
        return 4;
    }
    
    // Window 5: Ms Ronieliza Sansebuche
    if (in_array($dept, ['BSOA', 'BSENTREP', 'BSCPE', 'AET'])) {
        return 5;
    }
    
    // Window 6: Ms Arsenia E. Lumalang & Ms Marife De Castro
    if (in_array($dept, ['BSPSY', 'BSECO', 'BSCOMM', 'BSPA', 'BSA', 'ABPOLSCI'])) {
        return 6;
    }
    
    // Default to Window 1 if department not found
    return 1;
}

$action = $_GET['action'] ?? '';

// Create ticket
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    
    if ($studentId === '' || $name === '' || $department === '' || $purpose === '') {
        json_response(['ok' => false, 'error' => 'All fields are required.'], 400);
    }
    
    $windowNumber = get_window_for_department($department);

    $pdo = get_pdo();
    $pdo->beginTransaction();
    try {
        // Determine next sequence number for this window based on non-done tickets only.
        // This means when all tickets for a window are marked 'done', the next one starts again at REG-001.
        $seqStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM students_queue WHERE window_number = ? AND status <> 'done'");
        $seqStmt->execute([$windowNumber]);

        $seqRow = $seqStmt->fetch();
        $nextSeq = ((int)($seqRow['cnt'] ?? 0)) + 1;
        $queueNumber = format_queue_number($nextSeq);

        // Insert ticket with per-window queue number
        $stmt = $pdo->prepare('INSERT INTO students_queue (queue_number, student_id, name, department, purpose, window_number, status) VALUES (?, ?, ?, ?, ?, ?, "waiting")');
        $stmt->execute([$queueNumber, $studentId, $name, $department, $purpose, $windowNumber]);

        // Estimate waiting time for this specific window
        $countStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM students_queue WHERE window_number = ? AND status IN ('waiting','serving')");
        $countStmt->execute([$windowNumber]);
        $row = $countStmt->fetch();
        $queueCount = (int)($row['cnt'] ?? 0);
        $estimatedMinutes = max(0, $queueCount * 3);

        $pdo->commit();
        json_response([
            'ok' => true,
            'queue_number' => $queueNumber,
            'window_number' => $windowNumber,
            'estimated_wait_minutes' => $estimatedMinutes,
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'Failed to create ticket: ' . $e->getMessage()], 500);
    }
}

// List queue (for admin - can filter by window)
if ($action === 'list') {
    $pdo = get_pdo();
    $windowNumber = isset($_GET['window']) ? (int)$_GET['window'] : 0;
    
    if ($windowNumber > 0) {
        $stmt = $pdo->prepare("SELECT * FROM students_queue WHERE window_number = ? AND status <> 'done' ORDER BY FIELD(status,'serving','hold','waiting'), created_at ASC");
        $stmt->execute([$windowNumber]);
    } else {
        $stmt = $pdo->query("SELECT * FROM students_queue WHERE status <> 'done' ORDER BY window_number, FIELD(status,'serving','hold','waiting'), created_at ASC");
    }
    $rows = $stmt->fetchAll();
    json_response(['ok' => true, 'items' => $rows]);
}

// Now serving & next list (for display - per window)
if ($action === 'display') {
    $pdo = get_pdo();
    $windowNumber = isset($_GET['window']) ? (int)$_GET['window'] : 0;
    
    if ($windowNumber > 0) {
        $servingStmt = $pdo->prepare("SELECT * FROM students_queue WHERE window_number = ? AND status = 'serving' ORDER BY served_at DESC, created_at ASC LIMIT 1");
        $servingStmt->execute([$windowNumber]);
        $serving = $servingStmt->fetch();
        
        $nextStmt = $pdo->prepare("SELECT * FROM students_queue WHERE window_number = ? AND status IN ('waiting','hold') ORDER BY FIELD(status,'hold','waiting'), created_at ASC LIMIT 5");
        $nextStmt->execute([$windowNumber]);
        $next = $nextStmt->fetchAll();
    } else {
        // Get all windows data
        $windows = [];
        for ($w = 1; $w <= 6; $w++) {
            $servingStmt = $pdo->prepare("SELECT * FROM students_queue WHERE window_number = ? AND status = 'serving' ORDER BY served_at DESC, created_at ASC LIMIT 1");
            $servingStmt->execute([$w]);
            $serving = $servingStmt->fetch();
            
            $nextStmt = $pdo->prepare("SELECT * FROM students_queue WHERE window_number = ? AND status IN ('waiting','hold') ORDER BY FIELD(status,'hold','waiting'), created_at ASC LIMIT 5");
            $nextStmt->execute([$w]);
            $next = $nextStmt->fetchAll();
            
            $windows[$w] = ['serving' => $serving, 'next' => $next];
        }
        json_response(['ok' => true, 'windows' => $windows]);
        exit;
    }
    
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
    $windowNumber = isset($_POST['window']) ? (int)$_POST['window'] : 0;
    
    if ($windowNumber <= 0) {
        json_response(['ok' => false, 'error' => 'Window number required'], 400);
    }
    
    $pdo->beginTransaction();
    try {
        // Put current serving on hold for this window
        $clearStmt = $pdo->prepare("UPDATE students_queue SET status='hold' WHERE window_number = ? AND status='serving'");
        $clearStmt->execute([$windowNumber]);
        
        // Pick next for this window (prioritize hold then waiting)
        $pickStmt = $pdo->prepare("SELECT id FROM students_queue WHERE window_number = ? AND status IN ('hold','waiting') ORDER BY FIELD(status,'hold','waiting'), created_at ASC LIMIT 1");
        $pickStmt->execute([$windowNumber]);
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