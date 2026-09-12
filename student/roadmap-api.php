<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/../includes/roadmap-service.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}
$input = json_decode((string)file_get_contents('php://input'), true) ?: [];
$action = (string)($input['action'] ?? 'toggle');

ensureRoadmapSchema($pdo);

if ($action === 'enroll') {
    $roadmapId = (int)($input['roadmap_id'] ?? 0);
    if ($roadmapId < 1) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล Roadmap'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM roadmaps WHERE id = ? AND status = 'published'");
    $stmt->execute([$roadmapId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Roadmap นี้ยังไม่เปิดให้ใช้งาน'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO roadmap_enrollments (roadmap_id, user_id, enrolled_at, is_mandatory) VALUES (?, ?, NOW(), 0)'
    );
    $stmt->execute([$roadmapId, (int)$currentUser['id']]);
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'toggle') {
    $taskId = (int)($input['task_id'] ?? 0);
    $completed = filter_var($input['is_completed'] ?? null, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    
    if ($taskId < 1 || $completed === null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ข้อมูลภารกิจไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $task = $pdo->prepare('SELECT id, roadmap_id, completion_type, points_reward FROM roadmap_tasks WHERE id = ? AND is_active = 1 LIMIT 1');
    $task->execute([$taskId]);
    $taskRow = $task->fetch();
    
    if (!$taskRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ไม่พบภารกิจนี้'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($taskRow['completion_type'] !== 'manual') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'ภารกิจนี้จะสำเร็จโดยอัตโนมัติเมื่อทำตามเงื่อนไข (ไม่สามารถติ๊กเองได้)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $status = $completed ? 'completed' : 'not_started';
    $source = $completed ? 'manual' : null;
    $date = $completed ? date('Y-m-d H:i:s') : null;
    
    require_once __DIR__ . '/../includes/roadmap-evaluator.php';
    
    $stmt = $pdo->prepare(
        "INSERT INTO roadmap_task_progress (task_id, user_id, status, completion_source, completed_at, is_completed) 
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE status = VALUES(status), completion_source = VALUES(completion_source), completed_at = VALUES(completed_at), is_completed = VALUES(is_completed)"
    );
    $stmt->execute([$taskId, (int)$currentUser['id'], $status, $source, $date, $completed ? 1 : 0]);
    
    if ($completed) {
        awardNCPoints($pdo, (int)$currentUser['id'], $taskId, (int)$taskRow['roadmap_id'], (int)$taskRow['points_reward']);
    } else {
        // Revoke NC points
        $pdo->prepare("INSERT INTO nc_point_transactions (user_id, task_id, roadmap_id, points, transaction_type, reason) VALUES (?, ?, ?, ?, 'revoke', 'ยกเลิกการทำภารกิจ')")
            ->execute([(int)$currentUser['id'], $taskId, (int)$taskRow['roadmap_id'], -(int)$taskRow['points_reward']]);
    }
    
    // Audit log
    $pdo->prepare("INSERT INTO roadmap_audit_log (user_id, task_id, roadmap_id, action, new_value, performed_by) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([(int)$currentUser['id'], $taskId, (int)$taskRow['roadmap_id'], 'manual_toggle', $status, (int)$currentUser['id']]);
    
    recalculateRoadmapEnrollmentProgress($pdo, (int)$currentUser['id'], (int)$taskRow['roadmap_id']);
    
    $tasks = getRoadmapTasks($pdo, (int)$taskRow['roadmap_id'], (int)$currentUser['id']);
    echo json_encode(['success' => true, 'progress' => roadmapProgress($tasks)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'คำสั่งไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
