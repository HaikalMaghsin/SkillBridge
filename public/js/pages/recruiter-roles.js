// public/js/pages/recruiter-roles.js
// Target Role & Kebutuhan Skill — kartu role + CRUD modal + mapping skill dua-panel.
const { el, clear } = UI;

let allRoles = [];
let selectedRoleId = null;
let catalogSkills = [];
let connectedIds = new Set();
let careersList = [];

// DOM Elements
const modalRole = document.getElementById("modal-role");
const modalRoleTitle = document.getElementById("modal-role-title");
const formRole = document.getElementById("form-role");
const btnCreateRole = document.getElementById("btn-create-role");
const btnCloseModal = document.getElementById("modal-role-close");
const backdropModal = document.getElementById("modal-role-backdrop");
const btnCancelRole = document.getElementById("btn-cancel-role");

const roleInputId = document.getElementById("role-input-id");
const roleInputTitle = document.getElementById("role-input-title");
const roleInputCareer = document.getElementById("role-input-career");
const roleInputPositions = document.getElementById("role-input-positions");
const roleInputDesc = document.getElementById("role-input-desc");
const roleInputReq = document.getElementById("role-input-req");
const rolesSummary = document.getElementById("roles-summary");

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  const elDate = document.getElementById("rec-today");
  if (elDate) {
    elDate.textContent = `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
  }
}

function chip(label) {
  return el("span", { class: "rec-skill-chip" }, [
    el("span", { class: "rec-skill-dot" }),
    label,
  ]);
}

function populateCareerDropdown() {
  if (!roleInputCareer) return;
  roleInputCareer.innerHTML = '<option value="">Pilih Jalur Karier</option>';
  for (const c of careersList) {
    const opt = document.createElement("option");
    opt.value = c.id;
    opt.textContent = c.name;
    roleInputCareer.appendChild(opt);
  }
}

function openCreateModal() {
  if (!modalRole) return;
  roleInputId.value = "";
  roleInputTitle.value = "";
  roleInputCareer.value = "";
  roleInputPositions.value = "1";
  roleInputDesc.value = "";
  roleInputReq.value = "";
  if (modalRoleTitle) modalRoleTitle.textContent = "Tambah Lowongan Baru";
  modalRole.style.display = "block";
}

function openEditModal(role) {
  if (!modalRole) return;
  roleInputId.value = role.id;
  roleInputTitle.value = role.name || role.title || "";
  roleInputCareer.value = role.career_id || "";
  roleInputPositions.value = role.positions || "1";
  roleInputDesc.value = role.description || "";
  roleInputReq.value = role.requirements || "";
  if (modalRoleTitle) modalRoleTitle.textContent = "Edit Lowongan";
  modalRole.style.display = "block";
}

function closeModal() {
  if (modalRole) modalRole.style.display = "none";
}

// Modal Listeners
if (btnCreateRole) btnCreateRole.addEventListener("click", openCreateModal);
if (btnCloseModal) btnCloseModal.addEventListener("click", closeModal);
if (backdropModal) backdropModal.addEventListener("click", closeModal);
if (btnCancelRole) btnCancelRole.addEventListener("click", closeModal);

// Form Submit (Create / Edit)
if (formRole) {
  formRole.addEventListener("submit", async (e) => {
    e.preventDefault();
    const id = roleInputId.value ? parseInt(roleInputId.value, 10) : null;
    const title = roleInputTitle.value.trim();
    const career_id = roleInputCareer.value ? parseInt(roleInputCareer.value, 10) : null;
    const positions = parseInt(roleInputPositions.value, 10) || 1;
    const description = roleInputDesc.value.trim();
    const requirements = roleInputReq.value.trim();

    if (!title) {
      UI.toast("Judul lowongan wajib diisi");
      return;
    }

    try {
      if (id) {
        await API.updateRecruiterRole({
          id,
          title,
          career_id,
          positions,
          description,
          requirements
        });
        UI.toast("Lowongan berhasil diperbarui");
      } else {
        await API.createRecruiterRole({
          title,
          career_id,
          positions,
          description,
          requirements
        });
        UI.toast("Lowongan berhasil ditambahkan");
      }
      closeModal();
      await load();
    } catch (err) {
      console.error(err);
      UI.toast("Gagal menyimpan: " + (err.message || "Terjadi kesalahan"));
    }
  });
}

function renderCards() {
  const wrap = document.getElementById("rec-role-cards");
  if (!wrap) return;
  clear(wrap);

  if (rolesSummary) {
    rolesSummary.textContent = `${allRoles.length} Lowongan`;
  }

  if (!allRoles.length) {
    wrap.append(el("p", { class: "rec-empty", text: "Belum ada lowongan dibuka. Klik Tambah Lowongan untuk membuka lowongan baru." }));
    return;
  }

  for (const r of allRoles) {
    const isSelected = r.id === selectedRoleId;
    const card = el("div", {
      class: "rec-role-card" + (isSelected ? " rec-role-card--active" : ""),
      onClick: (e) => {
        if (e.target.closest("button") || e.target.closest("a")) return;
        selectRole(r.id);
      },
    }, [
      el("div", { class: "rec-role-card-head" }, [
        el("div", { style: "display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;" }, [
          el("span", {
            class: "rec-role-card-name",
            text: r.name,
            style: "white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
          }),
          el("span", { class: "rec-role-card-pos", text: `${r.positions} posisi` }),
        ]),
        el("div", { style: "display: flex; gap: 6px; margin-left: 8px;" }, [
          el("button", {
            type: "button",
            title: "Edit lowongan",
            style: "background: none; border: 1px solid #E2E8F0; border-radius: 6px; padding: 4px 6px; cursor: pointer; color: #64748B; display: inline-flex; align-items: center;",
            onClick: (e) => {
              e.stopPropagation();
              openEditModal(r);
            }
          }, [
            // Pencil Icon
            el("svg", {
              width: "14",
              height: "14",
              viewBox: "0 0 24 24",
              fill: "none",
              stroke: "currentColor",
              "stroke-width": "2",
              "stroke-linecap": "round",
              "stroke-linejoin": "round"
            }, [
              el("path", { d: "M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" }),
              el("path", { d: "M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" })
            ])
          ]),
          el("button", {
            type: "button",
            title: "Hapus lowongan",
            style: "background: none; border: 1px solid #FECACA; border-radius: 6px; padding: 4px 6px; cursor: pointer; color: #EF4444; display: inline-flex; align-items: center;",
            onClick: async (e) => {
              e.stopPropagation();
              if (confirm(`Yakin ingin menghapus lowongan "${r.name}"?`)) {
                try {
                  await API.deleteRecruiterRole(r.id);
                  UI.toast("Lowongan berhasil dihapus");
                  await load();
                } catch (err) {
                  UI.toast("Gagal menghapus lowongan: " + (err.message || "Terjadi kesalahan"));
                }
              }
            }
          }, [
            // Trash Icon
            el("svg", {
              width: "14",
              height: "14",
              viewBox: "0 0 24 24",
              fill: "none",
              stroke: "currentColor",
              "stroke-width": "2",
              "stroke-linecap": "round",
              "stroke-linejoin": "round"
            }, [
              el("polyline", { points: "3 6 5 6 21 6" }),
              el("path", { d: "M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" })
            ])
          ])
        ])
      ]),
      el("p", { class: "rec-role-card-cat", text: r.category }),
      r.description ? el("p", {
        style: "font-size: 12px; color: #64748B; line-height: 1.4; margin: 2px 0 6px 0; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;",
        text: r.description
      }) : null,
      el("div", { class: "rec-role-card-skills" }, (r.skills || []).slice(0, 3).map(chip)),
      el("div", { class: "rec-role-card-foot", style: "margin-top: 6px; padding-top: 8px; border-top: 1px dashed #E2E8F0;" }, [
        el("span", { style: "font-weight: 600; color: #0F172A;", text: `${r.matches || 0} pelamar` }),
        el("a", {
          href: `candidates.php?vacancy_id=${r.id}`,
          class: "rec-pager-link",
          style: "text-decoration: none; font-weight: 600; color: #6366F1; display: inline-flex; align-items: center; gap: 4px;",
          onClick: (e) => e.stopPropagation()
        }, ["Lihat Pelamar →"])
      ]),
    ]);
    wrap.append(card);
  }
}

function selectRole(id) {
  selectedRoleId = id;
  const role = allRoles.find((r) => r.id === id);
  connectedIds = new Set();
  for (const name of role?.skills || []) {
    const s = catalogSkills.find((c) => c.name === name);
    if (s) connectedIds.add(s.id);
  }
  renderCards();
  renderMapping(role);
}

function renderMapping(role) {
  const panel = document.getElementById("rec-mapping-panel");
  if (!panel) return;
  if (!role) {
    clear(panel);
    panel.append(el("p", { class: "rec-empty", text: "Pilih salah satu lowongan di atas untuk melihat detail kebutuhan skill." }));
    return;
  }

  clear(panel);

  // Panel kiri: skill terhubung (read-only)
  const left = el("div", { class: "rec-mapping-col" }, [
    el("h3", { class: "rec-mapping-col-title", text: "Skill Terhubung" }),
    el("p", { class: "rec-mapping-col-sub", text: `${connectedIds.size} skill terhubung untuk role ini.` }),
  ]);
  const list = el("div", { class: "rec-mapping-list" });
  if (!connectedIds.size) {
    list.append(el("p", { class: "rec-empty", text: "Belum ada skill terhubung pada target role ini." }));
  } else {
    for (const sid of connectedIds) {
      const s = catalogSkills.find((c) => c.id === sid);
      if (!s) continue;
      list.append(el("div", { class: "rec-mapping-item" }, [
        el("span", { class: "rec-mapping-item-name", text: s.name }),
        el("span", { class: "rec-mapping-item-cat", text: s.category }),
      ]));
    }
  }
  left.append(list);
  left.append(el("p", { class: "rec-info-note", text: "Skill yang terhubung menentukan kandidat mana yang cocok di pipeline role ini. Data sinkron dengan target career industri." }));

  // Panel kanan: katalog skill (read-only)
  const right = el("div", { class: "rec-mapping-col" }, [
    el("h3", { class: "rec-mapping-col-title", text: "Panel Data Skill" }),
    el("input", {
      type: "search",
      class: "rec-search",
      id: "rec-skill-search",
      placeholder: "Cari skill…",
      "aria-label": "Cari skill",
    }),
  ]);
  const catList = el("div", { class: "rec-mapping-list", id: "rec-skill-catalog" });
  right.append(catList);
  const count = el("p", { class: "rec-mapping-count", id: "rec-skill-count" });
  right.append(count);

  function renderCatalog() {
    const q = (document.getElementById("rec-skill-search")?.value || "").toLowerCase();
    clear(catList);
    let shown = 0;
    for (const s of catalogSkills) {
      if (q && !s.name.toLowerCase().includes(q)) continue;
      shown += 1;
      const checked = connectedIds.has(s.id);
      const row = el("label", { class: "rec-check-row" }, [
        el("input", {
          type: "checkbox",
          class: "rec-check",
          ...(checked ? { checked: "checked" } : {}),
          disabled: "disabled",
        }),
        el("span", { class: "rec-check-name", text: s.name }),
        el("span", { class: "rec-check-cat", text: s.category }),
      ]);
      catList.append(row);
    }
    if (!shown) catList.append(el("p", { class: "rec-empty", text: "Tidak ada skill yang cocok." }));
    const c = document.getElementById("rec-skill-count");
    if (c) c.textContent = `${connectedIds.size} skill terhubung dari ${catalogSkills.length} tersedia`;
  }

  panel.append(left, right);
  renderCatalog();
  document.getElementById("rec-skill-search").addEventListener("input", renderCatalog);
}

async function load() {
  renderDate();
  try {
    const [roles, catalog, careers] = await Promise.all([
      API.getRecruiterRoles(),
      API.getSkillCatalog(),
      API.getCareers(),
    ]);
    allRoles = roles || [];
    catalogSkills = catalog || [];
    careersList = careers || [];
    populateCareerDropdown();
  } catch (err) {
    console.error("Failed to load recruiter roles data:", err);
    allRoles = [];
    catalogSkills = [];
    careersList = [];
  }

  if (!selectedRoleId || !allRoles.find((r) => r.id === selectedRoleId)) {
    selectedRoleId = allRoles[0]?.id ?? null;
  }
  renderCards();
  selectRole(selectedRoleId);
}

load();
