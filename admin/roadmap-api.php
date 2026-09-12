<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/access.php';
require_once __DIR__ . '/../includes/roadmap-service.php';
header('Content-Type: application/json; charset=utf-8');

if ($consoleUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'สำหรับผู้ดูแลระบบเท่านั้น'], JSON_UNESCAPED_UNICODE);
    exit;
}

ensureRoadmapSchema($pdo);
$input = json_decode((string)file_get_contents('php://input'), true) ?: [];
$action = (string)($input['action'] ?? $_GET['action'] ?? 'list_roadmaps');

try {
    if ($action === 'list_options') {
        $exams = $pdo->query("SELECT id, title, type FROM exams WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();
        $courses = $pdo->query("SELECT id, title FROM courses WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();
        $lessons = $pdo->query("SELECT id, course_id, title FROM lessons ORDER BY course_id, sort_order")->fetchAll();
        echo json_encode(['success' => true, 'exams' => $exams, 'courses' => $courses, 'lessons' => $lessons], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'list_roadmaps') {
        $roadmaps = $pdo->query("
            SELECT r.*, 
                   COUNT(DISTINCT t.id) AS task_count,
                   COUNT(DISTINCT e.id) AS student_count
            FROM roadmaps r
            LEFT JOIN roadmap_tasks t ON t.roadmap_id = r.id
            LEFT JOIN roadmap_enrollments e ON e.roadmap_id = r.id
            GROUP BY r.id
            ORDER BY r.stage DESC, r.created_at DESC
        ")->fetchAll();
        echo json_encode(['success' => true, 'roadmaps' => $roadmaps], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'save_roadmap') {
        $id = (int)($input['id'] ?? 0);
        $title = trim((string)($input['title'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $stage = validRoadmapStage((string)($input['stage'] ?? 'tcas'));
        $status = in_array($input['status'] ?? '', ['draft', 'published', 'archived']) ? $input['status'] : 'draft';
        
        if ($title === '') throw new InvalidArgumentException('กรุณาระบุชื่อ Roadmap');
        
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE roadmaps SET title=?, description=?, stage=?, status=? WHERE id=?');
            $stmt->execute([$title, $description, $stage, $status, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO roadmaps (title, description, stage, status, created_by) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$title, $description, $stage, $status, (int)$consoleUser['id']]);
            $id = (int)$pdo->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete_roadmap') {
        $id = (int)($input['id'] ?? 0);
        $pdo->prepare('DELETE FROM roadmaps WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE); exit;
    }

    if ($action === 'list_tasks') {
        $roadmapId = (int)($_GET['roadmap_id'] ?? 0);
        $tasks = $pdo->prepare('SELECT * FROM roadmap_tasks WHERE roadmap_id = ? ORDER BY sort_order, id');
        $tasks->execute([$roadmapId]);
        echo json_encode(['success' => true, 'tasks' => $tasks->fetchAll()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'save_task') {
        $id = (int)($input['id'] ?? 0);
        $roadmapId = (int)($input['roadmap_id'] ?? 0);
        $title = trim((string)($input['title'] ?? ''));
        $subject = trim((string)($input['subject'] ?? '')) ?: 'ทั่วไป';
        $category = trim((string)($input['category'] ?? '')) ?: 'ทั่วไป';
        $dueDate = trim((string)($input['due_date'] ?? '')) ?: null;
        $points = min(1000, max(0, (int)($input['points_reward'] ?? 10)));
        $sortOrder = max(0, (int)($input['sort_order'] ?? 0));
        $active = !empty($input['is_active']) ? 1 : 0;
        $isRequired = !empty($input['is_required']) ? 1 : 0;
        $prereqTaskId = !empty($input['prerequisite_task_id']) ? (int)$input['prerequisite_task_id'] : null;
        
        $completionType = $input['completion_type'] ?? 'manual';
        $refCourseId = !empty($input['ref_course_id']) ? (int)$input['ref_course_id'] : null;
        $refLessonId = !empty($input['ref_lesson_id']) ? (int)$input['ref_lesson_id'] : null;
        $refExamId = !empty($input['ref_exam_id']) ? (int)$input['ref_exam_id'] : null;
        $passScore = !empty($input['pass_score']) ? (float)$input['pass_score'] : null;
        $scoreMode = in_array($input['score_mode'] ?? '', ['latest', 'best']) ? $input['score_mode'] : 'latest';
        
        if ($title === '') throw new InvalidArgumentException('กรุณาระบุชื่อภารกิจ');
        
        $params = [
            $title, $subject, $category, $dueDate, $points, $sortOrder, $active, $isRequired, $prereqTaskId,
            $completionType, $refCourseId, $refLessonId, $refExamId, $passScore, $scoreMode
        ];
        
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE roadmap_tasks SET title=?, subject=?, category=?, due_date=?, points_reward=?, sort_order=?, is_active=?, is_required=?, prerequisite_task_id=?, completion_type=?, ref_course_id=?, ref_lesson_id=?, ref_exam_id=?, pass_score=?, score_mode=? WHERE id=?');
            $params[] = $id;
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare('INSERT INTO roadmap_tasks (title, subject, category, due_date, points_reward, sort_order, is_active, is_required, prerequisite_task_id, completion_type, ref_course_id, ref_lesson_id, ref_exam_id, pass_score, score_mode, roadmap_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $params[] = $roadmapId;
            $stmt->execute($params);
        }
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete_task') {
        $id = (int)($input['id'] ?? 0);
        $pdo->prepare('DELETE FROM roadmap_tasks WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE); exit;
    }
    
    if ($action === 'toggle_task') {
        $id = (int)($input['id'] ?? 0);
        $active = !empty($input['is_active']) ? 1 : 0;
        $pdo->prepare('UPDATE roadmap_tasks SET is_active=? WHERE id=?')->execute([$active, $id]);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE); exit;
    }
    
    if ($action === 'assign_roadmap') {
        $roadmapId = (int)($input['roadmap_id'] ?? 0);
        $assignType = $input['assign_type'] ?? 'all';
        $assignTarget = $input['assign_target'] ?? '';
        $isMandatory = !empty($input['is_mandatory']) ? 1 : 0;
        
        $pdo->prepare('INSERT INTO roadmap_assignments (roadmap_id, assignment_type, target_value, is_mandatory, assigned_by) VALUES (?, ?, ?, ?, ?)')
            ->execute([$roadmapId, $assignType, $assignTarget, $isMandatory, (int)$consoleUser['id']]);
            
        // Now find users to enroll
        $userQuery = "SELECT id FROM users";
        $params = [];
        
        if ($assignType === 'stage') {
            $userQuery .= " WHERE stage = ?";
            $params[] = $assignTarget;
        }
        
        $users = $pdo->prepare($userQuery);
        $users->execute($params);
        
        $enroll = $pdo->prepare('INSERT INTO roadmap_enrollments (roadmap_id, user_id, assigned_by, is_mandatory, enrolled_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE is_mandatory = ?');
        foreach ($users->fetchAll() as $u) {
            $enroll->execute([$roadmapId, $u['id'], (int)$consoleUser['id'], $isMandatory, $isMandatory]);
        }
        
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE); exit;
    }

    throw new InvalidArgumentException('คำสั่งไม่ถูกต้อง');
} catch (InvalidArgumentException $error) {
    http_response_code(422); echo json_encode(['success' => false, 'message' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('[Roadmap Admin] ' . $error->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'], JSON_UNESCAPED_UNICODE);
}
