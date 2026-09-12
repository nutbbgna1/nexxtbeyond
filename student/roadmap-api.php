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
$taskId = (int)($input['task_id'] ?? 0);
$completed = filter_var($input['is_completed'] ?? null, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
if ($taskId < 1 || $completed === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลภารกิจไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

ensureRoadmapSchema($pdo);
$task = $pdo->prepare('SELECT id, stage FROM roadmap_tasks WHERE id = ? AND is_active = 1 LIMIT 1');
$task->execute([$taskId]);
$taskRow = $task->fetch();
if (!$taskRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบภารกิจนี้'], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt = $pdo->prepare(
    'INSERT INTO roadmap_task_progress (task_id,user_id,is_completed,completed_at) VALUES (?,?,?,?)
     ON DUPLICATE KEY UPDATE is_completed=VALUES(is_completed), completed_at=VALUES(completed_at)'
);
$stmt->execute([$taskId, (int)$currentUser['id'], $completed ? 1 : 0, $completed ? date('Y-m-d H:i:s') : null]);
$tasks = getStudentRoadmap($pdo, (int)$currentUser['id'], (string)$taskRow['stage']);
echo json_encode(['success' => true, 'progress' => roadmapProgress($tasks)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
