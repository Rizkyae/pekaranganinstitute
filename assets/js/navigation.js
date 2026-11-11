document.addEventListener("DOMContentLoaded", () => {
  const menuButton = document.getElementById("tombol-menu");
  const sidebar = document.getElementById("sidebar-kanan");
  const backdrop = document.getElementById("sidebar-backdrop");
  const closeButton = document.getElementById("tombol-sidebar-tutup");
  if (!menuButton || !sidebar) return;

  const closeSidebar = () => {
    sidebar.classList.remove("terbuka");
    backdrop?.classList.remove("aktif");
    menuButton.setAttribute("aria-expanded", "false");
    document.body.classList.remove("sidebar-terbuka");
  };

  menuButton.addEventListener("click", () => {
    const open = !sidebar.classList.contains("terbuka");
    sidebar.classList.toggle("terbuka", open);
    backdrop?.classList.toggle("aktif", open);
    menuButton.setAttribute("aria-expanded", String(open));
    document.body.classList.toggle("sidebar-terbuka", open);
  });
  closeButton?.addEventListener("click", closeSidebar);
  backdrop?.addEventListener("click", closeSidebar);
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && sidebar.classList.contains("terbuka")) {
      closeSidebar();
      menuButton.focus();
    }
  });
});
