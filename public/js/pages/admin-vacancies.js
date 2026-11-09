// admin-vacancies.js - manajemen lowongan kerja (Admin Console)
// Terhubung ke PostgreSQL untuk moderasi lowongan

(async () => {
  const { el, clear } = UI;

  const STATUS_MAP = {
    active: { cls: "admin-badge-success", label: "Aktif" },
    inactive: { cls: "admin-badge-inactive", label: "Non-aktif" },
    closed: { cls: "admin-badge-danger", label: "Ditutup" },
  };

  let allVacancies = [];

  async function load() {
    try {
      const response = await fetch(`${window.APP_BASE_URL}/api/admin/vacancies.php`, {
        headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' }
      });

      if (!response.ok) throw new Error('Failed to load vacancies');

      allVacancies = await response.json();
    } catch (err) {
      console.error('Error loading vacancies:', err);
      UI.toast('Gagal memuat data lowongan', 'error');
      allVacancies = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("vacancy-search")?.value || "").toLowerCase();
    const status = document.getElementById("vacancy-status-filter")?.value || "all";

    const list = allVacancies.filter((v) => {
      const matchQ =
        !q ||
        v.title.toLowerCase().includes(q) ||
        v.company.toLowerCase().includes(q) ||
        v.category.toLowerCase().includes(q);
      const matchS = status === "all" || v.status === status;
      return matchQ && matchS;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("vacancies-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "7", class: "admin-empty", text: "Tidak ada lowongan yang cocok." })]));
      return;
    }

    for (const v of list) {
      const badge = STATUS_MAP[v.status] || STATUS_MAP.inactive;

      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", {}, [
              el("p", { class: "admin-user-name", text: v.title }),
              el("p", { class: "admin-user-email", text: v.category }),
            ])
          ]),
          el("td", { text: v.company }),
          el("td", { text: String(v.positions) + " posisi" }),
          el("td", {}, [el("span", { class: `admin-badge-pill ${badge.cls}`, text: badge.label })]),
          el("td", { text: v.posted_by || "—" }),
          el("td", { text: UI.formatDate(v.created_at) }),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn",
                type: "button",
                "data-action": "toggle-status",
                "data-id": String(v.id),
                "aria-label": "Ubah status",
                title: "Ubah status lowongan",
              }, [
                el("svg", { width: "14", height: "14", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" }, [
                  el("path", { d: "M2 8a6 6 0 0111.2-3M14 8a6 6 0 01-11.2 3" }),
                  el("path", { d: "M13.5 2v3h-3M2.5 14v-3h3" }),
                ])
              ]),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger",
                type: "button",
                "data-action": "delete",
                "data-id": String(v.id),
                "aria-label": "Hapus",
                title: "Hapus lowongan",
              }, [
                el("svg", { width: "14", height: "14", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" }, [
                  el("path", { d: "M3 5h10M6 5V3.5a1 1 0 011-1h2a1 1 0 011 1V5M5.5 5l.5 9h4l.5-9" }),
                ])
              ]),
            ]),
          ]),
        ])
      );
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load();

    // Event delegation untuk tombol aksi di tabel
    document.getElementById("vacancies-table-body")?.addEventListener("click", async (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;

      const action = btn.dataset.action;
      const id = Number(btn.dataset.id);
      const vacancy = allVacancies.find(x => x.id === id);
      if (!vacancy) return;

      if (action === "toggle-status") {
        // Cycle: active -> inactive -> closed -> active
        const statusCycle = { active: 'inactive', inactive: 'closed', closed: 'active' };
        const nextStatus = statusCycle[vacancy.status] || 'active';

        const statusLabel = { active: 'Aktif', inactive: 'Non-aktif', closed: 'Ditutup' };

        if (!confirm(`Ubah status "${vacancy.title}" menjadi ${statusLabel[nextStatus]}?`)) return;

        try {
          const response = await fetch(`${window.APP_BASE_URL}/api/admin/vacancies.php`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
              id: vacancy.id,
              status: nextStatus
            })
          });

          if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || 'Failed to update status');
          }

          UI.toast(`Status lowongan "${vacancy.title}" diubah menjadi ${statusLabel[nextStatus]}`);
          await load();
        } catch (err) {
          console.error('Error updating vacancy status:', err);
          UI.toast(err.message || 'Gagal mengubah status', 'error');
        }

      } else if (action === "delete") {
        if (!confirm(`Hapus lowongan "${vacancy.title}" dari ${vacancy.company}?\n\nTindakan ini tidak dapat dibatalkan.`)) return;

        try {
          const response = await fetch(`${window.APP_BASE_URL}/api/admin/vacancies.php`, {
            method: 'DELETE',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ id: vacancy.id })
          });

          if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || 'Failed to delete vacancy');
          }

          UI.toast("Lowongan berhasil dihapus");
          await load();
        } catch (err) {
          console.error('Error deleting vacancy:', err);
          UI.toast(err.message || 'Gagal menghapus lowongan', 'error');
        }
      }
    });

    document.getElementById("vacancy-search")?.addEventListener("input", applyFilter);
    document.getElementById("vacancy-status-filter")?.addEventListener("change", applyFilter);
  });
})();
