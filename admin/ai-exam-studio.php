<?php
require_once __DIR__ . '/includes/access.php';
$pageTitle = 'AI Exam Studio';
$pageDesc = 'สร้างข้อสอบจากเอกสารด้วย AI';
$currentPage = 'ai-exam-studio.php';
$defaultSubjects = ['คณิตศาสตร์', 'วิทยาศาสตร์', 'ภาษาอังกฤษ', 'ภาษาไทย', 'สังคมศึกษา'];
$examSubjects = $defaultSubjects;
try {
    $rows = $pdo->query("SELECT DISTINCT subject FROM (SELECT subject FROM courses WHERE subject IS NOT NULL AND TRIM(subject) <> '' UNION SELECT subject FROM exams WHERE subject IS NOT NULL AND TRIM(subject) <> '') s ORDER BY subject")->fetchAll(PDO::FETCH_COLUMN);
    $examSubjects = array_values(array_unique(array_merge($defaultSubjects, array_filter(array_map('trim', $rows)))));
} catch (Throwable $error) {
    error_log('AI Exam Studio subjects: ' . $error->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - Next Beyond Admin</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="../assets/js/admin-guard.js"></script>
</head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans antialiased">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[960px]:ml-0 transition-all duration-300">
    <?php include 'includes/topbar.php'; ?>
    <main class="flex-1 p-8 max-[640px]:p-4 overflow-y-auto">
      <div class="max-w-[1180px] mx-auto">
        <section id="studio-form" class="bg-white border border-[#dce4ef] rounded-lg shadow-[0_12px_32px_rgba(15,42,83,0.06)] overflow-hidden relative">
          <div id="studio-loading" class="hidden absolute inset-0 z-30 bg-white/95 items-center justify-center text-center p-8">
            <div><div class="w-12 h-12 mx-auto rounded-full border-4 border-[#dbeafe] border-t-[#2563eb] animate-spin"></div><h3 class="mt-5 text-xl font-bold">AI กำลังสร้างข้อสอบ</h3><p class="mt-1 text-[#65738a]">กำลังอ่านเอกสารและจัดทำเฉลย อาจใช้เวลาสักครู่</p></div>
          </div>
          <header class="px-8 pt-8 pb-6 max-[640px]:px-5 border-b border-[#e8ecf2]">
            <div class="flex justify-between gap-4 items-start">
              <div><span class="inline-flex items-center px-3 py-1.5 rounded-full bg-[#eef2ff] text-[#4338ca] border border-[#c7d2fe] text-xs font-bold">AI EXAM STUDIO</span><h1 class="mt-4 text-[30px] max-[640px]:text-[24px] leading-tight font-bold">สร้างชุดข้อสอบจากเอกสาร</h1><p class="mt-2 text-[#65738a]">เลือกเอกสาร กำหนดรูปแบบ แล้วตรวจข้อสอบก่อนบันทึกเข้าคลัง</p></div>
              <button type="button" onclick="openStudioSettings()" class="shrink-0 w-10 h-10 grid place-items-center border border-[#dce4ef] rounded-md text-[#65738a] hover:text-[#2563eb] hover:border-[#93c5fd]" title="ตั้งค่า Gemini API Key" aria-label="ตั้งค่า Gemini API Key">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM19.4 15a1.7 1.7 0 00.34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0015 19.4a1.7 1.7 0 00-1 .6 1.7 1.7 0 00-.4 1.1V21h-4v-.09A1.7 1.7 0 008.6 19.4a1.7 1.7 0 00-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 004.6 15a1.7 1.7 0 00-.6-1 1.7 1.7 0 00-1.1-.4H3v-4h.09A1.7 1.7 0 004.6 8.6a1.7 1.7 0 00-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 009 4.6a1.7 1.7 0 001-.6 1.7 1.7 0 00.4-1.1V3h4v.09A1.7 1.7 0 0015.4 4.6a1.7 1.7 0 001.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0019.4 9c.09.38.3.73.6 1 .3.27.69.4 1.1.4h.09v4h-.09c-.41 0-.8.14-1.1.4-.3.27-.51.62-.6 1z"/></svg>
              </button>
            </div>
          </header>
          <form id="studio-generate-form" class="p-8 max-[640px]:p-5 space-y-7">
            <div class="grid grid-cols-2 max-[760px]:grid-cols-1 gap-6">
              <label class="block"><span class="block mb-2 text-sm font-bold">1. วิชาเรียน</span><select id="studio-subject" required class="w-full h-12 px-4 bg-white border border-[#cbd5e1] rounded-md outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#dbeafe]"><option value="">เลือกวิชาเรียน</option><?php foreach ($examSubjects as $subject): ?><option value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
              <label class="block"><span class="block mb-2 text-sm font-bold">ระดับชั้น</span><select id="studio-grade" class="w-full h-12 px-4 bg-white border border-[#cbd5e1] rounded-md outline-none focus:border-[#2563eb]"><option>ทุกระดับ</option><option>ประถมศึกษา</option><option>มัธยมศึกษาตอนต้น</option><option>มัธยมศึกษาตอนปลาย</option><option>มหาวิทยาลัย</option></select></label>
            </div>
            <div><span class="block mb-2 text-sm font-bold">2. ระดับความยาก</span><div id="studio-difficulty" class="grid grid-cols-3 max-[640px]:grid-cols-1 gap-3"><button type="button" data-value="easy" class="studio-choice h-12 border border-[#cbd5e1] rounded-md font-bold text-[#65738a]">ปูพื้นฐาน</button><button type="button" data-value="medium" class="studio-choice is-active h-12 border rounded-md font-bold">สนามจริง</button><button type="button" data-value="hard" class="studio-choice h-12 border border-[#cbd5e1] rounded-md font-bold text-[#65738a]">ห้องพิเศษ / Gifted</button></div></div>
            <div class="grid grid-cols-2 max-[760px]:grid-cols-1 gap-6">
              <div><span class="block mb-2 text-sm font-bold">3. จำนวนข้อสอบ (1–50 ข้อ)</span><div class="flex gap-2 max-[480px]:flex-wrap"><input id="studio-count" type="number" min="1" max="50" value="10" class="w-24 h-12 px-3 bg-white border border-[#cbd5e1] rounded-md outline-none focus:border-[#2563eb]"><button type="button" data-count="5" class="studio-count h-12 px-4 border border-[#cbd5e1] rounded-md font-bold">5</button><button type="button" data-count="10" class="studio-count is-active h-12 px-4 border rounded-md font-bold">10</button><button type="button" data-count="20" class="studio-count h-12 px-4 border border-[#cbd5e1] rounded-md font-bold">20</button><button type="button" data-count="30" class="studio-count h-12 px-4 border border-[#cbd5e1] rounded-md font-bold">30</button></div></div>
              <div><span class="block mb-2 text-sm font-bold">เวลาสอบแนะนำ</span><div id="studio-time" class="h-12 px-4 flex items-center bg-[#f8fafc] border border-[#dce4ef] rounded-md text-[#2563eb] font-bold">20 นาที (~2 นาที/ข้อ)</div></div>
            </div>
            <label class="block"><span class="block mb-2 text-sm font-bold">หัวข้อที่ต้องการเน้น</span><input id="studio-topic" type="text" placeholder="เช่น สมการกำลังสอง, Reading comprehension" class="w-full h-12 px-4 bg-white border border-[#cbd5e1] rounded-md outline-none focus:border-[#2563eb]"></label>
            <div><span class="block mb-2 text-sm font-bold">4. แหล่งเอกสาร</span><div class="inline-flex p-1 bg-[#f1f5f9] rounded-md mb-4"><button type="button" data-source="drive" class="studio-source is-active px-4 h-9 rounded text-sm font-bold">Google Drive</button><button type="button" data-source="upload" class="studio-source px-4 h-9 rounded text-sm font-bold text-[#65738a]">อัปโหลดขึ้น Server</button></div>
              <div id="source-drive"><label class="block"><span class="sr-only">Google Drive URL</span><input id="studio-drive-url" type="url" placeholder="https://drive.google.com/file/d/... หรือ Google Docs" class="w-full h-12 px-4 bg-white border border-[#cbd5e1] rounded-md outline-none focus:border-[#2563eb]"></label><p class="mt-2 text-xs text-[#65738a]">เอกสารต้องตั้งค่าเป็น Anyone with the link</p></div>
              <div id="source-upload" class="hidden"><label id="studio-dropzone" class="min-h-[140px] px-5 py-6 grid place-items-center text-center bg-white border-2 border-dashed border-[#cbd5e1] rounded-md cursor-pointer hover:border-[#60a5fa]"><input id="studio-file" type="file" accept=".pdf,.txt,.docx,application/pdf,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="sr-only"><span><svg class="w-7 h-7 mx-auto mb-2 text-[#2563eb]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L7 9m5-5l5 5M4 15v4a1 1 0 001 1h14a1 1 0 001-1v-4"/></svg><strong id="studio-file-label" class="block">เลือกไฟล์หรือลากมาวาง</strong><small class="text-[#65738a]">PDF, TXT, DOCX สูงสุด 15 MB</small></span></label><div id="studio-upload-status" class="hidden mt-3 p-3 bg-[#f0fdf4] border border-[#bbf7d0] rounded-md text-sm font-bold text-[#15803d]"></div></div>
            </div>
            <div><span class="block mb-2 text-sm font-bold">รูปแบบการสร้าง</span><div class="grid grid-cols-2 max-[640px]:grid-cols-1 gap-3"><label class="flex gap-3 p-4 border border-[#cbd5e1] rounded-md cursor-pointer"><input type="radio" name="studio-type" value="similar" checked class="mt-1"><span><strong class="block">สร้างข้อสอบชุดใหม่</strong><small class="text-[#65738a]">วิเคราะห์เนื้อหาแล้วสร้างโจทย์พร้อมเฉลย</small></span></label><label class="flex gap-3 p-4 border border-[#cbd5e1] rounded-md cursor-pointer"><input type="radio" name="studio-type" value="copy" class="mt-1"><span><strong class="block">คัดลอกจากต้นฉบับ</strong><small class="text-[#65738a]">ถอดข้อสอบจากเอกสารตามลำดับ</small></span></label></div></div>
            <label class="flex items-center gap-3"><input id="studio-shuffle" type="checkbox" class="w-5 h-5"><span class="text-sm font-bold">สลับลำดับข้อสอบหลังสร้างเสร็จ</span></label>
            <button id="studio-submit" type="submit" class="w-full h-14 rounded-md bg-gradient-to-r from-[#4f46e5] to-[#ec4899] text-white text-lg font-bold shadow-[0_8px_20px_rgba(79,70,229,.2)]">สร้างข้อสอบและเปิดพรีวิว</button>
          </form>
        </section>

        <section id="studio-preview" class="hidden space-y-5">
          <div class="bg-white border border-[#dce4ef] rounded-lg p-6 flex justify-between gap-4 items-start max-[640px]:flex-col"><div><span class="text-xs font-bold text-[#2563eb]">PREVIEW</span><h2 class="mt-1 text-2xl font-bold">ตรวจข้อสอบก่อนบันทึก</h2><div id="studio-meta" class="mt-3 flex flex-wrap gap-2"></div></div><button type="button" id="studio-back" class="px-4 h-10 border border-[#cbd5e1] rounded-md font-bold text-sm">กลับไปแก้การตั้งค่า</button></div>
          <div id="studio-questions" class="space-y-4"></div>
          <div class="sticky bottom-4 bg-white border border-[#dce4ef] rounded-lg p-4 shadow-[0_12px_32px_rgba(15,42,83,.12)] flex justify-between items-center gap-3 max-[640px]:flex-col"><p id="studio-save-status" class="text-sm text-[#65738a]">ตรวจคำถามและเฉลยให้เรียบร้อยก่อนบันทึก</p><button id="studio-save" type="button" class="h-11 px-6 bg-[#2563eb] text-white rounded-md font-bold max-[640px]:w-full">บันทึกเข้าคลังข้อสอบ</button></div>
        </section>
      </div>
    </main>
  </div>
</div>

<div id="studio-settings" class="hidden fixed inset-0 z-[100] bg-navy-950/40 p-4 items-center justify-center"><div class="w-full max-w-md bg-white rounded-lg border border-[#dce4ef] shadow-xl"><div class="p-6 border-b border-[#e8ecf2]"><h3 class="text-xl font-bold">ตั้งค่า Gemini API Key</h3><p id="studio-key-status" class="mt-2 text-sm text-[#65738a]"></p></div><div class="p-6"><input id="studio-key" type="password" placeholder="AIzaSy..." class="w-full h-12 px-4 border border-[#cbd5e1] rounded-md outline-none focus:border-[#2563eb]"></div><div class="p-4 bg-[#f8fafc] flex justify-end gap-3"><button type="button" onclick="closeStudioSettings()" class="h-10 px-4 border border-[#cbd5e1] rounded-md font-bold">ยกเลิก</button><button type="button" onclick="saveStudioSettings()" class="h-10 px-5 bg-[#2563eb] text-white rounded-md font-bold">บันทึก</button></div></div></div>

<style>
.studio-choice.is-active,.studio-count.is-active{background:#2563eb;border-color:#2563eb;color:#fff}.studio-source.is-active{background:#fff;color:#1d4ed8;box-shadow:0 1px 3px rgba(15,23,42,.12)}
</style>
<script>window.AI_STUDIO_ENDPOINTS={generate:'../AI-EXAM/api.php',upload:'ai-exam-upload-api.php',settings:'ai-settings-api.php',save:'exams-api.php'};</script>
<script src="../assets/js/admin-ai-exam-studio.js?v=<?= rawurlencode((string) filemtime(__DIR__ . '/../assets/js/admin-ai-exam-studio.js')) ?>"></script>
</body></html>
