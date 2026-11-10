// hr-candidates.js - HR Candidates Management with Grid + Modal Detail

(async () => {
  const { el, clear } = UI;

  let allCandidates = [];
  let currentCandidate = null;

  // Status configuration
  const statusConfig = {
    'New': { color: 'var(--primary)', bg: 'var(--tint-blue)', label: 'Baru Masuk' },
    'Shortlisted': { color: 'var(--success)', bg: 'var(--tint-green)', label: 'Di-shortlist' },
    'Interview': { color: 'var(--warning, #ca8a04)', bg: 'var(--tint-yellow, #fefce8)', label: 'Undangan Interview' },
    'Accepted': { color: 'var(--success)', bg: 'var(--tint-green)', label: 'Diterima' },
    'Rejected': { color: 'var(--danger, #dc2626)', bg: 'var(--tint-red, #fef2f2)', label: 'Ditolak' }
  };

  const tierConfig = {
    1: { color: 'var(--success)', bg: 'var(--tint-green)', label: 'Sangat Sesuai' },
    2: { color: 'var(--warning, #ca8a04)', bg: 'var(--tint-yellow, #fefce8)', label: 'Cocok Magang/Intern' },
    3: { color: 'var(--primary)', bg: 'var(--tint-blue)', label: 'Perlu Pengembangan' }
  };

  await init();

  async function init() {
    // Setup filter
    const filterSelect = document.getElementById('status-filter');
    if (filterSelect) {
      filterSelect.addEventListener('change', (e) => loadCandidates(e.target.value));
    }

    // Setup search
    const searchInput = document.getElementById('candidate-search');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        const filtered = allCandidates.filter(c =>
          (c.name && c.name.toLowerCase().includes(query)) ||
          (c.target_role && c.target_role.toLowerCase().includes(query)) ||
          (c.vacancy_title && c.vacancy_title.toLowerCase().includes(query)) ||
          (c.skills && c.skills.some(s => s.name.toLowerCase().includes(query)))
        );
        renderCandidatesList(filtered);
      });
    }

    // Setup modal close
    const modalClose = document.getElementById('candidate-modal-close');
    const modalBackdrop = document.getElementById('candidate-modal-backdrop');
    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);

    // Support vacancy_id filter from query param if opened from roles.php
    const urlParams = new URLSearchParams(window.location.search);
    const vacancyId = urlParams.get('vacancy_id');
    const initialStatus = urlParams.get('status') || 'all';
    if (filterSelect && initialStatus !== 'all') {
      filterSelect.value = initialStatus;
    }

    // Load initial data
    await loadCandidates(initialStatus, vacancyId);
  }

  function renderCandidatesList(list) {
    const container = document.getElementById('candidates-grid');
    const emptyState = document.getElementById('empty-state');
    if (!container) return;

    if (list.length === 0) {
      container.style.display = 'none';
      if (emptyState) emptyState.style.display = 'flex';
    } else {
      if (emptyState) emptyState.style.display = 'none';
      container.style.display = 'grid';
      clear(container);
      list.forEach(candidate => {
        container.append(renderCandidateCard(candidate));
      });
    }

    const countEl = document.getElementById('candidates-count');
    if (countEl) countEl.textContent = `${list.length} kandidat`;
  }

  async function loadCandidates(status = 'all', vacancyId = null) {
    const container = document.getElementById('candidates-grid');
    const emptyState = document.getElementById('empty-state');
    const loadingState = document.getElementById('loading-state');

    if (loadingState) loadingState.style.display = 'block';
    if (container) container.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';

    try {
      let url = `${window.APP_BASE_URL}/api/hr/candidates.php?status=${encodeURIComponent(status)}`;
      if (vacancyId) {
        url += `&vacancy_id=${encodeURIComponent(vacancyId)}`;
      }
      const response = await fetch(url);

      if (!response.ok) {
        throw new Error('Failed to load candidates');
      }

      allCandidates = await response.json();

      if (loadingState) loadingState.style.display = 'none';
      renderCandidatesList(allCandidates);

    } catch (err) {
      console.error('Error loading candidates:', err);
      if (loadingState) loadingState.style.display = 'none';
      UI.toast('Gagal memuat data kandidat', 'error');
    }
  }

  function renderCandidateCard(candidate) {
    const statusCfg = statusConfig[candidate.status] || statusConfig['New'];
    const tierCfg = tierConfig[candidate.tier] || tierConfig[3];

    return el("article", {
      class: "candidate-card",
      style: "background: var(--surface); border: 1px solid var(--border-light); border-radius: 16px; padding: 20px; cursor: pointer; transition: all 0.2s",
      onClick: () => openCandidateModal(candidate)
    }, [
      // Header
      el("div", { style: "display: flex; align-items: start; gap: 16px; margin-bottom: 16px" }, [
        // Avatar
        el("div", {
          style: "width: 56px; height: 56px; border-radius: 12px; background: var(--primary-dark); color: white; display: grid; place-items: center; font-size: 24px; font-weight: 700; flex-shrink: 0",
          text: candidate.name.charAt(0).toUpperCase()
        }),
        // Info
        el("div", { style: "flex: 1; min-width: 0" }, [
          el("h3", { style: "font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px", text: candidate.name }),
          el("p", { style: "font-size: 13px; color: var(--text-secondary); margin-bottom: 8px", text: candidate.target_role }),
          el("div", { style: "display: flex; gap: 6px; flex-wrap: wrap" }, [
            el("span", {
              style: `font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; background: ${statusCfg.bg}; color: ${statusCfg.color}`,
              text: statusCfg.label
            }),
            el("span", {
              style: `font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; background: ${tierCfg.bg}; color: ${tierCfg.color}`,
              text: tierCfg.label
            })
          ])
        ])
      ]),

      // Stats
      el("div", { style: "display: flex; gap: 16px; padding: 12px; background: var(--surface-secondary); border-radius: 8px; margin-bottom: 12px" }, [
        el("div", { style: "flex: 1" }, [
          el("p", { style: "font-size: 11px; color: var(--text-muted); margin-bottom: 4px", text: "Skill Match" }),
          el("p", { style: "font-size: 14px; font-weight: 700; color: var(--text-primary)", text: `${candidate.skills_matched}/${candidate.skills_total}` })
        ]),
        el("div", { style: "flex: 1" }, [
          el("p", { style: "font-size: 11px; color: var(--text-muted); margin-bottom: 4px", text: "Evidence" }),
          el("p", { style: "font-size: 14px; font-weight: 700; color: var(--text-primary)", text: String(candidate.evidence_count) })
        ])
      ]),

      // Vacancy info
      candidate.vacancy_title && el("p", {
        style: "font-size: 12px; color: var(--text-muted); margin-bottom: 12px",
        text: `Melamar: ${candidate.vacancy_title}`
      }),

      // Footer
      el("div", { style: "display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border-light)" }, [
        el("span", { style: "font-size: 12px; color: var(--text-muted)", text: formatRelativeTime(candidate.pipeline_updated) }),
        el("span", { style: "font-size: 13px; color: var(--primary); font-weight: 600", text: "Lihat Detail →" })
      ])
    ]);
  }

  function openCandidateModal(candidate) {
    currentCandidate = candidate;

    // Populate header
    document.getElementById('modal-candidate-name').textContent = candidate.name;
    document.getElementById('modal-candidate-email').textContent = candidate.email;
    document.getElementById('modal-target-role').textContent = candidate.target_role;

    // Avatar
    const avatarEl = document.getElementById('modal-candidate-avatar');
    avatarEl.textContent = candidate.name.charAt(0).toUpperCase();

    // Badges
    const statusCfg = statusConfig[candidate.status] || statusConfig['New'];
    const tierCfg = tierConfig[candidate.tier] || tierConfig[3];

    const statusBadge = document.getElementById('modal-status-badge');
    statusBadge.style.background = statusCfg.bg;
    statusBadge.style.color = statusCfg.color;
    statusBadge.textContent = statusCfg.label;

    const tierBadge = document.getElementById('modal-tier-badge');
    tierBadge.style.background = tierCfg.bg;
    tierBadge.style.color = tierCfg.color;
    tierBadge.textContent = tierCfg.label;

    // Stats
    document.getElementById('modal-skill-match').textContent = `${candidate.skills_matched}/${candidate.skills_total}`;
    document.getElementById('modal-evidence-count').textContent = String(candidate.evidence_count);

    // Vacancy
    const vacancyEl = document.getElementById('modal-vacancy-info');
    if (candidate.vacancy_title) {
      vacancyEl.textContent = candidate.vacancy_title;
      vacancyEl.parentElement.style.display = 'block';
    } else {
      vacancyEl.parentElement.style.display = 'none';
    }

    // Skills
    const skillsContainer = document.getElementById('modal-skills-list');
    clear(skillsContainer);

    if (candidate.skills.length === 0) {
      skillsContainer.append(el("p", { style: "font-size: 13px; color: var(--text-muted); font-style: italic", text: "Belum ada skill ditambahkan" }));
    } else {
      candidate.skills.forEach(skill => {
        skillsContainer.append(
          el("span", {
            style: "font-size: 13px; padding: 6px 12px; border-radius: 8px; background: var(--tint-indigo); color: var(--primary); font-weight: 600; display: inline-flex; align-items: center; gap: 4px"
          }, [
            el("span", { style: "width: 5px; height: 5px; border-radius: 50%; background: var(--primary)" }),
            document.createTextNode(skill.name)
          ])
        );
      });
    }

    // Notes
    const notesEl = document.getElementById('modal-notes');
    notesEl.value = candidate.notes || '';

    // Setup action buttons
    setupModalActions(candidate);

    // Show modal
    document.getElementById('candidate-detail-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
  }

  function setupModalActions(candidate) {
    const btnViewPortfolio = document.getElementById('modal-btn-portfolio');
    const btnShortlist = document.getElementById('modal-btn-shortlist');
    const btnInterview = document.getElementById('modal-btn-interview');
    const btnAccept = document.getElementById('modal-btn-accept');
    const btnReject = document.getElementById('modal-btn-reject');

    // Remove old listeners
    [btnViewPortfolio, btnShortlist, btnInterview, btnAccept, btnReject].forEach(btn => {
      if (btn) btn.replaceWith(btn.cloneNode(true));
    });

    // Re-get elements after clone
    const newBtnPortfolio = document.getElementById('modal-btn-portfolio');
    const newBtnShortlist = document.getElementById('modal-btn-shortlist');
    const newBtnInterview = document.getElementById('modal-btn-interview');
    const newBtnAccept = document.getElementById('modal-btn-accept');
    const newBtnReject = document.getElementById('modal-btn-reject');

    if (newBtnPortfolio && candidate.portfolio_url) {
      newBtnPortfolio.addEventListener('click', () => window.open(candidate.portfolio_url, '_blank'));
    }

    if (newBtnShortlist) {
      newBtnShortlist.disabled = candidate.status === 'Shortlisted';
      newBtnShortlist.addEventListener('click', () => updateCandidateStatus('Shortlisted'));
    }

    if (newBtnInterview) {
      newBtnInterview.disabled = candidate.status === 'Interview';
      newBtnInterview.addEventListener('click', () => updateCandidateStatus('Interview'));
    }

    if (newBtnAccept) {
      newBtnAccept.disabled = candidate.status === 'Accepted';
      newBtnAccept.addEventListener('click', () => updateCandidateStatus('Accepted'));
    }

    if (newBtnReject) {
      newBtnReject.disabled = candidate.status === 'Rejected';
      newBtnReject.addEventListener('click', () => updateCandidateStatus('Rejected'));
    }
  }

  async function updateCandidateStatus(newStatus) {
    if (!currentCandidate) return;

    const notesEl = document.getElementById('modal-notes');
    const notes = notesEl.value.trim();

    try {
      const response = await fetch(`${window.APP_BASE_URL}/api/hr/candidates.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
          pipeline_id: currentCandidate.pipeline_id || currentCandidate.id,
          user_id: currentCandidate.user_id || currentCandidate.id,
          vacancy_id: currentCandidate.vacancy_id,
          status: newStatus,
          notes: notes
        })
      });

      if (!response.ok) {
        const err = await response.json();
        throw new Error(err.error || 'Failed to update status');
      }

      UI.toast(`Status kandidat diupdate ke: ${statusConfig[newStatus].label}`);
      closeModal();

      // Reload candidates
      const filterSelect = document.getElementById('status-filter');
      await loadCandidates(filterSelect ? filterSelect.value : 'all');

    } catch (err) {
      console.error('Error updating status:', err);
      UI.toast(err.message || 'Gagal update status', 'error');
    }
  }

  function closeModal() {
    document.getElementById('candidate-detail-modal').style.display = 'none';
    document.body.style.overflow = '';
    currentCandidate = null;
  }

  function formatRelativeTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMin < 1) return 'baru saja';
    if (diffMin < 60) return `${diffMin} menit lalu`;
    if (diffHours < 24) return `${diffHours} jam lalu`;
    if (diffDays < 7) return `${diffDays} hari lalu`;
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  }
})();
