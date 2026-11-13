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

    // Response chips from student
    const responseChips = [];
    if (candidate.candidate_response === 'confirmed') {
      responseChips.push(el("span", {
        style: "font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;",
        text: "✓ Mahasiswa Hadir"
      }));
    } else if (candidate.candidate_response === 'reschedule_requested') {
      responseChips.push(el("span", {
        style: "font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; background: #fef3c7; color: #b45309; border: 1px solid #fde68a;",
        text: "⚠ Minta Reschedule"
      }));
    } else if (candidate.candidate_response === 'offer_accepted') {
      responseChips.push(el("span", {
        style: "font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;",
        text: "✓ Tawaran Diterima"
      }));
    }

    // Avatar element
    const avatarEl = el("div", {
      style: "width: 56px; height: 56px; border-radius: 12px; background: var(--primary-dark); color: white; display: grid; place-items: center; font-size: 24px; font-weight: 700; flex-shrink: 0; overflow: hidden;"
    });
    if (candidate.avatar_url) {
      avatarEl.append(el("img", {
        src: candidate.avatar_url,
        alt: candidate.name,
        style: "width: 100%; height: 100%; object-fit: cover;"
      }));
    } else {
      avatarEl.textContent = candidate.name.charAt(0).toUpperCase();
    }

    return el("article", {
      class: "candidate-card",
      style: "background: var(--surface); border: 1px solid var(--border-light); border-radius: 16px; padding: 20px; cursor: pointer; transition: all 0.2s",
      onClick: () => openCandidateModal(candidate)
    }, [
      // Header
      el("div", { style: "display: flex; align-items: start; gap: 16px; margin-bottom: 16px" }, [
        avatarEl,
        // Info
        el("div", { style: "flex: 1; min-width: 0" }, [
          el("h3", { style: "font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px", text: candidate.name }),
          el("p", { style: "font-size: 13px; color: var(--text-secondary); margin-bottom: 8px", text: candidate.target_role }),
          el("div", { style: "display: flex; gap: 6px; flex-wrap: wrap; align-items: center;" }, [
            el("span", {
              style: `font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; background: ${statusCfg.bg}; color: ${statusCfg.color}`,
              text: statusCfg.label
            }),
            el("span", {
              style: `font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; background: ${tierCfg.bg}; color: ${tierCfg.color}`,
              text: tierCfg.label
            }),
            ...responseChips
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
    clear(avatarEl);
    if (candidate.avatar_url) {
      avatarEl.append(el("img", {
        src: candidate.avatar_url,
        alt: candidate.name,
        style: "width: 100%; height: 100%; object-fit: cover;"
      }));
    } else {
      avatarEl.textContent = candidate.name.charAt(0).toUpperCase();
    }

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

    // Student response banner
    const responseBanner = document.getElementById('modal-candidate-response');
    if (responseBanner) {
      if (candidate.candidate_response === 'confirmed') {
        responseBanner.style.display = 'block';
        responseBanner.style.background = '#f0fdf4';
        responseBanner.style.border = '1px solid #bbf7d0';
        responseBanner.style.color = '#166534';
        responseBanner.innerHTML = '<strong>✓ Mahasiswa Mengonfirmasi Hadir:</strong> Kandidat telah menyetujui jadwal wawancara.';
      } else if (candidate.candidate_response === 'reschedule_requested') {
        responseBanner.style.display = 'block';
        responseBanner.style.background = '#fffbeb';
        responseBanner.style.border = '1px solid #fde68a';
        responseBanner.style.color = '#92400e';
        responseBanner.innerHTML = `<strong>⚠ Mahasiswa Mengajukan Reschedule:</strong> "${candidate.candidate_reply_note || 'Mohon hubungi kembali untuk jadwal alternatif'}"`;
      } else if (candidate.candidate_response === 'offer_accepted') {
        responseBanner.style.display = 'block';
        responseBanner.style.background = '#f0fdf4';
        responseBanner.style.border = '1px solid #bbf7d0';
        responseBanner.style.color = '#166534';
        responseBanner.innerHTML = '<strong>✓ Mahasiswa Menerima Tawaran (Offering):</strong> Kandidat siap bergabung sesuai tanggal mulai onboarding.';
      } else {
        responseBanner.style.display = 'none';
      }
    }

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

    // Previous decision history display
    const prevDecisionBox = document.getElementById('modal-prev-decision');
    const prevDecisionStatus = document.getElementById('modal-prev-decision-status');
    const prevDecisionText = document.getElementById('modal-prev-decision-text');
    if (prevDecisionBox && prevDecisionStatus && prevDecisionText) {
      const p = candidate.decision_payload;
      if (p && Object.keys(p).length > 0) {
        prevDecisionBox.style.display = 'block';
        prevDecisionStatus.textContent = candidate.status;
        prevDecisionStatus.style.background = statusCfg.bg;
        prevDecisionStatus.style.color = statusCfg.color;

        let detailParts = [];
        if (candidate.status === 'Interview') {
          detailParts.push(`Metode: <strong>${p.interview_mode === 'offline' ? 'Offline di Kantor' : 'Online'}</strong>`);
          detailParts.push(`Waktu: <strong>${p.interview_date || ''} ${p.interview_time || ''} WIB</strong>`);
          detailParts.push(`Pewawancara: <strong>${p.interviewer_name || '-'}</strong>`);
          if (p.interview_mode === 'online' && p.meeting_url) {
            detailParts.push(`Tautan: <a href="${p.meeting_url}" target="_blank" rel="noopener" style="color:var(--primary);">${p.meeting_url}</a>`);
          } else if (p.location_detail || p.offline_address) {
            detailParts.push(`Lokasi: <strong>${p.location_detail || p.offline_address}</strong>`);
          }
          if (p.interview_message) detailParts.push(`Pesan: <em>${p.interview_message}</em>`);
        } else if (candidate.status === 'Shortlisted') {
          if (p.reason) detailParts.push(`Alasan: <strong>${p.reason}</strong>`);
          if (p.next_step) detailParts.push(`Tahap Berikutnya: <em>${p.next_step}</em>`);
        } else if (candidate.status === 'Accepted') {
          if (p.start_date) detailParts.push(`Mulai: <strong>${p.start_date}</strong>`);
          if (p.contact_info) detailParts.push(`Kontak HR: <strong>${p.contact_info}</strong>`);
          if (p.accept_message) detailParts.push(`Pesan: <em>${p.accept_message}</em>`);
        } else if (candidate.status === 'Rejected') {
          if (p.reject_reason) detailParts.push(`Alasan: <strong>${p.reject_reason}</strong>`);
          if (p.feedback) detailParts.push(`Feedback: <em>${p.feedback}</em>`);
        }
        prevDecisionText.innerHTML = detailParts.join(' &bull; ');
      } else if (candidate.notes) {
        prevDecisionBox.style.display = 'block';
        prevDecisionStatus.textContent = candidate.status;
        prevDecisionStatus.style.background = statusCfg.bg;
        prevDecisionStatus.style.color = statusCfg.color;
        prevDecisionText.textContent = candidate.notes;
      } else {
        prevDecisionBox.style.display = 'none';
      }
    }

    // Notes
    const notesEl = document.getElementById('modal-notes');
    notesEl.value = candidate.notes || '';

    // Clear dynamic decision sub-form panel
    const decisionPanel = document.getElementById('hr-decision-form-panel');
    if (decisionPanel) {
      clear(decisionPanel);
      decisionPanel.style.display = 'none';
    }

    // Setup action buttons
    setupModalActions(candidate);

    // Show modal
    document.getElementById('candidate-detail-modal').style.display = 'flex';
    document.body.classList.add('modal-open');
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
      newBtnShortlist.addEventListener('click', () => openDecisionSubForm('Shortlisted', candidate));
    }

    if (newBtnInterview) {
      newBtnInterview.addEventListener('click', () => openDecisionSubForm('Interview', candidate));
    }

    if (newBtnAccept) {
      newBtnAccept.addEventListener('click', () => openDecisionSubForm('Accepted', candidate));
    }

    if (newBtnReject) {
      newBtnReject.addEventListener('click', () => openDecisionSubForm('Rejected', candidate));
    }
  }

  function openDecisionSubForm(targetStatus, candidate) {
    const panel = document.getElementById('hr-decision-form-panel');
    if (!panel) return;
    clear(panel);
    panel.style.display = 'block';

    if (targetStatus === 'Shortlisted') {
      renderShortlistSubForm(panel, candidate);
    } else if (targetStatus === 'Interview') {
      renderInterviewSubForm(panel, candidate);
    } else if (targetStatus === 'Accepted') {
      renderAcceptedSubForm(panel, candidate);
    } else if (targetStatus === 'Rejected') {
      renderRejectedSubForm(panel, candidate);
    }

    // Scroll to panel smoothly
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function renderShortlistSubForm(container, candidate) {
    const errorEl = el("p", { class: "field-error", style: "display: none; color: #dc2626; font-size: 13px; margin-top: 8px;" });

    const reasonInput = el("textarea", {
      id: "hr-shortlist-reason",
      rows: "3",
      required: "required",
      placeholder: "Contoh: Bukti proyek React dan struktur REST API Anda sesuai dengan kebutuhan tim kami...",
      style: "width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px; resize: vertical;"
    });

    const nextStepInput = el("input", {
      id: "hr-shortlist-next-step",
      type: "text",
      placeholder: "Contoh: Review teknis oleh Lead Engineer dalam 2-3 hari kerja",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    });

    const submitBtn = el("button", {
      type: "button",
      class: "btn btn-primary",
      style: "font-size: 13px; padding: 8px 16px;",
      text: "Kirim Status Shortlist ke Kandidat",
      onClick: async () => {
        errorEl.style.display = "none";
        const reason = reasonInput.value.trim();
        const nextStep = nextStepInput.value.trim();
        if (!reason) {
          errorEl.textContent = "Alasan di-shortlist & apresiasi portofolio wajib diisi.";
          errorEl.style.display = "block";
          reasonInput.focus();
          return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Mengirim...";
        await submitHrDecision("Shortlisted", {
          reason: reason,
          next_step: nextStep
        });
      }
    });

    const cancelBtn = el("button", {
      type: "button",
      class: "btn btn-secondary",
      style: "font-size: 13px; padding: 8px 14px;",
      text: "Batal",
      onClick: () => {
        container.style.display = "none";
        clear(container);
      }
    });

    container.append(
      el("div", { style: "display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;" }, [
        el("h4", { style: "font-size: 15px; font-weight: 700; color: var(--primary); margin: 0;", text: "Form Konfirmasi Shortlist Kandidat" }),
        cancelBtn
      ]),
      el("div", { class: "field", style: "margin-bottom: 12px;" }, [
        el("label", { for: "hr-shortlist-reason", style: "display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Alasan di-Shortlist & Catatan Apresiasi Portofolio *" }),
        reasonInput
      ]),
      el("div", { class: "field", style: "margin-bottom: 14px;" }, [
        el("label", { for: "hr-shortlist-next-step", style: "display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Estimasi Tahap Selanjutnya" }),
        nextStepInput
      ]),
      errorEl,
      el("div", { style: "display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;" }, [
        submitBtn
      ])
    );
  }

  function renderInterviewSubForm(container, candidate) {
    const errorEl = el("p", { class: "field-error", style: "display: none; color: #dc2626; font-size: 13px; margin-top: 8px;" });

    const modeSelect = el("select", {
      id: "hr-interview-mode",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    }, [
      el("option", { value: "online", text: "Online — Google Meet / Zoom / MS Teams" }),
      el("option", { value: "offline", text: "Offline — Tatap Muka di Kantor" })
    ]);

    const today = new Date().toISOString().split("T")[0];
    const dateInput = el("input", {
      id: "hr-interview-date",
      type: "date",
      min: today,
      value: today,
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    });

    const timeInput = el("input", {
      id: "hr-interview-time",
      type: "time",
      value: "10:00",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    });

    const interviewerInput = el("input", {
      id: "hr-interviewer-name",
      type: "text",
      placeholder: "Contoh: Budi Santoso — Lead Tech Recruiter",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    });

    const meetingCreatorSelect = el("select", {
      id: "hr-meeting-creator",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    }, [
      el("option", { value: "hr_provided", text: "Disediakan HR (Link dimasukkan sekarang)" }),
      el("option", { value: "candidate_invited", text: "Dikoordinasikan bersama kandidat (Undangan Kalender/Email)" })
    ]);

    const platformSelect = el("select", {
      id: "hr-meeting-platform",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    }, [
      el("option", { value: "Google Meet", text: "Google Meet" }),
      el("option", { value: "Zoom Meeting", text: "Zoom Meeting" }),
      el("option", { value: "Microsoft Teams", text: "Microsoft Teams" })
    ]);

    const meetingUrlInput = el("input", {
      id: "hr-meeting-url",
      type: "url",
      placeholder: "https://meet.google.com/abc-defg-hij",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    });

    const onlineContainer = el("div", { id: "online-fields", style: "display: grid; gap: 10px; margin-bottom: 12px;" }, [
      el("div", { style: "display: grid; grid-template-columns: 1fr 1fr; gap: 10px;" }, [
        el("div", {}, [
          el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Pembuat Link Meeting *" }),
          meetingCreatorSelect
        ]),
        el("div", {}, [
          el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Platform *" }),
          platformSelect
        ])
      ]),
      el("div", { id: "meeting-url-wrapper" }, [
        el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Tautan Meeting URL *" }),
        meetingUrlInput
      ])
    ]);

    const offlineAddressInput = el("textarea", {
      id: "hr-offline-address",
      rows: "2",
      placeholder: "Contoh: Gedung PT Tech Indonesia, Lt. 3 Ruang Melati, Jl. Sudirman No. 45, Jakarta",
      style: "width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px; resize: vertical;"
    });

    const offlineItemsInput = el("input", {
      id: "hr-offline-items",
      type: "text",
      placeholder: "Contoh: Laptop pribadi untuk live demo portofolio, KTM",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    });

    const offlineContainer = el("div", { id: "offline-fields", style: "display: none; gap: 10px; margin-bottom: 12px;" }, [
      el("div", {}, [
        el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Alamat Lengkap Kantor & Ruangan *" }),
        offlineAddressInput
      ]),
      el("div", {}, [
        el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Perlengkapan yang Wajib Dibawa" }),
        offlineItemsInput
      ])
    ]);

    modeSelect.addEventListener("change", () => {
      const isOnline = modeSelect.value === "online";
      onlineContainer.style.display = isOnline ? "grid" : "none";
      offlineContainer.style.display = isOnline ? "none" : "grid";
    });

    meetingCreatorSelect.addEventListener("change", () => {
      const isProvided = meetingCreatorSelect.value === "hr_provided";
      const wrapper = document.getElementById("meeting-url-wrapper");
      if (wrapper) wrapper.style.display = isProvided ? "block" : "none";
    });

    const messageInput = el("textarea", {
      id: "hr-interview-message",
      rows: "3",
      placeholder: "Contoh: Mohon persiapkan demo aplikasi dari portofolio Anda dan hadir 10 menit sebelum jadwal dimulai.",
      style: "width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px; resize: vertical;"
    });

    const submitBtn = el("button", {
      type: "button",
      class: "btn btn-accent",
      style: "font-size: 13px; padding: 8px 18px; background: #d97706; border-color: #d97706;",
      text: "Kirim Undangan Interview Resmi",
      onClick: async () => {
        errorEl.style.display = "none";
        const mode = modeSelect.value;
        const date = dateInput.value;
        const time = timeInput.value;
        const interviewer = interviewerInput.value.trim();
        const message = messageInput.value.trim();

        if (!date || !time) {
          errorEl.textContent = "Tanggal dan waktu interview wajib diisi.";
          errorEl.style.display = "block";
          return;
        }

        if (!interviewer) {
          errorEl.textContent = "Nama pewawancara / PIC HR wajib diisi.";
          errorEl.style.display = "block";
          interviewerInput.focus();
          return;
        }

        const payload = {
          interview_mode: mode,
          interview_date: date,
          interview_time: time,
          interviewer_name: interviewer,
          interview_message: message
        };

        if (mode === "online") {
          const creator = meetingCreatorSelect.value;
          const url = meetingUrlInput.value.trim();
          if (creator === "hr_provided" && !url) {
            errorEl.textContent = "Tautan meeting wajib diisi jika link disediakan oleh HR.";
            errorEl.style.display = "block";
            meetingUrlInput.focus();
            return;
          }
          payload.meeting_creator = creator;
          payload.meeting_platform = platformSelect.value;
          payload.meeting_url = url;
        } else {
          const address = offlineAddressInput.value.trim();
          if (!address) {
            errorEl.textContent = "Alamat kantor & ruangan wajib diisi.";
            errorEl.style.display = "block";
            offlineAddressInput.focus();
            return;
          }
          payload.location_detail = address;
          payload.offline_items = offlineItemsInput.value.trim();
        }

        if (!message) {
          errorEl.textContent = "Pesan & persiapan interview untuk kandidat wajib diisi.";
          errorEl.style.display = "block";
          messageInput.focus();
          return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Mengirim...";
        await submitHrDecision("Interview", payload);
      }
    });

    const cancelBtn = el("button", {
      type: "button",
      class: "btn btn-secondary",
      style: "font-size: 13px; padding: 8px 14px;",
      text: "Batal",
      onClick: () => {
        container.style.display = "none";
        clear(container);
      }
    });

    container.append(
      el("div", { style: "display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;" }, [
        el("h4", { style: "font-size: 15px; font-weight: 700; color: #d97706; margin: 0;", text: "Undangan Interview Terjadwal" }),
        cancelBtn
      ]),
      el("div", { style: "display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;" }, [
        el("div", {}, [
          el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Metode Interview *" }),
          modeSelect
        ]),
        el("div", {}, [
          el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Nama Pewawancara / PIC *" }),
          interviewerInput
        ])
      ]),
      el("div", { style: "display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;" }, [
        el("div", {}, [
          el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Tanggal Pelaksanaan *" }),
          dateInput
        ]),
        el("div", {}, [
          el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Waktu (WIB) *" }),
          timeInput
        ])
      ]),
      onlineContainer,
      offlineContainer,
      el("div", { style: "margin-bottom: 14px;" }, [
        el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Pesan & Instruksi Persiapan untuk Kandidat *" }),
        messageInput
      ]),
      errorEl,
      el("div", { style: "display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;" }, [
        submitBtn
      ])
    );
  }

  function renderAcceptedSubForm(container, candidate) {
    const errorEl = el("p", { class: "field-error", style: "display: none; color: #dc2626; font-size: 13px; margin-top: 8px;" });

    const messageInput = el("textarea", {
      id: "hr-accept-message",
      rows: "3",
      placeholder: "Selamat! Tim kami sangat terkesan dengan bukti kompetensi dan komunikasi Anda. Kami dengan senang hati menawarkan posisi ini kepada Anda.",
      style: "width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px; resize: vertical;"
    });

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 14);
    const startDateInput = el("input", {
      id: "hr-accept-startdate",
      type: "date",
      value: tomorrow.toISOString().split("T")[0],
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    });

    const contactInput = el("input", {
      id: "hr-accept-contact",
      type: "text",
      placeholder: "WhatsApp HR: 08123456789 (Budi) / Email: hr@techindonesia.com",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    });

    const submitBtn = el("button", {
      type: "button",
      class: "btn btn-accent",
      style: "font-size: 13px; padding: 8px 18px; background: #059669; border-color: #059669;",
      text: "Kirim Keputusan Penerimaan (Offering)",
      onClick: async () => {
        errorEl.style.display = "none";
        const message = messageInput.value.trim();
        const startDate = startDateInput.value;
        const contact = contactInput.value.trim();

        if (!message || !startDate || !contact) {
          errorEl.textContent = "Pesan selamat, rencana tanggal mulai, dan kontak HR wajib diisi.";
          errorEl.style.display = "block";
          return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Mengirim...";
        await submitHrDecision("Accepted", {
          accept_message: message,
          start_date: startDate,
          contact_info: contact
        });
      }
    });

    const cancelBtn = el("button", {
      type: "button",
      class: "btn btn-secondary",
      style: "font-size: 13px; padding: 8px 14px;",
      text: "Batal",
      onClick: () => {
        container.style.display = "none";
        clear(container);
      }
    });

    container.append(
      el("div", { style: "display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;" }, [
        el("h4", { style: "font-size: 15px; font-weight: 700; color: #059669; margin: 0;", text: "Keputusan Penerimaan & Offering" }),
        cancelBtn
      ]),
      el("div", { style: "margin-bottom: 10px;" }, [
        el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Pesan Penerimaan / Offering Resmi *" }),
        messageInput
      ]),
      el("div", { style: "display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;" }, [
        el("div", {}, [
          el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Rencana Tanggal Mulai (Onboarding) *" }),
          startDateInput
        ]),
        el("div", {}, [
          el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Kontak PIC HR / Instruksi Offering *" }),
          contactInput
        ])
      ]),
      errorEl,
      el("div", { style: "display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;" }, [
        submitBtn
      ])
    );
  }

  function renderRejectedSubForm(container, candidate) {
    const errorEl = el("p", { class: "field-error", style: "display: none; color: #dc2626; font-size: 13px; margin-top: 8px;" });

    const reasonSelect = el("select", {
      id: "hr-reject-reason",
      style: "width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"
    }, [
      el("option", { value: "Bukti kompetensi belum cukup kuat (masih Growing)", text: "Bukti kompetensi belum cukup kuat (masih Growing)" }),
      el("option", { value: "Keahlian teknis utama belum sesuai kualifikasi lowongan", text: "Keahlian teknis utama belum sesuai kualifikasi lowongan" }),
      el("option", { value: "Kuota posisi magang/pekerjaan telah terpenuhi", text: "Kuota posisi magang/pekerjaan telah terpenuhi" }),
      el("option", { value: "Pengalaman proyek belum memenuhi standar minimum posisi", text: "Pengalaman proyek belum memenuhi standar minimum posisi" })
    ]);

    const feedbackInput = el("textarea", {
      id: "hr-reject-feedback",
      rows: "3",
      placeholder: "Contoh: Terima kasih atas minat Anda. Kami menyarankan untuk memperbanyak bukti proyek nyata (khususnya backend REST API) dan mengaitkan sertifikasi terkait di halaman Evidence agar dapat bersaing di kesempatan berikutnya.",
      style: "width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px; resize: vertical;"
    });

    const submitBtn = el("button", {
      type: "button",
      class: "btn btn-danger",
      style: "font-size: 13px; padding: 8px 18px;",
      text: "Kirim Evaluasi Penolakan",
      onClick: async () => {
        errorEl.style.display = "none";
        const reason = reasonSelect.value;
        const feedback = feedbackInput.value.trim();

        if (!feedback) {
          errorEl.textContent = "Saran evaluasi penolakan konstruktif wajib diisi.";
          errorEl.style.display = "block";
          feedbackInput.focus();
          return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Mengirim...";
        await submitHrDecision("Rejected", {
          reject_reason: reason,
          feedback: feedback
        });
      }
    });

    const cancelBtn = el("button", {
      type: "button",
      class: "btn btn-secondary",
      style: "font-size: 13px; padding: 8px 14px;",
      text: "Batal",
      onClick: () => {
        container.style.display = "none";
        clear(container);
      }
    });

    container.append(
      el("div", { style: "display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;" }, [
        el("h4", { style: "font-size: 15px; font-weight: 700; color: #dc2626; margin: 0;", text: "Evaluasi & Umpan Balik Penolakan" }),
        cancelBtn
      ]),
      el("div", { style: "margin-bottom: 10px;" }, [
        el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Kategori Alasan Utama *" }),
        reasonSelect
      ]),
      el("div", { style: "margin-bottom: 14px;" }, [
        el("label", { style: "display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);", text: "Pesan Evaluasi Konstruktif & Saran Pengembangan Skill *" }),
        feedbackInput
      ]),
      errorEl,
      el("div", { style: "display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;" }, [
        submitBtn
      ])
    );
  }

  async function submitHrDecision(newStatus, decisionPayload) {
    if (!currentCandidate) return;

    const notesEl = document.getElementById('modal-notes');
    const notes = notesEl ? notesEl.value.trim() : '';

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
          notes: notes,
          decision_payload: decisionPayload
        })
      });

      if (!response.ok) {
        const err = await response.json();
        throw new Error(err.error || 'Failed to update status');
      }

      UI.toast(`Status dan komunikasi ke kandidat berhasil dikirim: ${statusConfig[newStatus]?.label || newStatus}`);
      closeModal();

      // Reload candidates
      const filterSelect = document.getElementById('status-filter');
      await loadCandidates(filterSelect ? filterSelect.value : 'all');

    } catch (err) {
      console.error('Error submitting decision:', err);
      UI.toast(err.message || 'Gagal mengirim keputusan', 'error');
    }
  }

  function closeModal() {
    document.getElementById('candidate-detail-modal').style.display = 'none';
    document.body.classList.remove('modal-open');
    currentCandidate = null;
    const decisionPanel = document.getElementById('hr-decision-form-panel');
    if (decisionPanel) {
      clear(decisionPanel);
      decisionPanel.style.display = 'none';
    }
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
