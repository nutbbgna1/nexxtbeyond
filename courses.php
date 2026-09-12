<?php
$pageTitle = "คอร์สเรียน | Next Beyond Academy";
$pageDesc = "รวมคอร์ส Next Beyond Academy";
$currentPage = 'courses.php';
include 'includes/head.php';
include 'includes/header.php';
?>

<main id="main" data-filter-section>
  <section class="relative pt-[60px] pb-[20px] text-center overflow-hidden bg-[#f6f8fc]">
    <!-- Background Light Gradient Glow -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[300px] bg-gradient-to-b from-gray-200/50 to-transparent blur-[80px] rounded-full pointer-events-none"></div>

    <div class="container relative z-10 flex flex-col items-center">
      <p class="text-[11px] font-black tracking-[0.2em] text-[#65738a] mb-4 uppercase">NEXT BEYOND ACADEMY</p>
      <h1 class="text-navy-950 text-[clamp(36px,5vw,48px)] font-black mb-4 tracking-[-0.02em] leading-[1.1]">COURSE CATALOG</h1>
      <p class="text-[#65738a] text-[16px]">ค้นหาคอร์สที่เหมาะกับคุณ</p>
    </div>
  </section>

  <section class="py-12 bg-[#f6f8fc]">
    <div class="container max-w-[1140px]">
      
      <!-- Search Bar -->
      <div class="max-w-[700px] mx-auto mb-10 relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#8e9baf]">
          <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </span>
        <input class="w-full h-12 pl-12 pr-4 border border-[#dce4ef] rounded-full text-navy-950 bg-white placeholder-[#aebbd0] outline-none focus:border-pink-500 focus:shadow-[0_0_0_3px_rgba(231,45,130,0.1)] transition-all shadow-[0_4px_20px_rgba(15,42,83,0.04)]" type="search" placeholder="Search">
      </div>

      <div class="grid grid-cols-[240px_1fr] gap-8 items-start max-[1024px]:grid-cols-1">
        
        <!-- Sidebar Filter -->
        <aside class="bg-[#f8fafc] border border-[#e8ecf2] rounded-[20px] p-6 shadow-sm">
          <h2 class="text-[20px] font-bold text-navy-950 mb-6">Filter</h2>
          
          <div class="space-y-4 mb-8">
            <label class="flex items-center justify-between cursor-pointer group">
              <div class="flex items-center gap-3 text-[14px] text-navy-950 font-medium group-hover:text-[#2369dd] transition-colors">
                <svg class="w-5 h-5 text-[#2369dd]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                ทุกวิชา
              </div>
              <!-- Toggle Switch (On) -->
              <div class="relative w-9 h-5 bg-[#2369dd] rounded-full transition-colors">
                <div class="absolute right-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform"></div>
              </div>
            </label>

            <label class="flex items-center justify-between cursor-pointer group">
              <div class="flex items-center gap-3 text-[14px] text-[#65738a] font-medium group-hover:text-navy-950 transition-colors">
                <svg class="w-5 h-5 text-[#94a3b8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                ประถมศึกษา
              </div>
              <!-- Toggle Switch (Off) -->
              <div class="relative w-9 h-5 bg-[#e2e8f0] rounded-full transition-colors">
                <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform border border-[#cbd5e1]"></div>
              </div>
            </label>

            <label class="flex items-center justify-between cursor-pointer group">
              <div class="flex items-center gap-3 text-[14px] text-[#65738a] font-medium group-hover:text-navy-950 transition-colors">
                <svg class="w-5 h-5 text-[#94a3b8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                ปูพื้นฐาน
              </div>
              <!-- Toggle Switch (Off) -->
              <div class="relative w-9 h-5 bg-[#e2e8f0] rounded-full transition-colors">
                <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform border border-[#cbd5e1]"></div>
              </div>
            </label>
          </div>

          <h3 class="text-[11px] font-bold text-[#94a3b8] tracking-widest uppercase mb-4">MAGS</h3>
          <div class="space-y-4">
            <label class="flex items-center justify-between cursor-pointer group">
              <div class="flex items-center gap-3 text-[14px] text-navy-950 font-medium group-hover:text-[#2369dd] transition-colors">
                <svg class="w-5 h-5 text-[#2369dd]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                ขวด
              </div>
              <!-- Toggle Switch (On) -->
              <div class="relative w-9 h-5 bg-[#2369dd] rounded-full transition-colors">
                <div class="absolute right-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform"></div>
              </div>
            </label>

            <label class="flex items-center justify-between cursor-pointer group">
              <div class="flex items-center gap-3 text-[14px] text-[#65738a] font-medium group-hover:text-navy-950 transition-colors">
                <svg class="w-5 h-5 text-[#94a3b8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                เหรียญ
              </div>
              <div class="relative w-9 h-5 bg-[#e2e8f0] rounded-full transition-colors">
                <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform border border-[#cbd5e1]"></div>
              </div>
            </label>

            <label class="flex items-center justify-between cursor-pointer group">
              <div class="flex items-center gap-3 text-[14px] text-[#65738a] font-medium group-hover:text-navy-950 transition-colors">
                <svg class="w-5 h-5 text-[#94a3b8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                ลากน้อย
              </div>
              <div class="relative w-9 h-5 bg-[#e2e8f0] rounded-full transition-colors">
                <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform border border-[#cbd5e1]"></div>
              </div>
            </label>
          </div>
        </aside>

        <!-- Course Grid -->
        <div class="grid grid-cols-3 gap-5 max-[1140px]:grid-cols-2 max-[640px]:grid-cols-1">
          
          <!-- Card 1 -->
          <article class="bg-white border border-[#e8ecf2] rounded-[16px] overflow-hidden shadow-[0_4px_24px_rgba(15,42,83,0.05)] transition-transform hover:-translate-y-1 hover:shadow-lg flex flex-col group">
            <div class="relative h-[160px] bg-gray-200 overflow-hidden">
              <img loading="lazy" decoding="async" src="https://images.unsplash.com/photo-1577896851231-70ef18881754?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="English Class">
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-[#ff4a7a] text-white text-[11px] font-bold shadow-md">ยอดนิยม</span>
            </div>
            <div class="p-5 flex-1 flex flex-col">
              <h3 class="text-[17px] font-bold text-navy-950 mb-3 leading-snug line-clamp-2">English Communication...</h3>
              <div class="flex items-center gap-4 text-[#65738a] text-[12px] mb-4 mt-auto">
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 12 ชั่วโมง</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg> ผู้เรียน</span>
              </div>
              <div class="flex items-center justify-between mt-2 pt-4 border-t border-[#f1f5f9]">
                <div class="text-[18px] font-black text-navy-950">1,200 ฿</div>
                <a href="course-details.php" class="h-8 px-4 inline-flex items-center justify-center rounded-full bg-[#ff4a7a] text-white text-[13px] font-bold transition-transform hover:scale-105">ดูคอร์ส</a>
              </div>
            </div>
          </article>

          <!-- Card 2 -->
          <article class="bg-white border border-[#e8ecf2] rounded-[16px] overflow-hidden shadow-[0_4px_24px_rgba(15,42,83,0.05)] transition-transform hover:-translate-y-1 hover:shadow-lg flex flex-col group">
            <div class="relative h-[160px] bg-gray-200 overflow-hidden">
              <img loading="lazy" decoding="async" src="https://images.unsplash.com/photo-1532094349884-543bc11b234d?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Biology Class">
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-[#2369dd] text-white text-[11px] font-bold shadow-md">มาใหม่</span>
            </div>
            <div class="p-5 flex-1 flex flex-col">
              <h3 class="text-[17px] font-bold text-navy-950 mb-3 leading-snug line-clamp-2">Biology Foundation</h3>
              <div class="flex items-center gap-4 text-[#65738a] text-[12px] mb-4 mt-auto">
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 12 ชั่วโมง</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg> ผู้เรียน</span>
              </div>
              <div class="flex items-center justify-between mt-2 pt-4 border-t border-[#f1f5f9]">
                <div class="text-[18px] font-black text-navy-950">1,200 ฿</div>
                <a href="course-details.php" class="h-8 px-4 inline-flex items-center justify-center rounded-full bg-[#ff4a7a] text-white text-[13px] font-bold transition-transform hover:scale-105">ดูคอร์ส</a>
              </div>
            </div>
          </article>

          <!-- Card 3 -->
          <article class="bg-white border border-[#e8ecf2] rounded-[16px] overflow-hidden shadow-[0_4px_24px_rgba(15,42,83,0.05)] transition-transform hover:-translate-y-1 hover:shadow-lg flex flex-col group">
            <div class="relative h-[160px] bg-gray-200 overflow-hidden">
              <img loading="lazy" decoding="async" src="https://images.unsplash.com/photo-1632516643720-e7f5d7d6ecc9?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Math Class">
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-[#2369dd] text-white text-[11px] font-bold shadow-md">มาใหม่</span>
            </div>
            <div class="p-5 flex-1 flex flex-col">
              <h3 class="text-[17px] font-bold text-navy-950 mb-3 leading-snug line-clamp-2">Math Problem Solving</h3>
              <div class="flex items-center gap-4 text-[#65738a] text-[12px] mb-4 mt-auto">
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 12 ชั่วโมง</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg> ผู้เรียน</span>
              </div>
              <div class="flex items-center justify-between mt-2 pt-4 border-t border-[#f1f5f9]">
                <div class="text-[18px] font-black text-navy-950">1,200 ฿</div>
                <a href="course-details.php" class="h-8 px-4 inline-flex items-center justify-center rounded-full bg-[#ff4a7a] text-white text-[13px] font-bold transition-transform hover:scale-105">ดูคอร์ส</a>
              </div>
            </div>
          </article>
          
          <!-- Card 4 -->
          <article class="bg-white border border-[#e8ecf2] rounded-[16px] overflow-hidden shadow-[0_4px_24px_rgba(15,42,83,0.05)] transition-transform hover:-translate-y-1 hover:shadow-lg flex flex-col group">
            <div class="relative h-[160px] bg-gray-200 overflow-hidden">
              <img loading="lazy" decoding="async" src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="English Class">
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-[#ff4a7a] text-white text-[11px] font-bold shadow-md">ยอดนิยม</span>
            </div>
            <div class="p-5 flex-1 flex flex-col">
              <h3 class="text-[17px] font-bold text-navy-950 mb-3 leading-snug line-clamp-2">A-Level English Prep</h3>
              <div class="flex items-center gap-4 text-[#65738a] text-[12px] mb-4 mt-auto">
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 12 ชั่วโมง</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg> ผู้เรียน</span>
              </div>
              <div class="flex items-center justify-between mt-2 pt-4 border-t border-[#f1f5f9]">
                <div class="text-[18px] font-black text-navy-950">1,200 ฿</div>
                <a href="course-details.php" class="h-8 px-4 inline-flex items-center justify-center rounded-full bg-[#ff4a7a] text-white text-[13px] font-bold transition-transform hover:scale-105">ดูคอร์ส</a>
              </div>
            </div>
          </article>

          <!-- Card 5 -->
          <article class="bg-white border border-[#e8ecf2] rounded-[16px] overflow-hidden shadow-[0_4px_24px_rgba(15,42,83,0.05)] transition-transform hover:-translate-y-1 hover:shadow-lg flex flex-col group">
            <div class="relative h-[160px] bg-gray-200 overflow-hidden">
              <img loading="lazy" decoding="async" src="https://images.unsplash.com/photo-1576086213369-97a306d36557?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Chemistry Class">
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-[#2369dd] text-white text-[11px] font-bold shadow-md">มาใหม่</span>
            </div>
            <div class="p-5 flex-1 flex flex-col">
              <h3 class="text-[17px] font-bold text-navy-950 mb-3 leading-snug line-clamp-2">Chemistry Essentials</h3>
              <div class="flex items-center gap-4 text-[#65738a] text-[12px] mb-4 mt-auto">
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 12 ชั่วโมง</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg> ผู้เรียน</span>
              </div>
              <div class="flex items-center justify-between mt-2 pt-4 border-t border-[#f1f5f9]">
                <div class="text-[18px] font-black text-navy-950">1,200 ฿</div>
                <a href="course-details.php" class="h-8 px-4 inline-flex items-center justify-center rounded-full bg-[#ff4a7a] text-white text-[13px] font-bold transition-transform hover:scale-105">ดูคอร์ส</a>
              </div>
            </div>
          </article>

          <!-- Card 6 -->
          <article class="bg-white border border-[#e8ecf2] rounded-[16px] overflow-hidden shadow-[0_4px_24px_rgba(15,42,83,0.05)] transition-transform hover:-translate-y-1 hover:shadow-lg flex flex-col group">
            <div class="relative h-[160px] bg-gray-200 overflow-hidden">
              <img loading="lazy" decoding="async" src="https://images.unsplash.com/photo-1543269865-cbf427effbad?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Exam Prep">
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-[#ff4a7a] text-white text-[11px] font-bold shadow-md">ยอดนิยม</span>
            </div>
            <div class="p-5 flex-1 flex flex-col">
              <h3 class="text-[17px] font-bold text-navy-950 mb-3 leading-snug line-clamp-2">Exam Practice Hub</h3>
              <div class="flex items-center gap-4 text-[#65738a] text-[12px] mb-4 mt-auto">
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 12 ชั่วโมง</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg> ผู้เรียน</span>
              </div>
              <div class="flex items-center justify-between mt-2 pt-4 border-t border-[#f1f5f9]">
                <div class="text-[18px] font-black text-navy-950">1,200 ฿</div>
                <a href="course-details.php" class="h-8 px-4 inline-flex items-center justify-center rounded-full bg-[#ff4a7a] text-white text-[13px] font-bold transition-transform hover:scale-105">ดูคอร์ส</a>
              </div>
            </div>
          </article>

        </div>
      </div>
    </div>
  </section>

  <section class="py-[70px] bg-surface-soft">
    <div class="container">
      <div class="p-[42px] rounded-xl flex justify-between items-center gap-[35px] bg-white shadow-default max-[680px]:flex-col max-[680px]:items-stretch max-[680px]:p-6">
        <div>
          <p class="eyebrow">NEED A RECOMMENDATION?</p>
          <h2 class="text-[clamp(30px,4vw,47px)] tracking-[-0.035em] mb-4">ไม่แน่ใจว่าคอร์สไหนเหมาะ?</h2>
          <p class="text-muted m-0">เริ่มจากแบบวัดระดับ แล้วใช้ผลเพื่อเลือกแผนการเรียน</p>
        </div>
        <a class="btn btn-primary whitespace-nowrap" href="placement-test.php">วัดระดับก่อนเรียน →</a>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
