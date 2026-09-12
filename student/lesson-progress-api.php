<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/../includes/roadmap-evaluator.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string)file_get_contents('php://input'), true) ?: [];
$action = (string)($input['action'] ?? '');
$lessonId = (int)($input['lesson_id'] ?? 0);
$courseId = (int)($input['course_id'] ?? 0);

if ($lessonId < 1 || $courseId < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลบทเรียนไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

ensureRoadmapSchema($pdo);
$userId = (int)$currentUser['id'];

try {
    if ($action === 'start') {
        $stmt = $pdo->prepare("
            INSERT INTO lesson_progress (user_id, lesson_id, course_id, started_at, last_watched_at) 
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE last_watched_at = NOW()
        ");
        $stmt->execute([$userId, $lessonId, $courseId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'heartbeat') {
        $stmt = $pdo->prepare("
            UPDATE lesson_progress 
            SET last_watched_at = NOW(), watch_duration_seconds = watch_duration_seconds + 30 
            WHERE user_id = ? AND lesson_id = ?
        ");
        $stmt->execute([$userId, $lessonId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'complete') {
        $stmt = $pdo->prepare("
            UPDATE lesson_progress 
            SET is_completed = 1, completed_at = COALESCE(completed_at, NOW()) 
            WHERE user_id = ? AND lesson_id = ?
        ");
        $stmt->execute([$userId, $lessonId]);
        
        $progressIdStmt = $pdo->prepare("SELECT id FROM lesson_progress WHERE user_id = ? AND lesson_id = ?");
        $progressIdStmt->execute([$userId, $lessonId]);
        $progressId = (int)$progressIdStmt->fetchColumn();

        if ($progressId > 0) {
            // Trigger roadmap evaluation
            evaluateLessonTask($pdo, $userId, $lessonId, $courseId, $progressId);
            
            // Check if entire course is completed
            $courseCheck = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM lessons WHERE course_id = ?) AS total_lessons,
                    (SELECT COUNT(*) FROM lesson_progress WHERE course_id = ? AND user_id = ? AND is_completed = 1) AS completed_lessons
            ");
            $courseCheck->execute([$courseId, $courseId, $userId]);
            $counts = $courseCheck->fetch();
            
            if ($counts && $counts['total_lessons'] > 0 && $counts['total_lessons'] == $counts['completed_lessons']) {
                evaluateCourseTask($pdo, $userId, $courseId);
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    throw new InvalidArgumentException('Action not supported');
} catch (Throwable $e) {
    error_log('[Lesson API Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ระบบขัดข้อง'], JSON_UNESCAPED_UNICODE);
}
