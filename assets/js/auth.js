(() => {
  "use strict";

  const root = document.querySelector("[data-auth-app]");
  if (!root) return;

  // State
  const state = {
    firstName: "",
    lastName: "",
    grade: "",
    parentFirstName: "",
    parentLastName: "",
    parentPhone: "",
    accountEmail: ""
  };

  // Tabs
  const tabs = root.querySelectorAll("[data-tab]");
  const tabContents = root.querySelectorAll("[data-tab-content]");

  tabs.forEach(tab => {
    tab.addEventListener("click", () => {
      const target = tab.dataset.tab;
      
      // Update tabs style
      tabs.forEach(t => {
        if (t === tab) {
          t.classList.remove("border-transparent", "text-[#94a3b8]");
          t.classList.add("border-pink-500", "text-navy-950");
          t.setAttribute("data-active", "");
        } else {
          t.classList.remove("border-pink-500", "text-navy-950");
          t.classList.add("border-transparent", "text-[#94a3b8]");
          t.removeAttribute("data-active");
        }
      });

      // Update contents
      tabContents.forEach(content => {
        if (content.dataset.tabContent === target) {
          content.classList.remove("hidden");
        } else {
          content.classList.add("hidden");
        }
      });
    });
  });

  // Navigation (Steps)
  const steps = root.querySelectorAll("[data-step]");
  const gotoButtons = root.querySelectorAll("[data-goto]");

  function showStep(stepId) {
    steps.forEach(step => {
      if (step.dataset.step === stepId) {
        step.classList.remove("hidden");
      } else {
        step.classList.add("hidden");
      }
    });

    if (stepId === "account") {
      updateReviewSummary();
    } else if (stepId === "success") {
      updateSuccessView();
    }
    
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  gotoButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      const target = btn.dataset.goto;
      if (target === "success") return;
      
      // Save input values before navigating
      root.querySelectorAll("input[data-field], select[data-field]").forEach(input => {
        state[input.dataset.field] = input.value;
      });

      showStep(target);
    });
  });

  async function hashPassword(password) {
    const bytes = new TextEncoder().encode(password);
    const digest = await crypto.subtle.digest("SHA-256", bytes);
    return [...new Uint8Array(digest)].map(byte => byte.toString(16).padStart(2, "0")).join("");
  }

  function readUsers() {
    try { return JSON.parse(localStorage.getItem("nb_users")) || []; } catch { return []; }
  }

  const registerBtn = root.querySelector('[data-goto="success"]');
  registerBtn?.addEventListener("click", async () => {
    root.querySelectorAll("input[data-field], select[data-field], textarea[data-field]").forEach(input => {
      state[input.dataset.field] = input.value;
    });
    const email = String(state.accountEmail || "").trim().toLowerCase();
    if (!state.firstName || !state.lastName || !email || !state.password) {
      alert("กรุณากรอกชื่อ นามสกุล บัญชีเข้าสู่ระบบ และรหัสผ่านให้ครบ");
      return;
    }
    if (state.password.length < 8 || !/[a-z]/.test(state.password) || !/[A-Z]/.test(state.password) || !/\d/.test(state.password)) {
      alert("รหัสผ่านต้องมีอย่างน้อย 8 ตัว และมีตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข");
      return;
    }
    if (state.password !== state.confirmPassword) {
      alert("รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน");
      return;
    }
    const requiredConsents = [...root.querySelectorAll('[data-step="account"] input[type="checkbox"][required]')];
    if (requiredConsents.some(input => !input.checked)) {
      alert("กรุณายอมรับข้อกำหนดและความยินยอมที่จำเป็น");
      return;
    }
    const users = readUsers();
    if (users.some(user => user.email === email)) {
      alert("อีเมลหรือเบอร์โทรศัพท์นี้มีบัญชีอยู่แล้ว กรุณาเข้าสู่ระบบ");
      return;
    }
    const user = {
      id: `student_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
      email,
      name: `${state.firstName} ${state.lastName}`.trim(),
      grade: state.grade || "",
      role: "student",
      passwordHash: await hashPassword(state.password),
      createdAt: new Date().toISOString()
    };
    users.push(user);
    state.studentId = user.id;
    localStorage.setItem("nb_users", JSON.stringify(users));
    localStorage.setItem("nb_user_role", "student");
    localStorage.setItem("nb_user", JSON.stringify({ id: user.id, email: user.email, name: user.name, grade: user.grade }));
    showStep("success");
  });

  // Chips
  const chipsContainers = root.querySelectorAll("[data-chips]");
  chipsContainers.forEach(container => {
    const isMulti = container.dataset.chips === "subjects" || container.dataset.chips === "contact";
    const chips = container.querySelectorAll("[data-chip]");
    
    chips.forEach(chip => {
      chip.addEventListener("click", () => {
        if (isMulti) {
          chip.classList.toggle("active");
        } else {
          chips.forEach(c => c.classList.remove("active"));
          chip.classList.add("active");
        }
      });
    });
  });

  // Update Review Summary
  function updateReviewSummary() {
    const studentNameEl = root.querySelector("[data-review='studentName']");
    const gradeEl = root.querySelector("[data-review='grade']");
    const parentNameEl = root.querySelector("[data-review='parentName']");
    const parentPhoneEl = root.querySelector("[data-review='parentPhone']");

    if (studentNameEl) studentNameEl.textContent = state.firstName ? `${state.firstName} ${state.lastName}` : "—";
    if (gradeEl) gradeEl.textContent = state.grade || "—";
    if (parentNameEl) parentNameEl.textContent = state.parentFirstName ? `${state.parentFirstName} ${state.parentLastName}` : "—";
    if (parentPhoneEl) parentPhoneEl.textContent = state.parentPhone || "—";
  }



  // Update Success View
  function updateSuccessView() {
    const nameEl = root.querySelector("[data-success='name']");
    const accountEl = root.querySelector("[data-success='account']");
    const parentEl = root.querySelector("[data-success='parent']");

    if (nameEl) nameEl.textContent = state.firstName ? `${state.firstName} ${state.lastName}` : "—";
    if (accountEl) accountEl.textContent = state.accountEmail || state.parentPhone || "—";
    if (parentEl) parentEl.textContent = state.parentFirstName ? `${state.parentFirstName} ${state.parentLastName}` : "—";
    const idEl = root.querySelector("[data-success='id']");
    if (idEl) idEl.textContent = state.studentId || "—";
  }

  // Login state used by the current browser-based account flow.
  const loginBtn = root.querySelector("[data-action='login']");
  if (loginBtn) {
    loginBtn.addEventListener("click", async () => {
      const emailInput = root.querySelector("#login-email");
      const passwordInput = root.querySelector("#login-password");
      
      const email = emailInput ? emailInput.value.trim() : "";
      const password = passwordInput ? passwordInput.value : "";
      if (!email || !password) {
        alert("กรุณากรอกอีเมลและรหัสผ่าน");
        return;
      }
      
      if (email === "admin" || email === "admin@nextbeyond.edu" || email === "admin@nextbeyond.com") {
        localStorage.setItem("nb_user_role", "admin");
        window.location.href = "admin/";
      } else {
        const normalizedEmail = email.toLowerCase();
        const passwordHash = await hashPassword(password);
        const account = readUsers().find(user => user.email === normalizedEmail && user.passwordHash === passwordHash && user.role === "student");
        if (!account) {
          alert("ไม่พบบัญชีหรือรหัสผ่านไม่ถูกต้อง");
          return;
        }
        localStorage.setItem("nb_user_role", "student");
        localStorage.setItem("nb_user", JSON.stringify({ id: account.id, email: account.email, name: account.name, grade: account.grade || "" }));
        const returnTo = new URLSearchParams(window.location.search).get("returnTo");
        window.location.href = returnTo && !returnTo.includes(":") && !returnTo.startsWith("//") ? returnTo : "index.php";
      }
    });
  }

})();
