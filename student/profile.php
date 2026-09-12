<?php
$pageTitle = 'โปรไฟล์ของฉัน';
$currentPage = 'profile.php';
require_once __DIR__ . '/includes/guard.php';
$message = '';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $first = trim((string) ($_POST['first_name'] ?? ''));
    $last = trim((string) ($_POST['last_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    try {
        if ($first === '' || $last === '') throw new RuntimeException('กรุณากรอกชื่อและนามสกุล');
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE users SET first_name=:first,last_name=:last,phone=:phone WHERE id=:id');
        $stmt->execute([':first'=>$first,':last'=>$last,':phone'=>$phone?:null,':id'=>$currentUser['id']]);
        $newPassword = (string) ($_POST['new_password'] ?? '');
        if ($newPassword !== '') {
            $oldPassword = (string) ($_POST['old_password'] ?? '');
            $check = $pdo->prepare('SELECT password_hash FROM users WHERE id=:id');
            $check->execute([':id'=>$currentUser['id']]);
            if (!password_verify($oldPassword, (string)$check->fetchColumn())) throw new RuntimeException('รหัสผ่านเดิมไม่ถูกต้อง');
            if (strlen($newPassword) < 8) throw new RuntimeException('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร');
            $update = $pdo->prepare('UPDATE users SET password_hash=:hash WHERE id=:id');
            $update->execute([':hash'=>password_hash($newPassword,PASSWORD_DEFAULT),':id'=>$currentUser['id']]);
        }
        $pdo->commit();
        $message = 'บันทึกโปรไฟล์แล้ว';
        $currentUser['first_name']=$first; $currentUser['last_name']=$last; $currentUser['phone']=$phone;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($pageTitle) ?> - Next Beyond</title><link rel="stylesheet" href="../assets/css/output.css?v=<?= filemtime(__DIR__ . '/../assets/css/output.css') ?>"></head><body class="bg-[#f4f7fb] text-navy-950"><div class="min-h-screen flex"><?php include 'includes/sidebar.php'; ?><div class="flex-1 flex flex-col ml-[240px] max-[960px]:ml-0"><?php include 'includes/topbar.php'; ?><main class="p-8 max-[640px]:p-4 max-w-[900px]">
<?php if($message): ?><div class="mb-4 p-4 rounded-xl bg-green-50 text-green-700 font-bold"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if($error): ?><div class="mb-4 p-4 rounded-xl bg-red-50 text-red-700 font-bold"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="space-y-5"><section class="bg-white rounded-[20px] border border-[#e8ecf2] p-6"><h2 class="font-bold text-[17px] mb-5">ข้อมูลส่วนตัว</h2><div class="grid grid-cols-2 gap-4 max-[640px]:grid-cols-1"><label class="text-[13px] font-bold">ชื่อ<input name="first_name" value="<?= htmlspecialchars($currentUser['first_name']) ?>" required class="mt-2 w-full h-11 px-4 border rounded-xl"></label><label class="text-[13px] font-bold">นามสกุล<input name="last_name" value="<?= htmlspecialchars($currentUser['last_name']) ?>" required class="mt-2 w-full h-11 px-4 border rounded-xl"></label><label class="text-[13px] font-bold">อีเมล<input value="<?= htmlspecialchars($currentUser['email']) ?>" disabled class="mt-2 w-full h-11 px-4 border rounded-xl bg-[#f8fafc]"></label><label class="text-[13px] font-bold">เบอร์โทร<input name="phone" value="<?= htmlspecialchars($currentUser['phone']??'') ?>" class="mt-2 w-full h-11 px-4 border rounded-xl"></label></div></section>
<section class="bg-white rounded-[20px] border border-[#e8ecf2] p-6"><h2 class="font-bold text-[17px] mb-5">เปลี่ยนรหัสผ่าน</h2><div class="grid grid-cols-2 gap-4 max-[640px]:grid-cols-1"><input type="password" name="old_password" placeholder="รหัสผ่านเดิม" class="h-11 px-4 border rounded-xl"><input type="password" name="new_password" placeholder="รหัสผ่านใหม่ อย่างน้อย 8 ตัว" class="h-11 px-4 border rounded-xl"></div></section><button class="h-11 px-7 rounded-xl bg-pink-500 text-white font-bold">บันทึกการเปลี่ยนแปลง</button></form>
</main>
<?php include 'includes/bottom-nav.php'; ?>
</div></div></body></html>
