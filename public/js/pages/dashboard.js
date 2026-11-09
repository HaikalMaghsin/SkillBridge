// dashboard.js - Dynamic dashboard with HR pipeline notifications

(async () => {
  const { el } = UI;

  // Fetch semua data secara paralel
  const [careers, catalog, gap, evidence, mine, targetId, pipelines] = await Promise.all([
    API.getCareers(),
    API.getSkillCatalog(),
    API.getSkillGap(),
    API.getEvidence(),
    API.getMySkills(),
    API.getTargetCareer(),
    fetchPipelineStatus()
  ]);

  const target = careers.find((c) => c.id === targetId) || careers[0];

  // ============ HR Pipeline Notifications ============
  if (pipelines.length > 0) {
    renderPipelineNotifications(pipelines);
  }

  // ============ Card: Target Career ============
  const cardTargetEl = document.getElementById("card-target");
  if (target) {
    cardTargetEl.append(
      el("p", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: target.name }),
      el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: target.description })
    );
  } else {
    cardTargetEl.append(
      el("p", { style: "font-size: 20px; font-weight: 600; line-height: 1.2; color: var(--text-primary)", text: "No target set" }),
      el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: "Choose a target career to start mapping your skills." })
    );
  }

  // ============ Card: Skills Summary ============
  const strongCount = mine.filter((s) => s.label === "Strong").length;
  const growingCount = mine.filter((s) => s.label === "Growing").length;
  const exploringCount = mine.filter((s) => s.label === "Exploring").length;
  const mySkillNames = mine
    .map((s) => catalog.find((c) => c.id === s.skill_id)?.name)
    .filter(Boolean)
    .slice(0, 5);

  // Build dynamic description based on real counts
  let skillDesc = "";
  if (mine.length === 0) {
    skillDesc = "You haven't added any skills yet. Start by exploring your target career requirements.";
  } else {
    const parts = [];
    if (strongCount > 0) parts.push(`${strongCount} strong`);
    if (growingCount > 0) parts.push(`${growingCount} growing`);
    if (exploringCount > 0) parts.push(`${exploringCount} exploring`);
    skillDesc = `You have ${parts.join(", ")} skill${mine.length !== 1 ? "s" : ""}.`;
  }

  document.getElementById("card-skills").append(
    el("p", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: `${mine.length} skills` }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: skillDesc }),
    mySkillNames.length > 0
      ? el("div", { style: "display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px" },
          mySkillNames.map((name) => {
            const chip = el("div", { class: "tag-chip", style: "display: inline-flex; align-items: center; gap: 7px; background: var(--tint-gray); border: 1px solid var(--border); padding: 8px 12px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 700; color: var(--primary-dark)" });
            chip.append(
              el("span", { style: "display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--primary)" }),
              document.createTextNode(name)
            );
            return chip;
          }))
      : null
  );

  // ============ Card: Skill Gap ============
  const exploreNames = gap.explore.map((s) => s.name).slice(0, 3).join(", ");

  let gapDesc = "";
  if (gap.explore.length === 0 && gap.have.length > 0) {
    gapDesc = "All required skills covered! Focus on building more evidence to strengthen your portfolio.";
  } else if (gap.explore.length === 0 && gap.have.length === 0) {
    gapDesc = "Set a target career to see your skill gap analysis.";
  } else {
    gapDesc = `Focus on ${exploreNames || "your next goals"} this semester.`;
    if (strongCount > 0) {
      gapDesc += ` You already have ${strongCount} strong skill${strongCount !== 1 ? "s" : ""} in place.`;
    }
  }

  document.getElementById("card-explore").append(
    el("p", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: `${gap.explore.length} to develop` }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: gapDesc })
  );

  // ============ Card: Evidence Summary ============
  const recent = evidence.slice(-3).reverse();
  const evCardEl = document.getElementById("card-evidence");
  evCardEl.append(
    el("p", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: `${evidence.length} items` }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: "Projects, certificates, and links are all in one place so you can review them quickly." })
  );

  if (recent.length > 0) {
    const listEl = el("div", { style: "display: grid; gap: 12px; margin-top: 18px" });
    for (const ev of recent) {
      listEl.append(renderEvidenceItem(ev));
    }
    evCardEl.append(listEl);
  }

  // ============ Helper Functions ============

  async function fetchPipelineStatus() {
    try {
      const response = await fetch(`${window.APP_BASE_URL}/api/pipeline-status.php`);
      if (!response.ok) return [];
      return await response.json();
    } catch (err) {
      console.error('Error fetching pipeline status:', err);
      return [];
    }
  }

  function renderPipelineNotifications(pipelines) {
    const container = document.getElementById("pipeline-notifications");
    container.style.display = "block";

    // Status config: icon, color, bg, label
    const statusConfig = {
      'New': {
        icon: `<path d="M10 4l2 2-2 2M6 8l-2 2 2 2M8 4l-1 8"/>`,
        color: "var(--primary)",
        bg: "var(--tint-blue)",
        label: "Minat Tercatat"
      },
      'Shortlisted': {
        icon: `<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>`,
        color: "var(--success, #16a34a)",
        bg: "var(--tint-green, #f0fdf4)",
        label: "Di-shortlist"
      },
      'Interview': {
        icon: `<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>`,
        color: "var(--warning, #ca8a04)",
        bg: "var(--tint-yellow, #fefce8)",
        label: "Undangan Interview"
      },
      'Accepted': {
        icon: `<circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/>`,
        color: "var(--success, #16a34a)",
        bg: "var(--tint-green, #f0fdf4)",
        label: "Diterima"
      },
      'Rejected': {
        icon: `<circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/>`,
        color: "var(--danger, #dc2626)",
        bg: "var(--tint-red, #fef2f2)",
        label: "Tidak Lolos"
      }
    };

    const section = el("div", {
      style: "margin-bottom: 24px"
    }, [
      el("div", { style: "display: flex; align-items: center; gap: 10px; margin-bottom: 16px" }, [
        el("svg", {
          width: "20",
          height: "20",
          viewBox: "0 0 24 24",
          fill: "none",
          stroke: "var(--primary)",
          "stroke-width": "2",
          "stroke-linecap": "round",
          "stroke-linejoin": "round",
          innerHTML: `<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>`
        }),
        el("h2", { style: "font-size: 18px; font-weight: 700; color: var(--text-primary)", text: "Status Lamaran & Pipeline HR" })
      ])
    ]);

    const grid = el("div", { style: "display: grid; gap: 12px" });

    for (const p of pipelines) {
      const config = statusConfig[p.status] || statusConfig['New'];

      const card = el("div", {
        style: `background: var(--surface); border: 1px solid var(--border-light); border-radius: 12px; padding: 16px; display: flex; gap: 14px; align-items: start`
      }, [
        // Status icon
        el("div", {
          style: `width: 40px; height: 40px; border-radius: 10px; background: ${config.bg}; display: grid; place-items: center; flex-shrink: 0`,
          innerHTML: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${config.color}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${config.icon}</svg>`
        }),
        // Content
        el("div", { style: "flex: 1; min-width: 0" }, [
          el("div", { style: "display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px" }, [
            el("span", {
              style: `font-size: 12px; font-weight: 600; color: ${config.color}; background: ${config.bg}; padding: 3px 10px; border-radius: 20px`,
              text: config.label
            }),
            el("span", {
              style: "font-size: 12px; color: var(--text-muted)",
              text: formatRelativeTime(p.updated_at)
            })
          ]),
          el("p", {
            style: "font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px",
            text: p.vacancy_title || "General Pipeline"
          }),
          el("p", {
            style: "font-size: 13px; color: var(--text-secondary)",
            text: `${p.company_name}${p.industry ? ' · ' + p.industry : ''}`
          })
        ])
      ]);

      grid.append(card);
    }

    section.append(grid);
    container.append(section);
  }

  function formatRelativeTime(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMin < 1) return "baru saja";
    if (diffMin < 60) return `${diffMin} menit lalu`;
    if (diffHours < 24) return `${diffHours} jam lalu`;
    if (diffDays < 7) return `${diffDays} hari lalu`;
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function renderEvidenceItem(ev) {
    const iconMap = {
      project: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><path d="M6.375 2.125h4.25M4.25 14.167h8.5a1.417 1.417 0 0 0 1.417-1.417V4.25a1.417 1.417 0 0 0-1.417-1.417h-8.5A1.417 1.417 0 0 0 2.833 4.25v8.5c0 .782.635 1.417 1.417 1.417z"/></svg>`,
      certificate: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><circle cx="8.5" cy="5.667" r="4.25"/><path d="M5.667 12.042v2.833l2.833-1.417 2.833 1.417v-2.833"/></svg>`,
      portfolio: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><circle cx="8.5" cy="8.5" r="7.084"/><path d="M1.416 9.917h14.168M8.5 8.5c1.566 0 2.834-3.175 2.834-7.084M8.5 8.5c-1.566 0-2.834-3.175-2.834-7.084"/></svg>`,
      other: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><path d="M9.917 1.416H4.25a1.417 1.417 0 0 0-1.417 1.417v11.334c0 .782.635 1.416 1.417 1.416h8.5c.782 0 1.417-.634 1.417-1.416V5.667L9.917 1.416z"/><path d="M9.917 1.416v4.25h4.25M10.625 9.208H6.375M10.625 11.333H6.375M7.792 7.083H6.375"/></svg>`,
    };
    const icon = iconMap[ev.source_type] || iconMap.other;

    return el("div", { style: "display: flex; gap: 13px; padding: 11px 0; border-bottom: 1px solid var(--border); opacity: 0.9; align-items: center" }, [
      el("div", { style: "width: 38px; height: 38px; border-radius: 10px; background: var(--tint-gray); display: grid; place-items: center; flex-shrink: 0", innerHTML: icon }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 14px; font-weight: 700; color: var(--text-primary)", text: ev.title }),
        el("p", { style: "font-size: 11px; color: var(--text-muted)", text: `${ev.source_type} · ${UI.formatDate(ev.metadata?.date)}` }),
      ]),
      el("span", { style: "background: var(--tint-pink); color: var(--accent); font-size: 11px; font-weight: 700; padding: 5px 10px; border-radius: var(--radius-round); opacity: 0.9", text: "Added" }),
    ]);
  }
})();
