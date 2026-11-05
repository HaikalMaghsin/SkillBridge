// Pengaturan — tab switching (read-only, tidak ada penyimpanan).
const TABS = ["company", "email", "security", "api"];

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  document.getElementById("rec-today").textContent =
    `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

function switchTab(name) {
  for (const t of TABS) {
    const active = t === name;
    document.querySelector(`.rec-tab[data-tab="${t}"]`).classList.toggle("active", active);
    document.querySelector(`.rec-tab[data-tab="${t}"]`).setAttribute("aria-selected", String(active));
    document.getElementById(`tab-${t}`).classList.toggle("active", active);
  }
}

function initTabs() {
  for (const t of TABS) {
    document
      .querySelector(`.rec-tab[data-tab="${t}"]`)
      .addEventListener("click", () => switchTab(t));
  }
}

renderDate();
initTabs();
