<?php
require_once __DIR__ . '/includes/access.php';
$pageTitle = '🤖 AI สร้างข้อสอบ (AI Exam Generator)';
$pageDesc = 'สร้างข้อสอบอัตโนมัติด้วย AI พร้อมเฉลยและคำอธิบาย';
$currentPage = 'ai-exam.php';
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Next Beyond Admin</title>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="../assets/js/admin-guard.js"></script>
</head>
<body class="bg-[#f4f7fb] text-navy-950 font-sans antialiased selection:bg-pink-500/20 selection:text-pink-600">

  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 ml-[240px] max-[1024px]:ml-0 transition-all duration-300">
      <!-- Topbar -->
      <?php include 'includes/topbar.php'; ?>

      <!-- Content -->
      <main class="flex-1 p-8 max-[640px]:p-4 overflow-y-auto">
        <div class="max-w-[800px] mx-auto">
          <?php include '../AI-EXAM/index.php'; ?>
        </div>
      </main>
    </div>
  </div>

</body>
</html>
