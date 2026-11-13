// skills.js - My Skills page

(async () => {
  const { el } = UI;

  // Ikon per kategori (jalur geometris sederhana, bukan logo merek).
  const CATEGORY_ICONS = {
    Programming: `<path d="M8 6 3 12l5 6M16 6l5 6-5 6"/>`,
    Database: `<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/>`,
    Web: `<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/>`,
    Tools: `<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.1-2.1 2.7-2.5z"/>`,
    Data: `<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>`,
    Design: `<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>`,
  };

  const CATEGORY_ICON_FALLBACK = `<path d="M4 6h16M4 12h16M4 18h10"/>`;

  // Ikon kategori sebagai SVG inline string.
  function categoryIconSvg(category) {
    const path = CATEGORY_ICONS[category] || CATEGORY_ICON_FALLBACK;
    return `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${path}</svg>`;
  }

  // Tile ikon skill: biru di daftar utama, pink saat terpilih di picker.
  function skillAvatar(skill, { size = 44, variant = "primary" } = {}) {
    return el("div", {
      class: `skill-avatar-tile skill-avatar-tile-${variant}`,
      style: `width: ${size}px; height: ${size}px`,
      innerHTML: categoryIconSvg(skill.category),
    });
  }
  let careers, catalog, gap, evidence, mine, targetId;

  try {
    [careers, catalog, gap, evidence, mine, targetId] = await Promise.all([
      API.getCareers(),
      API.getSkillCatalog(),
      API.getSkillGap(),
      API.getEvidence(),
      API.getMySkills(),
      API.getTargetCareer(),
    ]);
  } catch (err) {
    console.error('Error loading skills data:', err);
    UI.toast('Error loading data. Please refresh the page.', 'error');

    // Set fallback values to prevent crash
    careers = [];
    catalog = [];
    gap = { explore: [], covered: [] };
    evidence = [];
    mine = [];
    targetId = null;
  }

  const target = careers.find((c) => c.id === targetId) || careers[0] || { name: 'No Career Selected', description: 'Please set your target career in Profile.' };

  // Card: Target Career
  document.getElementById("card-target").append(
    el("h2", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: target.name }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: target.description })
  );

  // Skills Summary - badge count
  const strongCount = mine.filter((s) => s.label === "Strong").length;
  document.getElementById("badge-strong").textContent = `${strongCount} strong`;

  // Skills List
  const listEl = document.getElementById("skills-list");
  if (mine.length === 0) {
    listEl.append(el("p", { style: "color: var(--text-muted); font-size: 15px", text: "No skills added yet. Use the Add skill button to start building your profile." }));
  } else {
    const skillRows = mine.map((s) => {
      const skill = catalog.find((c) => c.id === s.skill_id);
      if (!skill) return null;
      return renderSkillRow(skill, s.evidence_count, s.label);
    }).filter(Boolean);

    listEl.append(el("div", { style: "display: grid; gap: 16px" }, skillRows));
  }

  // Card: Evidence Summary
  document.getElementById("card-evidence").append(
    el("h2", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: `${evidence.length} evidence items` }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: "You already have a solid foundation of work examples, case studies, and project outcomes." })
  );

  // Card: Skill Gap
  const gapEl = document.getElementById("card-gap");
  if (gap.explore.length === 0) {
    gapEl.append(
      el("h2", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: "All covered" }),
      el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: "You have all required skills for your target career." })
    );
  } else {
    gapEl.append(
      el("h2", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: `${gap.explore.length} areas to grow` }),
      el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: "Focus on the most important gaps first. These are the easiest next steps to strengthen your profile." })
    );

    const gapList = el("div", { style: "display: grid; gap: 12px; margin-top: 18px" });
    for (const skill of gap.explore.slice(0, 3)) {
      gapList.append(renderGapItem(skill));
    }
    gapEl.append(gapList);
  }

  // Add skill button handler
  document.getElementById("btn-add-skill").addEventListener("click", () => {
    // Safety: Remove any stuck overlays first
    document.querySelectorAll('.modal-overlay').forEach(el => el.remove());
    showAddSkillModal();
  });

  async function showAddSkillModal() {
    const catalog = await API.getSkillCatalog();
    const mySkills = await API.getMySkills();
    const mine = new Set(mySkills.map((s) => s.skill_id));
    const available = catalog.filter((s) => !mine.has(s.id));

    const modeExisting = el("input", { type: "radio", id: "mode-existing", name: "mode", value: "existing", checked: true });
    const modeNew = el("input", { type: "radio", id: "mode-new", name: "mode", value: "new" });

    // Search + skill picker
    const searchInput = el("input", {
      type: "text",
      id: "skill-search",
      placeholder: "Search skills...",
      style: "border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px; width: 100%; font-size: 14px",
    });

    const pickerList = el("div", { class: "skill-picker-list", role: "listbox", "aria-label": "Skills" });
    let selectedSkillId = null;

    function renderSkillOptions(skills) {
      UI.clear(pickerList);
      if (skills.length === 0) {
        pickerList.append(el("p", { style: "text-align: center; color: var(--text-muted); padding: 20px; font-size: 14px", text: "No skills found" }));
        return;
      }
      skills.forEach((s) => {
        const option = el("div", {
          class: "skill-option",
          "data-skill-id": s.id,
          role: "option",
          "aria-selected": "false",
          tabindex: "0",
          onClick: () => selectSkill(s.id),
          onKeydown: (event) => {
            if (event.key === "Enter" || event.key === " ") {
              event.preventDefault();
              selectSkill(s.id);
            }
          },
        }, [
          skillAvatar(s, { size: 40 }),
          el("div", { class: "skill-option-text" }, [
            el("p", { class: "skill-option-name", text: s.name }),
            el("p", { class: "skill-option-category", text: s.category }),
          ]),
          el("div", { class: "skill-option-radio" }),
        ]);
        pickerList.append(option);
      });
    }

    function selectSkill(id) {
      selectedSkillId = id;
      pickerList.querySelectorAll(".skill-option").forEach((opt) => {
        const isSelected = Number(opt.dataset.skillId) === id;
        opt.classList.toggle("selected", isSelected);
        opt.setAttribute("aria-selected", String(isSelected));
      });
      errorEl.style.display = "none";
    }

    searchInput.addEventListener("input", (e) => {
      const query = e.target.value.toLowerCase();
      const filtered = available.filter((s) =>
        s.name.toLowerCase().includes(query) || s.category.toLowerCase().includes(query)
      );
      renderSkillOptions(filtered);
    });

    renderSkillOptions(available);

    const selectField = el("div", { class: "field", id: "field-select" }, [
      el("label", { text: "Select skill from catalog" }),
      searchInput,
      pickerList,
    ]);

    const newFields = el("div", { id: "field-new", style: "display: none; gap: 16px" }, [
      el("div", { class: "field" }, [
        el("label", { for: "skill-name", text: "Skill name" }),
        el("input", { type: "text", id: "skill-name", placeholder: "e.g. Laravel" }),
      ]),
      el("div", { class: "field" }, [
        el("label", { for: "skill-category", text: "Category" }),
        el("select", { id: "skill-category" }, [
          el("option", { value: "Programming", text: "Programming" }),
          el("option", { value: "Database", text: "Database" }),
          el("option", { value: "Web", text: "Web" }),
          el("option", { value: "Tools", text: "Tools" }),
          el("option", { value: "Data", text: "Data" }),
          el("option", { value: "Design", text: "Design" }),
        ]),
      ]),
    ]);

    const errorEl = el("p", { class: "field-error", style: "display: none" });

    function toggleMode() {
      const mode = document.querySelector('input[name="mode"]:checked').value;
      selectField.style.display = mode === "existing" ? "" : "none";
      newFields.style.display = mode === "new" ? "grid" : "none";
      errorEl.style.display = "none";
      selectedSkillId = null;
      pickerList.querySelectorAll(".skill-option").forEach((opt) => opt.classList.remove("selected"));
    }

    modeExisting.addEventListener("change", toggleMode);
    modeNew.addEventListener("change", toggleMode);

    UI.modal({
      title: "Add skill",
      body: [
        el("div", { style: "display: flex; gap: 20px; margin-bottom: 8px" }, [
          el("label", { style: "display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 600" }, [modeExisting, "From catalog"]),
          el("label", { style: "display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 600" }, [modeNew, "New skill"]),
        ]),
        selectField,
        newFields,
        errorEl,
      ],
      submitLabel: "Add",
      onSubmit: async (close, form) => {
        errorEl.style.display = "none";
        const mode = form.querySelector('input[name="mode"]:checked').value;

        if (mode === "existing") {
          if (!selectedSkillId) {
            errorEl.textContent = "Please select a skill.";
            errorEl.style.display = "block";
            return false;
          }
          await API.addSkill(selectedSkillId);
        } else {
          const name = form.querySelector("#skill-name").value.trim();
          const category = form.querySelector("#skill-category").value;
          if (!name) {
            errorEl.textContent = "Skill name required.";
            errorEl.style.display = "block";
            return false;
          }
          const allSkills = await API.getSkillCatalog();
          const dup = allSkills.find((s) => s.name.toLowerCase() === name.toLowerCase());
          if (dup) {
            errorEl.textContent = `"${name}" already exists in catalog.`;
            errorEl.style.display = "block";
            return false;
          }
          const skill = await API.addCustomSkill(name, category);
          await API.addSkill(skill.id);
        }

        UI.toast("Skill added");
        setTimeout(() => window.location.reload(), 300);
        return true;
      },
    });
  }

  // Render skill row: icon + name + count + badge + edit button
  function renderSkillRow(skill, evidenceCount, label) {
    const badgeClass = label === "Strong" ? "badge-strong" : "badge-growing";

    const row = el("div", {
      style: "display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); cursor: pointer; transition: all 0.2s",
      onMouseOver: (e) => e.currentTarget.style.background = "var(--tint-gray)",
      onMouseOut: (e) => e.currentTarget.style.background = "var(--surface)"
    }, [
      skillAvatar(skill, { size: 44 }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 16px; font-weight: 700; color: var(--text-primary)", text: skill.name }),
        el("p", { style: "font-size: 13px; color: var(--text-muted); margin-top: 2px", text: `${evidenceCount} evidence ${evidenceCount === 1 ? "item" : "items"}` }),
      ]),
      el("span", { class: `badge ${badgeClass}`, style: "font-size: 12px; padding: 5px 10px", text: label }),
      el("button", {
        type: "button",
        class: "btn-icon-edit",
        "aria-label": "Edit skill",
        style: "padding: 6px; border: none; background: transparent; cursor: pointer; border-radius: 6px; transition: background 0.2s",
        innerHTML: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11.333 2A1.886 1.886 0 0 1 14 4.667l-9 9-3.667.666.667-3.666 9-9z"/></svg>`,
        onClick: (e) => {
          e.stopPropagation();
          showEditSkillModal(skill);
        }
      })
    ]);

    row.addEventListener("click", () => showEditSkillModal(skill));

    return row;
  }

  // Render gap item: icon + skill name + action hint
  function renderGapItem(skill) {
    const row = el("div", {
      style: "display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s",
      onMouseOver: (e) => e.currentTarget.style.background = "var(--tint-gray)",
      onMouseOut: (e) => e.currentTarget.style.background = "var(--surface)"
    }, [
      skillAvatar(skill, { size: 38, variant: "accent" }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 15px; font-weight: 700; color: var(--text-primary)", text: skill.name }),
        el("p", { style: "font-size: 12px; color: var(--text-muted); margin-top: 2px", text: "Click to add" }),
      ]),
    ]);

    row.addEventListener("click", async () => {
      try {
        await API.addSkill(skill.id);
        UI.toast("Skill added from gap list!");
        setTimeout(() => window.location.reload(), 300);
      } catch (err) {
        UI.toast(`Error: ${err.message}`, "error");
      }
    });

    return row;
  }

  // Modal Detail Keterampilan & Bukti Pendukung (Triangulasi Kompetensi)
  async function showEditSkillModal(skill) {
    let mySkills = [];
    let allEvidence = [];
    try {
      [mySkills, allEvidence] = await Promise.all([
        API.getMySkills(),
        API.getEvidence()
      ]);
    } catch {
      mySkills = [];
      allEvidence = [];
    }

    // Ambil detail keahlian aktif beserta bukti pendukung yang sudah ditautkan
    const currentSkill = mySkills.find((s) => s.skill_id === skill.id) || {
      label: "Growing",
      evidence_count: 0,
      distinct_source_count: 0,
      practical_count: 0,
      linked_evidence: []
    };

    let linkedEv = currentSkill.linked_evidence || [];
    if (linkedEv.length === 0 && allEvidence.length > 0) {
      linkedEv = allEvidence
        .filter((ev) => ev.skill_ids && ev.skill_ids.includes(skill.id))
        .map((ev) => ({
          id: ev.id,
          title: ev.title,
          source_type: ev.source_type,
          file_url: ev.file_url,
          level: ev.metadata?.level || "intermediate"
        }));
    }

    const isStrong = currentSkill.label === "Strong";
    const count = linkedEv.length;

    // Badge status
    const statusBadgeClass = isStrong ? "badge-strong" : "badge-growing";

    // Header info modal
    const headerInfo = el("div", {
      style: "display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border);"
    }, [
      el("div", { style: "display: flex; align-items: center; gap: 12px" }, [
        skillAvatar(skill, { size: 42 }),
        el("div", {}, [
          el("h3", { style: "font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0", text: skill.name }),
          el("span", { style: "font-size: 12px; color: var(--text-muted)", text: `Kategori: ${skill.category || "General"}` })
        ])
      ]),
      el("span", {
        class: `badge ${statusBadgeClass}`,
        style: "font-size: 12px; padding: 5px 12px; font-weight: 700;",
        text: currentSkill.label
      })
    ]);

    // Kotak Penjelasan Syarat Triangulasi
    let explanationText = "";
    if (isStrong) {
      explanationText = "Keahlian ini telah terverifikasi Strong melalui triangulasi bukti kompetensi yang beragam (didukung minimal 2 bukti dari kategori sumber berbeda atau karya nyata).";
    } else {
      if (count === 0) {
        explanationText = "Status masih Growing. Belum ada bukti yang ditautkan ke keahlian ini. Tambahkan minimal 2 bukti dari minimal 2 kategori sumber berbeda (seperti CV + Proyek/GitHub atau Sertifikat) melalui halaman Evidence agar naik menjadi Strong.";
      } else if (count === 1) {
        const firstType = linkedEv[0]?.source_type?.toUpperCase() || "CV";
        explanationText = `Status masih Growing. Saat ini baru didukung oleh 1 bukti (${firstType}). Tambahkan minimal 1 bukti dari sumber berbeda (seperti Proyek/GitHub atau Sertifikat) melalui halaman Evidence agar status keahlian naik menjadi Strong.`;
      } else {
        explanationText = "Status masih Growing. Keahlian ini memiliki bukti namun belum memenuhi triangulasi minimal 2 jenis sumber berbeda atau proyek nyata. Tambahkan bukti proyek/kode untuk mencapai status Strong.";
      }
    }

    const statusCard = el("div", {
      style: `padding: 14px 16px; border-radius: 10px; font-size: 13px; line-height: 1.5; ${
        isStrong
          ? "background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;"
          : "background: #fefce8; border: 1px solid #fef08a; color: #854d0e;"
      }`
    }, [
      el("div", { style: "font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px" }, [
        el("span", { text: isStrong ? "✓ Bukti Terverifikasi Kuat (Strong)" : "ℹ Syarat Menuju Status Strong" })
      ]),
      el("p", { style: "margin: 0", text: explanationText })
    ]);

    // Daftar Bukti Pendukung Terkait (Linked Evidence)
    const typeLabels = {
      cv: "CV",
      project: "Proyek",
      github: "GitHub",
      portfolio: "Portofolio",
      certificate: "Sertifikat",
      assessment: "Asesmen"
    };

    const evidenceContainer = el("div", { style: "display: grid; gap: 10px;" });

    if (linkedEv.length === 0) {
      evidenceContainer.append(
        el("div", {
          style: "padding: 24px; text-align: center; border: 1px dashed var(--border); border-radius: 10px; color: var(--text-muted); font-size: 13px;"
        }, [
          el("p", { style: "margin: 0 0 6px; font-weight: 600;", text: "Belum ada bukti pendukung yang terhubung." }),
          el("p", { style: "margin: 0; font-size: 12px;", text: "Klik tombol di bawah untuk menambahkan bukti baru di halaman Evidence." })
        ])
      );
    } else {
      linkedEv.forEach((ev) => {
        const typeLabel = typeLabels[ev.source_type] || (ev.source_type ? ev.source_type.toUpperCase() : "BUKTI");
        const levelText = ev.level ? ev.level.charAt(0).toUpperCase() + ev.level.slice(1) : "Menengah";

        const rowCard = el("div", {
          style: "display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); width: 100%; box-sizing: border-box;"
        }, [
          el("div", { style: "display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;" }, [
            el("span", {
              style: "display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: var(--tint-blue, #eef2ff); color: var(--primary, #4338ca); flex-shrink: 0;",
              text: typeLabel
            }),
            el("span", {
              style: "font-size: 14px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;",
              text: ev.title
            })
          ]),
          el("div", { style: "display: flex; align-items: center; gap: 8px; flex-shrink: 0;" }, [
            el("span", {
              style: "font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; background: var(--tint-gray); color: var(--text-muted);",
              text: levelText
            }),
            ev.file_url ? el("a", {
              href: ev.file_url,
              target: "_blank",
              rel: "noopener",
              style: "font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 600;",
              text: "Lihat ↗"
            }) : null
          ])
        ]);

        evidenceContainer.append(rowCard);
      });
    }

    const evidenceSection = el("div", {}, [
      el("div", { style: "display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;" }, [
        el("label", { style: "font-size: 14px; font-weight: 700; color: var(--text-primary);", text: "Bukti Pendukung Terkait" }),
        el("span", { style: "font-size: 12px; color: var(--text-muted); font-weight: 600;", text: `${linkedEv.length} bukti` })
      ]),
      evidenceContainer
    ]);

    UI.modal({
      title: "Detail Keterampilan & Bukti Pendukung",
      body: [
        headerInfo,
        statusCard,
        evidenceSection
      ],
      footer: (close) => {
        const deleteBtn = el("button", {
          type: "button",
          class: "btn btn-danger",
          id: "btn-delete-skill",
          style: "margin-right: auto",
          text: "Hapus Skill",
          onClick: async () => {
            if (!confirm(`Hapus "${skill.name}" dari daftar keahlian Anda?`)) return;
            try {
              await API.removeMySkill(skill.id);
              UI.toast(`Keahlian "${skill.name}" berhasil dihapus.`);
              close();
              setTimeout(() => window.location.reload(), 300);
            } catch (err) {
              UI.toast(`Gagal menghapus skill: ${err.message}`, "error");
            }
          }
        });

        const addEvidenceBtn = el("a", {
          href: `evidence.php?skill_id=${skill.id}`,
          class: "btn btn-primary",
          style: "text-decoration: none; display: inline-flex; align-items: center; font-size: 13px;",
          text: "+ Tambah Bukti di Halaman Evidence"
        });

        const closeBtn = el("button", {
          type: "button",
          class: "btn btn-secondary",
          text: "Tutup",
          onClick: () => close()
        });

        return [deleteBtn, addEvidenceBtn, closeBtn];
      }
    });
  }
})();
