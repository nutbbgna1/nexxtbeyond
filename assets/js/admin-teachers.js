(() => {
  "use strict";
  const tbody = document.getElementById("teachers-tbody");
  if (!tbody) return;
  const modal = document.getElementById("teacher-modal");
  const form = document.getElementById("teacher-form");
  const search = document.getElementById("teachers-search");
  const errorBox = document.getElementById("teacher-form-error");
  const esc = value => String(value ?? "").replace(/[&<>'"]/g, char => ({"&":"&amp;","<":"&lt;",">":"&gt;","'":"&#39;",'"':"&quot;"}[char]));
  let teachers = [];

  async function api(url = "teachers-api", options = {}) {
    const response = await fetch(url, {...options, headers: {"Content-Type":"application/json", ...(options.headers || {})}});
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.error || "ไม่สามารถเชื่อมต่อระบบข้อมูลคุณครูได้");
    return data;
  }
  function initials(item) { return esc((item.firstName?.[0] || "") + (item.lastName?.[0] || "")); }
  function render() {
    const query = search.value.trim().toLocaleLowerCase("th");
    const rows = teachers.filter(item => `${item.firstName} ${item.lastName} ${item.email} ${item.subjects || ""}`.toLocaleLowerCase("th").includes(query));
    document.getElementById("teachers-count").textContent = `แสดง ${rows.length} จาก ${teachers.length} รายการ`;
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="p-10 text-center text-[#65738a]">ยังไม่มีข้อมูลคุณครู</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(item => `<tr class="hover:bg-[#f8fafc]">
      <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="w-11 h-11 rounded-full bg-pink-50 text-pink-500 flex items-center justify-center font-black">${initials(item)}</div><div><div class="font-bold text-[14px]">${esc(item.firstName)} ${esc(item.lastName)}</div><div class="text-[12px] text-[#65738a]">${esc(item.email)}${item.phone ? " · " + esc(item.phone) : ""}</div></div></div></td>
      <td class="px-4 py-4 text-[13px]">${esc(item.subjects || "ยังไม่ได้กำหนด")}</td>
      <td class="px-4 py-4 text-center font-bold text-[14px]">${item.courseCount}</td>
      <td class="px-4 py-4"><label class="flex items-center gap-2 text-[12px] font-bold"><input type="checkbox" data-active="${esc(item.id)}" ${item.isActive ? "checked" : ""}> ${item.isActive ? "Active" : "Inactive"}</label></td>
      <td class="px-4 py-4 text-right"><button data-delete="${esc(item.id)}" class="text-red-500 text-[12px] font-bold">ลบ</button></td>
    </tr>`).join("");
  }
  async function load() {
    try { teachers = (await api()).teachers || []; render(); }
    catch (error) { tbody.innerHTML = `<tr><td colspan="5" class="p-10 text-center text-red-500">${esc(error.message)}</td></tr>`; }
  }
  function showModal(show) {
    modal.classList.toggle("hidden", !show); modal.classList.toggle("flex", show);
    if (show) form.elements.firstName.focus(); else { form.reset(); errorBox.classList.add("hidden"); }
  }
  document.getElementById("add-teacher").addEventListener("click", () => showModal(true));
  document.querySelectorAll("[data-close-modal]").forEach(button => button.addEventListener("click", () => showModal(false)));
  modal.addEventListener("click", event => { if (event.target === modal) showModal(false); });
  search.addEventListener("input", render);
  form.addEventListener("submit", async event => {
    event.preventDefault();
    const save = document.getElementById("save-teacher");
    save.disabled = true; errorBox.classList.add("hidden");
    try {
      await api("teachers-api", {method:"POST", body:JSON.stringify(Object.fromEntries(new FormData(form)))});
      showModal(false); await load();
    } catch (error) { errorBox.textContent = error.message; errorBox.classList.remove("hidden"); }
    finally { save.disabled = false; }
  });
  tbody.addEventListener("change", async event => {
    const input = event.target.closest("[data-active]");
    if (!input) return;
    try { await api("teachers-api", {method:"PATCH", body:JSON.stringify({id:input.dataset.active, isActive:input.checked})}); await load(); }
    catch (error) { alert(error.message); await load(); }
  });
  tbody.addEventListener("click", async event => {
    const button = event.target.closest("[data-delete]");
    if (!button || !confirm("ยืนยันการลบบัญชีคุณครูนี้?")) return;
    try { await api(`teachers-api?id=${encodeURIComponent(button.dataset.delete)}`, {method:"DELETE"}); await load(); }
    catch (error) { alert(error.message); }
  });
  load();
})();
