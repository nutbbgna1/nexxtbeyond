<?php
require_once __DIR__ . '/includes/access.php';
require_once __DIR__ . '/../includes/roadmap-service.php';
if ($consoleUser['role'] !== 'admin') consoleDeny(403);
ensureRoadmapSchema($pdo);
$pageTitle = 'จัดการ Study Roadmap';
$pageDesc = 'สร้างเป้าหมายการเรียนรู้และจัดการภารกิจย่อยให้นักเรียน';
$currentPage = 'roadmap.php';
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> - Next Beyond Admin</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    .road-main { padding: 30px; background: #f4f7fb; min-height: calc(100vh - 72px); }
    .road-hero { padding: 28px; border-radius: 18px; background: #102b58; color: #fff; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .road-hero h1 { font-size: 25px; font-weight: 800; color: #fff; }
    .road-hero p { margin-top: 6px; color: #c5d0df; font-size: 13px; }
    .road-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 41px; padding: 0 16px; border: 1px solid #dce4ef; border-radius: 10px; background: #fff; color: #193557; font-size: 12px; font-weight: 800; cursor: pointer; text-decoration: none; }
    .road-btn.primary { border-color: #f54696; background: #f54696; color: #fff; }
    .road-table { overflow: hidden; border: 1px solid #e1e7f0; border-radius: 12px; background: #fff; margin-top: 20px; }
    .road-row { display: grid; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid #edf1f6; font-size: 12px; }
    .road-row:last-child { border-bottom: 0; }
    .road-row.head { background: #f8fafc; color: #718096; font-size: 10px; font-weight: 800; text-transform: uppercase; }
    .stage { display: inline-flex; width: max-content; padding: 5px 8px; border-radius: 6px; background: #eef2ff; color: #5545be; font-weight: 800; }
    .title { color: #102b58; font-size: 14px; font-weight: 800; cursor: pointer; text-decoration: underline; }
    .title:hover { color: #2369dd; }
    .sub { margin-top: 4px; color: #718096; font-size: 11px; }
    .road-actions { display: flex; gap: 6px; }
    .road-actions button { height: 32px; padding: 0 10px; border: 1px solid #dce4ef; border-radius: 8px; background: #fff; color: #53647d; font-size: 11px; font-weight: 700; cursor: pointer; }
    .road-actions .danger { color: #dc315f; }
    .switch { width: 39px; height: 22px; border: 0; border-radius: 20px; background: #cbd5e1; padding: 2px; cursor: pointer; position: relative; }
    .switch:after { content: ""; display: block; width: 18px; height: 18px; border-radius: 50%; background: #fff; transition: .15s; position: absolute; top: 2px; left: 2px; }
    .switch.on { background: #f54696; }
    .switch.on:after { transform: translateX(17px); }
    .modal { position: fixed; inset: 0; z-index: 60; display: none; align-items: center; justify-content: center; padding: 18px; background: rgba(5, 18, 40, .55); overflow-y: auto; }
    .modal.show { display: flex; }
    .modal-card { width: min(600px, 100%); padding: 24px; border-radius: 15px; background: #fff; max-height: 90vh; overflow-y: auto; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 13px; }
    .field.full { grid-column: 1 / -1; }
    .field label { display: block; margin-bottom: 5px; color: #53647d; font-size: 11px; font-weight: 800; }
    .field input, .field select, .field textarea { width: 100%; min-height: 42px; padding: 8px 12px; border: 1px solid #dce4ef; border-radius: 9px; outline: none; font-size: 13px; font-family: inherit; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 19px; padding-top: 16px; border-top: 1px solid #edf1f6; }
    .nav-breadcrumbs { display: flex; gap: 8px; align-items: center; margin-bottom: 20px; font-size: 13px; font-weight: 700; color: #718096; }
    .nav-breadcrumbs a { color: #2369dd; text-decoration: none; cursor: pointer; }
    
    #view-roadmaps .road-row { grid-template-columns: 80px 2fr 100px 100px 80px 150px; }
    #view-tasks .road-row { grid-template-columns: 80px 2fr 120px 100px 80px 150px; }
    
    [hidden] { display: none !important; }
  </style>
</head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 ml-[240px] max-[1024px]:ml-0 min-w-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="road-main">
      
      <!-- View 1: Roadmap List -->
      <div id="view-roadmaps">
        <section class="road-hero">
          <div>
            <h1>Study Roadmap สำหรับนักเรียน</h1>
            <p>จัดการเส้นทางการเรียนรู้ให้นักเรียนติดตามความคืบหน้า</p>
          </div>
          <button class="road-btn primary" onclick="openRoadmapModal()">+ สร้าง Roadmap</button>
        </section>
        <section class="road-table">
          <div class="road-row head">
            <span>ระดับชั้น</span>
            <span>ชื่อ Roadmap</span>
            <span>นักเรียนที่เรียน</span>
            <span>จำนวนภารกิจ</span>
            <span>สถานะ</span>
            <span>จัดการ</span>
          </div>
          <div id="roadmap-list-body"></div>
        </section>
      </div>

      <!-- View 2: Tasks in Roadmap -->
      <div id="view-tasks" hidden>
        <div class="nav-breadcrumbs">
            <a onclick="showView('roadmaps')">Study Roadmaps</a> 
            <span>/</span>
            <span id="current-roadmap-name">...</span>
        </div>
        <section class="road-hero" style="flex-direction:column; align-items:flex-start;">
          <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
              <div>
                <h1 id="roadmap-hero-title">Roadmap Tasks</h1>
                <p id="roadmap-hero-desc">จัดการภารกิจภายใน Roadmap นี้</p>
              </div>
              <div class="flex gap-2">
                <button class="road-btn primary" onclick="openTaskModal()">+ เพิ่มภารกิจ</button>
              </div>
          </div>
          <div style="display:flex; gap:16px; margin-top:20px; border-bottom:1px solid #1c3c6d; width:100%;">
              <button class="rm-tab active" onclick="switchRmTab('tasks')">รายการภารกิจ</button>
              <button class="rm-tab" onclick="switchRmTab('assign')">การมอบหมาย</button>
              <button class="rm-tab" onclick="switchRmTab('report')">รายงานผู้เรียน</button>
          </div>
          <style>
              .rm-tab { background:transparent; color:#849fc7; border:none; padding:8px 12px; font-size:13px; font-weight:700; cursor:pointer; border-bottom:2px solid transparent; }
              .rm-tab.active { color:#fff; border-bottom-color:#f54696; }
              .tab-content { display:none; }
              .tab-content.active { display:block; }
          </style>
        </section>
        
        <div id="tab-tasks" class="tab-content active">
            <section class="road-table">
              <div class="road-row head">
                <span>ลำดับ</span>
                <span>ชื่อภารกิจ</span>
                <span>หมวดหมู่วิชา</span>
                <span>ประเภทเงื่อนไข</span>
                <span>แต้ม</span>
                <span>จัดการ</span>
              </div>
              <div id="task-list-body"></div>
            </section>
        </div>
        
        <div id="tab-assign" class="tab-content">
            <div style="background:#fff; border:1px solid #e1e7f0; border-radius:12px; padding:24px; margin-top:20px;">
                <h3 style="font-size:15px; font-weight:800; color:#102b58; margin-bottom:12px;">มอบหมาย Roadmap ให้นักเรียน</h3>
                <div class="form-grid" style="max-width:500px;">
                    <div class="field full">
                        <label>รูปแบบการมอบหมาย</label>
                        <select id="assign-type" onchange="toggleAssignTarget()">
                            <option value="all">นักเรียนทุกคนในระบบ</option>
                            <option value="stage">ตามระดับชั้น (ม.6, ม.4, ม.1)</option>
                        </select>
                    </div>
                    <div class="field full" id="group-assign-target" hidden>
                        <label>ระบุเป้าหมาย</label>
                        <select id="assign-target">
                            <option value="tcas">ม.6 / TCAS</option>
                            <option value="m4">ม.4 - ม.5</option>
                            <option value="m1">ม.1 - ม.3</option>
                        </select>
                    </div>
                    <div class="field full">
                        <label style="display:flex; align-items:center; gap:6px;"><input id="assign-mandatory" type="checkbox" checked> เป็น Roadmap บังคับ (นักเรียนถอนตัวไม่ได้)</label>
                    </div>
                    <div class="field full">
                        <button class="road-btn primary" onclick="assignRoadmap()">มอบหมายทันที</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="tab-report" class="tab-content">
             <div style="background:#fff; border:1px solid #e1e7f0; border-radius:12px; padding:24px; margin-top:20px; text-align:center; color:#718096; font-size:13px;">
                ระบบรายงานกำลังอยู่ระหว่างการพัฒนา...
             </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Modal: Roadmap -->
<div class="modal" id="roadmap-modal">
  <form class="modal-card" id="roadmap-form">
    <div class="flex items-center justify-between mb-5">
      <h2 id="rm-modal-title" class="text-[18px] font-bold">สร้าง Roadmap</h2>
      <button type="button" class="text-[#718096]" onclick="closeModals()">✕</button>
    </div>
    <input type="hidden" id="rm-id">
    <div class="form-grid">
      <div class="field full">
        <label>ชื่อ Roadmap *</label>
        <input id="rm-title" required maxlength="255">
      </div>
      <div class="field full">
        <label>รายละเอียด</label>
        <textarea id="rm-desc" rows="3"></textarea>
      </div>
      <div class="field">
        <label>ระดับชั้น</label>
        <select id="rm-stage">
          <option value="tcas">ม.6 / TCAS</option>
          <option value="m4">ม.4 - ม.5</option>
          <option value="m1">ม.1 - ม.3</option>
        </select>
      </div>
      <div class="field">
        <label>สถานะ</label>
        <select id="rm-status">
          <option value="draft">ฉบับร่าง (Draft)</option>
          <option value="published">เผยแพร่ (Published)</option>
          <option value="archived">จัดเก็บ (Archived)</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="road-btn" type="button" onclick="closeModals()">ยกเลิก</button>
      <button class="road-btn primary" type="submit">บันทึก Roadmap</button>
    </div>
  </form>
</div>

<!-- Modal: Task -->
<div class="modal" id="task-modal">
  <form class="modal-card" id="task-form">
    <div class="flex items-center justify-between mb-5">
      <h2 id="tk-modal-title" class="text-[18px] font-bold">เพิ่มภารกิจ</h2>
      <button type="button" class="text-[#718096]" onclick="closeModals()">✕</button>
    </div>
    <input type="hidden" id="tk-id">
    <div class="form-grid">
      <div class="field full">
        <label>ชื่อภารกิจ *</label>
        <input id="tk-title" required maxlength="255">
      </div>
      <div class="field">
        <label>วิชา</label>
        <input id="tk-subject" value="ทั่วไป">
      </div>
      <div class="field">
        <label>หมวดหมู่</label>
        <input id="tk-category" value="ทั่วไป">
      </div>
      
      <div class="field full">
        <label>ประเภทเงื่อนไขความสำเร็จ</label>
        <select id="tk-completion-type" onchange="toggleTaskConditionFields()">
          <option value="manual">ให้นักเรียนติ๊กสำเร็จด้วยตัวเอง (Manual)</option>
          <option value="complete_lesson">เรียนบทเรียนให้จบ</option>
          <option value="complete_course">เรียนจบคอร์ส</option>
          <option value="submit_test">ทำข้อสอบ/แบบฝึกหัด (ส่งคำตอบ)</option>
          <option value="pass_test">สอบผ่านข้อสอบ (ใช้เกณฑ์คะแนน)</option>
        </select>
      </div>
      
      <div class="field full" id="group-course" hidden>
        <label>เลือกคอร์ส</label>
        <select id="tk-course-id"><option value="">-- เลือกคอร์ส --</option></select>
      </div>
      
      <div class="field full" id="group-lesson" hidden>
        <label>เลือกบทเรียน</label>
        <select id="tk-lesson-id"><option value="">-- เลือกบทเรียน --</option></select>
      </div>
      
      <div class="field full" id="group-exam" hidden>
        <label>เลือกข้อสอบ (Active)</label>
        <select id="tk-exam-id"><option value="">-- เลือกข้อสอบ --</option></select>
      </div>
      
      <div class="field" id="group-score" hidden>
        <label>คะแนนขั้นต่ำ (เปอร์เซ็นต์)</label>
        <input id="tk-pass-score" type="number" step="0.01" min="0" max="100" placeholder="เช่น 70.00">
      </div>
      
      <div class="field" id="group-score-mode" hidden>
        <label>เลือกคะแนนที่จะนำมาคิด</label>
        <select id="tk-score-mode">
          <option value="best">คะแนนสอบที่ดีที่สุด (Best)</option>
          <option value="latest">คะแนนสอบล่าสุด (Latest)</option>
        </select>
      </div>

      <div class="field">
        <label>แต้ม NC เมื่อทำสำเร็จ</label>
        <input id="tk-points" type="number" min="0" max="1000" value="10">
      </div>
      <div class="field">
        <label>ลำดับ (น้อยไปมาก)</label>
        <input id="tk-sort-order" type="number" min="0" value="0">
      </div>
      
      <div class="field full" style="display: flex; gap: 16px; margin-top: 8px;">
        <label style="display:flex; align-items:center; gap:6px; margin:0;"><input id="tk-is-active" type="checkbox" checked> เผยแพร่</label>
        <label style="display:flex; align-items:center; gap:6px; margin:0;"><input id="tk-is-required" type="checkbox" checked> ภารกิจบังคับ</label>
      </div>
      
      <div class="field full">
        <label>เงื่อนไขก่อนหน้า (ต้องทำภารกิจใดก่อน?)</label>
        <select id="tk-prereq-id">
            <option value="">-- ไม่มีเงื่อนไขก่อนหน้า --</option>
        </select>
      </div>
    </div>
    <div class="modal-actions">
      <button class="road-btn" type="button" onclick="closeModals()">ยกเลิก</button>
      <button class="road-btn primary" type="submit">บันทึกภารกิจ</button>
    </div>
  </form>
</div>

<script>
let roadmaps = [];
let currentTasks = [];
let activeRoadmapId = null;
let options = { exams: [], courses: [], lessons: [] };

const labels = {tcas: 'ม.6 / TCAS', m4: 'ม.4 - ม.5', m1: 'ม.1 - ม.3'};
const statusLabels = {draft: 'ฉบับร่าง', published: 'เผยแพร่', archived: 'จัดเก็บ'};
const cTypes = {
    manual: 'ติ๊กเอง',
    complete_lesson: 'จบบทเรียน',
    complete_course: 'จบคอร์ส',
    submit_test: 'ส่งข้อสอบ',
    pass_test: 'สอบผ่าน'
};

const esc = val => { const n = document.createElement('div'); n.textContent = String(val??''); return n.innerHTML; };

async function api(payload = {}, query = '') {
    const response = await fetch('roadmap-api.php' + query, {
        method: query ? 'GET' : 'POST',
        headers: {'Content-Type': 'application/json'},
        body: query ? null : JSON.stringify(payload)
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'ดำเนินการไม่สำเร็จ');
    return data;
}

function showView(view) {
    document.getElementById('view-roadmaps').hidden = view !== 'roadmaps';
    document.getElementById('view-tasks').hidden = view !== 'tasks';
}

function switchRmTab(tabId) {
    document.querySelectorAll('.rm-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('tab-' + tabId).classList.add('active');
}

function toggleAssignTarget() {
    document.getElementById('group-assign-target').hidden = document.getElementById('assign-type').value !== 'stage';
}

async function assignRoadmap() {
    if(!confirm('ยืนยันการมอบหมาย Roadmap ให้นักเรียนกลุ่มเป้าหมาย?')) return;
    try {
        await api({
            action: 'assign_roadmap',
            roadmap_id: activeRoadmapId,
            assign_type: document.getElementById('assign-type').value,
            assign_target: document.getElementById('assign-target').value,
            is_mandatory: document.getElementById('assign-mandatory').checked
        });
        alert('มอบหมายเรียบร้อยแล้ว!');
        loadRoadmaps();
    } catch(err) { alert(err.message); }
}

async function loadRoadmaps() {
    try {
        const data = await api({ action: 'list_roadmaps' });
        roadmaps = data.roadmaps;
        renderRoadmaps();
    } catch(e) { alert(e.message); }
}

async function loadOptions() {
    try {
        const data = await api({ action: 'list_options' });
        options.exams = data.exams || [];
        options.courses = data.courses || [];
        options.lessons = data.lessons || [];
        
        let examHtml = '<option value="">-- เลือกข้อสอบ --</option>';
        options.exams.forEach(ex => examHtml += `<option value="${ex.id}">${esc(ex.title)}</option>`);
        document.getElementById('tk-exam-id').innerHTML = examHtml;
        
        let courseHtml = '<option value="">-- เลือกคอร์ส --</option>';
        options.courses.forEach(co => courseHtml += `<option value="${co.id}">${esc(co.title)}</option>`);
        document.getElementById('tk-course-id').innerHTML = courseHtml;
    } catch(e) { console.error('Failed to load options', e); }
}

function renderRoadmaps() {
    const list = document.getElementById('roadmap-list-body');
    if (!roadmaps.length) {
        list.innerHTML = '<div class="road-row"><span style="grid-column: 1/-1; text-align:center;">ไม่มีข้อมูล Roadmap</span></div>';
        return;
    }
    list.innerHTML = roadmaps.map(r => `
        <div class="road-row">
            <span class="stage">${labels[r.stage]}</span>
            <div>
                <div class="title" onclick="openRoadmapDetail(${r.id})">${esc(r.title)}</div>
                <div class="sub">${esc(r.description)}</div>
            </div>
            <span>${r.student_count} คน</span>
            <span>${r.task_count} งาน</span>
            <span><span class="roadmap-chip" style="background:#eef2ff;padding:4px 8px;border-radius:6px;font-size:11px;">${statusLabels[r.status]}</span></span>
            <div class="road-actions">
                <button onclick="openRoadmapModal(${r.id})">แก้ไข</button>
                <button class="danger" onclick="deleteRoadmap(${r.id})">ลบ</button>
            </div>
        </div>
    `).join('');
}

async function openRoadmapDetail(id) {
    activeRoadmapId = id;
    const r = roadmaps.find(x => Number(x.id) === Number(id));
    if(!r) return;
    document.getElementById('current-roadmap-name').textContent = r.title;
    document.getElementById('roadmap-hero-title').textContent = r.title;
    document.getElementById('roadmap-hero-desc').textContent = 'จัดการภารกิจภายใน Roadmap นี้';
    
    await loadTasks();
    showView('tasks');
}

async function loadTasks() {
    try {
        const data = await api({}, `?action=list_tasks&roadmap_id=${activeRoadmapId}`);
        currentTasks = data.tasks;
        renderTasks();
    } catch(e) { alert(e.message); }
}

function renderTasks() {
    const list = document.getElementById('task-list-body');
    if (!currentTasks.length) {
        list.innerHTML = '<div class="road-row"><span style="grid-column: 1/-1; text-align:center;">ไม่มีภารกิจใน Roadmap นี้</span></div>';
        return;
    }
    list.innerHTML = currentTasks.map(t => `
        <div class="road-row">
            <span>${t.sort_order}</span>
            <div>
                <div style="font-weight:800; color:#102b58; font-size:13px;">${esc(t.title)} ${Number(t.is_required)?'':'<span style="color:#aaa">(เสริม)</span>'}</div>
                <div class="sub">${esc(t.subject)} · ${esc(t.category)}</div>
            </div>
            <span>${esc(t.category)}</span>
            <span style="color:#2369dd;font-weight:700;">${cTypes[t.completion_type]}</span>
            <strong>+${t.points_reward} NC</strong>
            <div class="road-actions">
                <button onclick="openTaskModal(${t.id})">แก้ไข</button>
                <button class="danger" onclick="deleteTask(${t.id})">ลบ</button>
            </div>
        </div>
    `).join('');
}

// Modals
function openRoadmapModal(id = null) {
    const r = id ? roadmaps.find(x => Number(x.id) === Number(id)) : null;
    document.getElementById('roadmap-form').reset();
    document.getElementById('rm-id').value = r?.id || '';
    document.getElementById('rm-modal-title').textContent = r ? 'แก้ไข Roadmap' : 'สร้าง Roadmap';
    if(r) {
        document.getElementById('rm-title').value = r.title;
        document.getElementById('rm-desc').value = r.description;
        document.getElementById('rm-stage').value = r.stage;
        document.getElementById('rm-status').value = r.status;
    }
    document.getElementById('roadmap-modal').classList.add('show');
}

function updateLessonDropdown(courseId, selectedLessonId = null) {
    const select = document.getElementById('tk-lesson-id');
    const filtered = options.lessons.filter(l => Number(l.course_id) === Number(courseId));
    let html = '<option value="">-- เลือกบทเรียน --</option>';
    filtered.forEach(l => html += `<option value="${l.id}">${esc(l.title)}</option>`);
    select.innerHTML = html;
    if(selectedLessonId) select.value = selectedLessonId;
}

document.getElementById('tk-course-id').addEventListener('change', e => {
    updateLessonDropdown(e.target.value);
});

function toggleTaskConditionFields() {
    const type = document.getElementById('tk-completion-type').value;
    document.getElementById('group-course').hidden = !['complete_course', 'complete_lesson'].includes(type);
    document.getElementById('group-lesson').hidden = type !== 'complete_lesson';
    document.getElementById('group-exam').hidden = !['submit_test', 'pass_test'].includes(type);
    document.getElementById('group-score').hidden = type !== 'pass_test';
    document.getElementById('group-score-mode').hidden = type !== 'pass_test';
}

function openTaskModal(id = null) {
    const t = id ? currentTasks.find(x => Number(x.id) === Number(id)) : null;
    document.getElementById('task-form').reset();
    document.getElementById('tk-id').value = t?.id || '';
    document.getElementById('tk-modal-title').textContent = t ? 'แก้ไขภารกิจ' : 'เพิ่มภารกิจ';
    
    if(t) {
        document.getElementById('tk-title').value = t.title;
        document.getElementById('tk-subject').value = t.subject;
        document.getElementById('tk-category').value = t.category;
        document.getElementById('tk-completion-type').value = t.completion_type;
        document.getElementById('tk-course-id').value = t.ref_course_id || '';
        updateLessonDropdown(t.ref_course_id, t.ref_lesson_id);
        document.getElementById('tk-exam-id').value = t.ref_exam_id || '';
        document.getElementById('tk-pass-score').value = t.pass_score || '';
        document.getElementById('tk-score-mode').value = t.score_mode || 'latest';
        document.getElementById('tk-points').value = t.points_reward;
        document.getElementById('tk-sort-order').value = t.sort_order;
        document.getElementById('tk-is-active').checked = Number(t.is_active) === 1;
        document.getElementById('tk-is-required').checked = Number(t.is_required) === 1;
        
        let prereqHtml = '<option value="">-- ไม่มีเงื่อนไขก่อนหน้า --</option>';
        currentTasks.filter(x => Number(x.id) !== Number(id)).forEach(pt => {
            prereqHtml += `<option value="${pt.id}" ${t.prerequisite_task_id == pt.id ? 'selected' : ''}>${esc(pt.title)}</option>`;
        });
        document.getElementById('tk-prereq-id').innerHTML = prereqHtml;
    } else {
        document.getElementById('tk-completion-type').value = 'manual';
        let prereqHtml = '<option value="">-- ไม่มีเงื่อนไขก่อนหน้า --</option>';
        currentTasks.forEach(pt => {
            prereqHtml += `<option value="${pt.id}">${esc(pt.title)}</option>`;
        });
        document.getElementById('tk-prereq-id').innerHTML = prereqHtml;
    }
    
    toggleTaskConditionFields();
    document.getElementById('task-modal').classList.add('show');
}

function closeModals() {
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
}

// Submits
document.getElementById('roadmap-form').addEventListener('submit', async e => {
    e.preventDefault();
    try {
        await api({
            action: 'save_roadmap',
            id: document.getElementById('rm-id').value,
            title: document.getElementById('rm-title').value,
            description: document.getElementById('rm-desc').value,
            stage: document.getElementById('rm-stage').value,
            status: document.getElementById('rm-status').value
        });
        closeModals();
        loadRoadmaps();
    } catch(err) { alert(err.message); }
});

document.getElementById('task-form').addEventListener('submit', async e => {
    e.preventDefault();
    try {
        await api({
            action: 'save_task',
            id: document.getElementById('tk-id').value,
            roadmap_id: activeRoadmapId,
            title: document.getElementById('tk-title').value,
            subject: document.getElementById('tk-subject').value,
            category: document.getElementById('tk-category').value,
            completion_type: document.getElementById('tk-completion-type').value,
            ref_course_id: document.getElementById('tk-course-id').value || null,
            ref_lesson_id: document.getElementById('tk-lesson-id').value || null,
            ref_exam_id: document.getElementById('tk-exam-id').value || null,
            pass_score: document.getElementById('tk-pass-score').value || null,
            score_mode: document.getElementById('tk-score-mode').value,
            points_reward: document.getElementById('tk-points').value,
            sort_order: document.getElementById('tk-sort-order').value,
            is_active: document.getElementById('tk-is-active').checked,
            is_required: document.getElementById('tk-is-required').checked,
            prerequisite_task_id: document.getElementById('tk-prereq-id').value || null
        });
        closeModals();
        loadTasks();
    } catch(err) { alert(err.message); }
});

async function deleteRoadmap(id) {
    if(!confirm('ลบ Roadmap นี้พร้อมกับภารกิจและความคืบหน้าทั้งหมดหรือไม่? (ไม่สามารถย้อนกลับได้)')) return;
    try { await api({ action: 'delete_roadmap', id }); loadRoadmaps(); } catch(err) { alert(err.message); }
}

async function deleteTask(id) {
    if(!confirm('ลบภารกิจนี้หรือไม่?')) return;
    try { await api({ action: 'delete_task', id }); loadTasks(); } catch(err) { alert(err.message); }
}

loadOptions();
loadRoadmaps();
</script>
</body>
</html>
