<?php
$pageTitle = 'Study Roadmap';
$currentPage = 'roadmap.php';
require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/../includes/roadmap-service.php';

ensureRoadmapSchema($pdo);
$stages = roadmapStageLabels();
$requestedStage = (string)($_GET['stage'] ?? '');
$hasSelectedStage = array_key_exists($requestedStage, $stages);
$stage = $hasSelectedStage ? $requestedStage : '';
$tasks = $hasSelectedStage ? getStudentRoadmap($pdo, (int)$currentUser['id'], $stage) : [];
$progress = roadmapProgress($tasks);
$categories = [];
foreach ($tasks as $task) $categories[(string)$task['category']] = true;

$stageCounts = array_fill_keys(array_keys($stages), 0);
$countRows = $pdo->query('SELECT stage,COUNT(*) AS total FROM roadmap_tasks WHERE is_active=1 GROUP BY stage')->fetchAll();
foreach ($countRows as $row) $stageCounts[(string)$row['stage']] = (int)$row['total'];
$stageIcons = ['tcas' => '🎓', 'm4' => '📘', 'm1' => '📗'];
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> - Next Beyond</title>
  <link rel="stylesheet" href="../assets/css/output.css?v=<?= filemtime(__DIR__ . '/../assets/css/output.css') ?>">
  <link rel="stylesheet" href="../assets/css/student-portal.css?v=<?= filemtime(__DIR__ . '/../assets/css/student-portal.css') ?>">
  <style>
    [hidden]{display:none!important}.roadmap-tab{display:inline-flex;align-items:center;min-height:40px;padding:0 15px;border:1px solid #dce3ec;border-radius:9px;background:#fff;color:#65738a;font-size:12px;font-weight:800;text-decoration:none}.roadmap-tab.active{border-color:#f54696;background:#f54696;color:#fff}.roadmap-filter{min-height:34px;padding:0 12px;border:1px solid #dce3ec;border-radius:999px;background:#fff;color:#65738a;font-size:11px;font-weight:700}.roadmap-filter.active{border-color:#f7a1c9;background:#fff0f7;color:#d72c7a}.roadmap-task{display:flex;align-items:center;gap:14px;padding:16px;border:1px solid #e3e8ef;border-radius:10px;background:#fff}.roadmap-task.done{background:#f8fafc}.roadmap-task.done .task-title{text-decoration:line-through;color:#94a3b8}.roadmap-check{width:20px;height:20px;accent-color:#f54696;cursor:pointer}.roadmap-meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:6px;color:#718096;font-size:11px}.roadmap-chip{padding:3px 7px;border-radius:6px;background:#eef2f7;color:#4b5e7a;font-weight:700}.roadmap-points{margin-left:auto;color:#c97700;font-size:11px;font-weight:800;white-space:nowrap}.roadmap-choice-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:24px}.roadmap-choice{display:flex;flex-direction:column;min-height:260px;padding:24px;border:1px solid #dfe5ed;border-radius:12px;background:#fff;text-decoration:none;transition:.18s}.roadmap-choice:hover{border-color:#f49ac3;box-shadow:0 8px 24px rgba(15,42,83,.07);transform:translateY(-2px)}.choice-icon{display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:12px;background:#fff0f7;font-size:24px}.roadmap-choice h3{margin-top:18px;color:#061633;font-size:18px;font-weight:800}.roadmap-choice p{margin-top:8px;color:#65738a;font-size:12px;line-height:1.7}.choice-footer{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:20px;color:#d72c7a;font-size:12px;font-weight:800}.choice-banner{padding:32px;border:1px solid #17304f;border-radius:12px;background:#061633;color:#fff}.choice-banner h2{color:#fff;font-size:27px;font-weight:800}.choice-banner p{margin-top:9px;color:#b9c5d7;font-size:13px;line-height:1.7}@media(max-width:900px){.roadmap-choice-grid{grid-template-columns:1fr}}@media(max-width:640px){.roadmap-task{align-items:flex-start}.roadmap-points{margin-left:0}.roadmap-tabs{display:grid!important;grid-template-columns:1fr}.roadmap-tab{justify-content:center}.choice-banner{padding:24px}.choice-banner h2{font-size:22px}}
  </style>
</head>
<body class="student-portal bg-[#f4f7fb] text-navy-950">
<div class="min-h-screen flex">
  <?php include 'includes/sidebar.php'; ?>
  <div class="flex-1 ml-[240px] max-[960px]:ml-0 min-w-0">
    <?php include 'includes/topbar.php'; ?>
    <main class="p-8 max-[640px]:p-4">
      <?php if (!$hasSelectedStage): ?>
        <section class="choice-banner">
          <p class="student-kicker text-[#b9c5d7] mb-2">Choose your learning path</p>
          <h2>อยากได้ Roadmap แบบไหน?</h2>
          <p>เลือกระดับและเป้าหมายที่ตรงกับคุณก่อน ระบบจึงจะแสดงภารกิจที่ผู้ดูแลจัดเตรียมไว้<br>คุณสามารถกลับมาเปลี่ยน Roadmap ได้ทุกเมื่อ</p>
        </section>
        <section class="roadmap-choice-grid" aria-label="เลือก Study Roadmap">
          <?php foreach ($stages as $stageKey => $stageInfo): ?>
            <a class="roadmap-choice" href="roadmap.php?stage=<?= $stageKey ?>">
              <span class="choice-icon"><?= $stageIcons[$stageKey] ?></span>
              <h3><?= htmlspecialchars($stageInfo['title']) ?></h3>
              <p><?= htmlspecialchars($stageInfo['description']) ?></p>
              <div class="choice-footer"><span><?= $stageCounts[$stageKey] ?> ภารกิจ</span><span>เลือก Roadmap →</span></div>
            </a>
          <?php endforeach; ?>
        </section>
      <?php else: ?>
        <section class="rounded-xl bg-navy-950 p-7 text-white border border-[#17304f]">
          <div class="flex items-center justify-between gap-7 flex-wrap">
            <div><p class="student-kicker text-[#b9c5d7] mb-2"><?= htmlspecialchars($stages[$stage]['badge']) ?></p><h2 class="text-[26px] font-bold" style="color:#fff">แผนการเรียน &amp; Study Roadmap</h2><p class="mt-2 max-w-[650px] text-[13px] leading-6 text-[#b9c5d7]"><?= htmlspecialchars($stages[$stage]['description']) ?></p></div>
            <div class="min-w-[240px] rounded-xl border border-[#405270] bg-[#0d2346] p-4"><div class="flex justify-between text-[12px] font-bold"><span>ความคืบหน้า</span><strong id="progress-percent" class="text-pink-300"><?= $progress['percent'] ?>%</strong></div><div class="mt-3 h-2.5 overflow-hidden rounded-full bg-[#283d5d]"><div id="progress-bar" class="h-full rounded-full bg-pink-500" style="width:<?= $progress['percent'] ?>%"></div></div><div id="progress-detail" class="mt-2 text-[11px] text-[#b9c5d7]">สำเร็จ <?= $progress['completed'] ?> จาก <?= $progress['total'] ?> ภารกิจ</div></div>
          </div>
        </section>
        <nav class="roadmap-tabs mt-6 flex gap-2 flex-wrap" aria-label="Roadmap ปัจจุบัน">
          <a class="roadmap-tab" href="roadmap.php">← เลือก Roadmap ใหม่</a>
          <?php foreach ($stages as $stageKey => $stageInfo): ?><a class="roadmap-tab <?= $stage === $stageKey ? 'active' : '' ?>" href="roadmap.php?stage=<?= $stageKey ?>"><?= htmlspecialchars($stageInfo['title']) ?></a><?php endforeach; ?>
        </nav>
        <div class="mt-6 flex items-center justify-between gap-4 flex-wrap"><div><h2 class="student-section-title text-[17px] font-bold">ภารกิจที่ผู้ดูแลจัดให้</h2><p class="mt-1 text-[12px] text-[#718096]">ติ๊กเมื่อทำภารกิจสำเร็จ ระบบจะบันทึกความคืบหน้าของคุณทันที</p></div><div class="flex gap-2 flex-wrap"><button class="roadmap-filter active" data-category="all">ทั้งหมด</button><?php foreach (array_keys($categories) as $category): ?><button class="roadmap-filter" data-category="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></button><?php endforeach; ?></div></div>
        <section id="roadmap-list" class="mt-4 space-y-3">
          <?php if (!$tasks): ?><div class="rounded-xl border border-[#dfe5ed] bg-white p-12 text-center text-[14px] text-[#718096]">ผู้ดูแลยังไม่ได้เปิดภารกิจสำหรับระดับนี้</div><?php endif; ?>
          <?php foreach ($tasks as $task): ?>
            <article class="roadmap-task <?= $task['is_completed'] ? 'done' : '' ?>" data-task-id="<?= (int)$task['id'] ?>" data-category="<?= htmlspecialchars($task['category']) ?>">
              <input class="roadmap-check" type="checkbox" <?= $task['is_completed'] ? 'checked' : '' ?> aria-label="ทำภารกิจนี้สำเร็จ">
              <div class="min-w-0 flex-1"><h3 class="task-title text-[14px] font-bold text-navy-950"><?= htmlspecialchars($task['title']) ?></h3><div class="roadmap-meta"><span class="roadmap-chip"><?= htmlspecialchars($task['category']) ?></span><span><?= htmlspecialchars($task['subject']) ?></span><?php if ($task['due_date']): ?><span>กำหนด <?= date('d/m/Y', strtotime($task['due_date'])) ?></span><?php endif; ?></div></div>
              <span class="roadmap-points">+<?= (int)$task['points_reward'] ?> NC</span>
            </article>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
    </main>
  </div>
</div>
<?php if ($hasSelectedStage): ?>
<script>
const progress = {total: <?= $progress['total'] ?>, completed: <?= $progress['completed'] ?>};
function drawProgress(){const percent=progress.total?Math.round(progress.completed*100/progress.total):0;document.getElementById('progress-percent').textContent=percent+'%';document.getElementById('progress-bar').style.width=percent+'%';document.getElementById('progress-detail').textContent=`สำเร็จ ${progress.completed} จาก ${progress.total} ภารกิจ`}
document.querySelectorAll('.roadmap-check').forEach(input=>input.addEventListener('change',async()=>{const row=input.closest('.roadmap-task'),completed=input.checked;input.disabled=true;try{const response=await fetch('roadmap-api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({task_id:Number(row.dataset.taskId),is_completed:completed})});const data=await response.json();if(!response.ok||!data.success)throw new Error(data.message||'บันทึกไม่สำเร็จ');row.classList.toggle('done',completed);progress.completed=data.progress.completed;progress.total=data.progress.total;drawProgress()}catch(error){input.checked=!completed;alert(error.message)}finally{input.disabled=false}}));
document.querySelectorAll('.roadmap-filter').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('.roadmap-filter').forEach(item=>item.classList.toggle('active',item===button));document.querySelectorAll('.roadmap-task').forEach(row=>row.hidden=button.dataset.category!=='all'&&row.dataset.category!==button.dataset.category)}));
</script>
<?php endif; ?>
</body>
</html>
