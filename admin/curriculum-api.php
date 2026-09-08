<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../includes/db.php';

function curriculumResponse(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function curriculumBody(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) curriculumResponse(['error'=>'ข้อมูล JSON ไม่ถูกต้อง'], 400);
    return $data;
}
function lessonValues(array $body): array {
    $courseId = (int)($body['courseId'] ?? 0);
    $title = trim((string)($body['title'] ?? ''));
    $type = (string)($body['contentType'] ?? 'video');
    if ($courseId < 1 || $title === '') curriculumResponse(['error'=>'กรุณาเลือกคอร์สและกรอกชื่อบทเรียน'], 422);
    if (!in_array($type, ['video','document','quiz','live'], true)) curriculumResponse(['error'=>'ประเภทเนื้อหาไม่ถูกต้อง'], 422);
    $url = trim((string)($body['contentUrl'] ?? ''));
    $duration = $body['durationMinutes'] ?? null;
    return [
        'course_id'=>$courseId, 'title'=>$title, 'content_type'=>$type,
        'content_url'=>$url !== '' ? $url : null,
        'duration_minutes'=>$duration === '' || $duration === null ? null : max(0, (int)$duration),
        'is_preview'=>!empty($body['isPreview']) ? 1 : 0,
    ];
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        $courses = $pdo->query("SELECT id, title, status FROM courses ORDER BY title")->fetchAll();
        $courseId = (int)($_GET['course'] ?? 0);
        $lessons = [];
        if ($courseId > 0) {
            $stmt = $pdo->prepare('SELECT id id, course_id, title, sort_order, content_type, content_url, duration_minutes, is_preview FROM lessons WHERE course_id = :course_id ORDER BY sort_order, id');
            $stmt->execute([':course_id'=>$courseId]);
            $lessons = array_map(static fn(array $r): array => [
                'id'=>(string)$r['id'], 'courseId'=>(string)$r['course_id'], 'title'=>$r['title'],
                'sortOrder'=>(int)$r['sort_order'], 'contentType'=>$r['content_type'],
                'contentUrl'=>$r['content_url'], 'durationMinutes'=>$r['duration_minutes'] === null ? null : (int)$r['duration_minutes'],
                'isPreview'=>(bool)$r['is_preview'],
            ], $stmt->fetchAll());
        }
        curriculumResponse(['courses'=>$courses, 'lessons'=>$lessons]);
    }
    if ($method === 'POST') {
        $values = lessonValues(curriculumBody());
        $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM lessons WHERE course_id = :course_id');
        $orderStmt->execute([':course_id'=>$values['course_id']]);
        $values['sort_order'] = (int)$orderStmt->fetchColumn();
        $columns = array_keys($values);
        $params = array_combine(array_map(fn($k)=>':'.$k, $columns), array_values($values));
        $pdo->prepare('INSERT INTO lessons ('.implode(',',$columns).') VALUES (:'.implode(',:',$columns).')')->execute($params);
        curriculumResponse(['success'=>true, 'lessonId'=>(int)$pdo->lastInsertId()], 201);
    }
    if ($method === 'PATCH') {
        $body = curriculumBody();
        $id = (int)($body['id'] ?? 0);
        if ($id < 1) curriculumResponse(['error'=>'ไม่พบรหัสบทเรียน'], 422);
        if (isset($body['direction'])) {
            $pdo->beginTransaction();
            $current = $pdo->prepare('SELECT course_id, sort_order FROM lessons WHERE id = :id FOR UPDATE');
            $current->execute([':id'=>$id]);
            $row = $current->fetch();
            if (!$row) throw new RuntimeException('ไม่พบบทเรียน');
            $operator = $body['direction'] === 'up' ? '<' : '>';
            $order = $body['direction'] === 'up' ? 'DESC' : 'ASC';
            $near = $pdo->prepare("SELECT id, sort_order FROM lessons WHERE course_id = :course_id AND sort_order {$operator} :sort_order ORDER BY sort_order {$order}, id {$order} LIMIT 1");
            $near->execute([':course_id'=>$row['course_id'], ':sort_order'=>$row['sort_order']]);
            if ($other = $near->fetch()) {
                $pdo->prepare('UPDATE lessons SET sort_order = :sort_order WHERE id = :id')->execute([':sort_order'=>$other['sort_order'], ':id'=>$id]);
                $pdo->prepare('UPDATE lessons SET sort_order = :sort_order WHERE id = :id')->execute([':sort_order'=>$row['sort_order'], ':id'=>$other['id']]);
            }
            $pdo->commit();
            curriculumResponse(['success'=>true]);
        }
        $values = lessonValues($body);
        $sets = implode(',', array_map(fn($k)=>$k.'=:'.$k, array_keys($values)));
        $params = array_combine(array_map(fn($k)=>':'.$k, array_keys($values)), array_values($values));
        $params[':id']=$id;
        $pdo->prepare("UPDATE lessons SET {$sets} WHERE id=:id")->execute($params);
        curriculumResponse(['success'=>true]);
    }
    if ($method === 'DELETE') {
        $id=(int)($_GET['id']??0);
        if ($id<1) curriculumResponse(['error'=>'ไม่พบรหัสบทเรียน'],422);
        $pdo->prepare('DELETE FROM lessons WHERE id=:id')->execute([':id'=>$id]);
        curriculumResponse(['success'=>true]);
    }
    curriculumResponse(['error'=>'Method not allowed'],405);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Curriculum API error: '.$error->getMessage());
    curriculumResponse(['error'=>'ระบบบทเรียนขัดข้อง กรุณาลองใหม่'],500);
}
