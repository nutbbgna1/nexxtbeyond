<?php
$pageTitle = 'คอร์สของฉัน';
$currentPage = 'my-courses.php';
require_once __DIR__ . '/includes/guard.php';
$status = (string) ($_GET['status'] ?? '');
$allowed = ['active', 'completed', 'expired'];
$where = in_array($status, $allowed, true) ? ' AND en.status = :status' : '';
$stmt = $pdo->prepare("SELECT en.progress_percent, en.status, en.enrolled_at, en.expires_at, c.id, c.title, c.subject, c.level, c.cover_image FROM enrollments en INNER JOIN courses c ON c.id=en.course_id WHERE en.user_id=:uid{$where} ORDER BY en.enrolled_at DESC");
$params = [':uid' => $currentUser['id']];
if ($where) $params[':status'] = $status;
$stmt->execute($params);
$courses = $stmt->fetchAll();
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($pageTitle) ?> - Next Beyond</title><link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="../assets/css/student-portal.css?v=<?= filemtime(__DIR__ . '/../assets/css/student-portal.css') ?>"></head>
<body class="student-portal bg-[#f4f7fb] text-navy-950"><div class="min-h-screen flex"><?php include 'includes/sidebar.php'; ?><div class="flex-1 ml-[240px] max-[960px]:ml-0"><?php include 'includes/topbar.php'; ?><main class="p-8 max-[640px]:p-4">
<div class="flex gap-2 mb-6 flex-wrap"><?php foreach ([''=>'ทั้งหมด','active'=>'กำลังเรียน','completed'=>'เรียนจบ','expired'=>'หมดอายุ'] as $key=>$label): ?><a href="?status=<?= $key ?>" class="px-4 py-2 rounded-xl text-[13px] font-bold <?= $status===$key?'bg-pink-500 text-white':'bg-white border border-[#dce4ef]' ?>"><?= $label ?></a><?php endforeach; ?></div>
<?php if (!$courses): ?><div class="bg-white border border-[#e8ecf2] rounded-[20px] p-14 text-center"><h2 class="font-bold mb-2">ยังไม่มีคอร์สในรายการ</h2><a href="../courses" class="text-pink-500 font-bold">ดูคอร์สทั้งหมด →</a></div><?php else: ?><div class="grid grid-cols-3 gap-5 max-[1100px]:grid-cols-2 max-[680px]:grid-cols-1"><?php foreach ($courses as $course): $progress=min(100,(float)$course['progress_percent']); ?><article class="bg-white border border-[#e8ecf2] rounded-[18px] p-5"><span class="text-[11px] font-bold text-pink-500"><?= htmlspecialchars($course['subject']??'คอร์สเรียน') ?></span><h2 class="font-bold text-[16px] mt-2 mb-4"><?= htmlspecialchars($course['title']) ?></h2><div class="h-2 rounded-full bg-[#edf2f7] overflow-hidden"><div class="h-full bg-pink-500" style="width:<?= $progress ?>%"></div></div><div class="flex justify-between text-[12px] text-[#65738a] mt-2"><span><?= round($progress) ?>%</span><span><?= htmlspecialchars($course['status']) ?></span></div><a href="../course-details?id=<?= (int)$course['id'] ?>" class="mt-5 h-10 rounded-xl bg-navy-950 text-white flex items-center justify-center text-[13px] font-bold">เรียนต่อ</a></article><?php endforeach; ?></div><?php endif; ?>
</main></div></div></body></html>
