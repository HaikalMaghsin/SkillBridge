// admin-users.js - manajemen pengguna (Admin Console)
// Terhubung ke PostgreSQL via public/api/admin/users.php

document.addEventListener("DOMContentLoaded", () => {
  const { el, clear } = UI;

  const STATUS = {
    active: { cls: "", label: "Aktif" },
    inactive: { cls: "", label: "Non-aktif" },
    suspended: { cls: "admin-badge-danger", label: "Suspended" },
  };

  let allUsers = [];

  function initials(name) {
    return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
  }

  async function load() {
    try {
      allUsers = await API.getAdminUsers();
    } catch {
      allUsers = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("user-search")?.value || "").toLowerCase();
    const status = document.getElementById("user-status-filter")?.value || "all";
    const list = allUsers.filter((u) => {
      const matchQ =
        !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q);
      const matchS = status === "all" || u.status === status;
      return matchQ && matchS;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("users-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(
        el("tr", {}, [
          el("td", { colspan: "7", class: "admin-empty", text: "Tidak ada pengguna yang cocok." }),
        ])
      );
      return;
    }

    for (const u of list) {
      const badge = STATUS[u.status] || STATUS.active;
      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "admin-user-cell" }, [
              el("div", { class: "admin-user-avatar-sm", text: initials(u.name) }),
              el("div", {}, [
                el("p", { class: "admin-user-name", text: u.name }),
                el("p", { class: "admin-user-email", text: u.email }),
              ]),
            ]),
          ]),
          el("td", { text: u.career_target || "—" }),
          el("td", { text: String(u.skills_count ?? 0) }),
          el("td", { text: String(u.evidence_count ?? 0) }),
          el("td", {}, [el("span", { class: `admin-badge-pill ${badge.cls}`, text: badge.label })]),
          el("td", { text: UI.formatDate(u.created_at) }),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                type: "button",
                class: "admin-icon-btn",
                "data-action": "edit", "data-id": String(u.id), "aria-label": `Edit ${u.name}`,
                title: "Edit",
                innerHTML:
                  '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.5 2.5l3 3L4 13H1v-3L8.5 2.5z"/></svg>',
              }),
              el("button", {
                type: "button",
                class: "admin-icon-btn admin-icon-btn-danger",
                "data-action": "delete", "data-id": String(u.id), "aria-label": `Hapus ${u.name}`,
                title: "Hapus",
                innerHTML:
                  '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  function openEditModal(u) {
    UI.modal({
      title: "Edit Pengguna",
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "edit-u-name", text: "Nama lengkap" }),
          el("input", { type: "text", id: "edit-u-name", value: u.name }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "edit-u-email", text: "Email" }),
          el("input", { type: "email", id: "edit-u-email", value: u.email }),
        ]),
      ],
      submitLabel: "Simpan Perubahan",
      onSubmit: async (close) => {
        const name = document.getElementById("edit-u-name")?.value.trim();
        const email = document.getElementById("edit-u-email")?.value.trim();
        if (!name || !email) {
          UI.toast("Nama dan email wajib diisi");
          return false;
        }
        try {
          await API.updateAdminUser({ id: u.id, name, email });
          UI.toast(`Data pengguna "${name}" berhasil diperbarui`);
          close();
          await load();
          return true;
        } catch (err) {
          UI.toast("Gagal memperbarui: " + err.message);
          return false;
        }
      },
    });
  }

  // Event delegation untuk tombol aksi di tabel
  document.getElementById("users-table-body")?.addEventListener("click", async (e) => {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;
    const action = btn.dataset.action;
    const id = Number(btn.dataset.id);
    const u = allUsers.find(x => x.id === id);
    if (!u) return;

    if (action === "edit") {
      openEditModal(u);
    } else if (action === "delete") {
      if (!confirm(`Hapus akun "${u.name}"? Tindakan ini tidak dapat dibatalkan.`)) return;
      try {
        await API.deleteAdminUser(u.id);
        UI.toast(`Pengguna "${u.name}" berhasil dihapus`);
        await load();
      } catch (err) {
        UI.toast("Gagal menghapus: " + err.message);
      }
    }
  });

  document.getElementById("user-search")?.addEventListener("input", applyFilter);
  document.getElementById("user-status-filter")?.addEventListener("change", applyFilter);

  document.getElementById("btn-add-user")?.addEventListener("click", () => {
    UI.modal({
      title: "Tambah Pengguna Baru",
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "f-name", text: "Nama lengkap" }),
          el("input", { type: "text", id: "f-name", name: "name", placeholder: "Nama lengkap pengguna" }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-email", text: "Email" }),
          el("input", {
            type: "email",
            id: "f-email",
            name: "email",
            placeholder: "email@mahasiswa.ac.id",
          }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-pass", text: "Password awal" }),
          el("input", {
            type: "password",
            id: "f-pass",
            name: "password",
            placeholder: "Minimal 8 karakter (default: password123)",
          }),
        ]),
      ],
      submitLabel: "Simpan Pengguna",
      onSubmit: async (close) => {
        const name = document.getElementById("f-name")?.value.trim();
        const email = document.getElementById("f-email")?.value.trim();
        const password = document.getElementById("f-pass")?.value || "password123";
        if (!name || !email) {
          UI.toast("Nama dan email wajib diisi");
          return false;
        }
        try {
          await API.createAdminUser({ name, email, password, role: "mahasiswa" });
          UI.toast(`Pengguna "${name}" berhasil didaftarkan`);
          close();
          await load();
          return true;
        } catch (err) {
          UI.toast("Gagal mendaftarkan: " + err.message);
          return false;
        }
      },
    });
  });

  load();
});
