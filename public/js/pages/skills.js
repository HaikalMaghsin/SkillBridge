// skills.js - My Skills page

(async () => {
  const { el } = UI;
  const [careers, catalog, gap, evidence] = await Promise.all([
    API.getCareers(),
    API.getSkillCatalog(),
    API.getSkillGap(),
    API.getEvidence(),
  ]);

  const mine = API.getMySkills();
  const targetId = API.getTargetId();
  const target = careers.find((c) => c.id === targetId) || careers[0];

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
    UI.toast("Feature coming soon: Add new skill");
  });

  // Render skill row: icon + name + count + badge
  function renderSkillRow(skill, evidenceCount, label) {
    const initials = skill.name.split(" ").map((w) => w[0].toUpperCase()).join("").slice(0, 2);
    const badgeClass = label === "Strong" ? "badge-strong" : "badge-growing";

    return el("div", { style: "display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm)" }, [
      el("div", { style: "width: 44px; height: 44px; border-radius: 10px; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 14px; font-weight: 800; flex-shrink: 0", text: initials }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 16px; font-weight: 700; color: var(--text-primary)", text: skill.name }),
        el("p", { style: "font-size: 13px; color: var(--text-muted); margin-top: 2px", text: `${evidenceCount} evidence ${evidenceCount === 1 ? "item" : "items"}` }),
      ]),
      el("span", { class: `badge ${badgeClass}`, style: "font-size: 12px; padding: 5px 10px", text: label }),
    ]);
  }

  // Render gap item: initials + skill name + action hint
  function renderGapItem(skill) {
    const initials = skill.name.split(" ").map((w) => w[0].toUpperCase()).join("").slice(0, 2);
    return el("div", { style: "display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm)" }, [
      el("div", { style: "width: 38px; height: 38px; border-radius: 8px; background: var(--tint-pink); color: var(--accent); display: grid; place-items: center; font-size: 13px; font-weight: 800; flex-shrink: 0", text: initials }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 15px; font-weight: 700; color: var(--text-primary)", text: skill.name }),
        el("p", { style: "font-size: 12px; color: var(--text-muted); margin-top: 2px", text: "Add 1 case study" }),
      ]),
    ]);
  }
})();
