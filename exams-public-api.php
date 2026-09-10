<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/includes/db.php';

try {
    $stmt = $pdo->query(
        "SELECT id, title, subject, grade, type, time_limit_minutes,
                is_ai_generated, status, requires_login
         FROM exams
         WHERE is_published = 1 AND status IN ('active', 'closed')
         ORDER BY updated_at DESC, created_at DESC, id DESC"
    );
    $questionStmt = $pdo->prepare(
        'SELECT id, sort_order, question_text, image_url, options, correct_answer
         FROM exam_questions WHERE exam_id = :exam_id ORDER BY sort_order, id'
    );
    $exams = [];
    foreach ($stmt->fetchAll() as $exam) {
        $questionStmt->execute([':exam_id' => $exam['id']]);
        $questions = array_map(static function (array $row): array {
            $options = json_decode((string) $row['options'], true);
            return [
                'id' => (string) $row['id'],
                'sortOrder' => (int) $row['sort_order'],
                'questionText' => $row['question_text'],
                'imageUrl' => $row['image_url'],
                'options' => is_array($options) ? $options : [],
                'correctAnswer' => (int) $row['correct_answer'],
            ];
        }, $questionStmt->fetchAll());
        $exams[] = [
            'id' => (string) $exam['id'],
            'title' => $exam['title'],
            'subject' => $exam['subject'],
            'grade' => $exam['grade'],
            'type' => $exam['type'],
            'timeLimitMinutes' => $exam['time_limit_minutes'] === null ? null : (int) $exam['time_limit_minutes'],
            'isAiGenerated' => (bool) $exam['is_ai_generated'],
            'status' => $exam['status'],
            'requiresLogin' => (bool) $exam['requires_login'],
            'questions' => $questions,
        ];
    }
    echo json_encode(['exams' => $exams], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Public exam API error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถโหลดแบบทดสอบได้'], JSON_UNESCAPED_UNICODE);
}
