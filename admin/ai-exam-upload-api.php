<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/includes/access.php';

function uploadRespond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') uploadRespond(['error' => 'Method not allowed'], 405);
if (empty($_FILES['document']) || !is_uploaded_file($_FILES['document']['tmp_name'])) {
    uploadRespond(['error' => 'กรุณาเลือกเอกสารที่ต้องการอัปโหลด'], 422);
}

$file = $_FILES['document'];
if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) uploadRespond(['error' => 'อัปโหลดไฟล์ไม่สำเร็จ'], 422);
if ((int) $file['size'] <= 0 || (int) $file['size'] > 15 * 1024 * 1024) {
    uploadRespond(['error' => 'ไฟล์ต้องมีขนาดไม่เกิน 15 MB'], 422);
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$allowed = [
    'application/pdf' => 'pdf',
    'text/plain' => 'txt',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/zip' => 'docx',
    'application/octet-stream' => 'docx',
];
$originalExtension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
if (!isset($allowed[$mime]) || ($allowed[$mime] === 'docx' && $originalExtension !== 'docx')) {
    uploadRespond(['error' => 'รองรับเฉพาะไฟล์ PDF, TXT และ DOCX'], 422);
}

$uploadDir = __DIR__ . '/../assets/uploads/ai-exam';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    uploadRespond(['error' => 'สร้างพื้นที่จัดเก็บไฟล์ไม่สำเร็จ'], 500);
}

$extension = $allowed[$mime];
$filename = 'source-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
    uploadRespond(['error' => 'บันทึกไฟล์บนเซิร์ฟเวอร์ไม่สำเร็จ'], 500);
}

uploadRespond([
    'success' => true,
    'storedFile' => $filename,
    'originalName' => basename((string) $file['name']),
    'size' => (int) $file['size'],
    'url' => '/assets/uploads/ai-exam/' . $filename,
]);
