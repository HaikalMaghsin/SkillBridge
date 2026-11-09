// admin-admins.js - manajemen akun administrator
// Menampilkan daftar admin dari PostgreSQL (users.role = 'admin')

(async () => {
  const { el, clear } = UI;

  function initials(name) {
    return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
  }

  let allAdmins = [];
  let currentUserId = null;

  async function load() {
    try {
      // Ambil daftar admin dari endpoint users.php dengan filter role=admin
      const response = await fetch(`${window.APP_BASE_URL}/api/admin/users.php?role=admin`, {
        headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' }
      });

      if (!response.ok) throw new Error('Failed to load admins');

      const data = await response.json();
      allAdmins = data.users || [];
      currentUserId = data.current_user_id || null;
    } catch (err) {
      console.error('Error loading admins:', err);
      UI.toast('Gagal memuat data administrator', 'error');
      allAdmins = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("admin-search").value || "").toLowerCase();
    const status = document.getElementById("admin-status-filter").value;

    const list = allAdmins.filter((a) => {
      const matchQ =
        !q ||
        a.name.toLowerCase().includes(q) ||
        a.email.toLowerCase().includes(q);
      const matchStatus = status === "all" ||
        (status === "active" && a.is_active) ||
        (status === "inactive" && !a.is_active);
      return matchQ && matchStatus;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("admins-table-body");
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "5", class: "admin-empty", text: "Tidak ada administrator yang cocok." })]));
      return;
    }

    for (const a of list) {
      const isCurrentUser = a.id === currentUserId;
      const active = a.is_active;

      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "admin-user-cell" }, [
              el("div", { class: "admin-user-avatar-sm", text: initials(a.name) }),
              el("div", {}, [
                el("p", { class: "admin-user-name", text: a.name + (isCurrentUser ? " (Anda)" : "") }),
                el("p", { class: "admin-user-email", text: a.email }),
              ]),
            ]),
          ]),
          el("td", {}, [
            el("span", {
              class: active ? "admin-badge-pill" : "admin-badge-pill admin-badge-inactive",
              text: active ? "Aktif" : "Non-aktif",
            }),
          ]),
          el("td", { text: UI.formatDate(a.created_at) }),
          el("td", { text: UI.formatDate(a.updated_at) }),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              // Tombol toggle status (disabled jika diri sendiri atau admin terakhir)
              el("button", {
                type: "button",
                class: "admin-icon-btn",
                disabled: isCurrentUser,
                title: isCurrentUser ? "Tidak dapat mengubah status sendiri" : `Ubah status ${a.name}`,
                "data-action": "toggle-status",
                "data-id": String(a.id),
                "aria-label": `Ubah status ${a.name}`,
              }, [
                el("svg", { width: "14", height: "14", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" }, [
                  el("path", { d: "M2 8a6 6 0 0111.2-3M14 8a6 6 0 01-11.2 3" }),
                  el("path", { d: "M13.5 2v3h-3M2.5 14v-3h3" }),
                ]),
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
    document.getElementById("admins-table-body")?.addEventListener("click", async (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      if (btn.disabled) return;

      const action = btn.dataset.action;
      const id = Number(btn.dataset.id);
      const admin = allAdmins.find(x => x.id === id);
      if (!admin) return;

      if (action === "toggle-status") {
        const nextStatus = !admin.is_active;
        const statusText = nextStatus ? "aktif" : "nonaktif";

        if (!confirm(`${nextStatus ? "Aktifkan" : "Nonaktifkan"} administrator ${admin.name}?`)) return;

        try {
          const response = await fetch(`${window.APP_BASE_URL}/api/admin/users.php`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
              id: admin.id,
              is_active: nextStatus
            })
          });

          if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || 'Failed to update status');
          }

          UI.toast(`Status ${admin.name} diubah menjadi ${statusText}`);
          await load();
        } catch (err) {
          console.error('Error updating admin status:', err);
          UI.toast(err.message || 'Gagal mengubah status', 'error');
        }
      }
    });

    document.getElementById("admin-search")?.addEventListener("input", applyFilter);
    document.getElementById("admin-status-filter")?.addEventListener("change", applyFilter);
  });
})();
