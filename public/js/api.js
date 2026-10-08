// api.js - SATU-SATUNYA tempat pengambilan data untuk seluruh halaman.
// Mode dummy: baca data/*.json + localStorage. Saat backend siap, ganti isi file ini
// dengan fetch ke endpoint asli (public/api/*.php); halaman tidak perlu berubah.
// Jalankan dari root repo agar ../data/*.json dapat dilayani:
//   php -S localhost:8000        lalu buka  http://localhost:8000/public/dashboard.html

const API = (() => {
  const LS = {
    skills: "sb.mySkills",
    evidence: "sb.myEvidence",
    target: "sb.targetCareer",
  };

  // GET api/careers.php
  async function getCareers() {
    const res = await fetch("../data/careers.json");
    return res.json();
  }

  // GET api/skills.php
  async function getSkillCatalog() {
    const res = await fetch("../data/skills.json");
    return res.json();
  }

  // POST api/target.php  { career_id }
  function setTarget(id) {
    localStorage.setItem(LS.target, String(id));
  }

  // GET api/target.php
  function getTargetId() {
    const raw = localStorage.getItem(LS.target);
    return raw === null ? null : Number(raw);
  }

  // GET api/skills.php?mine=1
  // Mengembalikan skill pengguna + evidence_count + label (Strong jika >= 2 evidence).
  function getMySkills() {
    const ids = readIds(LS.skills);
    const counts = evidenceCountBySkill(ids);
    return ids.map((id) => {
      const count = counts.get(id) || 0;
      return { skill_id: id, evidence_count: count, label: count >= 2 ? "Strong" : "Growing" };
    });
  }

  // POST api/skills.php  { skill_id }
  function addSkill(id) {
    const ids = readIds(LS.skills);
    if (ids.includes(id)) return ids;
    ids.push(id);
    localStorage.setItem(LS.skills, JSON.stringify(ids));
    return ids;
  }

  // DELETE api/skills.php  { skill_id }
  function removeSkill(id) {
    const ids = readIds(LS.skills).filter((x) => x !== id);
    localStorage.setItem(LS.skills, JSON.stringify(ids));
    return ids;
  }

  // GET api/evidence.php
  function getEvidence() {
    const raw = localStorage.getItem(LS.evidence);
    return raw === null ? seedEvidence() : JSON.parse(raw);
  }

  // POST api/evidence.php  { source_type, title, description, file_url, skill_ids[] }
  function addEvidence(data) {
    const list = getEvidence();
    const id = list.reduce((max, e) => Math.max(max, e.id), 0) + 1;
    list.push({ id, ...data });
    localStorage.setItem(LS.evidence, JSON.stringify(list));
    return list;
  }

  // DELETE api/evidence.php  { id }
  function removeEvidence(id) {
    const list = getEvidence().filter((e) => e.id !== id);
    localStorage.setItem(LS.evidence, JSON.stringify(list));
    return list;
  }

  // GET api/gap.php  -> dua daftar: dimiliki vs perlu dikembangkan
  async function getSkillGap() {
    const [careers, catalog] = await Promise.all([getCareers(), getSkillCatalog()]);
    const career = careers.find((c) => c.id === getTargetId()) || null;
    if (!career) return { career: null, have: [], explore: [] };

    const mine = new Set(readIds(LS.skills));
    const byName = new Map(catalog.map((s) => [s.name, s]));
    const counts = evidenceCountBySkill([...mine]);
    const required = career.skills.map((n) => byName.get(n)).filter(Boolean);

    return {
      career,
      have: required
        .filter((s) => mine.has(s.id))
        .map((s) => ({ ...s, evidence_count: counts.get(s.id) || 0 })),
      explore: required.filter((s) => !mine.has(s.id)),
    };
  }

  // ---- helper internal ----

  function readIds(key) {
    const raw = localStorage.getItem(key);
    return raw === null ? [] : JSON.parse(raw);
  }

  function seedEvidence() {
    localStorage.setItem(LS.evidence, JSON.stringify([]));
    return [];
  }

  function evidenceCountBySkill(skillIds) {
    const counts = new Map();
    for (const ev of getEvidence()) {
      for (const sid of ev.skill_ids || []) {
        if (skillIds.includes(sid)) counts.set(sid, (counts.get(sid) || 0) + 1);
      }
    }
    return counts;
  }

  // Nilai CSRF dari <meta name="csrf-token"> (dipakai di mode asli).
  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : "";
  }

  return {
    getCareers,
    getSkillCatalog,
    setTarget,
    getTargetId,
    getMySkills,
    addSkill,
    removeSkill,
    getEvidence,
    addEvidence,
    removeEvidence,
    getSkillGap,
    csrfToken,
  };
})();

window.API = API;