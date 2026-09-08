<?php
$pageTitle = 'แบบทดสอบ (Tests)';
$pageDesc = 'จัดการแบบทดสอบ Placement Test, Quiz, และ Post-test';
$currentPage = 'tests.php';
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Next Beyond Admin</title>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="../assets/js/admin-guard.js"></script>
    <script defer src="../assets/js/admin-tests.js?v=<?= rawurlencode((string) filemtime(__DIR__ . '/../assets/js/admin-tests.js')) ?>"></script>
</head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans antialiased selection:bg-pink-500/20 selection:text-pink-600">

  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[960px]:ml-0 transition-all duration-300">
      <!-- Topbar -->
      <?php include 'includes/topbar.php'; ?>

      <!-- Content -->
      <main class="flex-1 p-8 max-[640px]:p-4 overflow-y-auto">
        <div id="tests-created-notice" class="hidden mb-5 rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-[#166534] font-bold text-[14px]"></div>
        <!-- Actions & Filters -->
        <div class="mb-6 flex items-center justify-between gap-4 max-[640px]:flex-col max-[640px]:items-stretch">
          <div class="flex items-center gap-3">
            <a href="ai-exam.php" class="h-10 px-5 rounded-xl bg-pink-500 text-white text-[14px] font-bold shadow-[0_4px_12px_rgba(231,45,130,0.3)] hover:-translate-y-0.5 transition-all flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              สร้างแบบทดสอบใหม่
            </a>
          </div>
          
          <div class="flex items-center gap-3 bg-white p-1 rounded-[14px] border border-[#dce4ef] shadow-sm">
            <div class="relative min-w-[240px] max-[640px]:min-w-0 max-[640px]:flex-1">
              <input id="tests-search" type="search" placeholder="ค้นหาชื่อแบบทดสอบ..." class="w-full h-9 pl-10 pr-4 rounded-lg bg-transparent border-none outline-none text-[14px] text-navy-950 placeholder:text-[#94a3b8]">
              <svg class="w-4 h-4 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="w-[1px] h-6 bg-[#e8ecf2]"></div>
            <select id="tests-type-filter" class="h-9 px-3 rounded-lg bg-transparent border-none outline-none text-[13px] font-medium text-[#65738a] cursor-pointer">
              <option value="">ทุกประเภท</option>
              <option value="placement">Placement Test</option>
              <option value="pretest">Pre-test</option>
              <option value="quiz">Quiz</option>
              <option value="posttest">Post-test</option>
            </select>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-[20px] shadow-[0_4px_24px_rgba(15,42,83,0.03)] border border-[#e8ecf2] overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
              <thead>
                <tr class="bg-[#f8fafc] border-b border-[#e8ecf2]">
                  <th class="px-4 py-3 text-[12px] font-black tracking-wider text-[#65738a] uppercase">ชื่อแบบทดสอบ</th>
                  <th class="px-4 py-3 text-[12px] font-black tracking-wider text-[#65738a] uppercase">วิชา / ระดับ</th>
                  <th class="px-4 py-3 text-[12px] font-black tracking-wider text-[#65738a] uppercase text-center">จำนวนข้อ</th>
                  <th class="px-4 py-3 text-[12px] font-black tracking-wider text-[#65738a] uppercase text-center">ผู้ทำ (ครั้ง)</th>
                  <th class="px-4 py-3 text-[12px] font-black tracking-wider text-[#65738a] uppercase">เผยแพร่ / สิทธิ์</th>
                  <th class="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody id="admin-tests-tbody" class="divide-y divide-[#e8ecf2]">
                <!-- Rendered by JS -->
              </tbody>
            </table>
          </div>
          <div class="p-4 border-t border-[#e8ecf2] flex items-center justify-between max-[640px]:flex-col max-[640px]:gap-4">
            <div id="tests-count" class="text-[13px] text-[#65738a]">0 รายการ</div>
          </div>
        </div>
      </main>
    </div>
  </div>

</body>
</html>
