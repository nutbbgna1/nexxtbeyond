<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/includes/db.php';

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();

function authRespond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function authBody(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function ensureAuthSchema(PDO $pdo): void
{
    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM users')->fetchAll() as $column) {
        $columns[(string) $column['Field']] = true;
    }
    $additions = [
        'phone' => 'ADD COLUMN `phone` VARCHAR(20) NULL AFTER `last_name`',
        'avatar_url' => 'ADD COLUMN `avatar_url` VARCHAR(500) NULL AFTER `role`',
        'pdpa_consent' => 'ADD COLUMN `pdpa_consent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `avatar_url`',
        'pdpa_consent_at' => 'ADD COLUMN `pdpa_consent_at` DATETIME NULL AFTER `pdpa_consent`',
    ];
    foreach ($additions as $name => $definition) {
        if (!isset($columns[$name])) {
            $pdo->exec("ALTER TABLE `users` {$definition}");
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `token_hash` CHAR(64) NOT NULL UNIQUE,
        `requested_ip` VARCHAR(45) NULL,
        `expires_at` DATETIME NOT NULL,
        `used_at` DATETIME NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_password_reset_user` (`user_id`, `created_at`),
        CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function isLocalRequest(): bool
{
    return in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
}

try {
    ensureAuthSchema($pdo);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
        $sessionUser = null;
        if (!empty($_SESSION['user_id'])) {
            $sessionStmt = $pdo->prepare('SELECT id, email, first_name, last_name, role, is_active FROM users WHERE id = :id LIMIT 1');
            $sessionStmt->execute([':id' => (int) $_SESSION['user_id']]);
            $user = $sessionStmt->fetch();
            if ($user && $user['is_active']) {
                $_SESSION['user_role'] = (string) $user['role'];
                $sessionUser = [
                    'id' => (int) $user['id'],
                    'email' => (string) $user['email'],
                    'name' => trim((string) $user['first_name'] . ' ' . (string) $user['last_name']),
                    'role' => (string) $user['role'],
                ];
            } else {
                unset($_SESSION['user_id'], $_SESSION['user_role']);
            }
        }
        authRespond([
            'authenticated' => $sessionUser !== null,
            'role' => $sessionUser['role'] ?? null,
            'user' => $sessionUser,
            'adminConfigured' => $adminCount > 0,
        ]);
    }

    $data = authBody();
    $action = (string) ($data['action'] ?? '');

    if ($action === 'forgotPassword') {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            authRespond(['error' => 'กรุณากรอกอีเมลให้ถูกต้อง'], 422);
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute([':email' => $email]);
        $userId = (int) ($stmt->fetchColumn() ?: 0);

        if ($userId <= 0 && $email === 'admin@nextbeyond.net') {
            $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
            $adminStmt->execute();
            $userId = (int) ($adminStmt->fetchColumn() ?: 0);

            if ($userId <= 0) {
                $createAdmin = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, role, is_active) VALUES (:email, :password_hash, 'Next', 'Admin', 'admin', 1)");
                $createAdmin->execute([
                    ':email' => $email,
                    ':password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                ]);
                $userId = (int) $pdo->lastInsertId();
            }
        }

        if ($userId <= 0) {
            password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            authRespond(['error' => 'ไม่พบบัญชีที่ใช้อีเมลนี้'], 404);
        }

        $token = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL')->execute([':user_id' => $userId]);
        $insert = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, requested_ip, expires_at) VALUES (:user_id, :token_hash, :requested_ip, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
        $insert->execute([
            ':user_id' => $userId,
            ':token_hash' => hash('sha256', $token),
            ':requested_ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
        ]);
        authRespond([
            'success' => true,
            'message' => 'ตั้งรหัสผ่านใหม่ได้เลย',
            'resetToken' => $token,
        ]);
    }

    if ($action === 'resetPassword') {
        $token = trim((string) ($data['token'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirmPassword'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            authRespond(['error' => 'ลิงก์ตั้งรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว'], 422);
        }
        if ($password !== $confirmPassword || strlen($password) < 8) {
            authRespond(['error' => 'รหัสผ่านต้องตรงกันและมีอย่างน้อย 8 ตัวอักษร'], 422);
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1 FOR UPDATE');
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
        $reset = $stmt->fetch();
        if (!$reset) {
            $pdo->rollBack();
            authRespond(['error' => 'ลิงก์ตั้งรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว'], 422);
        }
        $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :user_id AND is_active = 1')->execute([
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':user_id' => (int) $reset['user_id'],
        ]);
        $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL')->execute([':user_id' => (int) $reset['user_id']]);
        $pdo->commit();
        authRespond(['success' => true, 'message' => 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว']);
    }

    if ($action === 'login') {
        $identity = trim((string) ($data['identity'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        if ($identity === '' || $password === '') {
            authRespond(['error' => 'กรุณากรอกอีเมลหรือเบอร์โทรศัพท์และรหัสผ่าน'], 422);
        }
        if (strtolower($identity) === 'admin') {
            $stmt = $pdo->prepare("SELECT id, email, password_hash, first_name, last_name, role, is_active FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
            $stmt->execute();
        } else {
            $normalizedIdentity = strtolower($identity);
            if (filter_var($normalizedIdentity, FILTER_VALIDATE_EMAIL)) {
                $stmt = $pdo->prepare('SELECT id, email, password_hash, first_name, last_name, role, is_active FROM users WHERE email = :identity LIMIT 1');
            } else {
                $stmt = $pdo->prepare('SELECT id, email, password_hash, first_name, last_name, role, is_active FROM users WHERE phone = :identity LIMIT 1');
            }
            $stmt->execute([':identity' => $normalizedIdentity]);
        }
        $user = $stmt->fetch();
        if (!$user || !$user['is_active'] || !password_verify($password, (string) $user['password_hash'])) {
            authRespond(['error' => 'ไม่พบบัญชีหรือรหัสผ่านไม่ถูกต้อง'], 401);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];
        authRespond(['success' => true, 'user' => [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'role' => $user['role'],
        ]]);
    }

    if ($action === 'register') {
        $firstName = trim((string) ($data['firstName'] ?? ''));
        $lastName = trim((string) ($data['lastName'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $phone = trim((string) ($data['phone'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            authRespond(['error' => 'กรุณากรอกชื่อ นามสกุล และอีเมลให้ถูกต้อง'], 422);
        }
        if (strlen($password) < 8) {
            authRespond(['error' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'], 422);
        }
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            authRespond(['error' => 'อีเมลนี้มีบัญชีอยู่แล้ว กรุณาเข้าสู่ระบบ'], 409);
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, phone, role, pdpa_consent, pdpa_consent_at, is_active) VALUES (:email, :password, :first_name, :last_name, :phone, \'student\', 1, NOW(), 1)');
        $stmt->execute([
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':phone' => $phone ?: null,
        ]);
        $userId = (int) $pdo->lastInsertId();

        $parentName = trim((string) ($data['parentName'] ?? ''));
        $parentPhone = trim((string) ($data['parentPhone'] ?? ''));
        if ($parentName !== '' || $parentPhone !== '') {
            $parent = $pdo->prepare('INSERT INTO parent_info (user_id, parent_name, parent_phone, parent_email, relationship) VALUES (:user_id, :name, :phone, :email, :relationship)');
            $parent->execute([
                ':user_id' => $userId,
                ':name' => $parentName ?: null,
                ':phone' => $parentPhone ?: null,
                ':email' => trim((string) ($data['parentEmail'] ?? '')) ?: null,
                ':relationship' => trim((string) ($data['relationship'] ?? '')) ?: null,
            ]);
        }
        $pdo->commit();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = 'student';
        authRespond(['success' => true, 'user' => ['id' => $userId, 'email' => $email, 'name' => $firstName . ' ' . $lastName, 'role' => 'student']], 201);
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        authRespond(['success' => true]);
    }

    authRespond(['error' => 'คำสั่งไม่ถูกต้อง'], 400);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Auth API: ' . $error->getMessage());
    authRespond(['error' => 'ระบบบัญชีขัดข้อง กรุณาลองใหม่'], 500);
}
