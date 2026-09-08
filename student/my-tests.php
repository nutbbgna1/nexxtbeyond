<?php
/**
 * student/my-tests.php — ประวัติการทำข้อสอบ
 */
$pageTitle   = 'ประวัติข้อสอบ';
$currentPage = 'my-tests.php';
require_once __DIR__ . '/includes/guard.php';

$stmtAttempts = $pdo->prepare(
    'SELECT a.id, a.score, a.correct_count, a.total_questions, a.completed_at, a.exam_id,
            e.title, e.subject, e.grade, e.type
     FROM test_attempts a
     INNER JOIN exams e ON e.id = a.exam_id
     WHERE a.user_id = :uid AND a.completed_at IS NOT NULL
     ORDER BY a.completed_at DESC'
);
$stmtAttempts->execute([':uid' => $currentUser['id']]);
$attempts = $stmtAttempts->fetchAll();

// stats
$totalAttempts = count($attempts);
$avgScore = $totalAttempts > 0 ? round(array_sum(array_column($attempts, 'score')) / $totalAttempts, 1) : 0;
$bestScore = $totalAttempts > 0 ? max(array_column($attempts, 'score')) : 0;
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
<body class="student-portal bg-[#f4f7fb] font-sans antialiased">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col ml-[240px] max-[960px]:ml-0 min-w-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="flex-1 p-8 max-[640px]:p-4">

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-4 mb-8 max-[700px]:grid-cols-1">
        <div class="bg-white rounded-[18px] border border-[#e8ecf2] p-5">
          <div class="text-[12px] font-bold text-[#65738a] uppercase tracking-wide mb-2">ครั้งที่ทำทั้งหมด</div>
          <div class="text-[32px] font-black text-navy-950"><?= $totalAttempts ?></div>
        </div>
        <div class="bg-white rounded-[18px] border border-[#e8ecf2] p-5">
          <div class="text-[12px] font-bold text-[#65738a] uppercase tracking-wide mb-2">คะแนนเฉลี่ย</div>
          <div class="text-[32px] font-black text-navy-950"><?= $avgScore ?>%</div>
        </div>
        <div class="bg-white rounded-[18px] border border-[#e8ecf2] p-5">
          <div class="text-[12px] font-bold text-[#65738a] uppercase tracking-wide mb-2">คะแนนสูงสุด</div>
          <div class="text-[32px] font-black text-pink-500"><?= round((float)$bestScore) ?>%</div>
        </div>
      </div>

      <!-- Table -->
      <?php if (empty($attempts)): ?>
        <div class="bg-white rounded-[20px] border border-[#e8ecf2] p-16 text-center">
          <svg class="w-14 h-14 mx-auto mb-4 text-[#dce4ef]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          <p class="text-[16px] font-bold text-navy-950 mb-1">ยังไม่มีประวัติ</p>
          <a href="tests.php" class="mt-4 inline-flex items-center gap-2 h-10 px-5 rounded-xl bg-pink-500 text-white font-bold text-[13px] hover:bg-pink-600 transition-colors">เริ่มทำข้อสอบแรก →</a>
        </div>
      <?php else: ?>
        <div class="bg-white rounded-[20px] border border-[#e8ecf2] overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[700px]">
              <thead>
                <tr class="bg-[#f8fafc] border-b border-[#e8ecf2]">
                  <th class="px-5 py-3 text-[11px] font-black tracking-wider text-[#65738a] uppercase">ชื่อข้อสอบ</th>
                  <th class="px-5 py-3 text-[11px] font-black tracking-wider text-[#65738a] uppercase text-center">คะแนน</th>
                  <th class="px-5 py-3 text-[11px] font-black tracking-wider text-[#65738a] uppercase text-center">ถูก/ทั้งหมด</th>
                  <th class="px-5 py-3 text-[11px] font-black tracking-wider text-[#65738a] uppercase">วันที่ทำ</th>
                  <th class="px-5 py-3"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#f1f5f9]">
                <?php foreach ($attempts as $att):
                  $pct = round((float)$att['score'], 1);
                  $scoreColor = $pct >= 80 ? 'text-[#15803d]' : ($pct >= 60 ? 'text-blue-700' : 'text-red-600');
                  $typeLabel  = ['quiz'=>'Quiz','placement'=>'Placement','pretest'=>'Pre-test','posttest'=>'Post-test'][$att['type']] ?? $att['type'];
                ?>
                  <tr class="hover:bg-[#f8fafc] transition-colors">
                    <td class="px-5 py-4">
                      <div class="font-bold text-[14px] text-navy-950"><?= htmlspecialchars($att['title']) ?></div>
                      <div class="text-[11px] text-[#65738a] mt-0.5"><?= htmlspecialchars($att['subject'] ?? '') ?> · <span class="font-medium"><?= $typeLabel ?></span></div>
                    </td>
                    <td class="px-5 py-4 text-center">
                      <span class="text-[20px] font-black <?= $scoreColor ?>"><?= $pct ?>%</span>
                    </td>
                    <td class="px-5 py-4 text-center text-[13px] font-bold text-[#65738a]">
                      <?= $att['correct_count'] ?>/<?= $att['total_questions'] ?>
                    </td>
                    <td class="px-5 py-4 text-[13px] text-[#65738a]">
                      <?= date('d/m/Y H:i', strtotime($att['completed_at'])) ?>
                    </td>
                    <td class="px-5 py-4 text-right whitespace-nowrap">
                      <a href="test-result.php?id=<?= $att['id'] ?>" class="text-[#2369dd] text-[13px] font-bold hover:text-pink-500 mr-3 transition-colors">ดูเฉลย</a>
                      <a href="take-test.php?id=<?= $att['exam_id'] ?>" class="text-[#65738a] text-[13px] font-bold hover:text-navy-950 transition-colors">ทำอีกครั้ง</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="px-5 py-3 border-t border-[#e8ecf2] text-[13px] text-[#65738a]">ทั้งหมด <?= $totalAttempts ?> รายการ</div>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>
</body>
</html>
