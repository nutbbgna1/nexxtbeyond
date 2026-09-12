<?php
// test_roadmap_v2.php
declare(strict_types=1);

// We need to bypass the guard for CLI or just include the db and service
require_once __DIR__ . '/includes/db.local.php';
require_once __DIR__ . '/includes/roadmap-service.php';
require_once __DIR__ . '/includes/roadmap-evaluator.php';

try {
    ensureRoadmapSchema($pdo);
    
    echo "Starting Roadmap V2 Test...\n";

    // 1. Create a mock user
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("INSERT IGNORE INTO users (id, email, password_hash, role, first_name, last_name) VALUES (9999, 'test@example.com', 'test', 'student', 'TestUser', 'Last')");
    $userId = 9999;
    echo "User 9999 ensured.\n";

    // 3. Create a Roadmap
    $stmt = $pdo->prepare("INSERT INTO roadmaps (title, stage, status, assignment_mode) VALUES ('Test Roadmap', 'tcas', 'published', 'self_select')");
    $stmt->execute();
    $roadmapId = (int)$pdo->lastInsertId();
    echo "Created Roadmap ID: $roadmapId\n";

    // 4. Create Tasks
    // Task 1: Manual
    $stmt = $pdo->prepare("INSERT INTO roadmap_tasks (roadmap_id, title, completion_type, points_reward, sort_order, is_active) VALUES (?, 'Manual Task', 'manual', 10, 1, 1)");
    $stmt->execute([$roadmapId]);
    $task1Id = (int)$pdo->lastInsertId();

    // Task 2: Submit Test
    $stmt = $pdo->prepare("INSERT INTO roadmap_tasks (roadmap_id, title, completion_type, ref_exam_id, points_reward, sort_order, is_active) VALUES (?, 'Test Task', 'submit_test', 9999, 20, 2, 1)");
    $stmt->execute([$roadmapId]);
    $task2Id = (int)$pdo->lastInsertId();

    // Task 3: Complete Lesson
    $stmt = $pdo->prepare("INSERT INTO roadmap_tasks (roadmap_id, title, completion_type, ref_lesson_id, points_reward, sort_order, is_active) VALUES (?, 'Lesson Task', 'complete_lesson', 9999, 15, 3, 1)");
    $stmt->execute([$roadmapId]);
    $task3Id = (int)$pdo->lastInsertId();

    // Task 4: Prerequisite on Task 1
    $stmt = $pdo->prepare("INSERT INTO roadmap_tasks (roadmap_id, title, completion_type, prerequisite_task_id, points_reward, sort_order, is_active) VALUES (?, 'Prereq Task', 'manual', ?, 30, 4, 1)");
    $stmt->execute([$roadmapId, $task1Id]);
    $task4Id = (int)$pdo->lastInsertId();
    
    echo "Created Tasks: $task1Id, $task2Id, $task3Id, $task4Id\n";

    // 5. Test prerequisites visibility
    $tasks = getRoadmapTasks($pdo, $roadmapId, $userId);
    $prereqTask = array_filter($tasks, fn($t) => $t['id'] == $task4Id);
    $prereqTask = array_pop($prereqTask);
    echo "Prerequisite Task initial status (expected locked): " . $prereqTask['progress_status'] . "\n";

    // 6. Test manual task completion logic (simulating what roadmap-api.php does)
    markTaskCompleted($pdo, $userId, $task1Id, $roadmapId, 'manual', null, null, 10);
    echo "Manual task (Task 1) marked completed.\n";

    // Check if prerequisite unlocked
    $tasks = getRoadmapTasks($pdo, $roadmapId, $userId);
    $prereqTask = array_filter($tasks, fn($t) => $t['id'] == $task4Id);
    $prereqTask = array_pop($prereqTask);
    echo "Prerequisite Task status after Task 1 complete (expected not_started): " . $prereqTask['progress_status'] . "\n";

    // Check NC points
    $pointsQuery = $pdo->query("SELECT SUM(points) FROM nc_point_transactions WHERE user_id = $userId AND roadmap_id = $roadmapId")->fetchColumn();
    echo "Total NC Points from Roadmap (expected 10): " . $pointsQuery . "\n";

    // 7. Test Submit Test logic
    $pdo->exec("INSERT IGNORE INTO test_attempts (id, user_id, exam_id, score, completed_at) VALUES (9999, 9999, 9999, 100, NOW())");
    echo "Mock test attempt created.\n";
    evaluateTestTask($pdo, $userId, 9999, 9999);
    echo "Test evaluated.\n";

    // 8. Test Lesson completion logic
    $pdo->exec("INSERT IGNORE INTO lesson_progress (id, user_id, lesson_id, course_id, is_completed) VALUES (9999, 9999, 9999, 9999, 1)");
    evaluateLessonTask($pdo, $userId, 9999, 9999, 9999);
    echo "Lesson evaluated.\n";

    // Final checks
    $tasks = getRoadmapTasks($pdo, $roadmapId, $userId);
    foreach ($tasks as $t) {
        echo "- Task " . $t['id'] . " (" . $t['title'] . "): " . $t['progress_status'] . "\n";
    }
    $pointsQuery = $pdo->query("SELECT SUM(points) FROM nc_point_transactions WHERE user_id = $userId AND roadmap_id = $roadmapId")->fetchColumn();
    echo "Total NC Points from Roadmap (expected 45): " . $pointsQuery . "\n";
    
    // Cleanup
    $pdo->exec("DELETE FROM roadmaps WHERE id = $roadmapId");
    $pdo->exec("DELETE FROM users WHERE id = 9999");
    $pdo->exec("DELETE FROM test_attempts WHERE id = 9999");
    $pdo->exec("DELETE FROM lesson_progress WHERE id = 9999");
    
    echo "Test complete and cleaned up.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
