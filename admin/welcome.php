<?php
require_once __DIR__ . '/includes/access.php';
$pageTitle = 'Teacher';
$currentPage = 'welcome.php';
?>
<!DOCTYPE html>
<html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Teacher - Next Beyond</title><link rel="stylesheet" href="../assets/css/output.css"></head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="ml-[240px] max-[960px]:ml-0">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<main class="p-8"><h1 class="text-[20px] font-bold">เมนูของฉัน</h1>
<?php $hasMenu = false; foreach (teacherMenuSettings() as [$label, $pages]): if (!consoleAllowed($pages[0])) continue; $hasMenu = true; ?>
<a class="block py-4 border-b" href="<?= $pages[0] ?>"><?= htmlspecialchars($label) ?></a>
<?php endforeach; if (!$hasMenu): ?><p class="mt-4">ยังไม่ได้รับสิทธิ์ใช้งานเมนู กรุณาติดต่อ Admin</p><?php endif; ?>
</main></div></body></html>
