(() => {
  "use strict";
  const userRole = localStorage.getItem("nb_user_role");
  if (userRole !== "admin") {
    window.location.href = "auth.php";
  }
})();
