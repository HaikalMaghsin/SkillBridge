// admin-skills.js - manajemen keterampilan master (Admin Console)
// Terhubung ke PostgreSQL via public/api/admin/master.php

(async () => {
  const { el, clear } = UI;

  let allSkills = [];

  async function load() {
    try {
      allSkills = await API.getMasterSkills();
    } catch {
      allSkills = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("admin-search")?.value || "").toLowerCase();
    const cat = document.getElementById("admin-cat-filter")?.value || "";
    const list = allSkills.filter((s) => {
      const matchQ = !q || s.name.toLowerCase().includes(q);
      const matchCat = !cat || s.category === cat;
      return matchQ && matchCat;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("skills-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "4", class: "admin-empty", text: "Tidak ada keterampilan." })]));
      return;
    }

    for (const s of list) {
      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "admin-user-cell" }, [
              el("div", { class: "admin-user-avatar-sm", text: s.name.slice(0, 2).toUpperCase() }),
              el("div", {}, [
                el("div", { class: "admin-user-name", text: s.name }),
                el("div", { class: "admin-user-email", text: `ID ${s.id}` }),
              ]),
            ]),
          ]),
          el("td", {}, [el("span", { class: "admin-badge-pill", text: s.category || "—" })]),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn",
                type: "button",
                "data-action": "edit", "data-id": String(s.id), "aria-label": "Edit",
                title: "Edit",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.5 2.5l3 3L4 13H1v-3L8.5 2.5z"/></svg>',
              }),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger",
                type: "button",
                "data-action": "delete", "data-id": String(s.id), "aria-label": "Hapus",
                title: "Hapus",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M5.5 6v5M8.5 6v5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  function openEditModal(skill) {
    UI.modal({
      title: "Edit Keterampilan",
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "edit-skill-name", text: "Nama keterampilan" }),
          el("input", { type: "text", id: "edit-skill-name", value: skill.name }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "edit-skill-cat", text: "Kategori" }),
          el("select", { id: "edit-skill-cat" }, [
            "Programming", "Database", "Web", "Tools", "Data", "Design",
          ].map((c) => el("option", { value: c, text: c, ...(c === skill.category ? { selected: true } : {}) }))),
        ]),
      ],
      submitLabel: "Simpan Perubahan",
      onSubmit: async (close) => {
        const name = document.getElementById("edit-skill-name")?.value.trim();
        const category = document.getElementById("edit-skill-cat")?.value;
        if (!name) {
          UI.toast("Nama keterampilan wajib diisi");
          return false;
        }
        try {
          await API.updateMasterSkill({ id: skill.id, name, category });
          UI.toast(`Keterampilan "${name}" berhasil diperbarui`);
          close();
          load();
          return true;
        } catch (err) {
          UI.toast("Gagal memperbarui: " + err.message);
          return false;
        }
      },
    });
  }

  async function handleDelete(skill) {
    if (!confirm(`Hapus keterampilan "${skill.name}"?`)) return;
    try {
      await API.deleteMasterSkill(skill.id);
      UI.toast(`Keterampilan "${skill.name}" dihapus`);
      load();
    } catch (err) {
      UI.toast("Gagal menghapus: " + err.message);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load();

    // Event delegation tunggal pada tbody
    document.getElementById("skills-table-body")?.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      const action = btn.dataset.action;
      const id = Number(btn.dataset.id);
      const s = allSkills.find((x) => x.id === id);
      if (!s) return;

      if (action === "edit") {
        openEditModal(s);
      } else if (action === "delete") {
        handleDelete(s);
      }
    });

    document.getElementById("admin-search")?.addEventListener("input", applyFilter);
    document.getElementById("admin-cat-filter")?.addEventListener("change", applyFilter);

    document.getElementById("btn-add-skill")?.addEventListener("click", () => {
      UI.modal({
        title: "Tambah Keterampilan",
        body: [
          el("div", { class: "field" }, [
            el("label", { for: "f-name", text: "Nama keterampilan" }),
            el("input", { type: "text", id: "f-name", name: "name", placeholder: "Contoh: REST APIs" }),
          ]),
          el("div", { class: "field" }, [
            el("label", { for: "f-cat", text: "Kategori" }),
            el("select", { id: "f-cat", name: "category" }, [
              "Programming", "Database", "Web", "Tools", "Data", "Design",
            ].map((c) => el("option", { value: c, text: c }))),
          ]),
        ],
        submitLabel: "Simpan",
        onSubmit: async (close) => {
          const name = document.getElementById("f-name")?.value.trim();
          const category = document.getElementById("f-cat")?.value;
          if (!name) {
            UI.toast("Nama keterampilan wajib diisi");
            return false;
          }
          try {
            await API.createMasterSkill({ name, category });
            UI.toast(`Keterampilan "${name}" berhasil ditambahkan`);
            close();
            load();
            return true;
          } catch (err) {
            UI.toast("Gagal menambahkan: " + err.message);
            return false;
          }
        },
      });
    });
  });
})();
