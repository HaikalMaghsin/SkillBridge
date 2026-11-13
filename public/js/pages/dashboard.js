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
  await refreshPipelines();

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

  async function refreshPipelines() {
    try {
      const pipelines = await (API.getPipelineStatus ? API.getPipelineStatus() : fetchPipelineStatus());
      if (pipelines && pipelines.length > 0) {
        renderPipelineNotifications(pipelines);
      } else {
        const container = document.getElementById("pipeline-notifications");
        if (container) container.style.display = "none";
      }
    } catch (err) {
      console.error('Error refreshing pipelines:', err);
    }
  }

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
    if (!container) return;
    container.innerHTML = "";
    container.style.display = "block";

    // Status config: icon, color, bg, border, label
    const statusConfig = {
      'New': {
        icon: `<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>`,
        color: "var(--primary, #0284c7)",
        bg: "rgba(2, 132, 199, 0.08)",
        border: "rgba(2, 132, 199, 0.25)",
        label: "Minat Tercatat"
      },
      'Shortlisted': {
        icon: `<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>`,
        color: "#0284c7",
        bg: "#f0f9ff",
        border: "#bae6fd",
        label: "Di-shortlist HR"
      },
      'Interview': {
        icon: `<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>`,
        color: "#d97706",
        bg: "#fffbeb",
        border: "#fde68a",
        label: "Undangan Interview"
      },
      'Accepted': {
        icon: `<circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/>`,
        color: "#16a34a",
        bg: "#f0fdf4",
        border: "#bbf7d0",
        label: "Diterima (Offering)"
      },
      'Rejected': {
        icon: `<circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/>`,
        color: "#dc2626",
        bg: "#fef2f2",
        border: "#fecaca",
        label: "Belum Sesuai"
      }
    };

    const section = el("div", {
      style: "margin-bottom: 32px; background: #ffffff; border: 1px solid var(--border); border-radius: 16px; padding: 24px; box-shadow: 0 4px 14px rgba(0,0,0,0.04)"
    }, [
      el("div", { style: "display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 14px" }, [
        el("div", { style: "display: flex; align-items: center; gap: 10px" }, [
          el("div", {
            style: "width: 34px; height: 34px; border-radius: 8px; background: #e0f2fe; display: grid; place-items: center; color: #0284c7",
            innerHTML: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`
          }),
          el("div", {}, [
            el("h2", { style: "font-size: 17px; font-weight: 700; color: var(--text-primary); margin: 0", text: "Status & Pesan dari HR Perusahaan Mitra" }),
            el("p", { style: "font-size: 13px; color: var(--text-muted); margin-top: 2px", text: "Komunikasi dua arah dan jadwal seleksi aktif Anda bersama recruiter mitra industri." })
          ])
        ]),
        el("span", {
          style: "font-size: 12px; font-weight: 700; color: var(--primary); background: #f0fdf4; border: 1px solid #bbf7d0; padding: 4px 12px; border-radius: 20px",
          text: `${pipelines.length} Proses Aktif`
        })
      ])
    ]);

    const grid = el("div", { style: "display: grid; gap: 16px" });

    for (const p of pipelines) {
      const config = statusConfig[p.status] || statusConfig['New'];
      const payload = p.decision_payload && typeof p.decision_payload === 'object' ? p.decision_payload : {};

      const card = el("div", {
        style: `background: #ffffff; border: 1.5px solid ${config.border}; border-radius: 14px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 14px`
      });

      // --- 1. Header Card ---
      const headerRow = el("div", { style: "display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap" }, [
        el("div", { style: "display: flex; gap: 12px; align-items: center" }, [
          el("div", {
            style: `width: 44px; height: 44px; border-radius: 10px; background: ${config.bg}; border: 1px solid ${config.border}; display: grid; place-items: center; flex-shrink: 0`,
            innerHTML: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="${config.color}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${config.icon}</svg>`
          }),
          el("div", {}, [
            el("h3", { style: "font-size: 16px; font-weight: 700; color: var(--text-primary); margin: 0", text: p.vacancy_title || "General Pipeline" }),
            el("p", { style: "font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-top: 2px", text: `${p.company_name}${p.industry ? ' · ' + p.industry : ''}` })
          ])
        ]),
        el("div", { style: "display: flex; align-items: center; gap: 8px; flex-wrap: wrap" }, [
          el("span", {
            style: `font-size: 12px; font-weight: 700; color: ${config.color}; background: ${config.bg}; border: 1px solid ${config.border}; padding: 4px 12px; border-radius: 20px`,
            text: config.label
          }),
          el("span", {
            style: "font-size: 12px; color: var(--text-muted)",
            text: formatRelativeTime(p.updated_at)
          })
        ])
      ]);
      card.append(headerRow);

      // --- 2. Detail Body Berdasarkan Status ---
      if (p.status === 'Shortlisted') {
        const shortlistBox = el("div", {
          style: "background: #f8fafc; border-left: 4px solid #0284c7; padding: 14px 16px; border-radius: 0 8px 8px 0; font-size: 14px; line-height: 1.5; color: var(--text-primary)"
        }, [
          el("div", { style: "font-weight: 700; color: #0284c7; margin-bottom: 4px; display: flex; align-items: center; gap: 6px" }, [
            el("span", { innerHTML: "★" }),
            document.createTextNode("Catatan Apresiasi Portofolio & Keunggulan:")
          ]),
          el("div", { style: "color: #334155; margin-bottom: 8px", text: payload.reason || p.notes || "Kandidat memenuhi kualifikasi dasar posisi ini." }),
          payload.next_step ? el("div", { style: "font-size: 13px; color: #64748b; border-top: 1px dashed #cbd5e1; padding-top: 6px; margin-top: 6px" }, [
            el("strong", { text: "Tahap Selanjutnya: " }),
            document.createTextNode(payload.next_step)
          ]) : null
        ]);
        card.append(shortlistBox);
      } else if (p.status === 'Interview') {
        const interviewBox = el("div", {
          style: "background: #fffdf5; border: 1.5px solid #fde68a; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; gap: 12px"
        });

        // Mode badge & Title
        const isOnline = payload.interview_mode === 'online' || !payload.interview_mode;
        const modeBadge = el("div", { style: "display: flex; align-items: center; gap: 8px; flex-wrap: wrap" }, [
          el("span", {
            style: `font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; ${isOnline ? 'background: #e0f2fe; color: #0369a1;' : 'background: #fef3c7; color: #92400e;'}`,
            text: isOnline ? (payload.meeting_platform ? `Online — ${payload.meeting_platform}` : 'Online (Google Meet / Zoom)') : 'Offline — Tatap Muka di Kantor'
          }),
          payload.interviewer_name ? el("span", {
            style: "font-size: 12px; color: #475569; background: #f1f5f9; padding: 4px 10px; border-radius: 6px",
            text: `Pewawancara: ${payload.interviewer_name}`
          }) : null
        ]);
        interviewBox.append(modeBadge);

        // Schedule & Location Info
        const infoGrid = el("div", { style: "display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; background: #ffffff; padding: 12px 14px; border-radius: 8px; border: 1px solid #fed7aa" });

        // Waktu
        const dateVal = payload.interview_date || (payload.interview_datetime ? payload.interview_datetime.split(' ')[0] : '-');
        const timeVal = payload.interview_time || (payload.interview_datetime ? payload.interview_datetime.split(' ')[1] : '');
        infoGrid.append(el("div", {}, [
          el("div", { style: "font-size: 11px; text-transform: uppercase; font-weight: 700; color: #9a3412" }, [document.createTextNode("Jadwal Interview")]),
          el("div", { style: "font-size: 14px; font-weight: 700; color: #1e293b; margin-top: 2px" }, [
            document.createTextNode(`${dateVal}${timeVal ? ' pukul ' + timeVal + ' WIB' : ''}`)
          ])
        ]));

        // Lokasi / Meeting Detail
        if (isOnline) {
          const meetingCol = el("div", {}, [
            el("div", { style: "font-size: 11px; text-transform: uppercase; font-weight: 700; color: #0369a1" }, [document.createTextNode("Tautan Pertemuan")]),
            el("div", { style: "margin-top: 4px" }, [
              payload.meeting_url ? el("a", {
                href: payload.meeting_url,
                target: "_blank",
                rel: "noopener noreferrer",
                class: "btn btn-sm btn-primary",
                style: "display: inline-flex; align-items: center; gap: 6px; font-size: 12px; padding: 4px 10px; text-decoration: none; border-radius: 6px",
                innerHTML: `<span>↗ Buka Link Meeting</span>`
              }) : el("span", {
                style: "font-size: 13px; color: #64748b; font-style: italic",
                text: payload.meeting_creator === 'candidate_invited' ? 'Link dikoordinasikan via email/kalender' : 'Link meeting disediakan HR'
              })
            ])
          ]);
          infoGrid.append(meetingCol);
        } else {
          const officeCol = el("div", {}, [
            el("div", { style: "font-size: 11px; text-transform: uppercase; font-weight: 700; color: #92400e" }, [document.createTextNode("Lokasi Kantor & Ruangan")]),
            el("div", { style: "font-size: 13px; color: #1e293b; margin-top: 2px", text: payload.location_detail || payload.offline_address || "-" }),
            payload.offline_items ? el("div", { style: "font-size: 12px; color: #64748b; margin-top: 4px" }, [
              el("strong", { text: "Bawa: " }),
              document.createTextNode(payload.offline_items)
            ]) : null
          ]);
          infoGrid.append(officeCol);
        }
        interviewBox.append(infoGrid);

        // Pesan persiapan dari HR
        if (payload.interview_message || p.notes) {
          interviewBox.append(el("div", { style: "font-size: 13px; color: #334155; line-height: 1.5" }, [
            el("strong", { style: "color: #1e293b", text: "Pesan & Persiapan dari HR: " }),
            document.createTextNode(payload.interview_message || p.notes)
          ]));
        }

        // Response Banner & Action Controls
        const responseArea = renderStudentResponseArea(p, 'interview');
        interviewBox.append(responseArea);

        card.append(interviewBox);
      } else if (p.status === 'Accepted') {
        const acceptBox = el("div", {
          style: "background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; gap: 12px"
        });

        acceptBox.append(el("div", { style: "display: flex; align-items: center; gap: 8px" }, [
          el("span", { style: "font-size: 18px" }, [document.createTextNode("🎉")]),
          el("h4", { style: "font-size: 15px; font-weight: 700; color: #166534; margin: 0", text: "Selamat! Anda Menerima Tawaran Rekrutmen" })
        ]));

        if (payload.accept_message || p.notes) {
          acceptBox.append(el("div", { style: "font-size: 14px; color: #1e293b; line-height: 1.5", text: payload.accept_message || p.notes }));
        }

        const infoRow = el("div", { style: "display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #bbf7d0" }, [
          el("div", {}, [
            el("div", { style: "font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase" }, [document.createTextNode("Rencana Mulai (Onboarding)")]),
            el("div", { style: "font-size: 14px; font-weight: 700; color: #14532d; margin-top: 2px", text: payload.start_date || "Sesuai kesepakatan" })
          ]),
          el("div", {}, [
            el("div", { style: "font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase" }, [document.createTextNode("Kontak HR & Langkah Selanjutnya")]),
            el("div", { style: "font-size: 13px; color: #14532d; margin-top: 2px", text: payload.contact_info || "Silakan pantau email dan nomor kontak Anda." })
          ])
        ]);
        acceptBox.append(infoRow);

        const responseArea = renderStudentResponseArea(p, 'accepted');
        acceptBox.append(responseArea);

        card.append(acceptBox);
      } else if (p.status === 'Rejected') {
        const rejectBox = el("div", {
          style: "background: #fef2f2; border-left: 4px solid #ef4444; padding: 14px 16px; border-radius: 0 8px 8px 0; font-size: 14px; line-height: 1.5; color: #1e293b"
        }, [
          el("div", { style: "font-weight: 700; color: #b91c1c; margin-bottom: 4px" }, [
            document.createTextNode("Evaluasi Penolakan yang Konstruktif:")
          ]),
          el("div", { style: "color: #450a0a; margin-bottom: 6px", text: payload.reject_reason || p.notes || "Kualifikasi saat ini belum sepenuhnya memenuhi kebutuhan posisi." }),
          payload.feedback ? el("div", { style: "font-size: 13px; color: #7f1d1d; border-top: 1px dashed #fca5a5; padding-top: 6px; margin-top: 6px" }, [
            el("strong", { text: "Saran Pengembangan: " }),
            document.createTextNode(payload.feedback)
          ]) : null
        ]);
        card.append(rejectBox);
      } else {
        // Status 'New' / Interest noted
        card.append(el("div", { style: "font-size: 13px; color: var(--text-muted); font-style: italic" }, [
          document.createTextNode("Profil dan portofolio Anda sedang dalam antrean review oleh tim recruiter mitra.")
        ]));
      }

      grid.append(card);
    }

    section.append(grid);
    container.append(section);
  }

  function renderStudentResponseArea(pipeline, type) {
    const wrapper = el("div", { style: "margin-top: 6px; border-top: 1px dashed #e2e8f0; padding-top: 12px" });
    const currentResp = pipeline.candidate_response || 'pending';

    if (currentResp === 'confirmed') {
      wrapper.append(el("div", {
        style: "display: flex; align-items: center; gap: 8px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 10px 14px; color: #065f46; font-size: 13px; font-weight: 600"
      }, [
        el("svg", { width: "16", height: "16", viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", "stroke-width": "2.5", "stroke-linecap": "round", "stroke-linejoin": "round", innerHTML: `<polyline points="20 6 9 17 4 12"/>` }),
        el("span", { text: "Anda telah mengonfirmasi siap hadir tepat waktu. HR telah menerima notifikasi kesiapan Anda." })
      ]));
      return wrapper;
    }

    if (currentResp === 'offer_accepted') {
      wrapper.append(el("div", {
        style: "display: flex; align-items: center; gap: 8px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 10px 14px; color: #065f46; font-size: 13px; font-weight: 600"
      }, [
        el("svg", { width: "16", height: "16", viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", "stroke-width": "2.5", "stroke-linecap": "round", "stroke-linejoin": "round", innerHTML: `<polyline points="20 6 9 17 4 12"/>` }),
        el("span", { text: "Anda telah mengonfirmasi menerima penawaran ini. Selamat bergabung dan siapkan langkah onboarding selanjutnya!" })
      ]));
      return wrapper;
    }

    if (currentResp === 'reschedule_requested') {
      wrapper.append(el("div", {
        style: "background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; color: #92400e; font-size: 13px"
      }, [
        el("div", { style: "display: flex; align-items: center; gap: 6px; font-weight: 700" }, [
          el("span", { innerHTML: "🗓" }),
          document.createTextNode("Anda telah mengajukan permohonan reschedule / balasan:")
        ]),
        el("div", { style: "margin-top: 4px; font-style: italic; color: #78350f; background: #fef3c7; padding: 6px 10px; border-radius: 6px", text: pipeline.candidate_reply_note || "(Tanpa catatan tambahan)" }),
        el("div", { style: "margin-top: 4px; font-size: 11px; color: #b45309", text: "Menunggu penyesuaian jadwal atau tanggapan dari recruiter HR di konsol mitra." })
      ]));
      return wrapper;
    }

    // Pending: Action Buttons
    const btnGroup = el("div", { style: "display: flex; gap: 10px; flex-wrap: wrap; align-items: center" });

    // Action 1: Confirm
    const btnConfirm = el("button", {
      type: "button",
      class: "btn btn-sm btn-primary",
      style: "background: #16a34a; border-color: #15803d; color: #ffffff; font-weight: 600; padding: 8px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px",
      innerHTML: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>${type === 'accepted' ? '✓ Konfirmasi Terima Tawaran' : '✓ Konfirmasi Siap Hadir'}</span>`
    });

    // Action 2: Reschedule / Reply
    const btnReschedule = el("button", {
      type: "button",
      class: "btn btn-sm btn-secondary",
      style: "font-weight: 600; padding: 8px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px",
      innerHTML: `<span>${type === 'accepted' ? '💬 Kirim Pertanyaan ke HR' : '🗓 Ajukan Reschedule / Balasan'}</span>`
    });

    btnGroup.append(btnConfirm, btnReschedule);
    wrapper.append(btnGroup);

    // Reschedule form (hidden initially)
    const replyForm = el("div", {
      style: "display: none; margin-top: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px"
    });

    const replyTextarea = el("textarea", {
      placeholder: type === 'accepted'
        ? "Tuliskan pertanyaan mengenai tawaran, tanggal onboarding, atau dokumen kontrak..."
        : "Tuliskan alasan kendala waktu dan usulan jadwal interview alternatif Anda...",
      style: "width: 100%; min-height: 72px; padding: 10px; font-size: 13px; border: 1px solid var(--border); border-radius: 6px; box-sizing: border-box; resize: vertical; font-family: inherit"
    });

    const formActions = el("div", { style: "display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px" });

    const btnCancelReply = el("button", {
      type: "button",
      class: "btn btn-sm btn-secondary",
      style: "font-size: 12px; padding: 6px 12px",
      text: "Batal"
    });
    btnCancelReply.addEventListener("click", () => {
      replyForm.style.display = "none";
      btnGroup.style.display = "flex";
    });

    const btnSubmitReply = el("button", {
      type: "button",
      class: "btn btn-sm btn-primary",
      style: "font-size: 12px; padding: 6px 14px; background: #0284c7",
      text: "Kirim Balasan ke HR"
    });

    formActions.append(btnCancelReply, btnSubmitReply);
    replyForm.append(replyTextarea, formActions);
    wrapper.append(replyForm);

    btnReschedule.addEventListener("click", () => {
      replyForm.style.display = "block";
      btnGroup.style.display = "none";
      replyTextarea.focus();
    });

    // Handle Confirm Click
    btnConfirm.addEventListener("click", async () => {
      const confirmAction = type === 'accepted' ? 'offer_accepted' : 'confirmed';
      const promptText = type === 'accepted'
        ? "Konfirmasi bahwa Anda bersedia menerima tawaran magang/kerja ini?"
        : "Konfirmasi bahwa Anda siap hadir memenuhi jadwal interview ini?";

      if (!confirm(promptText)) return;

      btnConfirm.disabled = true;
      btnConfirm.innerText = "Mengirim...";

      try {
        await (API.respondPipeline ? API.respondPipeline(pipeline.id, confirmAction) : submitPipelineResponse(pipeline.id, confirmAction));
        UI.toast ? UI.toast("Konfirmasi berhasil terkirim ke HR!") : alert("Konfirmasi berhasil terkirim ke HR!");
        await refreshPipelines();
      } catch (err) {
        alert("Gagal mengirim konfirmasi: " + err.message);
        btnConfirm.disabled = false;
        btnConfirm.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>${type === 'accepted' ? '✓ Konfirmasi Terima Tawaran' : '✓ Konfirmasi Siap Hadir'}</span>`;
      }
    });

    // Handle Submit Reply Click
    btnSubmitReply.addEventListener("click", async () => {
      const note = replyTextarea.value.trim();
      if (!note) {
        alert("Harap tuliskan catatan atau usulan jadwal alternatif Anda.");
        replyTextarea.focus();
        return;
      }

      btnSubmitReply.disabled = true;
      btnSubmitReply.innerText = "Mengirim...";

      try {
        await (API.respondPipeline ? API.respondPipeline(pipeline.id, 'reschedule_requested', note) : submitPipelineResponse(pipeline.id, 'reschedule_requested', note));
        UI.toast ? UI.toast("Pesan permohonan reschedule berhasil terkirim ke HR!") : alert("Pesan permohonan reschedule berhasil terkirim ke HR!");
        await refreshPipelines();
      } catch (err) {
        alert("Gagal mengirim pesan: " + err.message);
        btnSubmitReply.disabled = false;
        btnSubmitReply.innerText = "Kirim Balasan ke HR";
      }
    });

    return wrapper;
  }

  async function submitPipelineResponse(pipelineId, response, replyNote = '') {
    const base = (typeof window !== "undefined" && window.APP_BASE_URL !== undefined) ? window.APP_BASE_URL.replace(/\/+$/, "") : "";
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.content : "";

    const res = await fetch(`${base}/api/pipeline-status.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        pipeline_id: pipelineId,
        response: response,
        reply_note: replyNote
      })
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: 'Gagal merespons' }));
      throw new Error(err.error || `HTTP ${res.status}`);
    }

    return await res.json();
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
