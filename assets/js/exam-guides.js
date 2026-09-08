(() => {
  "use strict";

  // --- Save Guide Functionality ---
  const saveButtons = document.querySelectorAll("[data-save-guide]");
  const storageKey = "nextBeyondSavedGuides";
  let saved = [];
  try { saved = JSON.parse(localStorage.getItem(storageKey)) || []; }
  catch { saved = []; }

  saveButtons.forEach((button) => {
    const id = button.dataset.saveGuide;
    const sync = () => {
      const active = saved.includes(id);
      button.textContent = active ? "★ บันทึกแล้ว" : "☆ บันทึกแนวข้อสอบ";
      button.classList.toggle("secondary", active);
      button.classList.toggle("outline", !active);
    };
    button.addEventListener("click", () => {
      saved = saved.includes(id) ? saved.filter((item) => item !== id) : [...saved, id];
      localStorage.setItem(storageKey, JSON.stringify(saved));
      sync();
      window.showToast?.(saved.includes(id) ? "บันทึกแนวข้อสอบแล้ว" : "นำออกจากรายการบันทึกแล้ว");
    });
    sync();
  });

  // --- Filter and Search Functionality ---
  const filterSection = document.querySelector("[data-filter-section]");
  if (!filterSection) return;

  const searchInput = filterSection.querySelector("[data-search]");
  const filterButtons = filterSection.querySelectorAll("[data-filter]");
  const cards = filterSection.querySelectorAll("[data-filter-card]");
  const emptyState = filterSection.querySelector("[data-empty]");
  const resultCount = filterSection.querySelector("[data-result-count]");
  
  let currentCategory = "all";
  let currentSearch = "";

  const render = () => {
    let count = 0;
    
    cards.forEach(card => {
      const category = card.dataset.category || "";
      const searchData = (card.dataset.search || "").toLowerCase();
      
      const matchCategory = currentCategory === "all" || category === currentCategory;
      const matchSearch = searchData.includes(currentSearch.toLowerCase());
      
      if (matchCategory && matchSearch) {
        card.removeAttribute("hidden");
        count++;
      } else {
        card.setAttribute("hidden", "");
      }
    });

    if (emptyState) {
      if (count === 0) emptyState.removeAttribute("hidden");
      else emptyState.setAttribute("hidden", "");
    }
    
    if (resultCount) {
      if (currentSearch || currentCategory !== "all") {
        resultCount.textContent = `พบ ${count} รายการ`;
        resultCount.style.display = "block";
      } else {
        resultCount.style.display = "none";
      }
    }
  };

  filterButtons.forEach(button => {
    button.addEventListener("click", () => {
      filterButtons.forEach(btn => btn.classList.remove("active"));
      button.classList.add("active");
      currentCategory = button.dataset.filter;
      render();
    });
  });

  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      currentSearch = e.target.value.trim();
      render();
    });
  }

  // Initial render
  render();

})();
