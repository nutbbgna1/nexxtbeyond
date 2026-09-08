<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../includes/db.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(404); exit; }
$data = json_decode(file_get_contents('php://input'), true);
$token = (string)($data['token'] ?? '');
if (!hash_equals('a286dceb450b0d65a8d4030a7954c443e876d5c893a92e2357318b4a8e798219', hash('sha256', $token))) { http_response_code(403); exit; }
$password = (string)($data['password'] ?? '');
if (strlen($password) < 14) { http_response_code(422); exit; }
$email = 'admin@nextbeyond.com';
$stmt = $pdo->prepare("SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1");
$stmt->execute();
$id = $stmt->fetchColumn();
if ($id) {
    $update = $pdo->prepare('UPDATE users SET password_hash=:hash,is_active=1 WHERE id=:id');
    $update->execute([':hash'=>password_hash($password,PASSWORD_DEFAULT),':id'=>$id]);
} else {
    $insert = $pdo->prepare("INSERT INTO users(email,password_hash,first_name,last_name,role,is_active) VALUES(:email,:hash,'Admin','Next Beyond','admin',1)");
    $insert->execute([':email'=>$email,':hash'=>password_hash($password,PASSWORD_DEFAULT)]);
    $id = $pdo->lastInsertId();
}
echo json_encode(['success'=>true,'id'=>(int)$id,'email'=>$email]);
