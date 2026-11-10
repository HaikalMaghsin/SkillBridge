// career.js - Career Target dengan 3-Tier Adaptive UX + Grid Card + Modal Detail

(async () => {
  const { el, clear } = UI;
  const [careers, catalog, mine] = await Promise.all([
    API.getCareers(),
    API.getSkillCatalog(),
    API.getMySkills()
  ]);

  const mineSet = new Set(mine.map((s) => s.skill_id));
  let targetId = await API.getTargetCareer();
  let currentVacancy = null; // Store current vacancy for modal

  const iconMap = {
    "Backend Developer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="16" height="14" rx="2"/><path d="M6 8l2 2-2 2M10 12h4"/></svg>`,
    "Frontend Developer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="16" height="14" rx="2"/><path d="M2 7h16"/></svg>`,
    "Data Analyst": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M3 16v-6M10 16V8M17 16v-3"/><circle cx="3" cy="3" r="1.5"/><circle cx="10" cy="3" r="1.5"/><circle cx="17" cy="3" r="1.5"/></svg>`,
    "UI/UX Designer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="10" cy="10" r="3"/><path d="M10 2v2M10 16v2M18 10h-2M4 10H2M15.5 4.5l-1.4 1.4M5.9 14.1l-1.4 1.4M15.5 15.5l-1.4-1.4M5.9 5.9L4.5 4.5"/></svg>`,
  };

  await init();

  async function init() {
    const btnChange = document.getElementById('btn-change-role');
    const modalClose = document.getElementById('modal-close-btn');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const vacancyModalClose = document.getElementById('vacancy-modal-close-btn');
    const vacancyModalBackdrop = document.getElementById('vacancy-modal-backdrop');

    if (btnChange) btnChange.addEventListener('click', showState2Modal);
    if (modalClose) modalClose.addEventListener('click', hideRoleModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', hideRoleModal);
    if (vacancyModalClose) vacancyModalClose.addEventListener('click', hideVacancyModal);
    if (vacancyModalBackdrop) vacancyModalBackdrop.addEventListener('click', hideVacancyModal);

    if (targetId) {
      await showState1(targetId);
    } else {
      showState2Initial();
    }
  }

  // ============ STATE 1: Show Current Target + Vacancies Grid ============
  async function showState1(careerId) {
    const target = careers.find((c) => c.id === careerId);
    if (!target) {
      showState2Initial();
      return;
    }

    document.getElementById('career-grid').style.display = 'none';
    document.getElementById('current-target-header').style.display = 'block';
    document.getElementById('vacancies-container').style.display = 'block';

    const icon = iconMap[target.name] || iconMap["Backend Developer"];
    document.getElementById('target-icon').innerHTML = icon;
    document.getElementById('target-name').textContent = target.name;
    document.getElementById('target-desc').textContent = target.description;

    const skillNames = target.skills.map((name) => catalog.find((s) => s.name === name)).filter(Boolean);
    const chipsContainer = document.getElementById('target-skills-chips');
    clear(chipsContainer);
    for (const skill of skillNames) {
      chipsContainer.append(renderSkillChip(skill, mineSet.has(skill.id)));
    }

    await loadVacancies(careerId);
  }

  async function loadVacancies(careerId) {
    const listEl = document.getElementById('vacancies-list');
    const emptyEl = document.getElementById('vacancies-empty');

    clear(listEl);

    try {
      const response = await fetch(`${window.APP_BASE_URL}/api/vacancies-by-career.php?career_id=${careerId}`);
      if (!response.ok) {
        listEl.style.display = 'none';
        emptyEl.style.display = 'block';
        return;
      }

      const vacancies = await response.json();
      if (!vacancies || vacancies.length === 0) {
        listEl.style.display = 'none';
        emptyEl.style.display = 'block';
        return;
      }

      listEl.style.display = 'grid';
      emptyEl.style.display = 'none';

      for (const vacancy of vacancies) {
        listEl.append(renderVacancyCard(vacancy));
      }
    } catch (err) {
      console.error('Error loading vacancies:', err);
      listEl.style.display = 'none';
      emptyEl.style.display = 'block';
    }
  }

  // ============ Render Vacancy Card (Grid Item) ============
  function renderVacancyCard(vacancy) {
    const tierColors = {
      1: { bg: 'var(--tint-green)', color: 'var(--success, #16a34a)' },
      2: { bg: 'var(--tint-yellow, #fefce8)', color: 'var(--warning, #ca8a04)' },
      3: { bg: 'var(--tint-blue)', color: 'var(--primary)' }
    };

    const tierConfig = tierColors[vacancy.tier] || tierColors[3];

    const jobTypeBadges = {
      'Internship': { bg: 'var(--tint-pink)', color: 'var(--accent)' },
      'Trainee Program': { bg: 'var(--tint-yellow, #fefce8)', color: 'var(--warning, #ca8a04)' },
      'Fresh Graduate': { bg: 'var(--tint-indigo)', color: 'var(--primary)' },
      'Full-time': { bg: 'var(--tint-gray)', color: 'var(--text-primary)' }
    };

    const jobTypeConfig = jobTypeBadges[vacancy.job_type] || jobTypeBadges['Full-time'];

    return el("article", {
      class: "vacancy-card",
      style: "background: var(--surface); border: 1px solid var(--border-light); border-radius: 16px; padding: 24px; cursor: pointer; transition: all 0.2s; position: relative; overflow: hidden",
      onClick: () => openVacancyModal(vacancy)
    }, [
      // Header
      el("div", { style: "display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px" }, [
        el("div", { style: "flex: 1; min-width: 0" }, [
          el("h3", { style: "font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; line-height: 1.3", text: vacancy.title }),
          el("p", { style: "font-size: 14px; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; flex-wrap: wrap" }, [
            el("span", { text: vacancy.company_name }),
            el("span", { text: "•", style: "color: var(--text-muted)" }),
            el("span", { text: vacancy.industry })
          ])
        ])
      ]),

      // Badges Row
      el("div", { style: "display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap" }, [
        el("span", {
          style: `font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; background: ${jobTypeConfig.bg}; color: ${jobTypeConfig.color}`,
          text: vacancy.job_type
        }),
        el("span", {
          style: `font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; background: ${tierConfig.bg}; color: ${tierConfig.color}`,
          text: `${vacancy.tier_label} (${vacancy.tier_description})`
        })
      ]),

      // Description Preview
      el("p", {
        style: "font-size: 13px; line-height: 1.5; color: var(--text-secondary); margin-bottom: 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden",
        text: vacancy.description || 'Deskripsi lowongan tidak tersedia.'
      }),

      // Skills Preview (max 5)
      el("div", { style: "margin-bottom: 12px" }, [
        el("p", { style: "font-size: 12px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600", text: "Skill yang dibutuhkan:" }),
        el("div", { style: "display: flex; flex-wrap: wrap; gap: 6px" },
          vacancy.skills.slice(0, 5).map(skill =>
            el("span", {
              style: skill.owned
                ? "font-size: 12px; padding: 5px 10px; border-radius: 8px; background: var(--tint-green); color: var(--success); font-weight: 600; display: inline-flex; align-items: center; gap: 4px"
                : "font-size: 12px; padding: 5px 10px; border-radius: 8px; background: var(--surface-secondary); color: var(--text-tertiary); font-weight: 600"
            }, skill.owned ? [
              el("svg", { width: "10", height: "10", viewBox: "0 0 10 10", fill: "none", stroke: "currentColor", "stroke-width": "2", innerHTML: `<path d="M2 5l2 2 4-4"/>` }),
              document.createTextNode(skill.name)
            ] : [document.createTextNode(skill.name)])
          )
        ),
        vacancy.skills.length > 5 && el("span", {
          style: "font-size: 12px; color: var(--text-muted); margin-top: 4px; display: inline-block",
          text: `+${vacancy.skills.length - 5} skill lainnya`
        })
      ]),

      // Footer CTA
      el("div", { style: "display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid var(--border-light)" }, [
        el("span", { style: "font-size: 13px; color: var(--primary); font-weight: 600", text: "Lihat Detail Loker" }),
        el("svg", { width: "16", height: "16", viewBox: "0 0 16 16", fill: "none", stroke: "var(--primary)", "stroke-width": "2", innerHTML: `<path d="M6 12l4-4-4-4"/>` })
      ])
    ]);
  }

  // ============ Modal: Vacancy Detail ============
  function openVacancyModal(vacancy) {
    currentVacancy = vacancy;

    // Populate modal
    document.getElementById('modal-vacancy-title').textContent = vacancy.title;
    document.getElementById('modal-company-name').textContent = vacancy.company_name;
    document.getElementById('modal-industry').textContent = vacancy.industry;
    document.getElementById('modal-location').textContent = vacancy.location;

    // Job type badge
    const jobTypeBadge = document.getElementById('modal-job-type-badge');
    const jobTypeColors = {
      'Internship': { bg: 'var(--tint-pink)', color: 'var(--accent)' },
      'Trainee Program': { bg: 'var(--tint-yellow, #fefce8)', color: 'var(--warning, #ca8a04)' },
      'Fresh Graduate': { bg: 'var(--tint-indigo)', color: 'var(--primary)' }
    };
    const jobTypeConfig = jobTypeColors[vacancy.job_type] || { bg: 'var(--tint-gray)', color: 'var(--text-primary)' };
    jobTypeBadge.style.background = jobTypeConfig.bg;
    jobTypeBadge.style.color = jobTypeConfig.color;
    jobTypeBadge.textContent = vacancy.job_type;

    // Tier badge
    const tierBadge = document.getElementById('modal-tier-badge');
    const tierColors = {
      1: { bg: 'var(--tint-green)', color: 'var(--success, #16a34a)' },
      2: { bg: 'var(--tint-yellow, #fefce8)', color: 'var(--warning, #ca8a04)' },
      3: { bg: 'var(--tint-blue)', color: 'var(--primary)' }
    };
    const tierConfig = tierColors[vacancy.tier];
    tierBadge.style.background = tierConfig.bg;
    tierBadge.style.color = tierConfig.color;
    tierBadge.textContent = `${vacancy.tier_label} (${vacancy.tier_description})`;

    // Company description
    document.getElementById('modal-company-desc').textContent = vacancy.company_description || 'Informasi perusahaan belum tersedia.';

    // Vision/Mission (placeholder for now)
    const visionEl = document.getElementById('modal-company-vision').querySelector('p');
    visionEl.textContent = 'Visi & Misi: Menjadi perusahaan teknologi terdepan yang memberdayakan talenta muda Indonesia untuk berinovasi dan berkembang.';

    // Job description & requirements
    document.getElementById('modal-job-desc').textContent = vacancy.description || 'Deskripsi pekerjaan tidak tersedia.';
    document.getElementById('modal-requirements').textContent = vacancy.requirements || 'Kualifikasi tidak tersedia.';

    // Skills analysis
    const ownedSkills = vacancy.skills.filter(s => s.owned);
    const gapSkills = vacancy.skills.filter(s => !s.owned);

    const ownedContainer = document.getElementById('modal-skills-owned');
    const gapContainer = document.getElementById('modal-skills-gap');

    clear(ownedContainer);
    clear(gapContainer);

    if (ownedSkills.length === 0) {
      ownedContainer.append(el("p", { style: "font-size: 13px; color: var(--text-muted); font-style: italic", text: "Belum ada skill yang cocok." }));
    } else {
      for (const skill of ownedSkills) {
        ownedContainer.append(
          el("span", {
            style: "font-size: 13px; padding: 6px 12px; border-radius: 8px; background: var(--tint-green); color: var(--success); font-weight: 600; display: inline-flex; align-items: center; gap: 4px"
          }, [
            el("svg", { width: "12", height: "12", viewBox: "0 0 12 12", fill: "none", stroke: "currentColor", "stroke-width": "2", innerHTML: `<path d="M2.5 6.5l2 2 5-5"/>` }),
            document.createTextNode(skill.name)
          ])
        );
      }
    }

    if (gapSkills.length === 0) {
      gapContainer.append(el("p", { style: "font-size: 13px; color: var(--text-muted); font-style: italic", text: "Semua skill sudah terpenuhi! 🎉" }));
    } else {
      for (const skill of gapSkills) {
        gapContainer.append(
          el("span", {
            style: "font-size: 13px; padding: 6px 12px; border-radius: 8px; background: var(--surface-secondary); color: var(--text-secondary); font-weight: 600"
          }, [document.createTextNode(skill.name)])
        );
      }
    }

    // Setup action buttons
    setupModalActions(vacancy);

    // Show modal
    document.getElementById('vacancy-detail-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
  }

  function setupModalActions(vacancy) {
    const btnFocusGap = document.getElementById('modal-btn-focus-gap');
    const btnExpressInterest = document.getElementById('modal-btn-express-interest');
    const btnText = document.getElementById('modal-btn-text');

    // Remove old listeners
    btnFocusGap.replaceWith(btnFocusGap.cloneNode(true));
    btnExpressInterest.replaceWith(btnExpressInterest.cloneNode(true));

    const newBtnFocusGap = document.getElementById('modal-btn-focus-gap');
    const newBtnExpressInterest = document.getElementById('modal-btn-express-interest');
    const newBtnText = document.getElementById('modal-btn-text');

    newBtnFocusGap.addEventListener('click', () => focusToCompany(vacancy.id));

    // Adaptive button based on tier
    if (vacancy.tier === 1) {
      // Tier 1: Direct express interest
      newBtnText.textContent = 'Tandai Minat & Buka Portofolio';
      newBtnExpressInterest.disabled = false;
      newBtnExpressInterest.className = 'btn btn-accent';
      newBtnExpressInterest.addEventListener('click', () => expressInterest(vacancy.id, 1));
    } else if (vacancy.tier === 2) {
      // Tier 2: Intern track
      newBtnText.textContent = 'Tandai Minat (Jalur Intern / Pembelajar)';
      newBtnExpressInterest.disabled = false;
      newBtnExpressInterest.className = 'btn btn-accent';
      newBtnExpressInterest.addEventListener('click', () => expressInterest(vacancy.id, 2));
    } else {
      // Tier 3: Confirmation modal
      newBtnText.textContent = 'Tetap Tandai Minat (Eksplorasi Intern)';
      newBtnExpressInterest.disabled = false;
      newBtnExpressInterest.className = 'btn btn-secondary';
      newBtnExpressInterest.addEventListener('click', () => confirmTier3Interest(vacancy));
    }
  }

  function confirmTier3Interest(vacancy) {
    const gapCount = vacancy.skills_total_count - vacancy.skills_owned_count;
    const confirmed = confirm(
      `Lowongan ini membutuhkan ${gapCount} skill yang belum ada di profilmu.\n\n` +
      `Kami menyarankan kamu mengecek halaman Skill Gap terlebih dahulu untuk melihat roadmap pengembangan skill.\n\n` +
      `Tetap lanjutkan menandai minat sebagai pembelajar?`
    );

    if (confirmed) {
      expressInterest(vacancy.id, 3);
    }
  }

  async function expressInterest(vacancyId, tier) {
    try {
      const response = await fetch(`${window.APP_BASE_URL}/api/express-interest.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ vacancy_id: vacancyId })
      });

      if (!response.ok) {
        const err = await response.json();
        throw new Error(err.error || 'Failed to express interest');
      }

      hideVacancyModal();

      if (tier === 1) {
        UI.toast('Profil dan portofoliomu sudah dibagikan ke HR perusahaan ini!');
      } else if (tier === 2) {
        UI.toast('Minat berhasil ditandai sebagai kandidat Intern/Pembelajar! Kamu bisa sambil melengkapi skill di menu Skill Gap.');
      } else {
        UI.toast('Minat berhasil ditandai! Kami sarankan fokus melengkapi skill gap terlebih dahulu.');
      }

      setTimeout(() => window.location.href = `${window.APP_BASE_URL}/mahasiswa/dashboard.php`, 2000);
    } catch (err) {
      console.error('Error expressing interest:', err);
      UI.toast(err.message || 'Gagal menandai minat', 'error');
    }
  }

  function hideVacancyModal() {
    document.getElementById('vacancy-detail-modal').style.display = 'none';
    document.body.style.overflow = '';
    currentVacancy = null;
  }

  // ============ STATE 2: Show Career Grid (Initial or Modal) ============
  function showState2Initial() {
    const gridEl = document.getElementById('career-grid');
    const headerEl = document.getElementById('current-target-header');
    const vacanciesEl = document.getElementById('vacancies-container');

    if (gridEl) gridEl.style.display = 'grid';
    if (headerEl) headerEl.style.display = 'none';
    if (vacanciesEl) vacanciesEl.style.display = 'none';

    renderCareerGrid('career-grid', false);
  }

  function showState2Modal() {
    document.getElementById('role-selection-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    renderCareerGrid('modal-career-grid', true);
  }

  function hideRoleModal() {
    document.getElementById('role-selection-modal').style.display = 'none';
    document.body.style.overflow = '';
  }

  function renderCareerGrid(containerId, isModal) {
    const gridEl = document.getElementById(containerId);
    clear(gridEl);

    const currentTarget = careers.find((c) => c.id === targetId);
    const list = currentTarget
      ? [currentTarget, ...careers.filter((c) => c.id !== targetId)]
      : careers;

    for (const career of list) {
      const isSelected = career.id === targetId;
      gridEl.append(renderCareerCard(career, isSelected, isModal));
    }
  }

  function renderCareerCard(career, isSelected, isModal) {
    const icon = iconMap[career.name] || iconMap["Backend Developer"];

    const card = el("article", {
      class: isSelected ? "career-card is-selected" : "career-card"
    });

    const head = el("div", { class: "career-card-head" }, [
      el("div", { class: "career-card-icon", innerHTML: icon }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("h3", { class: "career-card-title", text: career.name }),
      ]),
    ]);
    card.append(head);

    if (isSelected) {
      card.append(
        el("span", { class: "target-badge" }, [
          el("svg", { width: "12", height: "12", viewBox: "0 0 12 12", fill: "none", stroke: "currentColor", "stroke-width": "1.6", innerHTML: `<circle cx="6" cy="6" r="4.5"/><circle cx="6" cy="6" r="1.5"/>` }),
          document.createTextNode("Current Target"),
        ])
      );
    }

    card.append(el("p", { class: "career-card-desc", text: career.description }));

    const skillNames = career.skills.map((name) => catalog.find((s) => s.name === name)).filter(Boolean);
    card.append(
      el("div", { class: "career-card-skills" }, [
        el("p", { class: "career-card-skills-label", text: "Required skills" }),
        el("div", { class: "career-card-chips" }, skillNames.map((s) => renderSkillChip(s, mineSet.has(s.id)))),
      ])
    );

    if (isSelected && isModal) {
      card.append(
        el("button", {
          class: "btn btn-accent",
          style: "width: fit-content; gap: 7px",
          disabled: true,
        }, [
          el("svg", { width: "16", height: "16", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<circle cx="8" cy="8" r="6"/><circle cx="8" cy="8" r="2"/>` }),
          document.createTextNode("Current Target"),
        ])
      );
    } else if (!isSelected) {
      card.append(
        el("div", { class: "career-card-foot" }, [
          el("p", { class: "career-card-foot-text", text: "Review this path" }),
          el("button", {
            class: "btn btn-primary btn-sm",
            onClick: () => selectTarget(career, isModal),
          }, [
            el("svg", { width: "14", height: "14", viewBox: "0 0 14 14", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<circle cx="7" cy="7" r="5.5"/><circle cx="7" cy="7" r="1.8"/>` }),
            document.createTextNode("Set as Target"),
          ]),
        ])
      );
    }

    return card;
  }

  async function selectTarget(career, isModal) {
    try {
      await API.setTarget(career.id);
      UI.toast(`${career.name} set as your target career`);

      targetId = career.id;

      if (isModal) {
        hideRoleModal();
      }

      await showState1(career.id);
    } catch (err) {
      console.error('Error setting target:', err);
      UI.toast('Failed to set target. Please try again.', 'error');
    }
  }

  // ============ Helper Functions ============
  function renderSkillChip(skill, owned) {
    const color = owned ? "var(--accent)" : "var(--primary)";
    const bg = owned ? "var(--tint-pink)" : "var(--tint-indigo)";
    return el("span", {
      class: "skill-chip",
      style: `--chip-color: ${color}; --chip-bg: ${bg}`,
    }, [
      el("span", { style: `display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: ${color}` }),
      document.createTextNode(skill.name),
    ]);
  }

  function focusToCompany(vacancyId) {
    window.location.href = `${window.APP_BASE_URL}/mahasiswa/gap.php?vacancy_id=${vacancyId}`;
  }
})();
