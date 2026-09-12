(() => {
  "use strict";

  const grid = document.getElementById("calendar-grid");
  const modal = document.getElementById("calendar-modal");
  const form = document.getElementById("calendar-form");
  const teacherFilter = document.getElementById("calendar-teacher-filter");
  const courseFilter = document.getElementById("calendar-course-filter");
  const teacherField = document.getElementById("calendar-event-teacher");
  const courseField = document.getElementById("calendar-event-course");
  const errorBox = document.getElementById("calendar-form-error");
  const deleteButton = document.getElementById("delete-calendar-event");
  const esc = value => String(value ?? "").replace(/[&<>'"]/g, char => ({"&":"&amp;","<":"&lt;",">":"&gt;","'":"&#39;",'"':"&quot;"}[char]));
  const pad = value => String(value).padStart(2, "0");
  const dateKey = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
  const typeLabels = {lesson: "คาบเรียน", exam: "สอบ", meeting: "นัดหมาย", other: "อื่น ๆ"};
  const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
  let viewDate = new Date();
  viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
  let events = [], teachers = [], courses = [], isTeacher = false;

  async function request(url = "calendar-api", options = {}) {
    const response = await fetch(url, {...options, headers: {"Content-Type": "application/json", ...(options.headers || {})}, cache: "no-store"});
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.error || "ไม่สามารถเชื่อมต่อระบบปฏิทินได้");
    return data;
  }

  function monthRange() {
    return {
      start: dateKey(new Date(viewDate.getFullYear(), viewDate.getMonth(), 1)),
      end: dateKey(new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0))
    };
  }

  function showNotice(message, failed = false) {
    const box = document.getElementById("calendar-notice");
    box.textContent = message;
    box.className = "mb-5 px-5 py-4 rounded-xl border text-[13px] font-bold " + (failed ? "border-red-200 bg-red-50 text-red-700" : "border-green-200 bg-green-50 text-green-700");
    clearTimeout(showNotice.timer);
    showNotice.timer = setTimeout(() => box.classList.add("hidden"), 3500);
  }

  function populateFilters() {
    if (teacherFilter) {
      const current = teacherFilter.value;
      teacherFilter.innerHTML = '<option value="">ครูทุกคน</option>' + teachers.map(item => `<option value="${esc(item.id)}">${esc(item.name)}</option>`).join("");
      teacherFilter.value = current;
    }
    const currentCourse = courseFilter.value;
    courseFilter.innerHTML = '<option value="">ทุกคอร์ส</option>' + courses.map(item => `<option value="${esc(item.id)}">${esc(item.title)}</option>`).join("");
    courseFilter.value = currentCourse;
    teacherField.innerHTML = teachers.map(item => `<option value="${esc(item.id)}">${esc(item.name)}</option>`).join("");
    teacherField.disabled = isTeacher;
    filterModalCourses();
  }

  function filterModalCourses(selected = "") {
    const teacherId = teacherField.value;
    const available = courses.filter(course => !course.teacherId || !teacherId || course.teacherId === teacherId);
    courseField.innerHTML = '<option value="">ไม่ผูกกับคอร์ส</option>' + available.map(item => `<option value="${esc(item.id)}">${esc(item.title)}</option>`).join("");
    courseField.value = selected;
  }

  function renderStats() {
    const today = dateKey(new Date());
    const active = events.filter(event => event.status !== "cancelled");
    const hours = active.reduce((total, event) => {
      const [sh, sm] = event.startTime.split(":").map(Number);
      const [eh, em] = event.endTime.split(":").map(Number);
      return total + Math.max(0, (eh * 60 + em - sh * 60 - sm) / 60);
    }, 0);
    document.getElementById("calendar-month-count").textContent = events.length;
    document.getElementById("calendar-today-count").textContent = events.filter(event => event.eventDate === today).length;
    document.getElementById("calendar-hours").textContent = new Intl.NumberFormat("th-TH", {maximumFractionDigits: 1}).format(hours);
  }

  function render() {
    document.getElementById("calendar-heading").textContent = `${monthNames[viewDate.getMonth()]} ${viewDate.getFullYear() + 543}`;
    const first = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
    const cursor = new Date(first);
    cursor.setDate(cursor.getDate() - cursor.getDay());
    const today = dateKey(new Date());
    let html = "";
    for (let index = 0; index < 42; index += 1) {
      const key = dateKey(cursor);
      const outside = cursor.getMonth() !== viewDate.getMonth();
      const dayEvents = events.filter(event => event.eventDate === key);
      html += `<div class="calendar-day p-2 ${outside ? "outside" : ""}" data-date="${key}">
        <button type="button" data-add-date="${key}" class="w-7 h-7 rounded-full text-[12px] font-bold ${key === today ? "bg-pink-500 text-white" : "hover:bg-[#edf2f7]"}" aria-label="เพิ่มกิจกรรมวันที่ ${cursor.getDate()}">${cursor.getDate()}</button>
        <div class="mt-1 space-y-1">${dayEvents.slice(0, 4).map(event => `<button type="button" data-event-id="${esc(event.id)}" title="${esc(event.title)}" class="w-full rounded-md px-2 py-1 text-left text-white text-[11px] leading-tight overflow-hidden ${event.status === "cancelled" ? "opacity-45 line-through" : ""}" style="background:${esc(event.color)}">
          <b>${esc(event.startTime)}</b> <span class="event-detail">${esc(event.title)}</span>
        </button>`).join("")}${dayEvents.length > 4 ? `<div class="text-[10px] font-bold text-[#65738a] px-1">+ อีก ${dayEvents.length - 4} รายการ</div>` : ""}</div>
      </div>`;
      cursor.setDate(cursor.getDate() + 1);
    }
    grid.innerHTML = html;
    renderStats();
  }

  async function load() {
    const range = monthRange();
    const params = new URLSearchParams(range);
    if (teacherFilter?.value) params.set("teacherId", teacherFilter.value);
    if (courseFilter.value) params.set("courseId", courseFilter.value);
    grid.innerHTML = '<div class="col-span-7 p-16 text-center text-[#65738a]">กำลังโหลดปฏิทิน...</div>';
    try {
      const data = await request(`calendar-api?${params}`);
      events = data.events || [];
      teachers = data.teachers || [];
      courses = data.courses || [];
      isTeacher = Boolean(data.isTeacher);
      populateFilters();
      render();
    } catch (error) {
      grid.innerHTML = `<div class="col-span-7 p-16 text-center text-red-600">${esc(error.message)}</div>`;
    }
  }

  function openModal(item = null, date = "") {
    form.reset();
    errorBox.classList.add("hidden");
    form.elements.id.value = item?.id || "";
    form.elements.title.value = item?.title || "";
    form.elements.eventType.value = item?.eventType || "lesson";
    form.elements.status.value = item?.status || "scheduled";
    form.elements.eventDate.value = item?.eventDate || date || dateKey(new Date());
    form.elements.startTime.value = item?.startTime || "09:00";
    form.elements.endTime.value = item?.endTime || "10:30";
    teacherField.value = item?.teacherId || teachers[0]?.id || "";
    filterModalCourses(item?.courseId || "");
    form.elements.location.value = item?.location || "";
    form.elements.color.value = item?.color || "#2563eb";
    form.elements.notes.value = item?.notes || "";
    document.getElementById("calendar-modal-title").textContent = item ? "แก้ไขกิจกรรม" : "เพิ่มกิจกรรม";
    deleteButton.classList.toggle("hidden", !item);
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    setTimeout(() => form.elements.title.focus(), 0);
  }

  function closeModal() {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }

  document.getElementById("calendar-prev").addEventListener("click", () => { viewDate.setMonth(viewDate.getMonth() - 1); load(); });
  document.getElementById("calendar-next").addEventListener("click", () => { viewDate.setMonth(viewDate.getMonth() + 1); load(); });
  document.getElementById("calendar-today").addEventListener("click", () => { const now = new Date(); viewDate = new Date(now.getFullYear(), now.getMonth(), 1); load(); });
  document.getElementById("add-calendar-event").addEventListener("click", () => openModal());
  [teacherFilter, courseFilter].filter(Boolean).forEach(field => field.addEventListener("change", load));
  teacherField.addEventListener("change", () => filterModalCourses());
  document.querySelectorAll("[data-calendar-close]").forEach(button => button.addEventListener("click", closeModal));
  modal.addEventListener("click", event => { if (event.target === modal) closeModal(); });
  grid.addEventListener("click", event => {
    const eventButton = event.target.closest("[data-event-id]");
    if (eventButton) return openModal(events.find(item => item.id === eventButton.dataset.eventId));
    const dateButton = event.target.closest("[data-add-date]");
    if (dateButton) openModal(null, dateButton.dataset.addDate);
  });

  form.addEventListener("submit", async event => {
    event.preventDefault();
    const button = document.getElementById("save-calendar-event");
    const body = Object.fromEntries(new FormData(form));
    button.disabled = true;
    button.textContent = "กำลังบันทึก...";
    errorBox.classList.add("hidden");
    try {
      await request("calendar-api", {method: body.id ? "PATCH" : "POST", body: JSON.stringify(body)});
      closeModal();
      showNotice(body.id ? "แก้ไขกิจกรรมเรียบร้อยแล้ว" : "เพิ่มกิจกรรมลงปฏิทินแล้ว");
      await load();
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("hidden");
    } finally {
      button.disabled = false;
      button.textContent = "บันทึกกิจกรรม";
    }
  });

  deleteButton.addEventListener("click", async () => {
    const id = form.elements.id.value;
    if (!id || !confirm("ยืนยันการลบกิจกรรมนี้ออกจากปฏิทิน?")) return;
    deleteButton.disabled = true;
    try {
      await request(`calendar-api?id=${encodeURIComponent(id)}`, {method: "DELETE"});
      closeModal();
      showNotice("ลบกิจกรรมเรียบร้อยแล้ว");
      await load();
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("hidden");
    } finally {
      deleteButton.disabled = false;
    }
  });

  load();
})();
