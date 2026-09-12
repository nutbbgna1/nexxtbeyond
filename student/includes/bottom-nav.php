<?php
/**
 * student/includes/bottom-nav.php
 * เมนูด้านล่างสำหรับจอมือถือ (แสดงเฉพาะ max-width: 768px)
 */
$currentPage = $currentPage ?? 'index.php';

$mobileNav = [
    [
        'url' => 'index.php',
        'label' => 'ภาพรวม',
        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
    ],
    [
        'url' => 'my-courses.php',
        'label' => 'คอร์ส',
        'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'
    ],
    [
        'url' => 'roadmap.php',
        'label' => 'Roadmap',
        'icon' => 'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437A1 1 0 0021 17.305V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934a1.125 1.125 0 01-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689A1.125 1.125 0 003 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934a1.125 1.125 0 011.006 0l4.994 2.497a1.125 1.125 0 001.006 0z'
    ],
    [
        'url' => 'tests.php',
        'label' => 'ข้อสอบ',
        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
    ],
    [
        'url' => 'profile.php',
        'label' => 'โปรไฟล์',
        'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
    ]
];
?>
<!-- Bottom Nav: ซ่อนบน Desktop/Tablet (แสดงเฉพาะ max-width: 768px) -->
<nav class="hidden max-[768px]:flex sticky bottom-0 w-full h-[64px] bg-white border-t border-[#e8ecf2] shadow-[0_-2px_10px_rgba(15,42,83,0.04)] z-40 items-center justify-around px-2 pb-safe mt-auto">
    <?php foreach ($mobileNav as $item): ?>
        <?php $isActive = $currentPage === $item['url']; ?>
        <a href="<?= htmlspecialchars($item['url']) ?>" class="flex flex-col items-center justify-center w-full h-full gap-1 transition-colors <?= $isActive ? 'text-pink-500' : 'text-[#65738a] hover:text-navy-950' ?>">
            <div class="relative flex items-center justify-center w-8 h-8 rounded-full <?= $isActive ? 'bg-pink-50' : '' ?>">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
                </svg>
            </div>
            <span class="text-[9px] font-bold tracking-wide <?= $isActive ? 'text-pink-600' : '' ?>"><?= htmlspecialchars($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<style>
/* สำหรับ iPhone ที่มี Home Indicator */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
    .pb-safe {
        padding-bottom: env(safe-area-inset-bottom);
        height: calc(64px + env(safe-area-inset-bottom));
    }
}
</style>
