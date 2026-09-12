<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/access.php';

function calcRespond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM calculator_tracks ORDER BY sort_order ASC, id ASC");
        calcRespond(['tracks' => $stmt->fetchAll()]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) calcRespond(['error' => 'Invalid ID'], 400);
        $stmt = $pdo->prepare("DELETE FROM calculator_tracks WHERE id = ?");
        $stmt->execute([$id]);
        calcRespond(['success' => true]);
    }

    if ($method === 'POST' || $method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) calcRespond(['error' => 'Invalid JSON payload'], 400);

        $name = trim((string) ($body['name'] ?? ''));
        $icon = trim((string) ($body['icon'] ?? ''));
        $description = trim((string) ($body['description'] ?? ''));
        $min_score = (float) ($body['min_score'] ?? 58.0);
        $sort_order = (int) ($body['sort_order'] ?? 0);
        $is_active = !empty($body['is_active']) ? 1 : 0;
        $weights = $body['weights'] ?? '{}';
        $id = !empty($body['id']) ? (int) $body['id'] : 0;

        if ($name === '') calcRespond(['error' => 'ชื่อกลุ่มคณะห้ามว่าง'], 422);
        
        // validate weights
        $wArr = is_string($weights) ? json_decode($weights, true) : $weights;
        if (!is_array($wArr)) calcRespond(['error' => 'รูปแบบค่าน้ำหนักไม่ถูกต้อง'], 422);
        $sum = 0;
        foreach ($wArr as $k => $v) {
            $val = (float) $v;
            if ($val <= 0) unset($wArr[$k]);
            else {
                $wArr[$k] = $val;
                $sum += $val;
            }
        }
        if (abs($sum - 1.0) > 0.01) {
            calcRespond(['error' => 'ผลรวมของค่าน้ำหนักต้องเท่ากับ 1.0'], 422);
        }
        $weightsJson = json_encode($wArr);

        if ($method === 'POST' || ($method === 'PUT' && $id <= 0)) {
            $stmt = $pdo->prepare("INSERT INTO calculator_tracks (name, icon, description, weights, min_score, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $icon, $description, $weightsJson, $min_score, $is_active, $sort_order]);
        } else {
            $stmt = $pdo->prepare("UPDATE calculator_tracks SET name=?, icon=?, description=?, weights=?, min_score=?, is_active=?, sort_order=? WHERE id=?");
            $stmt->execute([$name, $icon, $description, $weightsJson, $min_score, $is_active, $sort_order, $id]);
        }
        calcRespond(['success' => true]);
    }

    calcRespond(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    error_log('Calculator API: ' . $e->getMessage());
    calcRespond(['error' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'], 500);
}
