// Detail Kandidat — read-only (profil publik, skill confirmed, evidence).
const { el, clear } = UI;

const candidateId = Number(new URLSearchParams(window.location.search).get("id"));

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  const today = document.getElementById("rec-today");
  if (today) today.textContent = `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

function chip(label) {
  return el("span", { class: "rec-skill-chip" }, [
    el("span", { class: "rec-skill-dot" }),
    label,
  ]);
}

function render(data) {
  const c = data.candidate || {};
  const skills = data.skills || [];
  const evidence = data.evidence || [];

  document.title = `${c.name || "Kandidat"} · Detail Kandidat · SkillBridge`;
  document.getElementById("cand-title").textContent = c.name || "Detail Kandidat";
  document.getElementById("cand-target").textContent = c.target_role || "Belum Ditentukan";
  document.getElementById("cand-name").textContent = c.name || "—";
  document.getElementById("cand-role").textContent = c.target_role || "Belum Ditentukan";
  document.getElementById("cand-email").textContent = c.email || "—";

  const skillWrap = document.getElementById("cand-skills");
  clear(skillWrap);
  if (!skills.length) {
    skillWrap.append(el("p", { class: "rec-empty", text: "Belum ada skill confirmed." }));
  } else {
    for (const name of skills) skillWrap.append(chip(name));
  }
  document.getElementById("cand-skill-count").textContent =
    `${skills.length} skill confirmed`;

  const body = document.getElementById("cand-evidence");
  clear(body);
  if (!evidence.length) {
    body.append(el("tr", {}, [
      el("td", { colspan: "3", class: "rec-empty", text: "Belum ada evidence." }),
    ]));
  } else {
    for (const item of evidence) {
      const safe = UI.safeUrl(item.url);
      const linkCell = safe
        ? el("a", {
            href: safe,
            target: "_blank",
            rel: "noopener noreferrer",
            class: "rec-pager-link",
            text: "Buka tautan",
          })
        : el("span", { text: "—" });
      body.append(el("tr", {}, [
        el("td", { text: item.title || "—" }),
        el("td", {}, [el("span", { class: "rec-badge rec-badge--indigo", text: item.type || "—" })]),
        el("td", {}, [linkCell]),
      ]));
    }
  }
  document.getElementById("cand-evidence-count").textContent =
    `${evidence.length} evidence`;
}

async function load() {
  renderDate();
  if (!candidateId) {
    UI.toast("ID kandidat tidak valid.");
    document.getElementById("cand-title").textContent = "Kandidat tidak ditemukan";
    return;
  }
  try {
    const data = await API.getRecruiterCandidate(candidateId);
    render(data);
  } catch (err) {
    UI.toast("Gagal memuat detail kandidat: " + err.message);
    document.getElementById("cand-title").textContent = "Kandidat tidak ditemukan";
  }
}

load();
