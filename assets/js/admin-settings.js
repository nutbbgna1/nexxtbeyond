(() => {
  "use strict";

  function init() {
    const tabs = [...document.querySelectorAll("[data-settings-tab]")];
    const panels = [...document.querySelectorAll("[data-settings-panel]")];
    const forms = [...document.querySelectorAll("[data-settings-form]")];
    const notice = document.getElementById("settings-notice");
    const logoInput = document.getElementById("school-logo-input");
    const logoPreview = document.getElementById("school-logo-preview");
    let savedSettings = {};

    async function request(url, options = {}) {
      const response = await fetch(url, options);
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.error || "ดำเนินการไม่สำเร็จ");
      return data;
    }

    function showNotice(message, isError = false) {
      if (!notice) return;
      notice.textContent = message;
      notice.className = "mb-5 px-5 py-4 rounded-xl border text-[13px] font-bold " +
        (isError ? "border-red-200 bg-red-50 text-red-700" : "border-green-200 bg-green-50 text-green-700");
      window.clearTimeout(showNotice.timer);
      showNotice.timer = window.setTimeout(() => notice.classList.add("hidden"), 3500);
    }

    function showLogo(path) {
      if (!logoPreview) return;
      logoPreview.innerHTML = "";
      if (!path) {
        logoPreview.textContent = "LOGO";
        return;
      }
      const image = document.createElement("img");
      image.src = path + "?v=" + Date.now();
      image.alt = "โลโก้สถาบัน";
      image.className = "w-full h-full object-contain p-2";
      logoPreview.appendChild(image);
    }

    function populate() {
      document.querySelectorAll("[data-setting]").forEach(field => {
        const value = savedSettings[field.dataset.setting];
        if (field.type === "checkbox") field.checked = Boolean(value);
        else field.value = value ?? "";
      });
      showLogo(savedSettings.school_logo || "");
    }

    function openTab(name) {
      const selected = tabs.some(tab => tab.dataset.settingsTab === name) ? name : "general";
      tabs.forEach(tab => {
        const active = tab.dataset.settingsTab === selected;
        tab.classList.toggle("border-pink-500", active);
        tab.classList.toggle("border-transparent", !active);
        tab.classList.toggle("text-navy-950", active);
        tab.classList.toggle("text-[#94a3b8]", !active);
      });
      panels.forEach(panel => {
        panel.classList.toggle("hidden", panel.dataset.settingsPanel !== selected);
      });
      try {
        history.replaceState({}, "", "#" + selected);
      } catch (e) {
        // Ignore if running from file:// or other history restrictions
      }
    }

    async function loadSettings() {
      try {
        const data = await request("settings-api", {
          headers: { Accept: "application/json" },
          cache: "no-store"
        });
        savedSettings = data.settings || {};
        populate();
      } catch (error) {
        showNotice(error.message, true);
      }
    }

    tabs.forEach(tab => tab.addEventListener("click", (e) => {
      e.preventDefault();
      openTab(tab.dataset.settingsTab);
    }));

    forms.forEach(form => {
      form.addEventListener("submit", async event => {
        event.preventDefault();
        const button = form.querySelector(".settings-save");
        if (!button) return;
        const settings = {};
        form.querySelectorAll("[data-setting]").forEach(field => {
          settings[field.dataset.setting] = field.type === "checkbox" ? field.checked : field.value.trim();
        });
        button.disabled = true;
        button.textContent = "กำลังบันทึก...";
        try {
          await request("settings-api", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ settings })
          });
          Object.assign(savedSettings, settings);
          showNotice("บันทึกการตั้งค่าเรียบร้อยแล้ว");
        } catch (error) {
          showNotice(error.message, true);
        } finally {
          button.disabled = false;
          button.textContent = button.dataset.label || "บันทึกการตั้งค่า";
        }
      });
      const saveButton = form.querySelector(".settings-save");
      if (saveButton) saveButton.dataset.label = saveButton.textContent;
    });

    document.querySelectorAll("[data-settings-reset]").forEach(button => {
      button.addEventListener("click", (e) => {
        e.preventDefault();
        populate();
      });
    });

    const chooseLogoBtn = document.getElementById("choose-school-logo");
    if (chooseLogoBtn && logoInput) {
      chooseLogoBtn.addEventListener("click", () => logoInput.click());
    }

    if (logoInput) {
      logoInput.addEventListener("change", async () => {
        const file = logoInput.files?.[0];
        if (!file) return;
        const button = document.getElementById("choose-school-logo");
        const body = new FormData();
        body.append("logo", file);
        if (button) {
          button.disabled = true;
          button.textContent = "กำลังอัปโหลด...";
        }
        try {
          const data = await request("settings-api", { method: "POST", body });
          savedSettings.school_logo = data.path;
          showLogo(data.path);
          showNotice("อัปโหลดโลโก้เรียบร้อยแล้ว");
        } catch (error) {
          showNotice(error.message, true);
        } finally {
          logoInput.value = "";
          if (button) {
            button.disabled = false;
            button.textContent = "เลือกไฟล์";
          }
        }
      });
    }

    const aiForm = document.getElementById("ai-settings-form");
    const aiInput = document.getElementById("settings-api-key");
    const aiStatus = document.getElementById("ai-key-status");
    const aiHint = document.getElementById("ai-key-hint");

    async function loadAiStatus() {
      if (!aiStatus) return;
      try {
        const data = await request("ai-settings-api", { cache: "no-store" });
        aiStatus.textContent = data.configured ? "ตั้งค่าแล้ว" : "ยังไม่ได้ตั้งค่า";
        aiStatus.className = "px-3 py-1 rounded-full text-[11px] font-bold " +
          (data.configured ? "bg-green-50 text-green-700" : "bg-amber-50 text-amber-700");
        if (data.configured && data.maskedKey && aiHint) {
          aiHint.textContent = "API Key ปัจจุบัน: " + data.maskedKey + " — กรอกค่าใหม่เมื่อต้องการเปลี่ยน";
        }
      } catch (error) {
        aiStatus.textContent = "ตรวจสอบไม่สำเร็จ";
        showNotice(error.message, true);
      }
    }

    if (aiForm && aiInput) {
      aiForm.addEventListener("submit", async event => {
        event.preventDefault();
        const apiKey = aiInput.value.trim();
        if (!apiKey) {
          showNotice("กรุณากรอก Gemini API Key", true);
          aiInput.focus();
          return;
        }
        const button = aiForm.querySelector(".settings-save");
        if (button) button.disabled = true;
        try {
          await request("ai-settings-api", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ apiKey })
          });
          aiInput.value = "";
          await loadAiStatus();
          showNotice("บันทึก Gemini API Key เรียบร้อยแล้ว");
        } catch (error) {
          showNotice(error.message, true);
        } finally {
          if (button) button.disabled = false;
        }
      });
    }

    openTab(location.hash.replace("#", "") || "general");
    loadSettings();
    loadAiStatus();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
