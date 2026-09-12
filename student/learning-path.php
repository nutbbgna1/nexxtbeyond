<?php
$pageTitle = 'เส้นทางการเรียน';
$currentPage = 'learning-path.php';
require_once __DIR__ . '/includes/guard.php';

$stmt = $pdo->prepare('SELECT * FROM learning_paths WHERE user_id = :user_id ORDER BY updated_at DESC, id DESC');
$stmt->execute([':user_id' => $currentUser['id']]);
$paths = $stmt->fetchAll();

$pathCount = count($paths);
$totalHours = 0;
$totalProgress = 0.0;
foreach ($paths as $path) {
    $totalHours += (int) ($path['hours_per_week'] ?? 0);
    $totalProgress += min(100, max(0, (float) ($path['progress_percent'] ?? 0)));
}
$averageProgress = $pathCount > 0 ? (int) round($totalProgress / $pathCount) : 0;

function learningPathSteps(array $path): array
{
    $decoded = json_decode((string) ($path['plan_data'] ?? ''), true);
    if (!is_array($decoded)) return [];
    if (isset($decoded['steps']) && is_array($decoded['steps'])) return array_values($decoded['steps']);
    return array_is_list($decoded) ? $decoded : [];
}

function learningStepTitle($step): string
{
    if (!is_array($step)) return trim((string) $step);
    return trim((string) ($step['title'] ?? $step['name'] ?? $step['topic'] ?? 'ขั้นตอนการเรียน'));
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> - Next Beyond</title>
  <link rel="stylesheet" href="../assets/css/output.css?v=<?= filemtime(__DIR__ . '/../assets/css/output.css') ?>">
  <link rel="stylesheet" href="../assets/css/student-portal.css?v=<?= filemtime(__DIR__ . '/../assets/css/student-portal.css') ?>">
</head>
<body class="student-portal bg-[#f4f7fb] text-navy-950">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col ml-[240px] max-[960px]:ml-0 min-w-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="p-8 max-[640px]:p-4">
      <section class="mb-8 border-b border-[#dce3ec] pb-7">
        <p class="student-kicker mb-2">Learning roadmap</p>
        <div class="flex items-end justify-between gap-5 flex-wrap">
          <div>
            <h2 class="text-[28px] font-bold text-navy-950">แผนการเรียนของฉัน</h2>
            <p class="mt-2 text-[14px] text-[#65738a]">ดูเป้าหมาย ลำดับการเรียน และความก้าวหน้าทั้งหมดในที่เดียว</p>
          </div>
          <a href="../courses" class="h-10 px-5 inline-flex items-center rounded-lg bg-pink-500 text-white text-[13px] font-bold hover:bg-pink-600">เลือกคอร์สเรียน</a>
        </div>
      </section>

      <div class="grid grid-cols-3 gap-px bg-[#dfe5ed] border border-[#dfe5ed] rounded-xl overflow-hidden mb-7 max-[680px]:grid-cols-1">
        <div class="bg-white px-5 py-4"><div class="student-kicker">แผนทั้งหมด</div><div class="mt-1 text-[25px] font-bold"><?= $pathCount ?></div></div>
        <div class="bg-white px-5 py-4"><div class="student-kicker">ความก้าวหน้าเฉลี่ย</div><div class="mt-1 text-[25px] font-bold"><?= $averageProgress ?>%</div></div>
        <div class="bg-white px-5 py-4"><div class="student-kicker">เวลาเรียนต่อสัปดาห์</div><div class="mt-1 text-[25px] font-bold"><?= $totalHours ?> <span class="text-[13px] font-semibold text-[#718096]">ชั่วโมง</span></div></div>
      </div>

      <?php if (!$paths): ?>
        <div class="learning-empty-grid">
          <section class="bg-white border border-[#dfe5ed] rounded-xl overflow-hidden">
            <div class="h-2 bg-pink-500"></div>
            <div class="px-10 py-12 max-[640px]:px-6 max-[640px]:py-9">
              <div class="w-14 h-14 rounded-xl bg-[#eef2f7] flex items-center justify-center text-navy-950 mb-6">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/></svg>
              </div>
              <p class="student-kicker mb-2">เริ่มต้นวางเส้นทาง</p>
              <h2 class="text-[24px] font-bold text-navy-950">ยังไม่มีแผนการเรียน</h2>
              <p class="mt-3 max-w-[560px] text-[14px] leading-7 text-[#65738a]">เริ่มจากเลือกคอร์สที่สนใจหรือทำแบบทดสอบวัดระดับ เมื่อมีแผนแล้ว คุณจะเห็นหัวข้อที่ควรเรียน ลำดับขั้น และความก้าวหน้าที่หน้านี้</p>
              <div class="mt-7 flex items-center gap-3 flex-wrap">
                <a href="../courses" class="h-11 px-6 inline-flex items-center rounded-lg bg-pink-500 text-white text-[14px] font-bold hover:bg-pink-600">ดูคอร์สทั้งหมด</a>
                <a href="tests" class="h-11 px-6 inline-flex items-center rounded-lg border border-[#cfd7e2] bg-white text-navy-950 text-[14px] font-bold hover:bg-[#f5f7fa]">ทำแบบทดสอบ</a>
              </div>
            </div>
          </section>

          <aside class="bg-navy-950 text-white rounded-xl px-6 py-7">
            <p class="text-[11px] font-bold uppercase tracking-[.14em] text-[#90a3bd]">How it works</p>
            <h2 class="mt-2 text-[18px] font-bold">เริ่มเรียนอย่างเป็นขั้นตอน</h2>
            <ol class="mt-7 space-y-6">
              <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full border border-[#4a607d] flex items-center justify-center text-[12px] font-bold">1</span><div><strong class="block text-[14px]">ตั้งเป้าหมาย</strong><span class="block mt-1 text-[12px] leading-5 text-[#aebbd0]">เลือกวิชาและระดับที่ต้องการ</span></div></li>
              <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full border border-[#4a607d] flex items-center justify-center text-[12px] font-bold">2</span><div><strong class="block text-[14px]">เรียนตามลำดับ</strong><span class="block mt-1 text-[12px] leading-5 text-[#aebbd0]">ทำแต่ละหัวข้อให้ครบตามแผน</span></div></li>
              <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full border border-[#4a607d] flex items-center justify-center text-[12px] font-bold">3</span><div><strong class="block text-[14px]">วัดผลและพัฒนา</strong><span class="block mt-1 text-[12px] leading-5 text-[#aebbd0]">ตรวจผลแล้วปรับแผนให้เหมาะกับคุณ</span></div></li>
            </ol>
          </aside>
        </div>
      <?php else: ?>
        <div class="space-y-5">
          <?php foreach ($paths as $path):
              $progress = min(100, max(0, (float) ($path['progress_percent'] ?? 0)));
              $steps = learningPathSteps($path);
          ?>
            <article class="bg-white border border-[#dfe5ed] rounded-xl overflow-hidden hover:border-[#b8c4d3]">
              <div class="grid grid-cols-[280px_minmax(0,1fr)] max-[760px]:grid-cols-1">
                <div class="bg-navy-950 text-white p-6 flex flex-col min-h-[250px]">
                  <span class="text-[11px] uppercase tracking-[.14em] font-bold text-[#90a3bd]"><?= htmlspecialchars($path['subject'] ?: 'Learning path') ?></span>
                  <h2 class="mt-3 text-[20px] leading-7 font-bold"><?= htmlspecialchars($path['target_goal'] ?: 'เป้าหมายการเรียนของคุณ') ?></h2>
                  <div class="mt-auto pt-6 text-[12px] text-[#aebbd0]">ระดับ <?= htmlspecialchars($path['current_level'] ?: 'ทั่วไป') ?></div>
                  <div class="mt-1 text-[12px] text-[#aebbd0]"><?= (int) ($path['hours_per_week'] ?? 0) ?> ชั่วโมงต่อสัปดาห์</div>
                </div>
                <div class="p-6 max-[640px]:p-5">
                  <div class="flex items-center justify-between gap-4 mb-2"><span class="text-[13px] font-bold">ความก้าวหน้าของแผน</span><strong class="text-[18px]"><?= (int) round($progress) ?>%</strong></div>
                  <div class="h-2 rounded-full bg-[#e8edf3] overflow-hidden"><div class="h-full bg-pink-500" style="width:<?= $progress ?>%"></div></div>
                  <div class="mt-7">
                    <h3 class="student-section-title text-[15px] font-bold">ลำดับการเรียน</h3>
                    <?php if ($steps): ?>
                      <ol class="mt-4 grid grid-cols-2 gap-3 max-[640px]:grid-cols-1">
                        <?php foreach (array_slice($steps, 0, 6) as $index => $step): ?>
                          <li class="flex items-center gap-3 border border-[#e3e8ef] rounded-lg p-3"><span class="w-7 h-7 shrink-0 rounded-md bg-[#eef2f7] flex items-center justify-center text-[11px] font-bold"><?= $index + 1 ?></span><span class="text-[13px] font-semibold leading-5"><?= htmlspecialchars(learningStepTitle($step)) ?></span></li>
                        <?php endforeach; ?>
                      </ol>
                    <?php else: ?>
                      <p class="mt-4 text-[13px] text-[#718096]">รายละเอียดขั้นตอนกำลังจัดเตรียม</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <?php include 'includes/bottom-nav.php'; ?>
  </div>
</div>
</body>
</html>
