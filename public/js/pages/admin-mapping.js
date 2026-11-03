// admin-mapping.js - relasi career <-> skill (Owner Console)
// Terhubung ke PostgreSQL via public/api/admin/master.php

(async () => {
  const { el, clear } = UI;

  let allRows = [];

  async function load() {
    try {
      allRows = await API.getCareerSkillMapping();
    } catch {
      allRows = [];
    }
    fillCareerFilter();
    applyFilter();
  }

  function fillCareerFilter() {
    const sel = document.getElementById("mapping-career-filter");
    if (!sel) return;
    clear(sel);
    sel.append(el("option", { value: "all", text: "Semua karier" }));
    const careers = [...new Set(allRows.map((r) => r.career_name))].sort();
    for (const c of careers) sel.append(el("option", { value: c, text: c }));
  }

  function applyFilter() {
    const q = (document.getElementById("admin-search")?.value || "").toLowerCase();
    const career = document.getElementById("mapping-career-filter")?.value || "all";
    const list = allRows.filter((r) => {
      const matchQ =
        !q ||
        r.career_name.toLowerCase().includes(q) ||
        r.skill_name.toLowerCase().includes(q);
      const matchC = career === "all" || r.career_name === career;
      return matchQ && matchC;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("mapping-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "4", class: "admin-empty", text: "Belum ada relasi." })]));
      return;
    }

    for (const r of list) {
      body.append(
        el("tr", {}, [
          el("td", {}, [el("span", { class: "admin-badge-pill", style: "font-size:12px;height:28px;padding:0 12px", text: r.career_name })]),
          el("td", {}, [el("span", { class: "admin-user-name", text: r.skill_name })]),
          el("td", {}, [el("span", { class: "admin-user-email", text: r.skill_category || "—" })]),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger",
                type: "button",
                "data-action": "delete",
                "data-career-id": String(r.career_id),
                "data-skill-id": String(r.skill_id),
                "aria-label": `Hapus relasi ${r.career_name} - ${r.skill_name}`,
                title: "Hapus relasi",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  async function handleDelete(careerId, skillId, careerName, skillName) {
    if (!confirm(`Hapus relasi "${careerName} → ${skillName}"?`)) return;
    try {
      await API.deleteCareerSkillMapping(careerId, skillId);
      UI.toast(`Relasi "${careerName} → ${skillName}" dihapus`);
      await load();
    } catch (err) {
      UI.toast("Gagal menghapus relasi: " + err.message);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load();

    document.getElementById("mapping-table-body")?.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-action='delete']");
      if (!btn) return;
      const careerId = Number(btn.dataset.careerId);
      const skillId = Number(btn.dataset.skillId);
      const r = allRows.find(x => x.career_id === careerId && x.skill_id === skillId);
      if (!r) return;
      handleDelete(careerId, skillId, r.career_name, r.skill_name);
    });

    document.getElementById("admin-search")?.addEventListener("input", applyFilter);
    document.getElementById("mapping-career-filter")?.addEventListener("change", applyFilter);

    document.getElementById("btn-add-mapping")?.addEventListener("click", async () => {
      let careers = [];
      let skills = [];
      try {
        const [cRes, sRes] = await Promise.all([
          API.getMasterCareers(),
          API.getMasterSkills()
        ]);
        careers = cRes || [];
        skills = sRes || [];
      } catch (err) {
        UI.toast("Gagal memuat data master: " + err.message);
        return;
      }

      if (careers.length === 0 || skills.length === 0) {
        UI.toast("Pastikan master karier dan master keahlian sudah terisi.");
        return;
      }

      UI.modal({
        title: "Tambah Relasi Karier - Keterampilan",
        body: [
          el("div", { class: "field" }, [
            el("label", { for: "f-mcareer", text: "Target karier" }),
            el("select", { id: "f-mcareer", name: "career_id" },
              careers.map((c) => el("option", { value: String(c.id), text: c.name }))
            ),
          ]),
          el("div", { class: "field" }, [
            el("label", { for: "f-mskill", text: "Keterampilan" }),
            el("select", { id: "f-mskill", name: "skill_id" },
              skills.map((s) => el("option", { value: String(s.id), text: `${s.name} (${s.category || 'General'})` }))
            ),
          ]),
        ],
        submitLabel: "Simpan",
        onSubmit: async (close) => {
          const careerId = Number(document.getElementById("f-mcareer")?.value);
          const skillId = Number(document.getElementById("f-mskill")?.value);
          if (!careerId || !skillId) {
            UI.toast("Karier dan keterampilan wajib dipilih");
            return false;
          }
          try {
            await API.createCareerSkillMapping(careerId, skillId, true);
            UI.toast("Relasi berhasil ditambahkan");
            close();
            await load();
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
