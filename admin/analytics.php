<?php
require_once __DIR__ . '/includes/access.php';
$pageTitle = 'Analytics และรายงาน';
$pageDesc = 'รายงานจากข้อมูลการใช้งานจริงในระบบ';
$currentPage = 'analytics.php';
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
  <div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[1024px]:ml-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="flex-1 p-8 max-[640px]:p-4">
      <div class="grid grid-cols-2 gap-6 mb-6 max-[1024px]:grid-cols-1">
        <?php foreach (['ยอดขาย', 'สัดส่วนนักเรียนตามระดับชั้น'] as $title): ?>
          <section class="bg-white rounded-[20px] p-6 border border-[#e8ecf2]">
            <h3 class="text-[16px] font-bold mb-4"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="h-[250px] rounded-xl bg-[#f8fafc] border border-dashed border-[#dce4ef] flex flex-col items-center justify-center text-center px-6">
              <div class="text-[14px] font-bold text-[#65738a]">ยังไม่มีข้อมูลสำหรับสร้างกราฟ</div>
              <p class="text-[12px] text-[#94a3b8] mt-1">กราฟจะแสดงเมื่อเชื่อมข้อมูลจริงแล้ว</p>
            </div>
          </section>
        <?php endforeach; ?>
      </div>
      <div class="grid grid-cols-3 gap-6 max-[1024px]:grid-cols-1">
        <section class="col-span-2 max-[1024px]:col-span-1 bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
          <div class="px-6 py-5 border-b border-[#e8ecf2]"><h3 class="text-[16px] font-bold">คอร์สยอดนิยม</h3></div>
          <div class="p-12 text-center"><div class="text-[14px] font-bold text-[#65738a]">ยังไม่มีข้อมูลคอร์ส</div><p class="text-[12px] text-[#94a3b8] mt-1">อันดับจะคำนวณจากข้อมูลการสมัครเรียนจริง</p></div>
        </section>
        <section class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
          <div class="px-6 py-5 border-b border-[#e8ecf2]"><h3 class="text-[16px] font-bold">สถิติแบบทดสอบ</h3></div>
          <div class="p-10 text-center"><div class="text-[14px] font-bold text-[#65738a]">ยังไม่มีผลการทดสอบ</div><p class="text-[12px] text-[#94a3b8] mt-1">คะแนนจริงจะแสดงเมื่อมีผู้ส่งคำตอบ</p></div>
        </section>
      </div>
    </main>
  </div>
</div>
</body>
</html>
