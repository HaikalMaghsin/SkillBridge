// public/js/pages/recruiter-team.js
// Manajemen Recruiter & Karyawan · SkillBridge

const { el, clear } = UI;

const STATUS_BADGE = {
  active: "rec-badge--indigo",
  inactive: "rec-badge--gray",
  pending: "rec-badge--pink",
};

const STATUS_LABEL = {
  active: "Aktif",
  inactive: "Nonaktif",
  pending: "Pending"
};

const PAGE_SIZE = 8;
let allTeam = [];
let page = 1;

function initials(name) {
  return String(name || "")
    .split(" ")
    .map((w) => w.charAt(0))
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  const dateEl = document.getElementById("rec-today");
  if (dateEl) {
    dateEl.textContent = `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
  }
}

async function renderStats() {
  const team = allTeam;
  const grid = document.getElementById("rec-team-stats");
  if (!grid) return;
  clear(grid);

  const activeCount = team.filter((t) => t.status === "active").length;
  const inactiveCount = team.filter((t) => t.status !== "active").length;
  const leadManagerCount = team.filter((t) => t.role && t.role !== "Recruiter").length;

  const cards = [
    {
      label: "Total Anggota Tim",
      value: team.length,
      context: `${activeCount} aktif · ${inactiveCount} nonaktif`
    },
    {
      label: "Lead & Manager",
      value: leadManagerCount,
      context: "Memiliki hak akses ke konfigurasi & pipeline"
    },
    {
      label: "Recruiter Staff",
      value: team.filter((t) => t.role === "Recruiter").length,
      context: "Akses peninjauan pelamar & wawancara"
    },
  ];

  for (const c of cards) {
    grid.appendChild(
      el("article", { class: "rec-stat-card" }, [
        el("p", { class: "rec-stat-label", text: c.label }),
        el("p", { class: "rec-stat-value", text: String(c.value) }),
        el("p", { class: "rec-stat-context", text: c.context }),
      ])
    );
  }
}

function applyFilter() {
  const q = (document.getElementById("rec-team-search")?.value || "").toLowerCase();
  const role = document.getElementById("rec-team-role-filter")?.value || "all";
  const status = document.getElementById("rec-team-status-filter")?.value || "all";

  return allTeam.filter((m) => {
    const hit = m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q);
    const roleMatch = (role === "all" || m.role === role);
    const statusMatch = (status === "all" || m.status === status);
    return hit && roleMatch && statusMatch;
  });
}

function render() {
  const list = applyFilter();
  const totalPages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
  if (page > totalPages) page = totalPages;

  const slice = list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);
  const tbody = document.getElementById("rec-team-tbody");
  if (!tbody) return;
  clear(tbody);

  if (slice.length === 0) {
    const td = el("td", { colspan: "5", class: "rec-empty", text: "Tidak ada anggota tim yang cocok dengan filter pencarian." });
    tbody.appendChild(el("tr", {}, [td]));
  } else {
    for (const m of slice) {
      // Actions container
      const actionsTd = el("td", { style: "text-align: right;" });

      if (m.is_self) {
        actionsTd.appendChild(
          el("span", {
            style: "font-size: 12px; color: #64748b; font-weight: 500; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;",
            text: "Akun Anda"
          })
        );
      } else {
        const actionWrap = el("div", { style: "display: inline-flex; gap: 6px; justify-content: flex-end;" });

        // Tombol Ubah Role
        const btnEditRole = el("button", {
          type: "button",
          class: "btn btn-secondary btn-sm",
          style: "padding: 4px 10px; font-size: 12px;",
          text: "Ubah Role",
          click: () => openEditRoleModal(m)
        });

        // Tombol Quick Toggle Status
        const isCurrentlyActive = (m.status === "active");
        const btnToggleStatus = el("button", {
          type: "button",
          class: `btn ${isCurrentlyActive ? "btn-secondary" : "btn-primary"} btn-sm`,
          style: "padding: 4px 10px; font-size: 12px;",
          text: isCurrentlyActive ? "Nonaktifkan" : "Aktifkan",
          click: () => toggleMemberStatus(m)
        });

        // Tombol Hapus
        const btnDelete = el("button", {
          type: "button",
          class: "btn btn-danger btn-sm",
          style: "padding: 4px 8px; font-size: 12px; color: #ef4444; background: #fef2f2; border: 1px solid #fecaca;",
          title: "Hapus dari Tim",
          text: "Hapus",
          click: () => deleteMember(m)
        });

        actionWrap.appendChild(btnEditRole);
        actionWrap.appendChild(btnToggleStatus);
        actionWrap.appendChild(btnDelete);
        actionsTd.appendChild(actionWrap);
      }

      tbody.appendChild(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "rec-row-name" }, [
              el("span", { class: "rec-avatar-sm", text: initials(m.name) }),
              el("div", { class: "rec-row-name-text" }, [
                el("span", { class: "rec-row-name-main", text: m.name }),
                el("span", { class: "rec-row-name-sub", text: m.email }),
              ]),
            ]),
          ]),
          el("td", {}, [
            el("span", {
              class: "rec-role-text",
              style: "font-weight: 600; color: #1e293b;",
              text: m.role || "Recruiter"
            }),
          ]),
          el("td", {}, [
            el("span", {
              class: `rec-badge ${STATUS_BADGE[m.status] || "rec-badge--gray"}`,
              text: STATUS_LABEL[m.status] || m.status,
            }),
          ]),
          el("td", { text: UI.formatDate(m.joined || m.created_at) }),
          actionsTd,
        ])
      );
    }
  }

  const pageNum = document.getElementById("rec-team-page");
  if (pageNum) pageNum.textContent = String(page);
  const prevBtn = document.getElementById("rec-team-prev");
  if (prevBtn) prevBtn.disabled = page <= 1;
  const nextBtn = document.getElementById("rec-team-next");
  if (nextBtn) nextBtn.disabled = page >= totalPages;
}

// Quick toggle status
async function toggleMemberStatus(member) {
  const newStatus = (member.status === "active") ? "inactive" : "active";
  const actionText = (newStatus === "active") ? "mengaktifkan" : "menonaktifkan";

  if (!confirm(`Apakah Anda yakin ingin ${actionText} akses untuk ${member.name}?`)) {
    return;
  }

  try {
    const res = await API.updateRecruiterTeamMember({ id: member.id, status: newStatus });
    UI.toast(res.message || `Status ${member.name} berhasil diubah`);
    await load();
  } catch (err) {
    UI.toast(err.message || "Gagal mengubah status", "error");
  }
}

// Delete member
async function deleteMember(member) {
  if (!confirm(`Hapus ${member.name} (${member.email}) dari tim perusahaan ini?`)) {
    return;
  }

  try {
    const res = await API.deleteRecruiterTeamMember(member.id);
    UI.toast(res.message || "Anggota tim berhasil dihapus");
    await load();
  } catch (err) {
    UI.toast(err.message || "Gagal menghapus anggota tim", "error");
  }
}

// Modal Ubah Role & Status
function openEditRoleModal(member) {
  const modal = document.getElementById("modal-edit-role");
  const idInput = document.getElementById("edit-member-id");
  const nameInput = document.getElementById("edit-member-name");
  const roleSelect = document.getElementById("edit-member-role");
  const statusSelect = document.getElementById("edit-member-status");

  if (!modal) return;

  if (idInput) idInput.value = member.id;
  if (nameInput) nameInput.value = `${member.name} (${member.email})`;
  if (roleSelect) roleSelect.value = member.role || "Recruiter";
  if (statusSelect) statusSelect.value = member.status || "active";

  modal.style.display = "flex";
}

function closeEditRoleModal() {
  const modal = document.getElementById("modal-edit-role");
  if (modal) modal.style.display = "none";
}

// Modal Tambah Anggota
function openAddMemberModal() {
  const modal = document.getElementById("modal-add-member");
  const form = document.getElementById("form-add-member");
  if (form) form.reset();
  if (modal) modal.style.display = "flex";
}

function closeAddMemberModal() {
  const modal = document.getElementById("modal-add-member");
  if (modal) modal.style.display = "none";
}

function initModals() {
  // Trigger Tambah Anggota
  const btnOpenAdd = document.getElementById("btn-open-add-member");
  if (btnOpenAdd) btnOpenAdd.addEventListener("click", openAddMemberModal);

  const btnCloseAdd = document.getElementById("btn-close-add-modal");
  if (btnCloseAdd) btnCloseAdd.addEventListener("click", closeAddMemberModal);

  const btnCancelAdd = document.getElementById("btn-cancel-add-member");
  if (btnCancelAdd) btnCancelAdd.addEventListener("click", closeAddMemberModal);

  // Submit Tambah Anggota
  const formAdd = document.getElementById("form-add-member");
  const btnSubmitAdd = document.getElementById("btn-submit-add-member");
  if (formAdd) {
    formAdd.addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = document.getElementById("add-member-name")?.value.trim() || "";
      const email = document.getElementById("add-member-email")?.value.trim() || "";
      const role_title = document.getElementById("add-member-role")?.value || "Recruiter";
      const password = document.getElementById("add-member-password")?.value.trim() || "";

      if (!name || !email) {
        UI.toast("Nama lengkap dan email wajib diisi", "error");
        return;
      }

      if (btnSubmitAdd) {
        btnSubmitAdd.disabled = true;
        btnSubmitAdd.textContent = "Mendaftarkan...";
      }

      try {
        const payload = { name, email, role_title };
        if (password) payload.password = password;

        const res = await API.addRecruiterTeamMember(payload);
        UI.toast(res.message || "Anggota tim baru berhasil didaftarkan!");
        closeAddMemberModal();
        await load();
      } catch (err) {
        UI.toast(err.message || "Gagal mendaftarkan anggota tim", "error");
      } finally {
        if (btnSubmitAdd) {
          btnSubmitAdd.disabled = false;
          btnSubmitAdd.textContent = "Daftarkan Anggota";
        }
      }
    });
  }

  // Trigger Ubah Role Modal
  const btnCloseEdit = document.getElementById("btn-close-edit-modal");
  if (btnCloseEdit) btnCloseEdit.addEventListener("click", closeEditRoleModal);

  const btnCancelEdit = document.getElementById("btn-cancel-edit-member");
  if (btnCancelEdit) btnCancelEdit.addEventListener("click", closeEditRoleModal);

  // Submit Ubah Role
  const formEdit = document.getElementById("form-edit-role");
  const btnSubmitEdit = document.getElementById("btn-submit-edit-member");
  if (formEdit) {
    formEdit.addEventListener("submit", async (e) => {
      e.preventDefault();
      const id = parseInt(document.getElementById("edit-member-id")?.value, 10);
      const role_title = document.getElementById("edit-member-role")?.value || "Recruiter";
      const status = document.getElementById("edit-member-status")?.value || "active";

      if (!id) return;

      if (btnSubmitEdit) {
        btnSubmitEdit.disabled = true;
        btnSubmitEdit.textContent = "Menyimpan...";
      }

      try {
        const res = await API.updateRecruiterTeamMember({ id, role_title, status });
        UI.toast(res.message || "Hak akses dan status anggota diperbarui!");
        closeEditRoleModal();
        await load();
      } catch (err) {
        UI.toast(err.message || "Gagal memperbarui data", "error");
      } finally {
        if (btnSubmitEdit) {
          btnSubmitEdit.disabled = false;
          btnSubmitEdit.textContent = "Simpan Perubahan";
        }
      }
    });
  }
}

async function load() {
  renderDate();
  try {
    allTeam = await API.getRecruiterTeam();
    await renderStats();
    render();
  } catch (err) {
    UI.toast("Gagal memuat tim recruiter: " + err.message, "error");
  }
}

// Event filter & pagination
const searchInput = document.getElementById("rec-team-search");
if (searchInput) {
  searchInput.addEventListener("input", () => {
    page = 1;
    render();
  });
}

const roleFilter = document.getElementById("rec-team-role-filter");
if (roleFilter) {
  roleFilter.addEventListener("change", () => {
    page = 1;
    render();
  });
}

const statusFilter = document.getElementById("rec-team-status-filter");
if (statusFilter) {
  statusFilter.addEventListener("change", () => {
    page = 1;
    render();
  });
}

const prevBtn = document.getElementById("rec-team-prev");
if (prevBtn) {
  prevBtn.addEventListener("click", () => {
    page -= 1;
    render();
  });
}

const nextBtn = document.getElementById("rec-team-next");
if (nextBtn) {
  nextBtn.addEventListener("click", () => {
    page += 1;
    render();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  initModals();
  load();
});

// Fallback execution
initModals();
load();
