<?php
require_once __DIR__.'/includes/access.php';
$pageTitle = 'ตั้งค่าระบบ (System Settings)';
$pageDesc = 'ตั้งค่าข้อมูลสถาบัน การชำระเงิน สิทธิ์ และ AI';
$currentPage = 'settings.php';
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> - Next Beyond Admin</title>
  <link rel="stylesheet" href="../assets/css/output.css?v=<?= filemtime(__DIR__ . '/../assets/css/output.css') ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="../assets/js/admin-guard.js"></script>
  <script defer src="../assets/js/admin-settings.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-settings.js') ?>"></script>
</head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans antialiased">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[960px]:ml-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="flex-1 p-8 max-[640px]:p-4 overflow-y-auto">
      <div class="max-w-[940px] mx-auto">
        <div id="settings-notice" class="hidden mb-5 px-5 py-4 rounded-xl border text-[13px] font-bold" role="status"></div>

        <div class="mb-7">
          <h2 class="text-[25px] font-bold">ตั้งค่าระบบ</h2>
          <p class="mt-1 text-[13px] text-[#65738a]">ข้อมูลทั้งหมดบันทึกลงฐานข้อมูลและเรียกกลับมาใช้เมื่อเปิดหน้าใหม่</p>
        </div>

        <nav class="flex items-center gap-5 border-b border-[#dce4ef] mb-6 overflow-x-auto" aria-label="หมวดการตั้งค่า">
          <button data-settings-tab="general" class="settings-tab pb-3 px-1 border-b-2 border-pink-500 text-[13px] font-bold whitespace-nowrap">ข้อมูลสถาบัน</button>
          <button data-settings-tab="payment" class="settings-tab pb-3 px-1 border-b-2 border-transparent text-[#94a3b8] text-[13px] font-bold whitespace-nowrap">การชำระเงิน</button>
          <button data-settings-tab="permissions" class="settings-tab pb-3 px-1 border-b-2 border-transparent text-[#94a3b8] text-[13px] font-bold whitespace-nowrap">บทบาทและสิทธิ์</button>
          <button data-settings-tab="ai" class="settings-tab pb-3 px-1 border-b-2 border-transparent text-[#94a3b8] text-[13px] font-bold whitespace-nowrap">AI API Key</button>
        </nav>

        <section data-settings-panel="general">
          <form data-settings-form class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
            <div class="px-7 py-5 border-b border-[#e8ecf2]"><h3 class="text-[17px] font-bold">ข้อมูลทั่วไป</h3><p class="mt-1 text-[12px] text-[#65738a]">ข้อมูลติดต่อและสถานะการให้บริการของสถาบัน</p></div>
            <div class="p-7 space-y-6">
              <div class="flex items-center gap-5 pb-6 border-b border-[#e8ecf2] max-[600px]:items-start">
                <div id="school-logo-preview" class="w-20 h-20 shrink-0 rounded-xl bg-[#f8fafc] border border-dashed border-[#cbd5e1] flex items-center justify-center overflow-hidden text-[11px] font-bold text-[#94a3b8]">LOGO</div>
                <div><div class="text-[13px] font-bold">โลโก้สถาบัน</div><p class="mt-1 mb-3 text-[11px] text-[#65738a]">PNG, JPG หรือ WEBP ขนาดไม่เกิน 2MB</p><input id="school-logo-input" type="file" accept=".png,.jpg,.jpeg,.webp" class="hidden"><button id="choose-school-logo" type="button" class="h-9 px-4 rounded-lg border border-[#dce4ef] text-[12px] font-bold hover:bg-[#f8fafc]">เลือกไฟล์</button></div>
              </div>
              <div class="grid grid-cols-2 gap-5 max-[640px]:grid-cols-1">
                <label class="text-[12px] font-bold">ชื่อสถาบัน *<input data-setting="school_name" required class="settings-field mt-2" placeholder="ชื่อสถาบัน"></label>
                <label class="text-[12px] font-bold">เบอร์โทรศัพท์<input data-setting="school_phone" class="settings-field mt-2" placeholder="เบอร์โทรศัพท์ติดต่อ"></label>
                <label class="text-[12px] font-bold">อีเมลติดต่อ<input data-setting="school_email" type="email" class="settings-field mt-2" placeholder="contact@example.com"></label>
                <label class="text-[12px] font-bold">ที่อยู่<textarea data-setting="school_address" class="settings-field mt-2 min-h-[92px] py-3 resize-y" placeholder="ที่อยู่สถาบัน"></textarea></label>
              </div>
              <div class="pt-5 border-t border-[#e8ecf2] space-y-5">
                <label class="settings-toggle-row"><span><b>เปิดรับสมัครนักเรียนใหม่</b><small>อนุญาตให้สร้างบัญชีใหม่จากหน้าเว็บไซต์</small></span><input data-setting="registration_enabled" type="checkbox" class="settings-checkbox"></label>
                <label class="settings-toggle-row"><span><b>โหมดบำรุงรักษา</b><small>บันทึกสถานะสำหรับหยุดให้บริการเว็บไซต์ชั่วคราว</small></span><input data-setting="maintenance_mode" type="checkbox" class="settings-checkbox"></label>
              </div>
            </div>
            <div class="settings-actions"><button type="button" data-settings-reset class="settings-cancel">ยกเลิก</button><button class="settings-save">บันทึกข้อมูลสถาบัน</button></div>
          </form>
        </section>

        <section data-settings-panel="payment" class="hidden">
          <form data-settings-form class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
            <div class="px-7 py-5 border-b border-[#e8ecf2]"><h3 class="text-[17px] font-bold">ข้อมูลรับชำระเงิน</h3><p class="mt-1 text-[12px] text-[#65738a]">ใช้สำหรับตรวจสอบรายการโอนและออกเอกสารทางการเงิน</p></div>
            <div class="p-7 grid grid-cols-2 gap-5 max-[640px]:grid-cols-1">
              <label class="text-[12px] font-bold">ธนาคาร<input data-setting="bank_name" class="settings-field mt-2" placeholder="ชื่อธนาคาร"></label>
              <label class="text-[12px] font-bold">ชื่อบัญชี<input data-setting="bank_account_name" class="settings-field mt-2" placeholder="ชื่อบัญชีธนาคาร"></label>
              <label class="text-[12px] font-bold">เลขที่บัญชี<input data-setting="bank_account_number" class="settings-field mt-2" placeholder="เลขที่บัญชี"></label>
              <label class="text-[12px] font-bold">PromptPay<input data-setting="promptpay_number" class="settings-field mt-2" placeholder="เลขบัตรประชาชนหรือเบอร์โทรศัพท์"></label>
              <label class="text-[12px] font-bold">เลขประจำตัวผู้เสียภาษี<input data-setting="tax_id" class="settings-field mt-2" placeholder="เลขประจำตัวผู้เสียภาษี"></label>
              <label class="text-[12px] font-bold">คำแนะนำการชำระเงิน<textarea data-setting="payment_instructions" class="settings-field mt-2 min-h-[92px] py-3 resize-y" placeholder="ข้อความที่ต้องการแจ้งผู้ชำระเงิน"></textarea></label>
            </div>
            <div class="settings-actions"><button type="button" data-settings-reset class="settings-cancel">ยกเลิก</button><button class="settings-save">บันทึกการชำระเงิน</button></div>
          </form>
        </section>

        <section data-settings-panel="permissions" class="hidden">
          <form data-settings-form class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
            <div class="px-7 py-5 border-b border-[#e8ecf2]"><h3 class="text-[17px] font-bold">บทบาทและสิทธิ์</h3><p class="mt-1 text-[12px] text-[#65738a]">กำหนดความสามารถหลักของผู้ใช้งานแต่ละกลุ่ม</p></div>
            <div class="p-7 space-y-5">
              <label class="settings-toggle-row"><span><b>เปิดใช้งาน Student Portal</b><small>ให้นักเรียนเข้าสู่แดชบอร์ด คอร์ส และประวัติข้อสอบ</small></span><input data-setting="student_portal_enabled" type="checkbox" class="settings-checkbox"></label>
              <h3 class="text-[17px] font-bold">เมนูของ Teacher ทุกคน</h3>
              <?php foreach (teacherMenuSettings() as $key => [$label]): ?>
              <label class="settings-toggle-row"><b><?= htmlspecialchars($label) ?></b><input data-setting="<?= $key ?>" type="checkbox" role="switch" class="teacher-menu-switch" disabled></label>
              <?php endforeach; ?>
              <style>
                .teacher-menu-switch{appearance:none;width:44px;height:26px;flex:0 0 44px;border:0;border-radius:13px;background:#8793a1;position:relative;cursor:pointer;transition:background .15s}
                .teacher-menu-switch:before{content:'';position:absolute;width:20px;height:20px;top:3px;left:3px;border-radius:50%;background:white;transition:transform .15s}
                .teacher-menu-switch:checked{background:#18845c}.teacher-menu-switch:checked:before{transform:translateX(18px)}
                .teacher-menu-switch:focus-visible{outline:3px solid #e72d82;outline-offset:3px}.teacher-menu-switch:disabled{opacity:.45;cursor:wait}
              </style>
              <label class="settings-toggle-row"><span><b>อนุญาตข้อสอบสำหรับ Guest</b><small>แอดมินสามารถเปิดข้อสอบให้ผู้ที่ไม่ได้เข้าสู่ระบบทำได้</small></span><input data-setting="guest_test_access" type="checkbox" class="settings-checkbox"></label>
              <label class="settings-toggle-row"><span><b>แจ้งเตือนทางอีเมล</b><small>เปิดสถานะการส่งอีเมลแจ้งเตือนจากระบบ</small></span><input data-setting="email_notifications" type="checkbox" class="settings-checkbox"></label>
            </div>
            <div class="settings-actions"><button type="button" data-settings-reset class="settings-cancel">ยกเลิก</button><button class="settings-save">บันทึกสิทธิ์</button></div>
          </form>
        </section>

        <section data-settings-panel="ai" class="hidden">
          <form id="ai-settings-form" class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
            <div class="px-7 py-5 border-b border-[#e8ecf2] flex items-center justify-between gap-4"><div><h3 class="text-[17px] font-bold">Gemini API Key</h3><p class="mt-1 text-[12px] text-[#65738a]">ใช้สำหรับ AI Exam Generator และจัดเก็บแบบเข้ารหัส</p></div><span id="ai-key-status" class="px-3 py-1 rounded-full bg-[#f1f5f9] text-[#65738a] text-[11px] font-bold">กำลังตรวจสอบ</span></div>
            <div class="p-7">
              <label class="text-[12px] font-bold">API Key<input id="settings-api-key" type="password" autocomplete="off" class="settings-field mt-2" placeholder="กรอก API Key ใหม่เมื่อต้องการเปลี่ยน"></label>
              <p id="ai-key-hint" class="mt-3 text-[11px] text-[#65738a]">ระบบจะไม่แสดง API Key ฉบับเต็มหลังบันทึก</p>
            </div>
            <div class="settings-actions"><span></span><button class="settings-save">บันทึก API Key</button></div>
          </form>
        </section>
      </div>
    </main>
  </div>
</div>
<style>
.settings-field{display:block;width:100%;min-height:46px;padding:0 14px;border:1px solid #dce4ef;border-radius:12px;background:#fff;color:#061633;font-size:13px;outline:none}
.settings-field:focus{border-color:#f54696;box-shadow:0 0 0 3px rgba(245,70,150,.08)}
.settings-toggle-row{display:flex;align-items:center;justify-content:space-between;gap:24px;cursor:pointer}
.settings-toggle-row b{display:block;font-size:13px}.settings-toggle-row small{display:block;margin-top:3px;color:#65738a;font-size:11px;font-weight:400}
.settings-checkbox{width:42px;height:22px;flex:none;accent-color:#f54696;cursor:pointer}
.settings-actions{display:flex;align-items:center;justify-content:flex-end;gap:12px;padding:18px 28px;border-top:1px solid #e8ecf2;background:#fafbfc}
.settings-cancel,.settings-save{height:42px;padding:0 20px;border-radius:10px;font-size:13px;font-weight:700}
.settings-cancel{border:1px solid #dce4ef;background:#fff}.settings-save{background:#f54696;color:#fff}.settings-save:disabled{opacity:.55}
</style>
</body>
</html>
