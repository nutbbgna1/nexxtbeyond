(() => {
  "use strict";

  const root = document.querySelector("[data-checkout-app]");
  if (!root) return;

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
    
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  gotoButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      showStep(btn.dataset.goto);
    });
  });

})();
