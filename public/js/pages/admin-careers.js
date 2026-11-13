// admin-careers.js - manajemen target karier master (Owner Console)
// Terhubung ke PostgreSQL via public/api/admin/master.php dengan integrasi skill mapping langsung

(async () => {
  const { el, clear } = UI;

  let allCareers = [];
  let catalogSkills = [];

  async function load() {
    try {
      const [careersRes, catalogRes] = await Promise.all([
        API.getMasterCareers(),
        API.getSkillCatalog()
      ]);
      allCareers = careersRes || [];
      catalogSkills = catalogRes || [];
    } catch (err) {
      console.error('Error loading master careers or skills:', err);
      allCareers = [];
      catalogSkills = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("admin-search")?.value || "").toLowerCase().trim();
    const list = allCareers.filter((c) => {
      if (!q) return true;
      const matchName = c.name.toLowerCase().includes(q);
      const matchDesc = (c.description || "").toLowerCase().includes(q);
      const matchSkill = (c.skills || []).some(s => s.toLowerCase().includes(q));
      return matchName || matchDesc || matchSkill;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("careers-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "3", class: "admin-empty", text: "Tidak ada target karier." })]));
      return;
    }

    for (const c of list) {
      const skillsList = c.skills || [];
      const chips = skillsList.slice(0, 5).map((s) =>
        el("span", { class: "admin-badge-pill", style: "margin-right:4px;margin-bottom:4px;font-size:11px;padding:3px 8px;border-radius:12px", text: s })
      );
      if (skillsList.length > 5) {
        chips.push(el("span", { style: "font-size:11px;color:var(--text-muted);font-weight:600;align-self:center", text: `+${skillsList.length - 5} lagi` }));
      }
      if (skillsList.length === 0) {
        chips.push(el("span", { style: "font-size:12px;color:var(--text-muted);font-style:italic", text: "Belum ada skill terkait" }));
      }

      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "admin-user-name", style: "font-weight:600;font-size:14px;color:var(--text-primary)", text: c.name }),
            el("div", { class: "admin-user-email", style: "font-size:12px;color:var(--text-muted);margin-top:2px", text: (c.description || "").slice(0, 80) + (c.description?.length > 80 ? "…" : "") }),
          ]),
          el("td", {}, [el("div", { style: "display:flex;flex-wrap:wrap;gap:4px;align-items:center" }, chips)]),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn", type: "button", "data-action": "edit", "data-id": String(c.id), "aria-label": "Edit", title: "Edit Karier & Keterampilan",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.5 2.5l3 3L4 13H1v-3L8.5 2.5z"/></svg>',
              }),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger", type: "button", "data-action": "delete", "data-id": String(c.id), "aria-label": "Hapus", title: "Hapus Karier",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  function openCareerModal(career = null) {
    const isEdit = Boolean(career);
    const selectedSkillIds = new Set();

    if (isEdit) {
      if (Array.isArray(career.skill_ids) && career.skill_ids.length > 0) {
        career.skill_ids.forEach(id => selectedSkillIds.add(Number(id)));
      } else if (Array.isArray(career.skills) && career.skills.length > 0) {
        career.skills.forEach(skillName => {
          const found = catalogSkills.find(s => s.name.toLowerCase() === String(skillName).toLowerCase());
          if (found) selectedSkillIds.add(found.id);
        });
      }
    }

    // Modal elements
    const nameInput = el("input", {
      type: "text",
      id: "career-name",
      placeholder: "Contoh: Backend Developer",
      value: career ? career.name : "",
      style: "width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; box-sizing: border-box;"
    });

    const descTextarea = el("textarea", {
      id: "career-desc",
      placeholder: "Deskripsi singkat tentang tanggung jawab dan lingkup peran karier ini...",
      text: career ? (career.description || "") : "",
      style: "width: 100%; min-height: 70px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; box-sizing: border-box; resize: vertical; font-family: inherit;"
    });

    const countBadge = el("span", {
      id: "career-skill-count",
      style: "font-size: 12px; font-weight: 700; color: #4338ca; background: #eef2ff; border: 1px solid #c7d2fe; padding: 3px 10px; border-radius: 12px;",
      text: `${selectedSkillIds.size} keterampilan dipilih`
    });

    const searchInput = el("input", {
      type: "search",
      id: "career-skill-search",
      placeholder: "Cari keterampilan (misal: PHP, SQL, Figma)...",
      style: "width: 100%; height: 38px; padding: 0 12px; margin: 8px 0; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; box-sizing: border-box;"
    });

    const pickerContainer = el("div", {
      id: "career-skills-picker",
      class: "skill-chips-container",
      style: "max-height: 200px; overflow-y: auto; display: flex; flex-wrap: wrap; gap: 8px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc;"
    });

    function updateCountText() {
      countBadge.textContent = `${selectedSkillIds.size} keterampilan dipilih`;
    }

    function renderChips(filterQuery = "") {
      clear(pickerContainer);
      const q = filterQuery.toLowerCase().trim();
      const filtered = catalogSkills.filter(s =>
        !q || s.name.toLowerCase().includes(q) || (s.category && s.category.toLowerCase().includes(q))
      );

      if (filtered.length === 0) {
        pickerContainer.append(el("p", {
          style: "color: var(--text-muted); font-size: 13px; padding: 8px 0; margin: 0; width: 100%",
          text: "Tidak ada keterampilan yang sesuai pencarian."
        }));
        return;
      }

      for (const skill of filtered) {
        const isSelected = selectedSkillIds.has(skill.id);
        const chip = el("div", {
          class: `skill-chip ${isSelected ? 'is-selected' : ''}`,
          "data-id": String(skill.id),
          style: "cursor: pointer; user-select: none; transition: all 0.15s ease;"
        }, [
          isSelected ? el("span", { style: "font-weight: 800; color: #4338ca", text: "✓" }) : null,
          document.createTextNode(skill.name),
          skill.category ? el("span", { class: "skill-chip-badge", text: skill.category }) : null
        ]);

        chip.addEventListener("click", () => {
          if (selectedSkillIds.has(skill.id)) {
            selectedSkillIds.delete(skill.id);
          } else {
            selectedSkillIds.add(skill.id);
          }
          updateCountText();
          renderChips(searchInput.value);
        });

        pickerContainer.append(chip);
      }
    }

    searchInput.addEventListener("input", (e) => {
      renderChips(e.target.value);
    });

    // Initial chip rendering
    renderChips();

    UI.modal({
      id: "modal-career",
      title: isEdit ? "Edit Target Karier" : "Tambah Target Karier",
      body: [
        el("div", { class: "field", style: "margin-bottom: 16px" }, [
          el("label", { for: "career-name", style: "font-weight: 600; font-size: 14px; margin-bottom: 6px; display: block", text: "Nama Karier" }),
          nameInput,
        ]),
        el("div", { class: "field", style: "margin-bottom: 20px" }, [
          el("label", { for: "career-desc", style: "font-weight: 600; font-size: 14px; margin-bottom: 6px; display: block", text: "Deskripsi Singkat" }),
          descTextarea,
        ]),
        el("div", { class: "field", style: "margin-bottom: 12px" }, [
          el("div", { style: "display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px" }, [
            el("label", { style: "font-weight: 600; font-size: 14px; color: var(--text-primary); margin: 0", text: "Keterampilan yang Dipersyaratkan" }),
            countBadge
          ]),
          el("p", { style: "font-size: 12px; color: var(--text-muted); margin: 2px 0 8px", text: "Pilih minimal 1 keterampilan wajib untuk target karier ini." }),
          searchInput,
          pickerContainer
        ])
      ],
      submitLabel: isEdit ? "Simpan Perubahan" : "Simpan Karier",
      onSubmit: async (close) => {
        const name = nameInput.value.trim();
        const description = descTextarea.value.trim();

        if (!name) {
          UI.toast("Nama target karier wajib diisi");
          nameInput.focus();
          return false;
        }

        if (selectedSkillIds.size === 0) {
          UI.toast("Pilih minimal 1 keterampilan yang dipersyaratkan");
          return false;
        }

        const skill_ids = Array.from(selectedSkillIds);

        try {
          if (isEdit) {
            await API.updateMasterCareer({
              id: career.id,
              name,
              description,
              skill_ids
            });
            UI.toast(`Target karier "${name}" berhasil diperbarui`);
          } else {
            await API.createMasterCareer({
              name,
              description,
              skill_ids
            });
            UI.toast(`Target karier "${name}" berhasil ditambahkan`);
          }
          close();
          await load();
          return true;
        } catch (err) {
          UI.toast("Gagal menyimpan target karier: " + err.message);
          return false;
        }
      }
    });
  }

  async function handleDelete(career) {
    if (!confirm(`Hapus target karier "${career.name}"?\nSemua relasi keterampilan untuk karier ini akan dihapus.`)) return;
    try {
      await API.deleteMasterCareer(career.id);
      UI.toast(`Karier "${career.name}" dihapus`);
      await load();
    } catch (err) {
      UI.toast("Gagal menghapus: " + err.message);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load();

    document.getElementById("careers-table-body")?.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      const action = btn.dataset.action;
      const id = Number(btn.dataset.id);
      const c = allCareers.find((x) => x.id === id);
      if (!c) return;

      if (action === "edit") {
        openCareerModal(c);
      } else if (action === "delete") {
        handleDelete(c);
      }
    });

    document.getElementById("admin-search")?.addEventListener("input", applyFilter);

    document.getElementById("btn-add-career")?.addEventListener("click", () => {
      openCareerModal(null);
    });
  });
})();
