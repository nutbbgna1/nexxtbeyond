<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/includes/access.php';
require_once __DIR__ . '/../includes/ai-settings.php';
require_once __DIR__ . '/../includes/ai-image-service.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new InvalidArgumentException('Method not allowed');
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) throw new InvalidArgumentException('ข้อมูลใหญ่เกินกำหนด');
    $body = json_decode(file_get_contents('php://input'), true, 64, JSON_THROW_ON_ERROR);
    $key = aiSettingsGetKey($pdo);
    if ($key === '') throw new InvalidArgumentException('กรุณาตั้งค่า Gemini API Key');
    $result = AiImageService::generate($key, (string) ($body['prompt'] ?? ''), is_array($body['context'] ?? null) ? $body['context'] : []);
    echo json_encode(['success' => true, 'image' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException | JsonException $error) {
    http_response_code(422); echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('AI image: ' . $error->getMessage());
    http_response_code(502); echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
}
