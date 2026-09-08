<?php
/**
 * student/test-result.php — ดูผลข้อสอบและเฉลย
 * ?id={attemptId}
 */
$pageTitle   = 'ผลข้อสอบ';
$currentPage = 'my-tests.php';
require_once __DIR__ . '/includes/guard.php';

$attemptId = (int)($_GET['id'] ?? 0);
if ($attemptId < 1) { header('Location: my-tests.php'); exit; }

// ดึง attempt
$stmtAtt = $pdo->prepare(
    'SELECT a.*, e.title, e.subject, e.grade, e.type, e.time_limit_minutes
     FROM test_attempts a
     INNER JOIN exams e ON e.id = a.exam_id
     WHERE a.id = :id AND a.user_id = :uid LIMIT 1'
);
$stmtAtt->execute([':id' => $attemptId, ':uid' => $currentUser['id']]);
$attempt = $stmtAtt->fetch();

if (!$attempt || !$attempt['completed_at']) {
    header('Location: my-tests.php');
    exit;
}

// ดึงคำถามพร้อมคำตอบที่นักเรียนเลือก
$stmtQ = $pdo->prepare(
    'SELECT q.id, q.sort_order, q.question_text, q.options, q.correct_answer, q.explanation, q.skill,
            ta.selected_answer, ta.is_correct
     FROM exam_questions q
     LEFT JOIN test_answers ta ON ta.question_id = q.id AND ta.attempt_id = :att_id
     WHERE q.exam_id = :eid
     ORDER BY q.sort_order, q.id'
);
$stmtQ->execute([':att_id' => $attemptId, ':eid' => $attempt['exam_id']]);
$questions = $stmtQ->fetchAll();

// stats
$total   = (int)$attempt['total_questions'];
$correct = (int)$attempt['correct_count'];
$score   = round((float)$attempt['score'], 1);
$grade   = $score >= 90 ? ['Excellent', 'text-[#15803d]', 'bg-[#f0fdf4]', '#bbf7d0']
         : ($score >= 75 ? ['Good', 'text-blue-700', 'bg-blue-50', '#bfdbfe']
         : ($score >= 60 ? ['Pass', 'text-[#92400e]', 'bg-amber-50', '#fde68a']
         : ['ต้องพัฒนา', 'text-red-700', 'bg-red-50', '#fecaca']));

