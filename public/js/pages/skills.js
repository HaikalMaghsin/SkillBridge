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
        console.log('onSubmit called!'); // DEBUG
        errorEl.style.display = "none";
        const mode = form.querySelector('input[name="mode"]:checked').value;
        console.log('Mode:', mode); // DEBUG

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

  // Modal Edit Skill
  async function showEditSkillModal(skill) {
    const allSkills = await API.getSkillCatalog();
    const allEvidence = await API.getEvidence();
    const mySkills = await API.getMySkills();

    // Find current user_skill entry
    const currentSkill = mySkills.find(s => s.skill_id === skill.id);

    // Get evidence linked to this skill
    const linkedEvidenceIds = new Set();
    allEvidence.forEach(ev => {
      if (ev.skill_ids && ev.skill_ids.includes(skill.id)) {
        linkedEvidenceIds.add(ev.id);
      }
    });

    let selectedSkillId = skill.id;
    let selectedEvidenceIds = new Set(linkedEvidenceIds);

    // Skill selector
    const skillSelect = el("select", {
      id: "edit-skill-select",
      style: "width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 14px"
    });

    allSkills.forEach(s => {
      const option = el("option", { value: s.id, text: s.name });
      if (s.id === skill.id) option.selected = true;
      skillSelect.appendChild(option);
    });

    skillSelect.addEventListener("change", (e) => {
      selectedSkillId = Number(e.target.value);
    });

    // Evidence checkboxes
    const evidenceList = el("div", { style: "display: grid; gap: 8px; max-height: 300px; overflow-y: auto" });

    if (allEvidence.length === 0) {
      evidenceList.append(el("p", { style: "color: var(--text-muted); font-size: 14px; padding: 12px; text-align: center", text: "No evidence yet. Add evidence first to link with skills." }));
    } else {
      allEvidence.forEach(ev => {
        const isLinked = linkedEvidenceIds.has(ev.id);
        const checkbox = el("input", { type: "checkbox", id: `ev-${ev.id}`, checked: isLinked });

        checkbox.addEventListener("change", (e) => {
          if (e.target.checked) {
            selectedEvidenceIds.add(ev.id);
          } else {
            selectedEvidenceIds.delete(ev.id);
          }
          updateStatusPreview();
        });

        const label = el("label", {
          for: `ev-${ev.id}`,
          style: "display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: background 0.2s",
          onMouseOver: (e) => e.currentTarget.style.background = "var(--tint-gray)",
          onMouseOut: (e) => e.currentTarget.style.background = "transparent"
        }, [
          checkbox,
          el("div", { style: "flex: 1" }, [
            el("p", { style: "font-size: 14px; font-weight: 600", text: ev.title }),
            el("p", { style: "font-size: 12px; color: var(--text-muted)", text: ev.source_type })
          ])
        ]);

        evidenceList.append(label);
      });
    }

    // Status preview
    const statusBadge = el("span", {
      id: "status-preview",
      class: "badge",
      style: "font-size: 13px; padding: 6px 12px"
    });

    function updateStatusPreview() {
      const count = selectedEvidenceIds.size;
      const isStrong = count >= 2;
      statusBadge.className = `badge ${isStrong ? "badge-strong" : "badge-growing"}`;
      statusBadge.textContent = isStrong ? "Strong" : "Growing";
    }

    updateStatusPreview();

    const errorEl = el("p", { class: "field-error", style: "display: none; color: var(--danger); font-size: 13px; margin-top: 8px" });

    UI.modal({
      title: "Edit Skill",
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "edit-skill-select", text: "Change Skill" }),
          skillSelect
        ]),
        el("div", { class: "field", style: "margin-top: 20px" }, [
          el("label", { text: "Linked Evidence" }),
          el("p", { style: "font-size: 13px; color: var(--text-muted); margin-bottom: 12px", text: "Select evidence that demonstrates this skill" }),
          evidenceList
        ]),
        el("div", { style: "margin-top: 16px; padding: 12px; background: var(--tint-gray); border-radius: var(--radius); display: flex; align-items: center; justify-content: space-between" }, [
          el("span", { style: "font-size: 14px; font-weight: 600", text: "Status Preview:" }),
          statusBadge
        ]),
        errorEl
      ],
      submitLabel: "Save Changes",
      cancelLabel: "Cancel",
      onSubmit: async (close, form) => {
        errorEl.style.display = "none";

        try {
          // If skill changed, remove old and add new
          if (selectedSkillId !== skill.id) {
            await API.removeSkill(skill.id);
            await API.addSkill(selectedSkillId);
          }

          // Update evidence links for the skill
          for (const ev of allEvidence) {
            const wasLinked = linkedEvidenceIds.has(ev.id);
            const isNowLinked = selectedEvidenceIds.has(ev.id);

            if (wasLinked !== isNowLinked) {
              // Rebuild skill_ids for this evidence
              const newSkillIds = [...(ev.skill_ids || [])];

              if (isNowLinked && !newSkillIds.includes(selectedSkillId)) {
                newSkillIds.push(selectedSkillId);
              } else if (!isNowLinked) {
                const idx = newSkillIds.indexOf(selectedSkillId);
                if (idx > -1) newSkillIds.splice(idx, 1);
              }

              await API.updateEvidenceSkills(ev.id, newSkillIds);
            }
          }

          UI.toast("Skill updated successfully!");
          setTimeout(() => window.location.reload(), 300);
          return true;

        } catch (err) {
          errorEl.textContent = `Error: ${err.message}`;
          errorEl.style.display = "block";
          return false;
        }
      }
    });

    // Add delete button to modal footer
    setTimeout(() => {
      const modalActions = document.querySelector('.modal-actions');
      if (modalActions) {
        const deleteBtn = el("button", {
          type: "button",
          class: "btn btn-danger",
          text: "Delete Skill",
          style: "margin-right: auto",
          onClick: async () => {
            if (!confirm(`Remove "${skill.name}" from your skills?`)) return;

            try {
              await API.removeSkill(skill.id);
              UI.toast("Skill removed");
              close();
              setTimeout(() => window.location.reload(), 300);
            } catch (err) {
              alert(`Error: ${err.message}`);
            }
          }
        });

        modalActions.insertBefore(deleteBtn, modalActions.firstChild);
      }
    }, 10);
  }
})();
