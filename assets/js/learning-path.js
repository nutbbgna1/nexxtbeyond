(() => {
  "use strict";

  const root = document.querySelector("[data-learning-path-app]");
  if (!root) return;

  const form = root.querySelector("[data-plan-form]");
  const output = root.querySelector("[data-plan-output]");
  const placementBox = root.querySelector("[data-placement-note]");
  const storageKey = "nextBeyondLearningPlan";
  const placementKey = "nextBeyondPlacementResult";
  let plan = null;
  let completed = [];

  const subjects = { english: "ภาษาอังกฤษ", biology: "ชีววิทยา", chemistry: "เคมี" };
  const levels = { starter: "ปูพื้นฐาน", foundation: "มีพื้นฐาน", advanced: "พร้อมต่อยอด" };
  const goals = { confidence: "เข้าใจและมั่นใจขึ้น", school: "พัฒนาผลการเรียน", exam: "เตรียมสอบ" };
  const paces = {
    light: { label: "ค่อยเป็นค่อยไป", hours: "2 ชม./สัปดาห์", weeks: 8 },
    standard: { label: "สมดุล", hours: "3 ชม./สัปดาห์", weeks: 6 },
    intensive: { label: "เข้มข้น", hours: "5 ชม./สัปดาห์", weeks: 4 }
  };

  const subjectSteps = {
    english: [
      ["วัดระดับภาษาอังกฤษ", "ตรวจ Vocabulary, Grammar, Reading และ Communication", "15 นาที", "1 แบบทดสอบ", "placement-test.php"],
      ["Core English Foundation", "จัดระบบคำศัพท์และโครงสร้างประโยคที่ใช้บ่อย", "2 สัปดาห์", "4 บทเรียน", "courses.php"],
      ["Speaking & Listening Lab", "ฝึกฟังและโต้ตอบผ่านสถานการณ์ใกล้ตัว", "1–2 สัปดาห์", "3 บทเรียน", "courses.php"],
      ["Practice & Feedback", "ทำแบบฝึกและแก้จุดที่ยังติดจาก Feedback", "2 สัปดาห์", "4 บทเรียน", "courses.php"]
    ],
    biology: [
      ["เช็กพื้นฐานชีววิทยา", "ตรวจเรื่องเซลล์ ระบบร่างกาย พันธุศาสตร์ และนิเวศ", "10 นาที", "1 แบบทดสอบ", "placement-test.php"],
      ["Core Biology Concepts", "เข้าใจคำศัพท์ หลักการ และภาพรวมของบทเรียน", "2 สัปดาห์", "4 บทเรียน", "courses.php"],
      ["Connect the Systems", "เชื่อมเซลล์ ระบบร่างกาย และสิ่งแวดล้อม", "1–2 สัปดาห์", "3 บทเรียน", "courses.php"],
      ["Biology Question Lab", "ฝึกวิเคราะห์โจทย์ แผนภาพ และตัวเลือก", "2 สัปดาห์", "4 ชุดฝึก", "courses.php"]
    ],
    chemistry: [
      ["เช็กพื้นฐานเคมี", "ตรวจเรื่องอะตอม พันธะ สาร และการคำนวณ", "10 นาที", "1 แบบทดสอบ", "placement-test.php"],
      ["Atoms, Elements & Bonding", "เข้าใจโครงสร้างอะตอม ตารางธาตุ และพันธะ", "2 สัปดาห์", "4 บทเรียน", "courses.php"],
      ["Chemistry Calculation Lab", "ฝึกอ่านสมการ อัตราส่วนโมล และการคำนวณ", "1–2 สัปดาห์", "3 บทเรียน", "courses.php"],
      ["Chemistry Question Lab", "ฝึกวิเคราะห์โจทย์และตรวจวิธีคิด", "2 สัปดาห์", "4 ชุดฝึก", "courses.php"]
    ]
  };

  function buildSteps(subject, level, goal) {
    let base = subjectSteps[subject];
    if (level === "foundation") base = [base[0], base[1], base[3]];
    if (level === "advanced") base = [base[0], base[2], base[3]];

    const goalStep = goal === "exam"
      ? ["Exam Strategy Intensive", "ฝึกจัดเวลา วิเคราะห์จุดหลอก และทำข้อสอบจำลอง", "1 สัปดาห์", "3 ชุดฝึก", "courses.php"]
      : goal === "school"
        ? ["School Success Review", "ทบทวนหัวข้อในโรงเรียนและฝึกงานแบบข้อสอบ", "1 สัปดาห์", "2 ชุดฝึก", "courses.php"]
        : ["Real-use Challenge", "นำความรู้ไปใช้ในกิจกรรมจริงเพื่อสร้างความมั่นใจ", "1 สัปดาห์", "2 กิจกรรม", "courses.php"];

    return [...base, goalStep, ["Post-test & Next Goal", "วัดพัฒนาการ สรุปผล และตั้งเป้าหมายรอบถัดไป", "20 นาที", "1 แบบทดสอบ", "placement-test.php"]];
  }

  function save() {
    localStorage.setItem(storageKey, JSON.stringify({ plan, completed }));
  }

  function render() {
    if (!plan) {
      output.innerHTML = `
        <div class="p-16 border border-dashed border-[#bdc9d9] rounded-[22px] text-center text-[#65738a] bg-white">
          <div class="w-[66px] h-[66px] mx-auto mb-4 grid place-items-center rounded-[20px] text-[#2369dd] bg-[#e8f1ff] text-[30px]">⌁</div>
          <h2 class="text-navy-950 font-bold mb-2">แผนของคุณจะแสดงที่นี่</h2>
          <p>เลือกข้อมูลด้านซ้าย แล้วกด "สร้างแผนการเรียน"</p>
        </div>`;
      return;
    }

    const done = completed.filter(Boolean).length;
    const percent = Math.round((done / plan.steps.length) * 100);

    output.innerHTML = `
      <!-- Summary Banner -->
      <div class="relative overflow-hidden mb-4 p-7 rounded-[22px] text-white shadow-[0_20px_60px_rgba(15,42,83,.12)]" style="background:linear-gradient(128deg,#061633,#2369dd)">
        <div class="absolute w-[230px] h-[230px] -right-[95px] -top-[105px] rounded-full pointer-events-none" style="background:rgba(245,70,150,.32)"></div>
        <div class="relative z-10 flex justify-between gap-5 max-[640px]:flex-col">
          <div>
            <p class="flex items-center gap-2.5 text-[12px] font-black tracking-[0.15em] text-[#b8d3ff] uppercase mb-2.5">
              <span class="w-[25px] h-[3px] rounded-full bg-pink-500"></span>YOUR LEARNING PATH
            </p>
            <h2 class="text-white text-[28px] font-bold mb-2">${subjects[plan.subject]} · ${goals[plan.goal]}</h2>
            <p class="text-[#c6d5e8] mb-0">${levels[plan.level]} · ${paces[plan.pace].hours}</p>
          </div>
          <span class="h-fit px-3.5 py-2 rounded-full bg-white/[.13] whitespace-nowrap text-[13px] font-bold max-[640px]:w-fit">ประมาณ ${paces[plan.pace].weeks} สัปดาห์</span>
        </div>
        <div class="relative z-10 mt-5">
          <div class="flex justify-between gap-4 mb-2 text-[13px] font-bold">
            <span>ความคืบหน้า</span>
            <span>${done}/${plan.steps.length} ขั้น · ${percent}%</span>
          </div>
          <div class="w-full h-[9px] rounded-full overflow-hidden bg-white/[.19]">
            <span class="block h-full rounded-full transition-[width] duration-300" style="width:${percent}%;background:linear-gradient(90deg,#8ec0ff,#f54696)"></span>
          </div>
        </div>
      </div>

      <!-- Steps Timeline -->
      <div class="grid gap-3.5">
        ${plan.steps.map((step, index) => {
          const locked = index > 0 && !completed[index - 1];
          const isDone = completed[index];
          const stepClasses = isDone
            ? "border-[#9bd5c2] bg-[#f3fcf8]"
            : locked
              ? "opacity-55 bg-[#f1f4f8] shadow-none"
              : "border-[#dce4ef] bg-white shadow-[0_12px_32px_rgba(15,42,83,.08)]";
          const indexBg = isDone ? "bg-[#168765]" : "bg-[#2369dd]";

          return `
            <article class="p-[18px] border rounded-[17px] grid grid-cols-[46px_minmax(0,1fr)_auto] items-center gap-4 transition-transform hover:-translate-y-0.5 ${stepClasses} max-[640px]:grid-cols-[43px_1fr]">
              <span class="w-[46px] h-[46px] grid place-items-center rounded-[14px] text-white font-black ${indexBg} max-[640px]:w-[43px] max-[640px]:h-[43px]">${isDone ? "✓" : index + 1}</span>
              <div>
                <h3 class="text-navy-950 text-[17px] font-bold mb-1">${step[0]}</h3>
                <p class="text-[#65738a] text-[13px] mb-0">${step[1]} · <a href="${step[4]}" class="text-[#2369dd] font-bold">เปิดเนื้อหา</a></p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span class="px-2 py-1 rounded-full text-[#2369dd] bg-[#e8f1ff] text-[11px] font-bold">${step[2]}</span>
                  <span class="px-2 py-1 rounded-full text-[#2369dd] bg-[#e8f1ff] text-[11px] font-bold">${step[3]}</span>
                </div>
              </div>
              <button class="w-[38px] h-[38px] border border-[#dce4ef] rounded-[11px] text-[#2369dd] bg-white font-black transition-colors hover:bg-[#e8f1ff] disabled:opacity-40 disabled:cursor-not-allowed max-[640px]:col-start-2" type="button" data-complete="${index}" ${locked ? "disabled" : ""} aria-label="${isDone ? "ยกเลิกสถานะเสร็จแล้ว" : "ทำขั้นนี้เสร็จแล้ว"}">${isDone ? "↺" : "✓"}</button>
            </article>`;
        }).join("")}
      </div>

      <!-- Actions -->
      <div class="mt-4 flex gap-2.5">
        <button class="min-h-[48px] px-[18px] border border-[#dce4ef] rounded-[13px] inline-flex items-center justify-center gap-2 text-navy-900 bg-white font-bold transition-transform hover:-translate-y-0.5" type="button" data-reset>เริ่มแผนใหม่</button>
      </div>`;

    output.querySelectorAll("[data-complete]").forEach(button => {
      button.addEventListener("click", () => {
        const index = Number(button.dataset.complete);
        completed[index] = !completed[index];
        if (!completed[index]) {
          for (let next = index + 1; next < completed.length; next += 1) completed[next] = false;
        }
        save();
        render();
      });
    });

    output.querySelector("[data-reset]")?.addEventListener("click", () => {
      localStorage.removeItem(storageKey);
      plan = null;
      completed = [];
      render();
      window.showToast?.("ล้างแผนการเรียนแล้ว");
    });
  }

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const subject = data.get("subject");
    const level = data.get("level");
    const goal = data.get("goal");
    const pace = data.get("pace");
    plan = { subject, level, goal, pace, steps: buildSteps(subject, level, goal) };
    completed = Array(plan.steps.length).fill(false);
    save();
    render();
    window.showToast?.("สร้างและบันทึก Learning Path แล้ว");
    if (window.innerWidth < 961) output.scrollIntoView({ behavior: "smooth", block: "start" });
  });

  try {
    const stored = JSON.parse(localStorage.getItem(storageKey));
    if (stored?.plan?.steps) {
      plan = stored.plan;
      completed = stored.completed || Array(plan.steps.length).fill(false);
    }
  } catch { /* ข้ามข้อมูลที่ไม่สมบูรณ์ */ }

  try {
    const placement = JSON.parse(localStorage.getItem(placementKey));
    if (placement?.subject && subjects[placement.subject]) {
      form.elements.subject.value = placement.subject;
      form.elements.level.value = placement.score >= 80 ? "advanced" : placement.score >= 50 ? "foundation" : "starter";
      if (placementBox) {
        placementBox.hidden = false;
        placementBox.classList.remove("hidden");
        placementBox.textContent = `ใช้ผลวัดระดับล่าสุด: ${placement.subjectLabel || subjects[placement.subject]} ${placement.score}% (${placement.level || "ระดับแนะนำ"})`;
      }
    }
  } catch { /* ไม่มีผลวัดระดับ */ }

  render();
})();
