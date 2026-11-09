// career.js - Career Target page dengan Lowongan Perusahaan Sekitar

(async () => {
  const { el, clear } = UI;
  const [careers, catalog, mine] = await Promise.all([
    API.getCareers(),
    API.getSkillCatalog(),
    API.getMySkills()
  ]);

  const mineSet = new Set(mine.map((s) => s.skill_id));

  const gridEl = document.getElementById("career-grid");
  const bannerText = document.getElementById("banner-text");

  render();

  async function render() {
    const targetId = await API.getTargetCareer();
    const target = careers.find((c) => c.id === targetId) || careers[0];
    const list = [target, ...careers.filter((c) => c.id !== target.id)];

    bannerText.textContent = `${target.name} is your current target. Required skills below are a practical starter list for this project.`;

    gridEl.classList.remove("career-grid--animated");
    clear(gridEl);

    for (const career of list) {
      const isSelected = career.id === target.id;
      gridEl.append(await renderCareerCard(career, isSelected, mineSet, catalog));
    }

    void gridEl.offsetWidth;
    requestAnimationFrame(() => gridEl.classList.add("career-grid--animated"));
  }

  async function selectTarget(career) {
    await API.setTarget(career.id);
    render();
  }

  async function renderCareerCard(career, isSelected, mine, catalog) {
    const iconMap = {
      "Backend Developer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="16" height="14" rx="2"/><path d="M6 8l2 2-2 2M10 12h4"/></svg>`,
      "Frontend Developer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="16" height="14" rx="2"/><path d="M2 7h16"/></svg>`,
      "Data Analyst": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M3 16v-6M10 16V8M17 16v-3"/><circle cx="3" cy="3" r="1.5"/><circle cx="10" cy="3" r="1.5"/><circle cx="17" cy="3" r="1.5"/></svg>`,
      "UI/UX Designer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="10" cy="10" r="3"/><path d="M10 2v2M10 16v2M18 10h-2M4 10H2M15.5 4.5l-1.4 1.4M5.9 14.1l-1.4 1.4M15.5 15.5l-1.4-1.4M5.9 5.9L4.5 4.5"/></svg>`,
    };
    const icon = iconMap[career.name] || iconMap["Backend Developer"];

    const card = el("article", {
      class: isSelected ? "career-card is-selected" : "career-card",
      "data-id": career.id,
    });

    // Head
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
          document.createTextNode("Your Target"),
        ])
      );
    }

    // Description
    card.append(el("p", { class: "career-card-desc", text: career.description }));

    // Required skills
    const skillNames = career.skills.map((name) => catalog.find((s) => s.name === name)).filter(Boolean);
    card.append(
      el("div", { class: "career-card-skills" }, [
        el("p", { class: "career-card-skills-label", text: "Required skills" }),
        el("div", { class: "career-card-chips" }, skillNames.map((s) => renderSkillChip(s, mine.has(s.id)))),
      ])
    );

    if (isSelected) {
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

      // Tampilkan lowongan perusahaan untuk career yang terpilih
      const vacanciesSection = await renderVacanciesSection(career.id);
      if (vacanciesSection) {
        card.append(vacanciesSection);
      }
    } else {
      card.append(
        el("div", { class: "career-card-foot" }, [
          el("p", { class: "career-card-foot-text", text: "Review this path" }),
          el("button", {
            class: "btn btn-primary btn-sm",
            onClick: () => selectTarget(career),
          }, [
            el("svg", { width: "14", height: "14", viewBox: "0 0 14 14", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<circle cx="7" cy="7" r="5.5"/><circle cx="7" cy="7" r="1.8"/>` }),
            document.createTextNode("Set as Target"),
          ]),
        ])
      );
    }

    return card;
  }

  async function renderVacanciesSection(careerId) {
    try {
      const response = await fetch(`${window.APP_BASE_URL}/api/vacancies-by-career.php?career_id=${careerId}`);
      if (!response.ok) return null;

      const vacancies = await response.json();
      if (!vacancies || vacancies.length === 0) return null;

      const section = el("div", { class: "vacancies-section", style: "margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-light)" }, [
        el("h4", { style: "font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-primary)" }, [
          document.createTextNode("Lowongan & Kebutuhan Industri Sekitar")
        ]),
      ]);

      for (const vacancy of vacancies) {
        section.append(renderVacancyCard(vacancy));
      }

      return section;
    } catch (err) {
      console.error('Error loading vacancies:', err);
      return null;
    }
  }

  function renderVacancyCard(vacancy) {
    const ownedCount = vacancy.skills_owned_count || 0;
    const totalCount = vacancy.skills_total_count || 0;
    const hasEnoughSkills = ownedCount >= Math.ceil(totalCount * 0.5); // Minimal 50% skill

    const card = el("div", {
      class: "vacancy-card",
      style: "background: var(--surface); border: 1px solid var(--border-light); border-radius: 12px; padding: 16px; margin-bottom: 12px"
    }, [
      el("div", { style: "display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px" }, [
        el("div", { style: "flex: 1" }, [
          el("h5", { style: "font-size: 15px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary)", text: vacancy.title }),
          el("p", { style: "font-size: 13px; color: var(--text-secondary)" }, [
            el("span", { text: vacancy.company_name }),
            el("span", { text: " • " }),
            el("span", { text: vacancy.industry }),
          ])
        ]),
        el("span", {
          class: "admin-badge-pill",
          style: "font-size: 12px",
          text: `${vacancy.positions} posisi`
        })
      ]),

      // Skills chips
      el("div", { style: "margin-bottom: 12px" }, [
        el("p", { style: "font-size: 13px; color: var(--text-secondary); margin-bottom: 8px", text: "Skill yang dibutuhkan:" }),
        el("div", { style: "display: flex; flex-wrap: wrap; gap: 6px" },
          vacancy.skills.map(skill =>
            el("span", {
              class: "skill-chip",
              style: skill.owned
                ? "--chip-color: var(--success); --chip-bg: var(--tint-green)"
                : "--chip-color: var(--text-tertiary); --chip-bg: var(--surface-secondary)"
            }, [
              skill.owned && el("svg", { width: "12", height: "12", viewBox: "0 0 12 12", fill: "none", stroke: "currentColor", "stroke-width": "2", innerHTML: `<path d="M2.5 6.5l2 2 5-5"/>` }),
              document.createTextNode(skill.name + (skill.owned ? " ✓" : ""))
            ])
          )
        )
      ]),

      // Actions
      el("div", { style: "display: flex; gap: 8px; flex-wrap: wrap" }, [
        el("button", {
          class: "btn btn-primary btn-sm",
          style: "gap: 6px",
          onClick: () => focusToCompany(vacancy.id)
        }, [
          el("svg", { width: "14", height: "14", viewBox: "0 0 14 14", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<circle cx="7" cy="7" r="5.5"/><circle cx="7" cy="7" r="1.8"/>` }),
          document.createTextNode("Fokuskan Skill Gap")
        ]),

        el("button", {
          class: hasEnoughSkills ? "btn btn-accent btn-sm" : "btn btn-secondary btn-sm",
          style: "gap: 6px",
          disabled: !hasEnoughSkills,
          title: hasEnoughSkills ? "Tandai minat ke perusahaan ini" : "Lengkapi skill terlebih dahulu",
          onClick: () => hasEnoughSkills && expressInterest(vacancy.id)
        }, [
          el("svg", { width: "14", height: "14", viewBox: "0 0 14 14", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<path d="M7 2.5v9M2.5 7h9"/>` }),
          document.createTextNode("Tandai Minat & Buka Portofolio")
        ])
      ])
    ]);

    return card;
  }

  function focusToCompany(vacancyId) {
    window.location.href = `${window.APP_BASE_URL}/mahasiswa/gap.php?vacancy_id=${vacancyId}`;
  }

  async function expressInterest(vacancyId) {
    if (!confirm('Tandai minat ke perusahaan ini?\n\nPortofolio Anda akan terlihat oleh HR perusahaan.')) return;

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

      UI.toast('Minat berhasil ditandai! Portofolio Anda sekarang terlihat oleh HR.');
      setTimeout(() => window.location.href = `${window.APP_BASE_URL}/mahasiswa/dashboard.php`, 1500);
    } catch (err) {
      console.error('Error expressing interest:', err);
      UI.toast(err.message || 'Gagal menandai minat', 'error');
    }
  }

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
})();
