<?php
require_once __DIR__ . '/includes/access.php';
$pageTitle='จัดการบทเรียน (Curriculum)'; $pageDesc='จัดการเนื้อหาและลำดับบทเรียนของแต่ละคอร์ส'; $currentPage='curriculum.php';
?>
<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> - Next Beyond Admin</title><link rel="stylesheet" href="../assets/css/output.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="../assets/js/admin-guard.js"></script><script defer src="../assets/js/admin-curriculum.js"></script></head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans antialiased"><div class="min-h-screen flex"><?php include 'includes/sidebar.php'; ?>
<div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[1024px]:ml-0"><?php include 'includes/topbar.php'; ?>
<main class="flex-1 p-8 max-[640px]:p-4">
  <div class="mb-6 flex justify-between gap-4 max-[700px]:flex-col">
    <select id="curriculum-course" class="h-11 px-4 rounded-xl bg-white border border-[#dce4ef] min-w-[360px] max-[700px]:min-w-0"><option value="">กำลังโหลดคอร์ส...</option></select>
    <div class="flex gap-3"><a id="student-preview" href="#" target="_blank" class="hidden h-11 px-5 rounded-xl bg-white border border-[#dce4ef] text-[14px] font-bold items-center">ดูเนื้อหา</a><button id="add-lesson" disabled class="h-11 px-5 rounded-xl bg-pink-500 disabled:bg-[#cbd5e1] text-white text-[14px] font-bold">＋ เพิ่มบทเรียน</button></div>
  </div>
  <div id="no-courses" class="hidden bg-white rounded-[20px] border border-[#e8ecf2] p-12 text-center"><h2 class="font-bold">ยังไม่มีคอร์สเรียน</h2><p class="text-[#65738a] text-[13px] mt-1 mb-5">สร้างคอร์สก่อนจึงจะเพิ่มบทเรียนได้</p><a href="courses.php" class="inline-flex h-10 px-5 items-center bg-pink-500 text-white rounded-xl font-bold">ไปหน้าคอร์สเรียน</a></div>
  <div id="curriculum-workspace" class="grid grid-cols-[360px_1fr] gap-6 max-[1024px]:grid-cols-1 items-start">
    <section class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden"><div class="px-5 py-4 border-b"><h3 class="font-bold text-[14px]">ลำดับบทเรียน</h3></div><div id="lessons-list" class="p-3 space-y-2"><div class="p-8 text-center text-[#65738a]">เลือกคอร์สเพื่อดูบทเรียน</div></div></section>
    <section id="lesson-editor" class="hidden bg-white rounded-[20px] border border-[#e8ecf2] p-7">
      <h2 id="editor-title" class="text-[20px] font-bold mb-6">เพิ่มบทเรียน</h2>
      <form id="lesson-form"><input name="id" type="hidden">
        <div class="space-y-5">
          <div><label class="label">ชื่อบทเรียน *</label><input name="title" required class="field"></div>
          <div class="grid grid-cols-2 gap-5 max-[640px]:grid-cols-1">
            <div><label class="label">ประเภทเนื้อหา</label><select name="contentType" class="field"><option value="video">วิดีโอ</option><option value="document">เอกสาร</option><option value="quiz">แบบทดสอบ</option><option value="live">เรียนสด</option></select></div>
            <div><label class="label">ระยะเวลา (นาที)</label><input name="durationMinutes" type="number" min="0" class="field"></div>
          </div>
          <div><label class="label">URL เนื้อหา</label><input name="contentUrl" type="url" class="field" placeholder="https://..."><p class="text-[11px] text-[#94a3b8] mt-1">ใส่ลิงก์วิดีโอ เอกสาร ห้องเรียนสด หรือหน้าแบบทดสอบ</p></div>
          <label class="flex gap-2 text-[13px] font-bold"><input name="isPreview" type="checkbox"> อนุญาตให้ผู้เรียนดูตัวอย่างฟรี</label>
        </div>
        <div id="lesson-error" class="hidden mt-4 p-3 bg-red-50 text-red-600 rounded-xl text-[13px] font-bold"></div>
        <div class="flex justify-between mt-7 pt-5 border-t"><button id="delete-lesson" type="button" class="hidden h-10 px-4 text-red-500 font-bold">ลบบทเรียน</button><div class="ml-auto flex gap-3"><button id="cancel-editor" type="button" class="h-10 px-5 rounded-xl border font-bold">ยกเลิก</button><button id="save-lesson" class="h-10 px-6 rounded-xl bg-pink-500 text-white font-bold">บันทึกบทเรียน</button></div></div>
      </form>
    </section>
  </div>
</main></div></div>
<style>.label{display:block;font-size:13px;font-weight:700;color:#65738a;margin-bottom:8px}.field{width:100%;height:44px;padding:0 14px;border:1px solid #dce4ef;border-radius:12px;outline:none}.field:focus{border-color:#f54696}</style>
</body></html>