// Skill breakdown
$skillStats = [];
foreach ($questions as $q) {
    $skill = $q['skill'] ?: 'ทั่วไป';
    if (!isset($skillStats[$skill])) $skillStats[$skill] = ['total' => 0, 'correct' => 0];
    $skillStats[$skill]['total']++;
    if ($q['is_correct']) $skillStats[$skill]['correct']++;
}
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> - Next Beyond Academy</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="bg-[#f4f7fb] font-sans antialiased">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col ml-[240px] max-[960px]:ml-0 min-w-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="flex-1 p-8 max-[640px]:p-4 max-w-[860px]">

      <!-- Score Card -->
      <div class="<?= $grade[2] ?> border border-[<?= $grade[3] ?>] rounded-[24px] p-8 mb-6 text-center">
        <p class="text-[13px] font-bold <?= $grade[1] ?> mb-2"><?= $grade[0] ?></p>
        <div class="text-[64px] font-black <?= $grade[1] ?> leading-none mb-2"><?= $score ?>%</div>
        <p class="text-[16px] font-bold text-navy-950 mb-1"><?= htmlspecialchars($attempt['title']) ?></p>
        <p class="text-[13px] text-[#65738a]">
          ถูก <?= $correct ?> จาก <?= $total ?> ข้อ
          <?php if ($attempt['time_spent_seconds']): ?>
            · ใช้เวลา <?= gmdate('i:s', (int)$attempt['time_spent_seconds']) ?> นาที
          <?php endif; ?>
          · <?= date('d/m/Y H:i', strtotime($attempt['completed_at'])) ?>
        </p>
      </div>

      <!-- Skill Breakdown -->
      <?php if (count($skillStats) > 1): ?>
        <div class="bg-white rounded-[20px] border border-[#e8ecf2] p-6 mb-6">
          <h3 class="text-[15px] font-bold text-navy-950 mb-4">คะแนนแยกตามทักษะ</h3>
          <div class="space-y-3">
            <?php foreach ($skillStats as $skill => $s):
              $pct = $s['total'] > 0 ? round($s['correct'] / $s['total'] * 100) : 0;
              $barColor = $pct >= 80 ? 'bg-green-500' : ($pct >= 60 ? 'bg-blue-500' : 'bg-red-400');
            ?>
              <div>
                <div class="flex justify-between mb-1">
                  <span class="text-[13px] font-bold text-navy-950"><?= htmlspecialchars($skill) ?></span>
                  <span class="text-[13px] font-bold text-[#65738a]"><?= $s['correct'] ?>/<?= $s['total'] ?> (<?= $pct ?>%)</span>
                </div>
                <div class="h-2.5 bg-[#f1f5f9] rounded-full overflow-hidden">
                  <div class="h-full <?= $barColor ?> rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Questions Review -->
      <div class="space-y-4">
        <h3 class="text-[16px] font-bold text-navy-950">รายละเอียดทุกข้อ</h3>
        <?php foreach ($questions as $i => $q):
          $opts    = json_decode($q['options'], true) ?: [];
          $sel     = $q['selected_answer'] !== null ? (int)$q['selected_answer'] : -1;
          $correct = (int)$q['correct_answer'];
          $isOk    = (bool)$q['is_correct'];
        ?>
          <div class="bg-white rounded-[18px] border-2 <?= $isOk ? 'border-[#22c55e]' : 'border-[#ef4444]' ?> p-5">
            <div class="flex items-start gap-3 mb-4">
              <div class="w-8 h-8 rounded-full <?= $isOk ? 'bg-[#22c55e]' : 'bg-[#ef4444]' ?> flex items-center justify-center text-white shrink-0">
                <?php if ($isOk): ?>
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <?php else: ?>
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                <?php endif; ?>
              </div>
              <div class="text-[14px] font-bold text-navy-950 whitespace-pre-wrap leading-relaxed">
                <span class="text-pink-500 mr-1">ข้อ <?= $i + 1 ?>.</span><?= htmlspecialchars($q['question_text']) ?>
              </div>
            </div>

            <div class="space-y-2 ml-11">
              <?php foreach ($opts as $oi => $opt):
                $isSelected = $sel === $oi;
                $isAnswer   = $correct === $oi;
                $cls = $isAnswer
                  ? 'border-[#22c55e] bg-[#f0fdf4] text-[#15803d]'
                  : ($isSelected && !$isOk ? 'border-[#ef4444] bg-[#fef2f2] text-[#b91c1c]' : 'border-[#e8ecf2] text-[#65738a]');
              ?>
                <div class="flex items-center gap-2.5 p-3 rounded-xl border-2 <?= $cls ?>">
                  <span class="w-6 h-6 rounded-full border-2 <?= $isAnswer ? 'border-[#22c55e] bg-[#22c55e]' : ($isSelected ? 'border-[#ef4444] bg-[#ef4444]' : 'border-[#dce4ef]') ?> flex items-center justify-center text-white shrink-0">
                    <?php if ($isAnswer): ?>
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <?php elseif ($isSelected): ?>
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <?php endif; ?>
                  </span>
                  <span class="text-[13px] font-medium leading-snug"><?= htmlspecialchars($opt) ?></span>
                  <?php if ($isAnswer): ?><span class="ml-auto text-[11px] font-bold text-[#15803d]">✓ เฉลย</span><?php endif; ?>
                  <?php if ($isSelected && !$isAnswer): ?><span class="ml-auto text-[11px] font-bold text-[#b91c1c]">คำตอบของคุณ</span><?php endif; ?>
                </div>
              <?php endforeach; ?>

              <?php if ($q['explanation']): ?>
                <div class="mt-3 p-4 rounded-xl bg-[#f8fafc] border border-[#e8ecf2]">
                  <div class="text-[12px] font-bold text-[#65738a] mb-1 uppercase tracking-wide">คำอธิบาย</div>
                  <p class="text-[13px] text-navy-950 font-medium leading-relaxed"><?= htmlspecialchars($q['explanation']) ?></p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap gap-3 mt-8 justify-center">
        <a href="take-test.php?id=<?= $attempt['exam_id'] ?>" class="h-11 px-6 rounded-xl bg-pink-500 text-white font-bold text-[14px] flex items-center gap-2 hover:bg-pink-600 transition-colors shadow-[0_4px_12px_rgba(231,45,130,0.25)]">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          ทำข้อสอบอีกครั้ง
        </a>
        <a href="my-tests.php" class="h-11 px-6 rounded-xl bg-white border border-[#dce4ef] text-navy-950 font-bold text-[14px] flex items-center gap-2 hover:bg-[#f4f7fb] transition-colors">
          ดูประวัติทั้งหมด
        </a>
        <a href="tests.php" class="h-11 px-6 rounded-xl bg-white border border-[#dce4ef] text-navy-950 font-bold text-[14px] flex items-center gap-2 hover:bg-[#f4f7fb] transition-colors">
          ข้อสอบชุดอื่น
        </a>
      </div>

    </main>
  </div>
</div>
</body>
</html>
