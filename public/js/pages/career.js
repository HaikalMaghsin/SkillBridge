// career.js - Career Target page

(async () => {
  const { el } = UI;
  const [careers, catalog] = await Promise.all([API.getCareers(), API.getSkillCatalog()]);

  const mine = new Set(API.getMySkills().map((s) => s.skill_id));
  const targetId = API.getTargetId();
  const target = careers.find((c) => c.id === targetId) || careers[0];

  // Banner
  const matched = countMatched(target, mine, catalog);
  document.getElementById("banner-text").textContent = `${target.name} is your current target. Required skills below are a practical starter list for this project.`;

  // Grid
  const gridEl = document.getElementById("career-grid");
  for (const career of careers) {
    const isSelected = career.id === target.id;
    gridEl.append(renderCareerCard(career, isSelected, mine, catalog));
  }

  function renderCareerCard(career, isSelected, mine, catalog) {
    const iconMap = {
      "Backend Developer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="16" height="14" rx="2"/><path d="M6 8l2 2-2 2M10 12h4"/></svg>`,
      "Frontend Developer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="16" height="14" rx="2"/><path d="M2 7h16"/></svg>`,
      "Data Analyst": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M3 16v-6M10 16V8M17 16v-3"/><circle cx="3" cy="3" r="1.5"/><circle cx="10" cy="3" r="1.5"/><circle cx="17" cy="3" r="1.5"/></svg>`,
      "UI/UX Designer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="10" cy="10" r="3"/><path d="M10 2v2M10 16v2M18 10h-2M4 10H2M15.5 4.5l-1.4 1.4M5.9 14.1l-1.4 1.4M15.5 15.5l-1.4-1.4M5.9 5.9L4.5 4.5"/></svg>`,
    };
    const icon = iconMap[career.name] || iconMap["Backend Developer"];

    const card = el("article", { class: isSelected ? "career-card is-selected" : "career-card" });

    // Head: icon + title + description
    const head = el("div", { class: "career-card-head" }, [
      el("div", { class: "career-card-icon", innerHTML: icon }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("h3", { style: "font-size: 18px; font-weight: 700; line-height: 1.2; color: var(--text-primary)", text: career.name }),
      ]),
    ]);
    card.append(head);

    // Description
    card.append(el("p", { style: "font-size: 14px; line-height: 1.5; color: var(--text-muted)", text: career.description }));

    // Required skills
    const skillNames = career.skills.map((name) => catalog.find((s) => s.name === name)).filter(Boolean);
    const matched = skillNames.filter((s) => mine.has(s.id)).length;

    card.append(
      el("div", { style: "display: grid; gap: 10px" }, [
        el("p", { style: "font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.9px; color: var(--text-light)", text: "Required skills" }),
        el("div", { style: "display: flex; flex-wrap: wrap; gap: 7px" }, skillNames.map((s) => renderSkillChip(s, mine.has(s.id)))),
      ])
    );

    if (isSelected) {
      card.append(el("p", { style: "font-size: 13px; color: var(--text-muted); margin-top: 4px", text: `${matched} of ${skillNames.length} skills covered` }));
      card.append(
        el("button", {
          class: "btn btn-accent",
          style: "width: fit-content; gap: 7px; margin-top: 4px",
          text: "Set as Target",
          disabled: true,
        }, [
          el("svg", { width: "16", height: "16", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<circle cx="8" cy="8" r="6"/><circle cx="8" cy="8" r="2"/>` }),
          document.createTextNode("Set as Target"),
        ])
      );
    } else {
      card.append(
        el("div", { style: "display: flex; justify-content: space-between; align-items: center; margin-top: 6px" }, [
          el("p", { style: "font-size: 13px; color: var(--text-muted)", text: "Review this path" }),
          el("button", {
            class: "btn btn-primary btn-sm",
            style: "gap: 6px",
            onClick: () => {
              API.setTarget(career.id);
              UI.toast(`Target set to ${career.name}`);
              setTimeout(() => location.reload(), 800);
            },
          }, [
            el("svg", { width: "14", height: "14", viewBox: "0 0 14 14", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<circle cx="7" cy="7" r="5.5"/><circle cx="7" cy="7" r="1.8"/>` }),
            document.createTextNode("Set as Target"),
          ]),
        ])
      );
    }

    return card;
  }

  function renderSkillChip(skill, owned) {
    const color = owned ? "var(--accent)" : "var(--primary)";
    const bg = owned ? "var(--tint-pink)" : "var(--tint-indigo)";
    return el("span", {
      style: `display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 6px; font-size: 13px; font-weight: 600; background: ${bg}; color: ${color}`,
    }, [
      el("span", { style: `display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: ${color}` }),
      document.createTextNode(skill.name),
    ]);
  }

  function countMatched(career, mine, catalog) {
    const skillNames = career.skills.map((name) => catalog.find((s) => s.name === name)).filter(Boolean);
    return skillNames.filter((s) => mine.has(s.id)).length;
  }
})();
