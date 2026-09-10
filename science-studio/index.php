<?php
require_once __DIR__.'/../admin/includes/access.php';
if (empty($_SESSION['science_csrf'])) $_SESSION['science_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['science_csrf'];
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>">
  <title>Science Learning Studio | Next Beyond</title>
  <link rel="stylesheet" href="assets/studio.css?v=<?= filemtime(__DIR__.'/assets/studio.css') ?>">
  <link rel="stylesheet" href="assets/ai-images.css?v=<?= filemtime(__DIR__.'/assets/ai-images.css') ?>">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script defer src="assets/lucide.min.js"></script>
  <script defer src="assets/app.js?v=<?= filemtime(__DIR__.'/assets/app.js') ?>"></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="./"><span class="brand-mark"><i data-lucide="orbit"></i></span><span>SCIENCE<span class="brand-sub">LEARNING STUDIO</span></span></a>
    <button class="new-project primary" id="new-project"><i data-lucide="plus"></i>หลักสูตรใหม่</button>
    <div class="sidebar-label">หลักสูตรของฉัน <span id="project-count">0</span></div>
    <nav id="projects" aria-label="หลักสูตร"><p class="muted">กำลังโหลด...</p></nav>
    <div class="sidebar-bottom"><button id="settings-open"><i data-lucide="settings-2"></i>ตั้งค่า Gemini <span class="dot" id="key-dot"></span></button><a href="../admin/"><i data-lucide="arrow-left"></i>กลับ Next Beyond</a></div>
  </aside>
  <main>
    <header class="topbar"><div class="topbar-left"><button class="icon mobile-menu" id="menu-toggle" title="เมนูหลักสูตร" aria-label="เมนูหลักสูตร"><i data-lucide="menu"></i></button><span class="breadcrumb">พื้นที่จัดการเรียนรู้ <span>/</span> วิทยาศาสตร์</span></div><div class="user"><span class="user-avatar"><?= htmlspecialchars(mb_substr($consoleUser['first_name'],0,1)) ?></span><?= htmlspecialchars($consoleUser['first_name']) ?></div></header>
    <div id="notice" role="status" aria-live="polite" hidden></div>
    <section id="create-view" class="workspace">
      <div class="page-heading"><div><div class="eyebrow"><span class="tiny-line"></span> NEW CURRICULUM</div><h1>ออกแบบการเรียนรู้วิทยาศาสตร์</h1><p>หลักสูตรใหม่</p></div><span class="tag">ฉบับร่าง</span></div>
      <form id="create-form">
        <section class="form-section"><div class="section-heading"><span class="step">01</span><div><h2>แขนงวิชา</h2><p>เลือกได้มากกว่าหนึ่งแขนง</p></div></div>
          <div class="branch-grid">
            <?php foreach ([['physics','ฟิสิกส์','atom','blue'],['chemistry','เคมี','flask-conical','pink'],['biology','ชีววิทยา','dna','green'],['earth','โลกและอวกาศ','globe-2','amber'],['integrated','บูรณาการ','layers','gray']] as [$id,$label,$icon,$color]): ?>
            <label class="branch <?= $color ?>"><input type="checkbox" name="branches" value="<?= $id ?>" <?= $id==='chemistry'?'checked':'' ?>><span class="branch-symbol"><i data-lucide="<?= $icon ?>"></i></span><span><?= $label ?></span><i data-lucide="check" class="branch-check"></i></label>
            <?php endforeach; ?>
          </div>
        </section>
        <section class="form-section"><div class="section-heading"><span class="step">02</span><div><h2>เป้าหมายและผู้เรียน</h2></div></div>
          <div class="form-grid"><label>ชื่อหลักสูตร<input name="title" required maxlength="200" placeholder="เช่น เคมีพื้นฐาน ม.4"></label><label>ระดับชั้น<select name="grade"><option>ประถมศึกษาตอนปลาย</option><option>มัธยมศึกษาตอนต้น</option><option selected>มัธยมศึกษาปีที่ 4</option><option>มัธยมศึกษาปีที่ 5</option><option>มัธยมศึกษาปีที่ 6</option><option>มหาวิทยาลัย</option></select></label>
          <label class="full">บทหรือหัวข้อที่ต้องการสอน<textarea name="topic" rows="2" required maxlength="1500" placeholder="เช่น โครงสร้างอะตอม ตารางธาตุ พันธะเคมี"></textarea></label>
          <label class="full">เป้าหมายการเรียนรู้<textarea name="goal" rows="2" required maxlength="1000" placeholder="นักเรียนอธิบายแนวคิดและแก้โจทย์พื้นฐานได้ด้วยตนเอง"></textarea></label>
          <label>พื้นฐานนักเรียน<select name="foundation"><option>เริ่มต้น ต้องการปูพื้นฐานทีละขั้น</option><option selected>มีพื้นฐานบ้าง ต้องการทำความเข้าใจให้ชัด</option><option>พื้นฐานดี ต้องการประยุกต์และเตรียมสอบ</option></select></label><label>ความยาก<select name="difficulty"><option>ปูพื้นฐาน</option><option selected>ปานกลาง</option><option>ประยุกต์ / ห้องพิเศษ</option></select></label></div>
        </section>
        <section class="form-section"><div class="section-heading"><span class="step">03</span><div><h2>จังหวะการเรียน</h2></div></div><div class="form-grid"><label>เวลาเป้าหมายต่อ Part (นาที)<input type="number" name="sessionMinutes" min="30" max="90" value="45" required></label><label>ข้อสอบท้าย EP (ข้อ)<input type="number" name="questionsPerEp" min="3" max="15" value="6" required></label><label class="full"><span><input type="checkbox" name="generateImages" value="1" checked> สร้างภาพประกอบแต่ละ Part ด้วย Nano Banana</span></label></div></section>
        <footer class="form-footer"><span class="muted"><i data-lucide="git-branch"></i>บทเรียน / EP / Part</span><button type="submit" class="primary"><i data-lucide="plus"></i>สร้างหลักสูตร</button></footer>
      </form>
    </section>
    <section id="project-view" class="workspace" hidden>
      <div class="page-heading"><div><div class="eyebrow" id="project-branches"></div><h1 id="project-title"></h1><p id="project-info"></p></div><button class="icon" id="refresh-project" title="โหลดใหม่" aria-label="โหลดใหม่"><i data-lucide="refresh-cw"></i></button></div>
      <div class="project-summary" id="project-summary"></div>
      <div class="queue-bar"><div class="queue-message"><span class="queue-dot"></span><div><strong id="queue-title"></strong><small id="queue-detail"></small></div></div><div class="commands"><button id="approve-outline" hidden><i data-lucide="check-check"></i>ยืนยันโครง</button><button id="step-once"><i data-lucide="skip-forward"></i><span>ทำ 1 งาน</span></button><button id="run-queue" class="primary"><i data-lucide="play"></i><span>เริ่มสร้างโครง</span></button><button id="pause-queue" hidden><i data-lucide="pause"></i>หยุดหลังงานนี้</button></div></div>
      <div class="tabs" role="tablist"><button role="tab" data-tab="outline" aria-selected="true">บทและ EP</button><button role="tab" data-tab="learn" aria-selected="false">เนื้อหาเรียน</button><button role="tab" data-tab="quiz" aria-selected="false">ข้อสอบ</button><button role="tab" data-tab="plan" aria-selected="false">แผนการเรียน</button><button role="tab" data-tab="jobs" aria-selected="false">ประวัติการสร้าง</button></div>
      <div id="tab-content" role="tabpanel"></div>
    </section>
  </main>
</div>
<dialog id="settings-dialog"><form id="settings-form"><div class="dialog-head"><h2>Gemini API</h2><button type="button" class="icon close-dialog" aria-label="ปิด" title="ปิด"><i data-lucide="x"></i></button></div><p id="key-status" class="muted"></p><label>API Key<input type="password" id="api-key" required autocomplete="new-password" maxlength="250" placeholder="AIza..."></label><footer><button class="primary" type="submit">บันทึก</button></footer></form></dialog>
<dialog id="editor-dialog" class="wide-dialog"><div class="dialog-head"><h2 id="editor-title">แก้ไข</h2><button type="button" class="icon close-dialog" aria-label="ปิด" title="ปิด"><i data-lucide="x"></i></button></div><div id="editor-content"></div></dialog>
</body></html>
