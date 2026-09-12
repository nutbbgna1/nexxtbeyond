<?php
declare(strict_types=1);

require_once __DIR__ . '/roadmap-service.php';

function evaluateTestTask(PDO $pdo, int $userId, int $examId, int $attemptId): void
{
    // Find all active roadmap tasks that depend on this exam
    // Ensure the roadmap itself is also published
    $stmt = $pdo->prepare("
        SELECT t.*
        FROM roadmap_tasks t
        JOIN roadmaps r ON r.id = t.roadmap_id
        WHERE t.ref_exam_id = :exam_id 
          AND t.is_active = 1 
          AND r.status = 'published'
          AND t.completion_type IN ('submit_test', 'pass_test')
    ");
    $stmt->execute([':exam_id' => $examId]);
    $tasks = $stmt->fetchAll();
    
    if (empty($tasks)) return;

    // Get the score of this attempt
    $attemptStmt = $pdo->prepare("SELECT score, correct_count, total_questions, started_at FROM test_attempts WHERE id = :id AND user_id = :user_id");
    $attemptStmt->execute([':id' => $attemptId, ':user_id' => $userId]);
    $attempt = $attemptStmt->fetch();
    if (!$attempt) return;
    
    // score in test_attempts is already a percentage (decimal 5,2) 
    $attemptScorePercent = (float)$attempt['score'];
    
    // Get the best score of all attempts if needed
    $bestScorePercent = $attemptScorePercent;
    
    foreach ($tasks as $task) {
        $taskId = (int)$task['id'];
        $roadmapId = (int)$task['roadmap_id'];
        $shouldComplete = false;
        
        if ($task['completion_type'] === 'submit_test') {
            $shouldComplete = true;
        } elseif ($task['completion_type'] === 'pass_test') {
            $passScore = (float)$task['pass_score'];
            $scoreMode = $task['score_mode'] ?? 'latest';
            
            if ($scoreMode === 'best') {
                $bestStmt = $pdo->prepare("SELECT MAX(score) FROM test_attempts WHERE exam_id = :exam_id AND user_id = :user_id");
                $bestStmt->execute([':exam_id' => $examId, ':user_id' => $userId]);
                $bestScorePercent = (float)$bestStmt->fetchColumn();
                $shouldComplete = $bestScorePercent >= $passScore;
            } else {
                $shouldComplete = $attemptScorePercent >= $passScore;
            }
        }
        
        if ($shouldComplete) {
            markTaskCompleted($pdo, $userId, $taskId, $roadmapId, 'test', $attemptId, null, (int)$task['points_reward']);
        }
    }
}

function evaluateLessonTask(PDO $pdo, int $userId, int $lessonId, int $courseId, int $lessonProgressId): void
{
    $stmt = $pdo->prepare("
        SELECT t.*
        FROM roadmap_tasks t
        JOIN roadmaps r ON r.id = t.roadmap_id
        WHERE t.ref_lesson_id = :lesson_id 
          AND t.is_active = 1 
          AND r.status = 'published'
          AND t.completion_type = 'complete_lesson'
    ");
    $stmt->execute([':lesson_id' => $lessonId]);
    $tasks = $stmt->fetchAll();
    
    foreach ($tasks as $task) {
        markTaskCompleted($pdo, $userId, (int)$task['id'], (int)$task['roadmap_id'], 'lesson', null, $lessonProgressId, (int)$task['points_reward']);
    }
}

function evaluateCourseTask(PDO $pdo, int $userId, int $courseId): void
{
    $stmt = $pdo->prepare("
        SELECT t.*
        FROM roadmap_tasks t
        JOIN roadmaps r ON r.id = t.roadmap_id
        WHERE t.ref_course_id = :course_id 
          AND t.is_active = 1 
          AND r.status = 'published'
          AND t.completion_type = 'complete_course'
    ");
    $stmt->execute([':course_id' => $courseId]);
    $tasks = $stmt->fetchAll();
    
    foreach ($tasks as $task) {
        markTaskCompleted($pdo, $userId, (int)$task['id'], (int)$task['roadmap_id'], 'system', null, null, (int)$task['points_reward']);
    }
}

function markTaskCompleted(PDO $pdo, int $userId, int $taskId, int $roadmapId, string $source, ?int $attemptId, ?int $lessonProgressId, int $pointsReward): void
{
    // Ensure the student is enrolled in this roadmap, if not, enroll them automatically if they complete a task?
    // According to the new logic, tasks can only be completed if the roadmap is available, but if they haven't "chosen" it, maybe we don't track.
    // Let's ensure enrollment first
    $stmt = $pdo->prepare("INSERT IGNORE INTO roadmap_enrollments (roadmap_id, user_id, enrolled_at, is_mandatory) VALUES (?, ?, NOW(), 0)");
    $stmt->execute([$roadmapId, $userId]);
    
    // Check if task is already completed
    $check = $pdo->prepare("SELECT status FROM roadmap_task_progress WHERE task_id = ? AND user_id = ?");
    $check->execute([$taskId, $userId]);
    $current = $check->fetch();
    
    if ($current && in_array($current['status'], ['completed', 'exempted'])) {
        return; // Already done
    }
    
    $date = date('Y-m-d H:i:s');
    
    // Mark as completed
    $stmt = $pdo->prepare("
        INSERT INTO roadmap_task_progress (task_id, user_id, status, completion_source, ref_attempt_id, ref_lesson_progress_id, completed_at, is_completed) 
        VALUES (?, ?, 'completed', ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
            status = 'completed', 
            completion_source = VALUES(completion_source), 
            ref_attempt_id = VALUES(ref_attempt_id), 
            ref_lesson_progress_id = VALUES(ref_lesson_progress_id), 
            completed_at = VALUES(completed_at), 
            is_completed = 1
    ");
    $stmt->execute([$taskId, $userId, $source, $attemptId, $lessonProgressId, $date]);
    
    // Audit log
    $pdo->prepare("INSERT INTO roadmap_audit_log (user_id, task_id, roadmap_id, action, new_value) VALUES (?, ?, ?, ?, ?)")
        ->execute([$userId, $taskId, $roadmapId, 'auto_complete', 'completed']);
    
    // Award NC points
    awardNCPoints($pdo, $userId, $taskId, $roadmapId, $pointsReward);
    
    // Recalculate Roadmap Progress
    recalculateRoadmapEnrollmentProgress($pdo, $userId, $roadmapId);
}

function awardNCPoints(PDO $pdo, int $userId, int $taskId, int $roadmapId, int $points): void
{
    if ($points <= 0) return;
    
    // Check if points already awarded for this task
    $stmt = $pdo->prepare("SELECT id FROM nc_point_transactions WHERE user_id = ? AND task_id = ? AND transaction_type = 'earn'");
    $stmt->execute([$userId, $taskId]);
    if ($stmt->fetch()) return;
    
    $stmt = $pdo->prepare("INSERT INTO nc_point_transactions (user_id, task_id, roadmap_id, points, transaction_type, reason) VALUES (?, ?, ?, ?, 'earn', 'ทำภารกิจสำเร็จ')");
    $stmt->execute([$userId, $taskId, $roadmapId, $points]);
}

function recalculateRoadmapEnrollmentProgress(PDO $pdo, int $userId, int $roadmapId): void
{
    $tasks = getRoadmapTasks($pdo, $roadmapId, $userId);
    $progress = roadmapProgress($tasks);
    
    $status = $progress['percent'] >= 100 ? 'completed' : 'active';
    
    $stmt = $pdo->prepare("UPDATE roadmap_enrollments SET progress_percent = ?, status = ? WHERE roadmap_id = ? AND user_id = ?");
    $stmt->execute([$progress['percent'], $status, $roadmapId, $userId]);
}
