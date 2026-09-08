<?php
require_once __DIR__ . '/includes/access.php';
$pageTitle = 'ภาพรวม (Dashboard)';
$pageDesc = 'ภาพรวมข้อมูลและกิจกรรมล่าสุดของระบบ';
$currentPage = 'index.php';
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> - Next Beyond Admin</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="../assets/js/admin-guard.js"></script>
</head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans antialiased">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[960px]:ml-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="flex-1 p-8 max-[640px]:p-4">
      <div class="grid grid-cols-4 gap-6 mb-8 max-[1200px]:grid-cols-2 max-[640px]:grid-cols-1">
        <?php foreach (['ยอดขายเดือนนี้', 'นักเรียนใหม่', 'คอร์สที่กำลัง Active', 'งานรอตรวจ / Feedback'] as $label): ?>
          <div class="bg-white rounded-[20px] p-6 border border-[#e8ecf2]">
            <div class="text-[#65738a] text-[13px] font-bold mb-2"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-[32px] font-black text-[#aebbd0] leading-none">—</div>
            <div class="text-[12px] text-[#94a3b8] mt-3">ยังไม่มีข้อมูลจริง</div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="grid grid-cols-[1fr_340px] gap-6 max-[1200px]:grid-cols-1 items-start">
        <section class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
          <div class="px-6 py-5 border-b border-[#e8ecf2]"><h3 class="text-[16px] font-bold">Task Queue (งานที่ต้องทำ)</h3></div>
          <div class="p-12 text-center"><div class="text-[15px] font-bold text-[#65738a]">ยังไม่มีงานที่ต้องทำ</div><p class="text-[13px] text-[#94a3b8] mt-1">รายการจากระบบจริงจะแสดงที่นี่</p></div>
        </section>
        <aside class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
          <div class="px-6 py-5 border-b border-[#e8ecf2]"><h3 class="text-[16px] font-bold">กิจกรรมล่าสุด</h3></div>
          <div class="p-10 text-center"><div class="text-[14px] font-bold text-[#65738a]">ยังไม่มีกิจกรรม</div><p class="text-[12px] text-[#94a3b8] mt-1">กิจกรรมจริงจะแสดงหลังเริ่มใช้งานระบบ</p></div>
        </aside>
      </div>
    </main>
  </div>
</div>
</body>
</html>
