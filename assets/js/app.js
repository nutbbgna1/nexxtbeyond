(() => {
  "use strict";

  const menuButton = document.querySelector("[data-menu-toggle]");
  const navigation = document.querySelector("[data-site-nav]");

  if (menuButton && navigation) {
    menuButton.addEventListener("click", () => {
      const isOpen = navigation.classList.toggle("open");
      document.body.classList.toggle("menu-open", isOpen);
      menuButton.setAttribute("aria-expanded", String(isOpen));
      menuButton.textContent = isOpen ? "✕" : "☰";
    });

    navigation.addEventListener("click", () => {
      navigation.classList.remove("open");
      document.body.classList.remove("menu-open");
      menuButton.setAttribute("aria-expanded", "false");
      menuButton.textContent = "☰";
    });
  }

  document.querySelectorAll("[data-current-year]").forEach((element) => {
    element.textContent = new Date().getFullYear();
  });

  const authLink = document.querySelector("[data-auth-link]");
  const mobileAuthLink = document.querySelector("[data-mobile-auth]");
  if (authLink && localStorage.getItem("nb_user_role") === "student") {
    let user = null;
    try { user = JSON.parse(localStorage.getItem("nb_user")); } catch { user = null; }
    if (user) {
      const group = document.createElement("div");
      group.className = "flex items-center gap-4";
      const scores = document.createElement("a");
      scores.href = "placement-test.php?view=history#test-workspace";
      scores.className = authLink.className;
      scores.textContent = "คะแนนของฉัน";
      const logout = document.createElement("button");
      logout.type = "button";
      logout.className = "text-[#a0aabf] hover:text-white text-[13px] font-bold";
      logout.textContent = "ออกจากระบบ";
      logout.addEventListener("click", () => {
        localStorage.removeItem("nb_user_role");
        localStorage.removeItem("nb_user");
        window.location.href = "index.php";
      });
      group.append(scores, logout);
      authLink.replaceWith(group);
      if (mobileAuthLink) {
        mobileAuthLink.href = "placement-test.php?view=history#test-workspace";
        mobileAuthLink.textContent = "คะแนนของฉัน";
      }
    }
  }

  const toast = document.querySelector("[data-toast]");
  window.showToast = (message) => {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add("show");
    window.clearTimeout(window.__nextBeyondToast);
    window.__nextBeyondToast = window.setTimeout(() => toast.classList.remove("show"), 2600);
  };

  document.querySelectorAll("[data-filter-section]").forEach((section) => {
    const search = section.querySelector("[data-search]");
    const buttons = [...section.querySelectorAll("[data-filter]")];
    const cards = [...section.querySelectorAll("[data-filter-card]")];
    const count = section.querySelector("[data-result-count]");
    const empty = section.querySelector("[data-empty]");
    let activeFilter = "all";

    const applyFilters = () => {
      const query = (search?.value || "").trim().toLocaleLowerCase("th");
      let visible = 0;

      cards.forEach((card) => {
        const category = card.dataset.category || "";
        const searchableText = (card.dataset.search || card.textContent).toLocaleLowerCase("th");
        const matchesCategory = activeFilter === "all" || category === activeFilter;
        const matchesSearch = !query || searchableText.includes(query);
        const show = matchesCategory && matchesSearch;
        card.hidden = !show;
        if (show) visible += 1;
      });

      if (count) count.textContent = `พบ ${visible} รายการ`;
      if (empty) empty.hidden = visible !== 0;
    };

    buttons.forEach((button) => {
      button.addEventListener("click", () => {
        activeFilter = button.dataset.filter;
        buttons.forEach((item) => item.classList.toggle("active", item === button));
        applyFilters();
      });
    });

    search?.addEventListener("input", applyFilters);
    applyFilters();
  });
})();
