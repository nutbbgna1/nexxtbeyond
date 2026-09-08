(() => {
  "use strict";
  const container = document.getElementById("placement-tests-container");
  const doing = document.querySelector('[data-test-step="doing"]');
  const result = document.querySelector('[data-test-step="result"]');
  if (!container || !doing || !result) return;
  // Remove the old design-only question and score samples before rendering data.
  doing.replaceChildren();
  result.replaceChildren();
  const historyContainer = document.getElementById("test-history-container");
  const read = key => { try { return JSON.parse(localStorage.getItem(key)) || []; } catch { return []; } };
  const esc = value => String(value ?? "").replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  let activeExam = null;
  let startedAt = 0;
  let publishedExams = [];

  function isLoggedIn() { return localStorage.getItem("nb_user_role") === "student" && !!localStorage.getItem("nb_user"); }
  function publicExams() { return publishedExams; }

  async function loadPublicExams() {
    container.innerHTML = '<div class="bg-white rounded-[16px] border border-[#e8ecf2] p-8 text-center text-[#65738a]">กำลังโหลดแบบทดสอบ...</div>';
    try {
      const response = await fetch("exams-public-api", { headers: { Accept: "application/json" } });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.error || "ไม่สามารถโหลดแบบทดสอบได้");
      publishedExams = data.exams || [];
      renderList();
      const requested = new URLSearchParams(location.search).get("exam");
      if (requested) startExam(requested);
    } catch (error) {
      container.innerHTML = `<div class="bg-white rounded-[16px] border border-red-200 p-8 text-center text-red-500">${esc(error.message)}<br><button data-reload class="mt-3 underline font-bold">ลองใหม่</button></div>`;
    }
  }

  function renderList() {
    const exams = publicExams();
    const count = document.querySelector("[data-available-count]");
    if (count) count.textContent = exams.length;
    if (!exams.length) {
      container.innerHTML = '<div class="bg-white rounded-[16px] border border-[#e8ecf2] p-8 text-center text-[#65738a]">ยังไม่มีแบบทดสอบที่เปิดเผยแพร่</div>';
      return;
    }
    container.innerHTML = exams.map(exam => {
      const open = exam.status === "active";
      const loginLabel = exam.requiresLogin ? "ต้องเข้าสู่ระบบ" : "ทำได้ทันที";
      return `<article data-test-item data-subject="${esc(String(exam.subject || 'general').toLowerCase())}" data-type="${esc(exam.type)}" class="bg-white rounded-[16px] border border-[#e8ecf2] p-5 flex items-center justify-between gap-4 max-[640px]:flex-col max-[640px]:items-start">
        <div><div class="flex items-center gap-2"><h3 class="text-[17px] font-bold text-navy-950">${esc(exam.title)}</h3>${exam.isAiGenerated?'<span class="px-2 py-0.5 rounded bg-pink-50 text-pink-600 text-[10px] font-bold">AI</span>':''}</div>
        <p class="text-[13px] text-[#65738a] mt-2">${exam.questions?.length || 0} ข้อ · ${esc(exam.type || 'quiz').toUpperCase()} · ${loginLabel}</p></div>
        <button data-start="${esc(exam.id)}" ${open?'':'disabled'} class="h-10 px-6 rounded-full font-bold text-[14px] ${open?'bg-[#2369dd] text-white':'bg-[#e2e8f0] text-[#64748b] cursor-not-allowed'}">${open?'เริ่มทำ':'ปิดรับทำ'}</button>
      </article>`;
    }).join("");
  }

  function formatDuration(seconds) {
    const value = Math.max(0, Number(seconds) || 0);
    return `${Math.floor(value / 60)}:${String(value % 60).padStart(2, "0")}`;
  }

  function renderHistory() {
    if (!historyContainer) return;
    const count = document.querySelector("[data-history-count]");
    if (!isLoggedIn()) {
      if (count) count.textContent = "0";
      historyContainer.innerHTML = '<div class="bg-white rounded-[16px] border border-[#e8ecf2] p-8 text-center"><p class="text-[#65738a] mb-4">เข้าสู่ระบบเพื่อดูคะแนนย้อนหลังของคุณ</p><a href="auth.php?returnTo=placement-test.php%3Fview%3Dhistory" class="inline-flex h-10 px-6 rounded-full bg-pink-500 text-white font-bold items-center">เข้าสู่ระบบ</a></div>';
      return;
    }
    const user = JSON.parse(localStorage.getItem("nb_user"));
    const exams = read("nb_exams");
    const attempts = read("nb_test_attempts").filter(a => String(a.userId) === String(user.id)).sort((a,b) => new Date(b.completedAt) - new Date(a.completedAt));
    if (count) count.textContent = attempts.length;
    if (!attempts.length) {
      historyContainer.innerHTML = '<div class="bg-white rounded-[16px] border border-[#e8ecf2] p-8 text-center text-[#65738a]">ยังไม่มีประวัติการทำแบบทดสอบ</div>';
      return;
    }
    historyContainer.innerHTML = attempts.map(attempt => {
      const exam = exams.find(e => e.id === attempt.examId);
      const completed = new Date(attempt.completedAt);
      return `<article class="bg-white rounded-[16px] border border-[#e8ecf2] p-5 flex items-center justify-between gap-5 max-[640px]:items-start">
        <div><h3 class="font-bold text-navy-950">${esc(exam?.title || "แบบทดสอบที่ถูกลบแล้ว")}</h3><p class="text-[12px] text-[#65738a] mt-1">${completed.toLocaleDateString('th-TH', {dateStyle:'medium'})} ${completed.toLocaleTimeString('th-TH', {hour:'2-digit',minute:'2-digit'})} · ใช้เวลา ${formatDuration(attempt.timeSpentSeconds)} นาที</p><p class="text-[12px] text-[#65738a] mt-1">ตอบถูก ${attempt.correctCount} จาก ${attempt.totalQuestions} ข้อ</p></div>
        <strong class="text-[28px] text-[#2369dd]">${Number(attempt.score) || 0}%</strong>
      </article>`;
    }).join("");
  }

  function startExam(id) {
    activeExam = publishedExams.find(e => String(e.id) === String(id) && e.status === "active");
    if (!activeExam) { alert("ข้อสอบนี้ไม่ได้เปิดรับทำแล้ว"); renderList(); return; }
    if (activeExam.requiresLogin && !isLoggedIn()) {
      window.location.href = `auth.php?returnTo=${encodeURIComponent(`placement-test.php?exam=${activeExam.id}`)}`;
      return;
    }
    startedAt = Date.now();
    doing.innerHTML = `<main class="max-w-[850px] mx-auto w-full bg-white rounded-[20px] border border-[#e8ecf2] p-6 md:p-8">
      <div class="mb-7"><p class="text-[12px] font-bold text-[#65738a] uppercase">${esc(activeExam.type)}</p><h2 class="text-[24px] font-black text-navy-950">${esc(activeExam.title)}</h2></div>
      <form id="live-exam-form" class="space-y-6">${(activeExam.questions || []).map((q, qi) => `<fieldset class="p-5 rounded-2xl border border-[#e8ecf2]"><legend class="px-2 font-bold text-navy-950">ข้อ ${qi+1}</legend><p class="mb-4 text-[15px]">${esc(q.questionText || q.question)}</p><div class="space-y-2">${(q.options || []).map((option, oi) => `<label class="flex items-start gap-3 p-3 rounded-xl border border-[#e8ecf2] cursor-pointer hover:bg-[#f8fafc]"><input required type="radio" name="q_${qi}" value="${oi}" class="mt-1 accent-[#2369dd]"><span>${esc(option)}</span></label>`).join('')}</div></fieldset>`).join('')}
      <div class="flex justify-between gap-3"><button type="button" data-cancel class="px-5 h-11 rounded-full border font-bold">กลับหน้ารายการ</button><button type="submit" class="px-7 h-11 rounded-full bg-pink-500 text-white font-bold">ส่งข้อสอบ</button></div></form></main>`;
    window.switchTestStep("doing");
  }

  function submitExam(form) {
    const questions = activeExam.questions || [];
    let correct = 0;
    const answers = questions.map((q, index) => {
      const selected = Number(new FormData(form).get(`q_${index}`));
      const answerIndex = Number(q.correctAnswerIndex ?? q.correct_answer ?? q.correctAnswer);
      const isCorrect = selected === answerIndex;
      if (isCorrect) correct++;
      return { questionIndex: index, selectedAnswer: selected, isCorrect };
    });
    const percent = questions.length ? Math.round(correct * 100 / questions.length) : 0;
    let saved = false;
    if (isLoggedIn()) {
      const user = JSON.parse(localStorage.getItem("nb_user"));
      const attempts = read("nb_test_attempts");
      attempts.push({ id: `attempt_${Date.now()}`, examId: activeExam.id, userId: user.id, userEmail: user.email, score: percent, correctCount: correct, totalQuestions: questions.length, timeSpentSeconds: Math.round((Date.now()-startedAt)/1000), completedAt: new Date().toISOString(), answers });
      localStorage.setItem("nb_test_attempts", JSON.stringify(attempts));
      saved = true;
    }
    result.innerHTML = `<div class="max-w-[650px] mx-auto bg-white rounded-[20px] border border-[#e8ecf2] p-8 text-center"><p class="text-[12px] font-bold text-[#65738a] uppercase">ผลการทดสอบ</p><h2 class="text-[22px] font-black text-navy-950 mt-1">${esc(activeExam.title)}</h2><div class="text-[54px] font-black text-[#2369dd] my-5">${percent}%</div><p class="font-bold">ตอบถูก ${correct} จาก ${questions.length} ข้อ</p><p class="mt-3 text-[13px] ${saved?'text-[#168765]':'text-[#65738a]'}">${saved?'บันทึกคะแนนเข้าบัญชีนักเรียนแล้ว':'ผลครั้งนี้ไม่ได้บันทึก เพราะทำข้อสอบโดยไม่ได้ Login'}</p><button data-back class="mt-6 px-7 h-11 rounded-full bg-[#2369dd] text-white font-bold">กลับหน้าข้อสอบ</button></div>`;
    window.switchTestStep("result");
  }

  container.addEventListener("click", e => {
    if (e.target.closest("[data-reload]")) return loadPublicExams();
    const btn=e.target.closest("[data-start]"); if(btn) startExam(btn.dataset.start);
  });
  doing.addEventListener("submit", e => { if(e.target.id === "live-exam-form") { e.preventDefault(); submitExam(e.target); } });
  doing.addEventListener("click", e => { if(e.target.closest("[data-cancel]")) window.switchTestStep("list"); });
  result.addEventListener("click", e => { if(e.target.closest("[data-back]")) { renderList(); window.switchTestStep("list"); } });
  document.querySelectorAll("[data-tests-tab]").forEach(tab => tab.addEventListener("click", () => {
    const history = tab.dataset.testsTab === "history";
    document.querySelectorAll("[data-tests-tab]").forEach(item => {
      item.classList.toggle("text-[#2369dd]", item === tab);
      item.classList.toggle("border-[#2369dd]", item === tab);
      item.classList.toggle("text-[#65738a]", item !== tab);
      item.classList.toggle("border-transparent", item !== tab);
    });
    container.classList.toggle("hidden", history);
    historyContainer?.classList.toggle("hidden", !history);
    if (history) renderHistory(); else renderList();
  }));
  window.filterPlacementTests = () => {
    const subjects = [...document.querySelectorAll('input[name="subject"]:checked')].map(x=>x.value);
    const types = [...document.querySelectorAll('input[name="type"]:checked')].map(x=>x.value);
    document.querySelectorAll('[data-test-item]').forEach(item => { item.style.display = (!subjects.length || subjects.includes(item.dataset.subject)) && (!types.length || types.includes(item.dataset.type)) ? 'flex' : 'none'; });
  };
  renderHistory();
  if (new URLSearchParams(location.search).get("view") === "history") {
    document.querySelector('[data-tests-tab="history"]')?.click();
  }
   loadPublicExams();
})();
