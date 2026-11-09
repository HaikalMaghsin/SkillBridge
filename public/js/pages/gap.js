// gap.js - Skill Gap page dengan Mode Adaptif per Perusahaan

(async () => {
  const { el } = UI;

  // Ikon per kategori
  const CATEGORY_ICONS = {
    Programming: `<path d="M8 6 3 12l5 6M16 6l5 6-5 6"/>`,
    Database: `<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/>`,
    Web: `<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/>`,
    Tools: `<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.1-2.1 2.7-2.5z"/>`,
    Data: `<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>`,
    Design: `<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>`,
  };
  const CATEGORY_ICON_FALLBACK = `<path d="M4 6h16M4 12h16M4 18h10"/>`;

  function categoryIconSvg(category) {
    const path = CATEGORY_ICONS[category] || CATEGORY_ICON_FALLBACK;
    return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
  }

  // Check for vacancy_id in URL
  const urlParams = new URLSearchParams(window.location.search);
  const vacancyId = urlParams.get('vacancy_id');

  // Fetch gap dengan atau tanpa vacancy_id
  const gapUrl = vacancyId
    ? `${window.APP_BASE_URL}/api/gap.php?vacancy_id=${vacancyId}`
    : `${window.APP_BASE_URL}/api/gap.php`;

  const response = await fetch(gapUrl);
  const gap = await response.json();

  if (!gap.career) {
    document.body.innerHTML = `<main class="container"><p style="text-align: center; margin-top: 100px; color: var(--text-muted)">No target career set. <a href="career.php" style="color: var(--primary); font-weight: 600">Choose one</a>.</p></main>`;
    return;
  }

  // Tampilkan context info jika mode vacancy
  const targetInfoEl = document.getElementById("target-info");
  if (gap.context && gap.context.mode === 'vacancy') {
    targetInfoEl.append(
      el("div", {
        style: "background: var(--tint-blue); border: 1px solid var(--primary); border-radius: 12px; padding: 16px; margin-bottom: 20px"
      }, [
        el("div", { style: "display: flex; align-items: center; gap: 8px; margin-bottom: 8px" }, [
          el("svg", {
            width: "18",
            height: "18",
            viewBox: "0 0 18 18",
            fill: "none",
            stroke: "var(--primary)",
            "stroke-width": "1.5",
            innerHTML: `<circle cx="9" cy="9" r="7.5"/><circle cx="9" cy="9" r="2"/>`
          }),
          el("span", {
            style: "font-size: 14px; font-weight: 600; color: var(--primary)",
            text: "Mode: Skill Gap Spesifik Perusahaan"
          })
        ]),
        el("p", {
          style: "font-size: 13px; color: var(--text-primary); line-height: 1.4",
          text: `${gap.context.vacancy_title} • ${gap.context.company_name} (${gap.context.industry})`
        }),
        el("a", {
          href: "gap.php",
          style: "font-size: 13px; color: var(--primary); font-weight: 500; margin-top: 8px; display: inline-block",
          text: "← Kembali ke standar umum karier"
        })
      ])
    );
  }

  // Target info
  targetInfoEl.append(
    el("h2", { style: "font-size: 30px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: gap.career.name }),
    el("p", { style: "font-size: 16px; line-height: 1.5; color: var(--text-muted); margin-top: 8px; opacity: 0.85", text: gap.career.description })
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
    haveEl.append(el("div", { style: "display: flex; flex-direction: column" }, gap.have.map((s) => renderSkillRow(s, s.evidence_count, "have"))));
  }

  // Explore next dengan rekomendasi project
  const exploreEl = document.getElementById("explore-list");
  if (gap.explore.length === 0) {
    exploreEl.append(el("p", { style: "color: var(--text-muted); font-size: 15px", text: "All required skills covered." }));
  } else {
    for (const skill of gap.explore) {
      exploreEl.append(renderSkillRowWithProject(skill));
    }
  }

  // Next step box
  const nextStepEl = document.getElementById("next-step-box");
  if (gap.explore.length > 0) {
    const first = gap.explore[0];
    const projectRec = getProjectRecommendation(first.name);

    nextStepEl.append(
      el("h3", { style: "font-size: 18px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: "Simple next step" }),
      el("p", { style: "font-size: 13px; line-height: 1.45; color: var(--text-primary); opacity: 0.85; margin-top: 8px", text: projectRec }),
      el("button", {
        class: "btn btn-accent",
        style: "width: fit-content; margin-top: 12px; gap: 7px",
        onClick: () => window.location.href = `${window.APP_BASE_URL}/mahasiswa/evidence.php`
      }, [
        el("svg", { width: "16", height: "16", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "2", innerHTML: `<path d="M3.333 8h9.334M8 3.333v9.334" stroke-linecap="round"/>` }),
        document.createTextNode("Go to Evidence"),
      ])
    );
  } else {
    nextStepEl.append(
      el("h3", { style: "font-size: 18px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: "All covered" }),
      el("p", { style: "font-size: 13px; line-height: 1.45; color: var(--text-primary); opacity: 0.85", text: "You have all required skills for this target. Focus on building more evidence." })
    );
  }

  function getProjectRecommendation(skillName) {
    const recommendations = {
      'Laravel': 'Buat 1 project CRUD Laravel dengan autentikasi (login/register) + REST API, hosting di GitHub, lalu unggah ke Evidence.',
      'Node.js': 'Buat REST API sederhana dengan Node.js + Express untuk manage data (CRUD), hosting di GitHub.',
      'React': 'Buat aplikasi web interaktif dengan React (misal: Todo App atau Dashboard), hosting di GitHub.',
      'Python': 'Buat script Python untuk data processing atau automation task, hosting di GitHub.',
      'Docker': 'Containerize salah satu project PHP/Node.js Anda dengan Dockerfile, push image ke Docker Hub.',
      'Git': 'Dokumentasikan workflow Git Anda dengan commit history yang rapi di salah satu repository GitHub.',
      'PostgreSQL': 'Buat database schema untuk sistem tertentu (e-commerce, blog), export SQL, dokumentasikan di GitHub.',
      'REST APIs': 'Buat dokumentasi API dengan Postman Collection untuk salah satu project backend Anda.',
      'Figma': 'Design UI mockup untuk aplikasi web (minimal 5 screen), export dan upload ke Evidence.'
    };

    return recommendations[skillName] || `Buat 1 project dummy yang menggunakan ${skillName}, dokumentasikan di GitHub, lalu unggah ke halaman Evidence.`;
  }

  function renderSkillRowWithProject(skill) {
    const avatarContent = el("div", {
      style: "width: 38px; height: 38px; border-radius: 10px; background: var(--primary-dark); color: #fff; display: grid; place-items: center; flex-shrink: 0",
      innerHTML: categoryIconSvg(skill.category),
    });

    const projectRec = getProjectRecommendation(skill.name);

    return el("div", {
      class: "skill-row",
      style: "flex-direction: column; align-items: start; padding: 16px"
    }, [
      el("div", { style: "display: flex; gap: 12px; width: 100%; margin-bottom: 8px" }, [
        avatarContent,
        el("div", { style: "flex: 1; min-width: 0" }, [
          el("p", { style: "font-size: 15px; font-weight: 700; line-height: 1.2; color: var(--text-primary)", text: skill.name }),
          el("p", { style: "font-size: 12px; color: var(--text-muted); margin-top: 4px; opacity: 0.85", text: skill.category }),
        ]),
        el("span", { class: "badge-not-added", text: "Not added" })
      ]),
      el("div", {
        style: "background: var(--surface-secondary); border-radius: 8px; padding: 10px 12px; width: 100%; font-size: 12px; color: var(--text-secondary); line-height: 1.4"
      }, [
        el("strong", { text: "💡 Project dummy: ", style: "color: var(--text-primary)" }),
        document.createTextNode(projectRec)
      ])
    ]);
  }

  function renderSkillRow(skill, evidenceCount, type) {
    const avatarContent = el("div", {
      style: "width: 38px; height: 38px; border-radius: 10px; background: var(--primary-dark); color: #fff; display: grid; place-items: center; flex-shrink: 0",
      innerHTML: categoryIconSvg(skill.category),
    });

    const badge = type === "have"
      ? el("span", { class: "badge-info", text: `${evidenceCount} evidence` })
      : el("span", { class: "badge-not-added", text: "Not added" });

    return el("div", { class: "skill-row" }, [
      avatarContent,
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 15px; font-weight: 700; line-height: 1.2; color: var(--text-primary)", text: skill.name }),
        el("p", { style: "font-size: 12px; color: var(--text-muted); margin-top: 4px; opacity: 0.85", text: skill.category }),
      ]),
      badge,
    ]);
  }
})();
