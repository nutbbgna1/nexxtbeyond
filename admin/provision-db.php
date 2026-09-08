<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$token = (string) ($input['token'] ?? '');
$expectedHash = '2334273d0a1b76341652c65b122c8ba407af23788186feefddae6f6aea5d9626';
if (!hash_equals($expectedHash, hash('sha256', $token))) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$config = $input['config'] ?? null;
$required = ['host', 'name', 'user', 'pass', 'charset'];
if (!is_array($config)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid configuration']);
    exit;
}
foreach ($required as $field) {
    if (!isset($config[$field]) || !is_string($config[$field])) {
        http_response_code(422);
        echo json_encode(['error' => 'Missing configuration']);
        exit;
    }
}

try {
    $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['name'] . ';charset=' . $config['charset'];
    new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $contents = "<?php\n"
        . "define('ADMIN_DB_HOST', " . var_export($config['host'], true) . ");\n"
        . "define('ADMIN_DB_NAME', " . var_export($config['name'], true) . ");\n"
        . "define('ADMIN_DB_USER', " . var_export($config['user'], true) . ");\n"
        . "define('ADMIN_DB_PASS', " . var_export($config['pass'], true) . ");\n"
        . "define('ADMIN_DB_CHARSET', " . var_export($config['charset'], true) . ");\n";

    $target = dirname(__DIR__) . '/includes/db.local.php';
    if (file_put_contents($target, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Cannot write configuration');
    }
    @chmod($target, 0600);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('Database provisioning failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Provisioning failed']);
}
