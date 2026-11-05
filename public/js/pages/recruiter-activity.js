// Log Aktivitas Recruiter — daftar aktivitas + filter tipe.
const { el, clear } = UI;

const TYPE_BADGE = {
  candidate_status_update: "rec-badge--indigo",
  candidate_shortlist: "rec-badge--indigo",
  candidate_interview: "rec-badge--pink",
  vacancy_created: "rec-badge--pink",
};

const TYPE_LABEL = {
  candidate_status_update: "Kandidat",
  candidate_shortlist: "Kandidat",
  candidate_interview: "Interview",
  vacancy_created: "Target Role",
};

let allActivity = [];

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  document.getElementById("rec-today").textContent =
    `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

function applyFilter() {
  const q = (document.getElementById("rec-act-search")?.value || "").toLowerCase();
  const type = document.getElementById("rec-act-type")?.value || "";
  return allActivity.filter((a) => {
    if (type && a.action !== type) return false;
    if (q && !`${a.recruiter || ""} ${a.description || ""}`.toLowerCase().includes(q)) return false;
    return true;
  });
}

function render() {
  const body = document.getElementById("rec-act-body");
  clear(body);
  const list = applyFilter();
  document.getElementById("rec-act-count").textContent =
    list.length ? `${list.length} aktivitas` : "";

  if (!list.length) {
    body.append(el("p", { class: "rec-empty", text: "Tidak ada aktivitas yang cocok." }));
    return;
  }

  for (const a of list) {
    const action = a.action || "activity";
    const text = `${a.recruiter || "—"} ${a.description || ""}`.trim();
    body.append(
      el("div", { class: "rec-activity-item" }, [
        el("span", { class: "rec-activity-time", text: a.timestamp || "—" }),
        el("div", { class: "rec-activity-content" }, [
          el("span", { class: "rec-activity-text", text }),
          el("span", {
            class: `rec-badge rec-activity-badge ${TYPE_BADGE[action] || "rec-badge--gray"}`,
            text: TYPE_LABEL[action] || action,
          }),
        ]),
      ])
    );
  }
}

async function load() {
  renderDate();
  try {
    const items = await API.getRecruiterActivity();
    allActivity = Array.isArray(items) ? items : [];
  } catch {
    allActivity = [];
  }
  render();
}

document.getElementById("rec-act-search").addEventListener("input", render);
document.getElementById("rec-act-type").addEventListener("change", render);

load();
