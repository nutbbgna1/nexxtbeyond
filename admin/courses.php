<?php
$pageTitle = 'จัดการคอร์สเรียน';
$pageDesc = 'สร้าง แก้ไข และเผยแพร่คอร์สเรียน';
$currentPage = 'courses.php';
?>
<!DOCTYPE html><html lang="th"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> - Next Beyond Admin</title>
<link rel="stylesheet" href="../assets/css/output.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="../assets/js/admin-guard.js"></script><script defer src="../assets/js/admin-courses.js"></script>
</head><body class="bg-[#f4f7fb] text-navy-950 font-sans antialiased">
<div class="min-h-screen flex"><?php include 'includes/sidebar.php'; ?>
<div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[960px]:ml-0"><?php include 'includes/topbar.php'; ?>
<main class="flex-1 p-8 max-[640px]:p-4">
  <div class="mb-6 flex justify-between gap-4 max-[700px]:flex-col">
    <button id="add-course" class="h-11 px-5 rounded-xl bg-pink-500 text-white text-[14px] font-bold shadow-[0_4px_12px_rgba(231,45,130,.3)]">＋ สร้างคอร์สใหม่</button>
    <div class="flex gap-2 max-[700px]:flex-col">
      <input id="courses-search" type="search" placeholder="ค้นหาชื่อคอร์ส..." class="h-11 min-w-[240px] px-4 rounded-xl bg-white border border-[#dce4ef] outline-none focus:border-pink-500">
      <select id="courses-subject" class="h-11 px-3 rounded-xl bg-white border border-[#dce4ef]"><option value="">ทุกวิชา</option></select>
      <select id="courses-status" class="h-11 px-3 rounded-xl bg-white border border-[#dce4ef]"><option value="">ทุกสถานะ</option><option value="active">เผยแพร่แล้ว</option><option value="draft">แบบร่าง</option><option value="archived">เก็บถาวร</option></select>
    </div>
  </div>
  <div class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden"><div class="overflow-x-auto">
    <table class="w-full text-left min-w-[900px]"><thead><tr class="bg-[#f8fafc] border-b border-[#e8ecf2]">
      <th class="px-5 py-3 text-[12px] font-black text-[#65738a] uppercase">ชื่อคอร์ส</th><th class="px-4 py-3 text-[12px] font-black text-[#65738a] uppercase">วิชา / ครู</th><th class="px-4 py-3 text-[12px] font-black text-[#65738a] uppercase">ราคา</th><th class="px-4 py-3 text-[12px] font-black text-[#65738a] uppercase text-center">นักเรียน</th><th class="px-4 py-3 text-[12px] font-black text-[#65738a] uppercase">สถานะ</th><th></th>
    </tr></thead><tbody id="courses-tbody" class="divide-y divide-[#e8ecf2]"><tr><td colspan="6" class="p-10 text-center text-[#65738a]">กำลังโหลดข้อมูล...</td></tr></tbody></table>
  </div><div id="courses-count" class="p-4 border-t border-[#e8ecf2] text-[13px] text-[#65738a]">0 รายการ</div></div>
</main></div></div>

<div id="course-modal" class="hidden fixed inset-0 z-50 bg-navy-950/50 p-4 items-center justify-center overflow-y-auto">
<div class="w-full max-w-[720px] bg-white rounded-[22px] my-auto">
  <div class="px-6 py-5 border-b border-[#e8ecf2] flex justify-between"><h2 id="course-modal-title" class="text-[18px] font-bold">สร้างคอร์สใหม่</h2><button type="button" data-close-modal class="text-[24px] text-[#94a3b8]">×</button></div>
  <form id="course-form" class="p-6"><input type="hidden" name="id">
    <div class="grid grid-cols-2 gap-4 max-[640px]:grid-cols-1">
      <div class="col-span-2 max-[640px]:col-span-1"><label class="block text-[13px] font-bold mb-1.5">ชื่อคอร์ส *</label><input name="title" required class="field"></div>
      <div><label class="block text-[13px] font-bold mb-1.5">วิชา</label><input name="subject" class="field" placeholder="เช่น ภาษาอังกฤษ"></div>
      <div><label class="block text-[13px] font-bold mb-1.5">ระดับ</label><input name="level" class="field" placeholder="เช่น ป.4-ป.6"></div>
      <div><label class="block text-[13px] font-bold mb-1.5">ครูผู้สอน</label><select name="teacherId" id="course-teacher" class="field"><option value="">ยังไม่กำหนด</option></select></div>
      <div><label class="block text-[13px] font-bold mb-1.5">สถานะ</label><select name="status" class="field"><option value="draft">แบบร่าง</option><option value="active">เผยแพร่</option><option value="archived">เก็บถาวร</option></select></div>
      <div><label class="block text-[13px] font-bold mb-1.5">ราคา</label><input name="price" type="number" min="0" step="0.01" value="0" class="field"></div>
      <div><label class="block text-[13px] font-bold mb-1.5">ราคาเดิม</label><input name="originalPrice" type="number" min="0" step="0.01" class="field"></div>
      <div><label class="block text-[13px] font-bold mb-1.5">จำนวนชั่วโมง</label><input name="durationHours" type="number" min="0" class="field"></div>
      <div><label class="block text-[13px] font-bold mb-1.5">จำนวนสัปดาห์</label><input name="durationWeeks" type="number" min="0" class="field"></div>
      <div><label class="block text-[13px] font-bold mb-1.5">รับนักเรียนสูงสุด</label><input name="maxStudents" type="number" min="1" class="field"></div>
      <div><label class="block text-[13px] font-bold mb-1.5">URL รูปปก</label><input name="coverImage" type="url" class="field"></div>
      <div class="col-span-2 max-[640px]:col-span-1"><label class="block text-[13px] font-bold mb-1.5">รายละเอียด</label><textarea name="description" rows="4" class="field h-auto py-3"></textarea></div>
      <label class="col-span-2 max-[640px]:col-span-1 flex gap-2 text-[13px] font-bold"><input name="isFree" type="checkbox"> คอร์สฟรี</label>
    </div>
    <div id="course-error" class="hidden mt-4 p-3 rounded-xl bg-red-50 text-red-600 text-[13px] font-bold"></div>
    <div class="flex justify-end gap-3 mt-6 pt-5 border-t"><button type="button" data-close-modal class="h-10 px-5 rounded-xl border font-bold">ยกเลิก</button><button id="save-course" class="h-10 px-6 rounded-xl bg-pink-500 text-white font-bold">บันทึกคอร์ส</button></div>
  </form>
</div></div>
<style>.field{width:100%;height:44px;padding:0 14px;border:1px solid #dce4ef;border-radius:12px;outline:none}.field:focus{border-color:#f54696}</style>
</body></html>
