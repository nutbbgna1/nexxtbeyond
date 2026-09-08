<?php
/**
 * student/take-test.php — หน้าทำข้อสอบ
 * ?id={examId}
 */
$pageTitle   = 'กำลังทำข้อสอบ';
$currentPage = 'tests.php';
require_once __DIR__ . '/includes/guard.php';

$examId = (int)($_GET['id'] ?? 0);
if ($examId < 1) { header('Location: tests.php'); exit; }

// ดึงข้อมูลข้อสอบ
$stmtExam = $pdo->prepare(
    "SELECT id, title, subject, grade, type, time_limit_minutes, requires_login, status, is_published
     FROM exams WHERE id = :id LIMIT 1"
);
$stmtExam->execute([':id' => $examId]);
$exam = $stmtExam->fetch();

if (!$exam || $exam['status'] !== 'active' || !$exam['is_published']) {
    header('Location: tests.php?error=not_found');
    exit;
}

// ดึงคำถาม (ซ่อน correctAnswer ไว้ใน PHP — ไม่ส่งออก JS จนกว่าจะ submit)
$stmtQ = $pdo->prepare(
    'SELECT id, sort_order, question_text, passage, options, correct_answer, explanation, skill
     FROM exam_questions WHERE exam_id = :eid ORDER BY sort_order, id'
);
$stmtQ->execute([':eid' => $examId]);
$questions = $stmtQ->fetchAll();

if (empty($questions)) {
    header('Location: tests.php?error=no_questions');
    exit;
}

// สร้าง attempt row (เริ่มต้น)
$stmtIns = $pdo->prepare(
    'INSERT INTO test_attempts (user_id, exam_id, total_questions, started_at)
     VALUES (:uid, :eid, :total, NOW())'
);
$stmtIns->execute([
    ':uid'   => $currentUser['id'],
    ':eid'   => $examId,
    ':total' => count($questions),
]);
$attemptId = (int)$pdo->lastInsertId();

// เตรียม JSON ข้อมูลคำถาม (ไม่มี correctAnswer)
$questionsForJS = array_map(fn($q) => [
    'id'           => (string)$q['id'],
    'sortOrder'    => (int)$q['sort_order'],
    'questionText' => $q['question_text'],
    'passage'      => $q['passage'],
    'options'      => json_decode($q['options'], true) ?: [],
    'skill'        => $q['skill'],
], $questions);

