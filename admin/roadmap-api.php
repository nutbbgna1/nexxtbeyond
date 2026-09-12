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
$action = (string)($input['action'] ?? $_GET['action'] ?? 'list');

try {
    if ($action === 'list') {
        $tasks = $pdo->query('SELECT t.*, COUNT(p.id) AS student_records, SUM(CASE WHEN p.is_completed=1 THEN 1 ELSE 0 END) AS completed_records FROM roadmap_tasks t LEFT JOIN roadmap_task_progress p ON p.task_id=t.id GROUP BY t.id ORDER BY FIELD(t.stage,"tcas","m4","m1"),t.sort_order,t.id')->fetchAll();
        echo json_encode(['success' => true, 'tasks' => $tasks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if ($action === 'save') {
        $id = (int)($input['id'] ?? 0);
        $title = trim((string)($input['title'] ?? ''));
        $stage = validRoadmapStage((string)($input['stage'] ?? 'tcas'));
        $subject = trim((string)($input['subject'] ?? '')) ?: 'ทั่วไป';
        $category = trim((string)($input['category'] ?? '')) ?: 'ทั่วไป';
        $dueDate = trim((string)($input['due_date'] ?? '')) ?: null;
        $points = min(1000, max(0, (int)($input['points_reward'] ?? 10)));
        $sortOrder = max(0, (int)($input['sort_order'] ?? 0));
        $active = !empty($input['is_active']) ? 1 : 0;
        if ($title === '' || mb_strlen($title) > 255) throw new InvalidArgumentException('กรุณาระบุชื่อภารกิจไม่เกิน 255 ตัวอักษร');
        if ($dueDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) throw new InvalidArgumentException('วันที่ไม่ถูกต้อง');
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE roadmap_tasks SET stage=?,title=?,subject=?,category=?,due_date=?,points_reward=?,sort_order=?,is_active=? WHERE id=?');
            $stmt->execute([$stage,$title,$subject,$category,$dueDate,$points,$sortOrder,$active,$id]);
            if ($stmt->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT COUNT(*) FROM roadmap_tasks WHERE id=?'); $exists->execute([$id]);
                if (!(int)$exists->fetchColumn()) throw new RuntimeException('ไม่พบภารกิจที่ต้องการแก้ไข');
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO roadmap_tasks (stage,title,subject,category,due_date,points_reward,sort_order,is_active,created_by) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$stage,$title,$subject,$category,$dueDate,$points,$sortOrder,$active,(int)$consoleUser['id']]);
            $id = (int)$pdo->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'toggle') {
        $id = (int)($input['id'] ?? 0); $active = !empty($input['is_active']) ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE roadmap_tasks SET is_active=? WHERE id=?'); $stmt->execute([$active,$id]);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE); exit;
    }
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM roadmap_tasks WHERE id=?'); $stmt->execute([$id]);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE); exit;
    }
    throw new InvalidArgumentException('คำสั่งไม่ถูกต้อง');
} catch (InvalidArgumentException $error) {
    http_response_code(422); echo json_encode(['success' => false, 'message' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('[Roadmap Admin] ' . $error->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'], JSON_UNESCAPED_UNICODE);
}
