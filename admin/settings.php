<?php
$pageTitle = 'ตั้งค่าระบบ (System Settings)';
$pageDesc = 'ตั้งค่าการทำงานของระบบ จัดการสิทธิ์การเข้าถึง และการแจ้งเตือน';
$currentPage = 'settings.php';
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
        
        <div class="max-w-[800px] mx-auto">
          <!-- Settings Tabs -->
          <div class="flex items-center gap-6 border-b border-[#e8ecf2] mb-8 overflow-x-auto">
            <button class="pb-3 px-2 border-b-2 border-pink-500 text-[14px] font-bold text-navy-950 whitespace-nowrap">ข้อมูลโรงเรียน</button>
            <button class="pb-3 px-2 border-b-2 border-transparent text-[14px] font-bold text-[#94a3b8] hover:text-navy-950 transition-colors whitespace-nowrap">การชำระเงิน</button>
            <button class="pb-3 px-2 border-b-2 border-transparent text-[14px] font-bold text-[#94a3b8] hover:text-navy-950 transition-colors whitespace-nowrap">บทบาทและสิทธิ์</button>
            <button class="pb-3 px-2 border-b-2 border-transparent text-[14px] font-bold text-[#94a3b8] hover:text-navy-950 transition-colors whitespace-nowrap">AI API Key</button>
          </div>

          <!-- General Settings Form -->
          <div class="bg-white rounded-[24px] shadow-[0_4px_24px_rgba(15,42,83,0.03)] border border-[#e8ecf2] p-8">
            <h3 class="text-[18px] font-bold text-navy-950 mb-6">ข้อมูลทั่วไป (General Info)</h3>
            
            <div class="space-y-6">
              <div class="flex items-center gap-6 pb-6 border-b border-[#e8ecf2]">
                <div class="w-24 h-24 rounded-2xl bg-[#f8fafc] border-2 border-dashed border-[#dce4ef] flex items-center justify-center cursor-pointer hover:border-pink-500 hover:bg-pink-50/50 transition-all">
                  <div class="text-center">
                    <div class="text-[24px] mb-1">📸</div>
                    <div class="text-[10px] font-bold text-[#94a3b8]">อัปโหลดโลโก้</div>
                  </div>
                </div>
                <div class="flex-1">
                  <div class="text-[14px] font-bold text-navy-950 mb-1">โลโก้โรงเรียน (School Logo)</div>
                  <div class="text-[12px] text-[#65738a] mb-3">ขนาดแนะนำ 400x400 px นามสกุล .png หรือ .jpg (ไม่เกิน 2MB)</div>
                  <button class="h-9 px-4 rounded-lg border border-[#dce4ef] text-[13px] font-bold text-navy-950 hover:bg-[#f8fafc]">เลือกไฟล์</button>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-6 max-[640px]:grid-cols-1">
                <div>
                  <label class="block text-[13px] font-bold text-navy-950 mb-2">ชื่อสถาบัน / โรงเรียนกวดวิชา *</label>
                  <input type="text" value="Next Beyond Academy" class="w-full h-12 px-4 border border-[#dce4ef] rounded-xl text-navy-950 outline-none focus:border-pink-500 transition-all">
                </div>
                <div>
                  <label class="block text-[13px] font-bold text-navy-950 mb-2">เบอร์โทรศัพท์ติดต่อ</label>
                  <input type="text" value="02-123-4567" class="w-full h-12 px-4 border border-[#dce4ef] rounded-xl text-navy-950 outline-none focus:border-pink-500 transition-all">
                </div>
              </div>

              <div>
                <label class="block text-[13px] font-bold text-navy-950 mb-2">อีเมลสำหรับติดต่อ</label>
                <input type="email" value="contact@nextbeyond.edu" class="w-full h-12 px-4 border border-[#dce4ef] rounded-xl text-navy-950 outline-none focus:border-pink-500 transition-all">
              </div>
              
              <div>
                <label class="block text-[13px] font-bold text-navy-950 mb-2">ที่อยู่</label>
                <textarea class="w-full h-24 px-4 py-3 border border-[#dce4ef] rounded-xl text-navy-950 outline-none focus:border-pink-500 transition-all resize-none">123 ถนนตัวอย่าง แขวงทดสอบ เขตจำลอง กรุงเทพมหานคร 10000</textarea>
              </div>
              
              <!-- Toggles -->
              <div class="pt-6 border-t border-[#e8ecf2] space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="text-[14px] font-bold text-navy-950 mb-0.5">เปิดรับสมัครนักเรียนใหม่</div>
                    <div class="text-[12px] text-[#65738a]">อนุญาตให้ผู้ใช้ใหม่สมัครสมาชิกผ่านหน้าเว็บไซต์</div>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" value="" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#10b981]"></div>
                  </label>
                </div>
                <div class="flex items-center justify-between">
                  <div>
                    <div class="text-[14px] font-bold text-navy-950 mb-0.5">โหมดบำรุงรักษา (Maintenance Mode)</div>
                    <div class="text-[12px] text-[#65738a]">ปิดระบบชั่วคราว เฉพาะ Admin ที่เข้าใช้งานได้</div>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" value="" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-500"></div>
                  </label>
                </div>
              </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3">
              <button class="h-12 px-6 rounded-xl border border-[#dce4ef] bg-white text-navy-950 text-[14px] font-bold hover:bg-[#f8fafc] transition-colors">ยกเลิก</button>
              <button class="h-12 px-8 rounded-xl bg-pink-500 text-white text-[14px] font-bold hover:bg-pink-600 transition-colors shadow-sm">บันทึกการตั้งค่า</button>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

</body>
</html>
