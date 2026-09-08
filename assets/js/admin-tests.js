(() => {
  "use strict";
  const tbody = document.getElementById("admin-tests-tbody");
  if (!tbody) return;

  const API_URL = "exams-api";
  const searchInput = document.getElementById("tests-search");
  const typeFilter = document.getElementById("tests-type-filter");
  const esc = value => String(value ?? "").replace(/[&<>'"]/g, char => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;"
  }[char]));
  let allExams = [];

  const createdId = new URLSearchParams(window.location.search).get("created");
  if (createdId) {
    const notice = document.getElementById("tests-created-notice");
    notice.textContent = `สร้างและบันทึกข้อสอบ #${createdId} เรียบร้อยแล้ว ข้อสอบอยู่ในสถานะฉบับร่าง`;
    notice.classList.remove("hidden");
    history.replaceState({}, "", window.location.pathname);
  }

  async function api(url = API_URL, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: { "Content-Type": "application/json", ...(options.headers || {}) }
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body.error || "ไม่สามารถเชื่อมต่อฐานข้อมูลข้อสอบได้");
    return body;
  }

  function render() {
    const query = (searchInput?.value || "").trim().toLocaleLowerCase("th");
    const selectedType = typeFilter?.value || "";
    const exams = allExams.filter(exam => {
      const searchable = `${exam.title || ""} ${exam.subject || ""} ${exam.grade || ""}`.toLocaleLowerCase("th");
      return (!query || searchable.includes(query)) && (!selectedType || exam.type === selectedType);
    });
    document.getElementById("tests-count").textContent = `แสดง ${exams.length} จาก ${allExams.length} รายการ`;

    if (!exams.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-10 text-center text-[#65738a]">ไม่พบแบบทดสอบที่ตรงกับตัวกรอง</td></tr>';
      return;
    }

    tbody.innerHTML = exams.map(exam => `<tr class="hover:bg-[#f8fafc] align-top">
      <td class="px-4 py-5"><div class="flex gap-2 items-center"><input data-id="${esc(exam.id)}" data-field="title" value="${esc(exam.title)}" maxlength="300" class="min-w-[230px] h-9 px-3 border border-transparent hover:border-[#dce4ef] focus:border-pink-500 rounded-lg font-bold text-[14px] outline-none"><span class="text-[11px] text-[#94a3b8]">แก้ชื่อได้</span></div><div class="text-[11px] text-[#65738a] mt-1">${exam.isAiGenerated ? "สร้างโดย AI" : "สร้างโดยแอดมิน"} · #${esc(exam.id)}</div><select data-id="${esc(exam.id)}" data-field="type" class="mt-2 border rounded-lg px-2 py-1 text-[11px]"><option value="quiz" ${exam.type === "quiz" ? "selected" : ""}>QUIZ</option><option value="placement" ${exam.type === "placement" ? "selected" : ""}>PLACEMENT</option><option value="pretest" ${exam.type === "pretest" ? "selected" : ""}>PRE-TEST</option><option value="posttest" ${exam.type === "posttest" ? "selected" : ""}>POST-TEST</option></select></td>
      <td class="px-4 py-5"><b class="text-[13px]">${esc(exam.subject || "ทั่วไป")}</b><div class="text-[11px] text-[#65738a]">${esc(exam.grade || "ทุกระดับ")}</div></td>
      <td class="px-4 py-5 text-center font-bold text-[13px]">${exam.questionCount || 0} ข้อ</td>
      <td class="px-4 py-5 text-center font-bold text-[13px]">${exam.attemptCount || 0}</td>
      <td class="px-4 py-5 space-y-2">
        <select data-id="${esc(exam.id)}" data-field="status" class="block w-full border rounded-lg px-2 py-1 text-[12px]"><option value="draft" ${exam.status === "draft" ? "selected" : ""}>ฉบับร่าง</option><option value="active" ${exam.status === "active" ? "selected" : ""}>เปิดรับทำ</option><option value="closed" ${exam.status === "closed" ? "selected" : ""}>ปิดรับทำ</option><option value="archived" ${exam.status === "archived" ? "selected" : ""}>เก็บถาวร</option></select>
        <label class="flex gap-2 text-[12px]"><input type="checkbox" data-id="${esc(exam.id)}" data-field="isPublished" ${exam.isPublished ? "checked" : ""}> แสดงบนเว็บไซต์</label>
        <label class="flex gap-2 text-[12px]"><input type="checkbox" data-id="${esc(exam.id)}" data-field="requiresLogin" ${exam.requiresLogin ? "checked" : ""}> ต้อง Login ก่อนทำ</label>
      </td>
      <td class="px-4 py-5 text-right whitespace-nowrap"><a href="question-bank.php?exam=${esc(exam.id)}" class="text-[#2369dd] text-[12px] font-bold mr-3">ดูคำถาม</a><button data-delete="${esc(exam.id)}" class="text-[#ef4444] text-[12px] font-bold">ลบ</button></td>
    </tr>`).join("");
  }

  async function load() {
    tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-10 text-center text-[#65738a]">กำลังโหลดข้อสอบ...</td></tr>';
    try {
      const result = await api();
      allExams = result.exams || [];
      render();
    } catch (error) {
      tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-10 text-center text-[#ef4444]">${esc(error.message)}<br><button data-retry class="mt-3 underline font-bold">ลองใหม่</button></td></tr>`;
      document.getElementById("tests-count").textContent = "โหลดข้อมูลไม่สำเร็จ";
    }
  }

  tbody.addEventListener("change", async event => {
    const element = event.target;
    if (!element.dataset.id || !element.dataset.field) return;
    element.disabled = true;
    const value = element.type === "checkbox" ? element.checked : element.value;
    try {
      await api(API_URL, { method: "PATCH", body: JSON.stringify({ id: element.dataset.id, field: element.dataset.field, value }) });
      const exam = allExams.find(item => item.id === element.dataset.id);
      if (exam) exam[element.dataset.field] = value;
      render();
    } catch (error) {
      alert(error.message);
      await load();
    }
  });

  tbody.addEventListener("click", async event => {
    if (event.target.closest("[data-retry]")) return load();
    const button = event.target.closest("[data-delete]");
    if (!button || !confirm("ยืนยันการลบแบบทดสอบนี้?")) return;
    button.disabled = true;
    try {
      await api(`${API_URL}?id=${encodeURIComponent(button.dataset.delete)}`, { method: "DELETE" });
      allExams = allExams.filter(exam => exam.id !== button.dataset.delete);
      render();
    } catch (error) {
      alert(error.message);
      button.disabled = false;
    }
  });

  searchInput?.addEventListener("input", render);
  typeFilter?.addEventListener("change", render);
  load();
})();