$pageTitle = htmlspecialchars($exam['title']);
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> - Next Beyond</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    .option-btn { transition: all 0.15s; }
    .option-btn.selected { border-color: #f54696; background: #fff5fa; }
    .animate-fade { animation: fadeUp 0.2s ease-out both; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
  </style>
</head>
<body class="bg-[#f4f7fb] font-sans antialiased">

<!-- Topbar ข้อสอบ (ไม่มี sidebar) -->
<header class="sticky top-0 z-40 bg-white border-b border-[#e8ecf2] shadow-sm">
  <div class="flex items-center h-[60px] px-6 gap-4 max-w-[900px] mx-auto">
    <a href="tests.php" class="text-[#65738a] hover:text-navy-950 flex items-center gap-1.5 text-[13px] font-bold transition-colors">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      กลับ
    </a>
    <div class="flex-1 text-center">
      <div class="text-[14px] font-bold text-navy-950 truncate"><?= $pageTitle ?></div>
      <div class="text-[11px] text-[#65738a]"><?= htmlspecialchars($exam['subject'] ?? '') ?> · <?= count($questions) ?> ข้อ</div>
    </div>
    <!-- Timer -->
    <?php if ($exam['time_limit_minutes']): ?>
      <div id="timer" class="shrink-0 text-right">
        <div class="text-[11px] text-[#65738a] font-bold">เวลาที่เหลือ</div>
        <div id="timer-display" class="text-[18px] font-black text-navy-950 tabular-nums" data-seconds="<?= (int)$exam['time_limit_minutes'] * 60 ?>">
          <?= sprintf('%02d:%02d', $exam['time_limit_minutes'], 0) ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Question Nav Bar -->
  <div class="border-t border-[#f1f5f9] px-6 py-2 max-w-[900px] mx-auto">
    <div class="flex flex-wrap gap-1.5" id="q-nav">
      <?php foreach ($questions as $i => $q): ?>
        <button onclick="jumpTo(<?= $i ?>)"
                id="nav-<?= $i ?>"
                class="w-8 h-8 rounded-lg text-[12px] font-bold border border-[#e8ecf2] text-[#65738a] hover:border-pink-400 hover:text-pink-500 transition-colors"><?= $i + 1 ?></button>
      <?php endforeach; ?>
    </div>
  </div>
</header>

<!-- Main -->
<main class="max-w-[900px] mx-auto px-4 py-8 pb-32">
  <div id="question-container" class="space-y-6"></div>

  <!-- Submit -->
  <div class="fixed bottom-0 inset-x-0 bg-white border-t border-[#e8ecf2] shadow-[0_-4px_20px_rgba(15,42,83,0.08)] z-30">
    <div class="max-w-[900px] mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="text-[13px] text-[#65738a] font-medium">
        ตอบแล้ว <span id="answered-count" class="font-black text-navy-950">0</span> / <?= count($questions) ?> ข้อ
      </div>
      <button id="submit-btn" onclick="submitExam()"
              class="h-11 px-8 rounded-xl bg-pink-500 text-white font-bold text-[14px] shadow-[0_4px_14px_rgba(231,45,130,0.3)] hover:bg-pink-600 transition-colors disabled:opacity-50">
        ส่งคำตอบ
      </button>
    </div>
  </div>
</main>

<!-- Submit Form (hidden) -->
<form id="submit-form" method="POST" action="submit-test.php">
  <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
  <input type="hidden" name="exam_id" value="<?= $examId ?>">
  <input type="hidden" id="answers-json" name="answers">
</form>

<script>
  const QUESTIONS = <?= json_encode($questionsForJS, JSON_UNESCAPED_UNICODE) ?>;
  const answers   = {};

  function escapeHTML(value) {
    const element = document.createElement('div');
    element.textContent = String(value ?? '');
    return element.innerHTML;
  }

  function renderAll() {
    const container = document.getElementById('question-container');
    container.innerHTML = '';
    QUESTIONS.forEach((q, qi) => {
      const div = document.createElement('div');
      div.id        = 'q-' + qi;
      div.className = 'animate-fade bg-white rounded-[20px] border border-[#e8ecf2] p-6';
      div.innerHTML = `
        ${q.passage ? `<div class="mb-5 rounded-xl bg-[#f8fafc] border border-[#e8ecf2] p-4 text-[14px] text-[#4b5e7a] whitespace-pre-wrap leading-relaxed">${escapeHTML(q.passage)}</div>` : ''}
        <div class="text-[15px] font-bold text-navy-950 mb-5 whitespace-pre-wrap leading-relaxed">
          <span class="text-pink-500 font-black mr-2">ข้อ ${qi + 1}.</span>${escapeHTML(q.questionText)}
        </div>
        <div class="space-y-2">
          ${q.options.map((opt, oi) => `
            <button type="button" onclick="selectAnswer(${qi},${oi})"
                    id="opt-${qi}-${oi}"
                    class="option-btn w-full text-left flex items-center gap-3 p-3.5 rounded-xl border-2 border-[#e8ecf2] hover:border-pink-300 hover:bg-pink-50/30 ${answers[qi]===oi?'selected':''}">
              <span class="w-7 h-7 rounded-full border-2 ${answers[qi]===oi?'border-pink-500 bg-pink-500':'border-[#dce4ef]'} flex items-center justify-center shrink-0">
                ${answers[qi]===oi?'<svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>':''}
              </span>
              <span class="text-[14px] font-medium text-navy-950 leading-snug">${escapeHTML(opt)}</span>
            </button>
          `).join('')}
        </div>
      `;
      container.appendChild(div);
    });
    updateNav();
    updateCount();
  }

  function selectAnswer(qi, oi) {
    answers[qi] = oi;
    renderAll();
    // scroll to next unanswered
    const nextUnanswered = QUESTIONS.findIndex((_, i) => i > qi && answers[i] === undefined);
    if (nextUnanswered !== -1) {
      document.getElementById('q-' + nextUnanswered)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function jumpTo(qi) {
    document.getElementById('q-' + qi)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function updateNav() {
    QUESTIONS.forEach((_, i) => {
      const btn = document.getElementById('nav-' + i);
      if (!btn) return;
      if (answers[i] !== undefined) {
        btn.className = 'w-8 h-8 rounded-lg text-[12px] font-bold bg-pink-500 text-white border border-pink-500';
      } else {
        btn.className = 'w-8 h-8 rounded-lg text-[12px] font-bold border border-[#e8ecf2] text-[#65738a] hover:border-pink-400 hover:text-pink-500 transition-colors';
      }
    });
  }

  function updateCount() {
    document.getElementById('answered-count').textContent = Object.keys(answers).length;
  }

  function submitExam() {
    const unanswered = QUESTIONS.length - Object.keys(answers).length;
    if (unanswered > 0) {
      if (!confirm(`คุณยังทำไม่ครบอีก ${unanswered} ข้อ ต้องการส่งคำตอบเลยไหม?`)) return;
    }
    document.getElementById('answers-json').value = JSON.stringify(answers);
    document.getElementById('submit-form').submit();
  }

  // Timer
  const timerDisplay = document.getElementById('timer-display');
  if (timerDisplay) {
    let secs = parseInt(timerDisplay.dataset.seconds);
    const interval = setInterval(() => {
      secs--;
      if (secs <= 0) {
        clearInterval(interval);
        alert('หมดเวลา! ระบบจะส่งคำตอบอัตโนมัติ');
        submitExam();
        return;
      }
      const m = Math.floor(secs / 60).toString().padStart(2, '0');
      const s = (secs % 60).toString().padStart(2, '0');
      timerDisplay.textContent = m + ':' + s;
      if (secs <= 60) timerDisplay.classList.add('text-red-500');
    }, 1000);
  }

  renderAll();
</script>
</body>
</html>
