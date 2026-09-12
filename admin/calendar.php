<?php
require_once __DIR__ . '/includes/access.php';
$pageTitle = 'ปฏิทินและตารางสอน';
$pageDesc = 'จัดตารางเรียน สอบ และนัดหมายของทีมผู้สอน';
$currentPage = 'calendar.php';
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> - Next Beyond Admin</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="../assets/js/admin-guard.js"></script>
  <script defer src="../assets/js/admin-calendar.js"></script>
  <style>
    .calendar-field{width:100%;height:44px;padding:0 14px;border:1px solid #dce4ef;border-radius:12px;background:#fff;outline:none;font-size:13px}
    .calendar-field:focus{border-color:#f54696;box-shadow:0 0 0 3px rgba(245,70,150,.08)}
    textarea.calendar-field{height:auto;padding-top:11px;padding-bottom:11px;resize:vertical}
    .calendar-day{min-height:128px;border-right:1px solid #e8ecf2;border-bottom:1px solid #e8ecf2;background:#fff;transition:background .15s}
    .calendar-day:hover{background:#fbfcfe}.calendar-day:nth-child(7n){border-right:0}.calendar-day.outside{background:#f8fafc;color:#aebbd0}
    @media(max-width:760px){.calendar-day{min-height:92px}.event-detail{display:none}}
  </style>
</head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans antialiased">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[960px]:ml-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="flex-1 p-8 max-[640px]:p-4">
      <div id="calendar-notice" class="hidden mb-5 px-5 py-4 rounded-xl border text-[13px] font-bold"></div>

      <div class="grid grid-cols-3 gap-4 mb-5 max-[700px]:grid-cols-1">
        <div class="bg-white rounded-[18px] border border-[#e8ecf2] px-5 py-4 flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-pink-50 text-pink-500 flex items-center justify-center text-xl">◫</div>
          <div><div id="calendar-month-count" class="text-[25px] font-black leading-none">0</div><div class="text-[12px] text-[#65738a] mt-1">กิจกรรมเดือนนี้</div></div>
        </div>
        <div class="bg-white rounded-[18px] border border-[#e8ecf2] px-5 py-4 flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">◷</div>
          <div><div id="calendar-today-count" class="text-[25px] font-black leading-none">0</div><div class="text-[12px] text-[#65738a] mt-1">กิจกรรมวันนี้</div></div>
        </div>
        <div class="bg-white rounded-[18px] border border-[#e8ecf2] px-5 py-4 flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">✓</div>
          <div><div id="calendar-hours" class="text-[25px] font-black leading-none">0</div><div class="text-[12px] text-[#65738a] mt-1">ชั่วโมงตามตารางเดือนนี้</div></div>
        </div>
      </div>

      <section class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
        <div class="p-5 border-b border-[#e8ecf2] flex items-center justify-between gap-4 flex-wrap">
          <div class="flex items-center gap-2">
            <button id="calendar-prev" type="button" class="w-10 h-10 rounded-xl border border-[#dce4ef] hover:bg-[#f8fafc] text-xl" aria-label="เดือนก่อน">‹</button>
            <button id="calendar-today" type="button" class="h-10 px-4 rounded-xl border border-[#dce4ef] text-[13px] font-bold hover:bg-[#f8fafc]">วันนี้</button>
            <button id="calendar-next" type="button" class="w-10 h-10 rounded-xl border border-[#dce4ef] hover:bg-[#f8fafc] text-xl" aria-label="เดือนถัดไป">›</button>
            <h3 id="calendar-heading" class="ml-2 text-[18px] font-extrabold max-[560px]:w-full max-[560px]:ml-0">กำลังโหลด...</h3>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <?php if ($consoleUser['role'] === 'admin'): ?>
              <select id="calendar-teacher-filter" class="h-10 px-3 rounded-xl border border-[#dce4ef] bg-white text-[13px]"><option value="">ครูทุกคน</option></select>
            <?php endif; ?>
            <select id="calendar-course-filter" class="h-10 px-3 rounded-xl border border-[#dce4ef] bg-white text-[13px]"><option value="">ทุกคอร์ส</option></select>
            <button id="add-calendar-event" type="button" class="h-10 px-5 rounded-xl bg-pink-500 text-white text-[13px] font-bold shadow-[0_4px_12px_rgba(231,45,130,.25)]">＋ เพิ่มกิจกรรม</button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <div class="min-w-[720px]">
            <div class="grid grid-cols-7 bg-[#f8fafc] border-b border-[#e8ecf2]">
              <?php foreach (['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'] as $day): ?>
                <div class="py-3 text-center text-[11px] font-black text-[#65738a] uppercase"><?= $day ?></div>
              <?php endforeach; ?>
            </div>
            <div id="calendar-grid" class="grid grid-cols-7"><div class="col-span-7 p-16 text-center text-[#65738a]">กำลังโหลดปฏิทิน...</div></div>
          </div>
        </div>
      </section>
    </main>
  </div>
</div>

<div id="calendar-modal" class="hidden fixed inset-0 z-50 bg-navy-950/50 p-4 items-center justify-center overflow-y-auto">
  <div class="w-full max-w-[680px] bg-white rounded-[22px] shadow-2xl my-auto">
    <div class="px-6 py-5 border-b border-[#e8ecf2] flex items-center justify-between">
      <div><h2 id="calendar-modal-title" class="text-[18px] font-bold">เพิ่มกิจกรรม</h2><p class="text-[12px] text-[#65738a] mt-1">ระบุวัน เวลา และผู้รับผิดชอบ</p></div>
      <button type="button" data-calendar-close class="text-[26px] text-[#94a3b8]" aria-label="ปิด">×</button>
    </div>
    <form id="calendar-form" class="p-6">
      <input type="hidden" name="id">
      <div class="grid grid-cols-2 gap-4 max-[580px]:grid-cols-1">
        <div class="col-span-2 max-[580px]:col-span-1"><label class="block text-[13px] font-bold mb-1.5">ชื่อกิจกรรม *</label><input name="title" required maxlength="300" class="calendar-field" placeholder="เช่น ติว A-Level ฟิสิกส์"></div>
        <div><label class="block text-[13px] font-bold mb-1.5">ประเภท *</label><select name="eventType" class="calendar-field"><option value="lesson">คาบเรียน</option><option value="exam">สอบ / แบบทดสอบ</option><option value="meeting">ประชุม / นัดหมาย</option><option value="other">อื่น ๆ</option></select></div>
        <div><label class="block text-[13px] font-bold mb-1.5">สถานะ</label><select name="status" class="calendar-field"><option value="scheduled">ตามกำหนด</option><option value="cancelled">ยกเลิก</option></select></div>
        <div><label class="block text-[13px] font-bold mb-1.5">วันที่ *</label><input name="eventDate" type="date" required class="calendar-field"></div>
        <div class="grid grid-cols-2 gap-2"><label class="block text-[13px] font-bold">เริ่ม *<input name="startTime" type="time" required value="09:00" class="calendar-field mt-1.5"></label><label class="block text-[13px] font-bold">สิ้นสุด *<input name="endTime" type="time" required value="10:30" class="calendar-field mt-1.5"></label></div>
        <div><label class="block text-[13px] font-bold mb-1.5">ครูผู้สอน *</label><select name="teacherId" id="calendar-event-teacher" required class="calendar-field"></select></div>
        <div><label class="block text-[13px] font-bold mb-1.5">คอร์ส</label><select name="courseId" id="calendar-event-course" class="calendar-field"><option value="">ไม่ผูกกับคอร์ส</option></select></div>
        <div><label class="block text-[13px] font-bold mb-1.5">สถานที่ / ลิงก์ห้องเรียน</label><input name="location" maxlength="255" class="calendar-field" placeholder="ห้อง 301 หรือ URL"></div>
        <div><label class="block text-[13px] font-bold mb-1.5">สีบนปฏิทิน</label><input name="color" type="color" value="#2563eb" class="calendar-field p-1.5"></div>
        <div class="col-span-2 max-[580px]:col-span-1"><label class="block text-[13px] font-bold mb-1.5">รายละเอียดเพิ่มเติม</label><textarea name="notes" rows="3" class="calendar-field"></textarea></div>
      </div>
      <div id="calendar-form-error" class="hidden mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-600 text-[13px] font-bold"></div>
      <div class="flex justify-between gap-3 mt-6 pt-5 border-t border-[#e8ecf2]">
        <button id="delete-calendar-event" type="button" class="hidden h-10 px-4 rounded-xl text-red-600 bg-red-50 font-bold text-[13px]">ลบกิจกรรม</button>
        <div class="flex gap-3 ml-auto"><button type="button" data-calendar-close class="h-10 px-5 rounded-xl border border-[#dce4ef] font-bold text-[13px]">ยกเลิก</button><button id="save-calendar-event" class="h-10 px-6 rounded-xl bg-pink-500 text-white font-bold text-[13px]">บันทึกกิจกรรม</button></div>
      </div>
    </form>
  </div>
</div>
</body>
</html>
