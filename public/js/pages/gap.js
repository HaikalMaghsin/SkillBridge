// gap.js - Skill Gap page

(async () => {
  const { el } = UI;
  const gap = await API.getSkillGap();

  if (!gap.career) {
    document.body.innerHTML = `<main class="container"><p style="text-align: center; margin-top: 100px; color: var(--text-muted)">No target career set. <a href="career.html" style="color: var(--primary); font-weight: 600">Choose one</a>.</p></main>`;
    return;
  }

  // Target info
  document.getElementById("target-info").append(
    el("h2", { style: "font-size: 32px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: gap.career.name }),
    el("p", { style: "font-size: 15px; line-height: 1.5; color: var(--text-muted); margin-top: 8px", text: gap.career.description })
  );

  // Stat tiles
  const evidence = await API.getEvidence();
  const tilesEl = document.getElementById("stat-tiles");
  tilesEl.append(
    el("div", { class: "stat-tile" }, [
      el("p", { class: "stat-label", text: "Skills you already have" }),
      el("p", { class: "stat-value", text: String(gap.have.length) }),
    ]),
    el("div", { class: "stat-tile" }, [
      el("p", { class: "stat-label", text: "What to explore next" }),
      el("p", { class: "stat-value", text: String(gap.explore.length) }),
    ]),
    el("div", { class: "stat-tile" }, [
      el("p", { class: "stat-label", text: "Evidence" }),
      el("p", { class: "stat-value", text: String(evidence.length) }),
    ])
  );

  // Skills you have
  const haveEl = document.getElementById("have-list");
  if (gap.have.length === 0) {
    haveEl.append(el("p", { style: "color: var(--text-muted); font-size: 15px", text: "No matching skills added yet." }));
  } else {
    haveEl.append(el("div", { style: "display: grid; gap: 12px" }, gap.have.map((s) => renderSkillRow(s, s.evidence_count))));
  }

  // Explore next
  const exploreEl = document.getElementById("explore-list");
  if (gap.explore.length === 0) {
    exploreEl.append(el("p", { style: "color: var(--text-muted); font-size: 15px", text: "All required skills covered." }));
  } else {
    exploreEl.append(el("div", { style: "display: grid; gap: 12px" }, gap.explore.slice(0, 1).map((s) => renderExploreItem(s))));
  }

  // Next step box
  const nextStepEl = document.getElementById("next-step-box");
  if (gap.explore.length > 0) {
    const first = gap.explore[0];
    nextStepEl.append(
      el("h3", { style: "font-size: 16px; font-weight: 700; color: var(--text-primary)", text: "Simple next step" }),
      el("p", { style: "font-size: 14px; line-height: 1.5; color: var(--text-muted)", text: `Add ${first.name} to your next coursework project, then attach the finished project as evidence.` }),
      el("button", {
        class: "btn btn-accent",
        style: "width: fit-content; margin-top: 8px; gap: 7px",
        onClick: () => UI.toast("Feature coming soon"),
      }, [
        el("svg", { width: "16", height: "16", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "2", innerHTML: `<path d="M3.333 8h9.334M8 3.333v9.334" stroke-linecap="round"/>` }),
        document.createTextNode(`Add ${first.name}`),
      ])
    );
  } else {
    nextStepEl.append(
      el("h3", { style: "font-size: 16px; font-weight: 700; color: var(--text-primary)", text: "All covered" }),
      el("p", { style: "font-size: 14px; line-height: 1.5; color: var(--text-muted)", text: "You have all required skills for this target. Focus on building more evidence." })
    );
  }

  function renderSkillRow(skill, evidenceCount) {
    const initials = skill.name.split(" ").map((w) => w[0].toUpperCase()).join("").slice(0, 2);
    return el("div", { class: "skill-row" }, [
      el("div", { class: "skill-avatar", text: initials }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 15px; font-weight: 700; color: var(--text-primary)", text: skill.name }),
        el("p", { style: "font-size: 13px; color: var(--text-muted); margin-top: 2px", text: skill.category || "Programming language" }),
      ]),
      el("span", { class: "badge-info", text: `${evidenceCount} evidence` }),
    ]);
  }

  function renderExploreItem(skill) {
    const initials = skill.name.split(" ").map((w) => w[0].toUpperCase()).join("").slice(0, 2);
    return el("div", { style: "display: flex; align-items: center; gap: 12px; padding: 14px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm)" }, [
      el("div", { style: "width: 44px; height: 44px; border-radius: 11px; background: var(--tint-pink); color: var(--accent); display: grid; place-items: center; font-size: 13px; font-weight: 800; flex-shrink: 0", text: initials }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 15px; font-weight: 700; color: var(--text-primary)", text: skill.name }),
        el("p", { style: "font-size: 13px; color: var(--text-muted); margin-top: 2px", text: skill.category || "Backend web framework" }),
      ]),
      el("span", { class: "badge", style: "background: var(--tint-indigo); color: var(--primary); font-size: 11px; padding: 5px 9px", text: "Not added" }),
    ]);
  }
})();
