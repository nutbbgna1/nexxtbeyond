<?php
/**
 * student/tests.php — รายการข้อสอบที่เปิดอยู่ให้ทำ
 */
$pageTitle   = 'ข้อสอบที่พร้อมทำ';
$currentPage = 'tests.php';
require_once __DIR__ . '/includes/guard.php';

// ดึงข้อสอบทั้งหมดที่ is_published=1 และ status=active
$stmt = $pdo->query(
    "SELECT e.id, e.title, e.subject, e.grade, e.type, e.time_limit_minutes,
            e.is_ai_generated, e.requires_login, e.updated_at,
            COUNT(q.id) AS question_count
     FROM exams e
     LEFT JOIN exam_questions q ON q.exam_id = e.id
     WHERE e.is_published = 1 AND e.status = 'active'
     GROUP BY e.id
     ORDER BY e.updated_at DESC, e.id DESC"
);
$exams = $stmt->fetchAll();

// ดึง attempts ของ user นี้ เพื่อแสดงว่าเคยทำหรือยัง
$stmtDone = $pdo->prepare(
    'SELECT exam_id, MAX(score) as best_score, COUNT(*) as times
     FROM test_attempts WHERE user_id = :uid AND completed_at IS NOT NULL
     GROUP BY exam_id'
);
$stmtDone->execute([':uid' => $currentUser['id']]);
$doneMap = [];
foreach ($stmtDone->fetchAll() as $row) {
    $doneMap[$row['exam_id']] = $row;
}
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - Next Beyond Academy</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="../assets/css/student-portal.css?v=<?= filemtime(__DIR__ . '/../assets/css/student-portal.css') ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="student-portal bg-[#f4f7fb] text-navy-950 font-sans antialiased">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col ml-[240px] max-[960px]:ml-0 min-w-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="flex-1 p-8 max-[640px]:p-4">

      <!-- Filter Bar -->
      <div class="mb-6 flex items-center gap-3 flex-wrap">
        <div class="relative flex-1 min-w-[200px]">
          <input id="exam-search" type="search" placeholder="ค้นหาข้อสอบ..." class="w-full h-11 pl-10 pr-4 rounded-xl bg-white border border-[#dce4ef] outline-none focus:border-pink-400 text-[14px]">
          <svg class="w-4 h-4 text-[#94a3b8] absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <select id="type-filter" class="h-11 px-4 rounded-xl bg-white border border-[#dce4ef] text-[14px] outline-none font-medium text-navy-950">
          <option value="">ทุกประเภท</option>
          <option value="placement">Placement</option>
          <option value="pretest">Pre-test</option>
          <option value="quiz">Quiz</option>
          <option value="posttest">Post-test</option>
        </select>
        <div class="text-[13px] text-[#65738a] font-medium ml-auto" id="exam-count"><?= count($exams) ?> ชุด</div>
      </div>

      <?php if (empty($exams)): ?>
        <div class="bg-white rounded-[20px] border border-[#e8ecf2] p-16 text-center">
          <svg class="w-16 h-16 mx-auto mb-4 text-[#dce4ef]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <p class="text-[16px] font-bold text-navy-950 mb-1">ยังไม่มีข้อสอบที่เปิดอยู่</p>
          <p class="text-[14px] text-[#65738a]">รอครูเปิดข้อสอบ หรือกลับมาใหม่ภายหลัง</p>
        </div>
      <?php else: ?>
        <div id="exam-grid" class="grid grid-cols-3 gap-5 max-[1200px]:grid-cols-2 max-[700px]:grid-cols-1">
          <?php foreach ($exams as $exam):
            $done = $doneMap[$exam['id']] ?? null;
            $typeLabel = ['quiz'=>'Quiz','placement'=>'Placement','pretest'=>'Pre-test','posttest'=>'Post-test'][$exam['type']] ?? $exam['type'];
            $typeBg    = ['quiz'=>'bg-blue-50 text-blue-600','placement'=>'bg-purple-50 text-purple-600','pretest'=>'bg-green-50 text-green-600','posttest'=>'bg-orange-50 text-orange-600'][$exam['type']] ?? 'bg-[#f4f7fb] text-[#65738a]';
          ?>
          <article class="exam-card bg-white rounded-[20px] border border-[#e8ecf2] p-5 flex flex-col hover:border-pink-200 hover:shadow-[0_8px_24px_rgba(231,45,130,0.08)] transition-all"
                   data-title="<?= htmlspecialchars(strtolower($exam['title'])) ?>"
                   data-subject="<?= htmlspecialchars(strtolower($exam['subject'] ?? '')) ?>"
                   data-type="<?= htmlspecialchars($exam['type']) ?>">
            <div class="flex items-start justify-between mb-4">
              <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg <?= $typeBg ?>"><?= $typeLabel ?></span>
              <?php if ($done): ?>
                <span class="text-[11px] font-bold text-green-600 flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                  ทำแล้ว <?= $done['times'] ?> ครั้ง
                </span>
              <?php endif; ?>
            </div>

            <h3 class="text-[15px] font-bold text-navy-950 mb-1 flex-1"><?= htmlspecialchars($exam['title']) ?></h3>
            <p class="text-[12px] text-[#65738a] mb-4"><?= htmlspecialchars($exam['subject'] ?? 'ทั่วไป') ?> <?= $exam['grade'] ? '· ' . htmlspecialchars($exam['grade']) : '' ?></p>

            <div class="flex items-center gap-3 text-[12px] text-[#65738a] mb-5">
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= (int)$exam['question_count'] ?> ข้อ
              </span>
              <?php if ($exam['time_limit_minutes']): ?>
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= (int)$exam['time_limit_minutes'] ?> นาที
              </span>
              <?php endif; ?>
              <?php if ($exam['is_ai_generated']): ?>
              <span class="flex items-center gap-1 text-pink-500">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                AI
              </span>
              <?php endif; ?>
            </div>

            <?php if ($done && $done['best_score'] !== null): ?>
              <div class="mb-4 p-3 rounded-xl bg-[#f0fdf4] border border-[#bbf7d0]">
                <div class="text-[12px] text-[#15803d] font-bold">คะแนนสูงสุด: <?= round((float)$done['best_score']) ?>%</div>
              </div>
            <?php endif; ?>

            <a href="take-test.php?id=<?= $exam['id'] ?>" class="w-full h-11 rounded-[12px] bg-pink-500 text-white font-bold text-[14px] flex items-center justify-center gap-2 hover:bg-pink-600 transition-colors shadow-[0_4px_12px_rgba(231,45,130,0.25)] mt-auto">
              <?= $done ? 'ทำอีกครั้ง' : 'เริ่มทำข้อสอบ' ?>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
          </article>
          <?php endforeach; ?>
        </div>
        <div id="no-results" class="hidden bg-white rounded-[20px] border border-[#e8ecf2] p-10 text-center text-[#65738a]">ไม่พบข้อสอบที่ตรงกับการค้นหา</div>
      <?php endif; ?>

    </main>
  </div>
</div>
<script>
  const searchEl = document.getElementById('exam-search');
  const typeEl   = document.getElementById('type-filter');
  const countEl  = document.getElementById('exam-count');
  function filterExams() {
    const q = (searchEl?.value || '').trim().toLowerCase();
    const t = typeEl?.value || '';
    const cards = document.querySelectorAll('.exam-card');
    let visible = 0;
    cards.forEach(card => {
      const matchQ = !q || card.dataset.title.includes(q) || card.dataset.subject.includes(q);
      const matchT = !t || card.dataset.type === t;
      const show = matchQ && matchT;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if (countEl) countEl.textContent = visible + ' ชุด';
    const noRes = document.getElementById('no-results');
    if (noRes) noRes.classList.toggle('hidden', visible > 0);
  }
  searchEl?.addEventListener('input', filterExams);
  typeEl?.addEventListener('change', filterExams);
</script>
</body>
</html>
