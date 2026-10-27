// api.js - Client untuk REST API PHP Backend
// Semua request ke public/api/*.php dengan CSRF token dan session handling

const API = (() => {
  function getBaseUrl() {
    return (typeof window !== "undefined" && window.APP_BASE_URL !== undefined)
      ? (window.APP_BASE_URL === "/" ? "" : window.APP_BASE_URL.replace(/\/+$/, ""))
      : "";
  }

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : "";
  }

  async function fetchAPI(endpoint, options = {}) {
    const base = getBaseUrl();
    const url = `${base}/api/${endpoint}`;

    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    // Tambahkan CSRF token untuk POST/PUT/DELETE
    if (options.method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method.toUpperCase())) {
      headers['X-CSRF-Token'] = csrfToken();
    }

    const response = await fetch(url, {
      ...options,
      headers,
      credentials: 'same-origin'
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ error: 'Request failed' }));
      throw new Error(error.error || `HTTP ${response.status}`);
    }

    return response.json();
  }

  // ============ MAHASISWA: Skills & Careers ============

  // GET api/skills.php -> { skills, careers, mappings }
  async function getSkillCatalog() {
    const data = await fetchAPI('skills.php');
    return data.skills || [];
  }

  async function getCareers() {
    const data = await fetchAPI('skills.php');
    return data.careers || [];
  }

  // GET api/my-skills.php
  // Mengembalikan array dengan format: { skill_id, evidence_count, label }
  async function getMySkills() {
    const skills = await fetchAPI('my-skills.php');
    // Transform untuk kompatibilitas dengan kode lama yang expect { skill_id, evidence_count, label }
    return skills.map(s => ({
      skill_id: s.skill_id,
      evidence_count: s.evidence_count,
      label: s.label
    }));
  }

  // POST api/my-skills.php { skill_id }
  async function addMySkill(skill_id) {
    return await fetchAPI('my-skills.php', {
      method: 'POST',
      body: JSON.stringify({ skill_id })
    });
  }

  // DELETE api/my-skills.php { skill_id }
  async function removeMySkill(skill_id) {
    return await fetchAPI('my-skills.php', {
      method: 'DELETE',
      body: JSON.stringify({ skill_id })
    });
  }

  // POST api/skills.php { name, category, description }
  async function addCustomSkill(name, category, description = '') {
    return await fetchAPI('skills.php', {
      method: 'POST',
      body: JSON.stringify({ name, category, description })
    });
  }

  // Alias untuk kompatibilitas dengan kode lama
  const addSkill = addMySkill;
  const removeSkill = removeMySkill;

  // ============ MAHASISWA: Evidence ============

  // GET api/evidence.php
  async function getEvidence() {
    return await fetchAPI('evidence.php');
  }

  // POST api/evidence.php { source_type, title, description, file_url, metadata, skill_ids }
  async function addEvidence(data) {
    // Transform metadata jika perlu
    const payload = {
      source_type: data.source_type,
      title: data.title,
      description: data.description || '',
      file_url: data.file_url || null,
      metadata: data.metadata || {},
      skill_ids: data.metadata?.skills || [] // Ambil skill_ids dari metadata.skills
    };

    return await fetchAPI('evidence.php', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  }

  // DELETE api/evidence.php { id }
  async function removeEvidence(id) {
    return await fetchAPI('evidence.php', {
      method: 'DELETE',
      body: JSON.stringify({ id })
    });
  }

  // ============ MAHASISWA: Target Career ============

  // GET api/target.php -> { target_career_id }
  async function getTargetCareer() {
    const data = await fetchAPI('target.php');
    return data.target_career_id;
  }

  // Alias untuk kompatibilitas - sinkron (dipanggil di dashboard.js tanpa await)
  // Dashboard.js menggunakan API.getTargetId() secara sinkron, jadi kita perlu cache
  let cachedTargetId = null;

  async function fetchAndCacheTargetId() {
    cachedTargetId = await getTargetCareer();
    return cachedTargetId;
  }

  function getTargetId() {
    // Return cached value jika ada, atau null jika belum di-fetch
    return cachedTargetId;
  }

  // POST api/target.php { career_id }
  async function setTargetCareer(career_id) {
    return await fetchAPI('target.php', {
      method: 'POST',
      body: JSON.stringify({ career_id })
    });
  }

  // Alias untuk kompatibilitas
  async function setTarget(id) {
    return await setTargetCareer(id);
  }

  // ============ MAHASISWA: Skill Gap ============

  // GET api/gap.php -> { career, have, explore }
  async function getSkillGap() {
    return await fetchAPI('gap.php');
  }

  // ============ MAHASISWA: Profile ============

  // GET api/profile.php
  async function getProfile() {
    return await fetchAPI('profile.php');
  }

  // POST api/profile.php { action: 'update_info', name, email }
  async function updateProfile(data) {
    return await fetchAPI('profile.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'update_info',
        ...data
      })
    });
  }

  // POST api/profile.php { action: 'change_password', current_password, new_password }
  async function changePassword(current_password, new_password) {
    return await fetchAPI('profile.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'change_password',
        current_password,
        new_password
      })
    });
  }

  // POST api/profile.php { action: 'update_visibility', is_public }
  async function updateVisibility(is_public) {
    return await fetchAPI('profile.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'update_visibility',
        is_public
      })
    });
  }

  // ============ ADMIN: Skills & Careers Management ============

  async function getMasterSkills() {
    return await getSkillCatalog(); // Sama dengan katalog biasa
  }

  async function getMasterCareers() {
    return await getCareers(); // Sama dengan careers biasa
  }

  async function getCareerSkillMapping() {
    const data = await fetchAPI('skills.php');
    return data.mappings || [];
  }

  // ============ ADMIN: Users Management ============

  async function getAdminUsers() {
    return await fetchAPI('admin/users.php');
  }

  async function createAdminUser(data) {
    return await fetchAPI('admin/users.php', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async function updateAdminUser(data) {
    return await fetchAPI('admin/users.php', {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async function deleteAdminUser(id) {
    return await fetchAPI('admin/users.php', {
      method: 'DELETE',
      body: JSON.stringify({ id })
    });
  }

  async function getAdminStats() {
    return await fetchAPI('admin/stats.php');
  }

  // ============ ADMIN: Master Data Management ============

  async function createMasterSkill(data) {
    return await fetchAPI('admin/master.php', {
      method: 'POST',
      body: JSON.stringify({ entity: 'skill', ...data })
    });
  }

  async function updateMasterSkill(data) {
    return await fetchAPI('admin/master.php', {
      method: 'PUT',
      body: JSON.stringify({ entity: 'skill', ...data })
    });
  }

  async function deleteMasterSkill(id) {
    return await fetchAPI('admin/master.php', {
      method: 'DELETE',
      body: JSON.stringify({ entity: 'skill', id })
    });
  }

  async function createMasterCareer(data) {
    return await fetchAPI('admin/master.php', {
      method: 'POST',
      body: JSON.stringify({ entity: 'career', ...data })
    });
  }

  async function updateMasterCareer(data) {
    return await fetchAPI('admin/master.php', {
      method: 'PUT',
      body: JSON.stringify({ entity: 'career', ...data })
    });
  }

  async function deleteMasterCareer(id) {
    return await fetchAPI('admin/master.php', {
      method: 'DELETE',
      body: JSON.stringify({ entity: 'career', id })
    });
  }

  async function createCareerSkillMapping(career_id, skill_id, is_mandatory = true) {
    return await fetchAPI('admin/master.php', {
      method: 'POST',
      body: JSON.stringify({ entity: 'mapping', career_id, skill_id, is_mandatory })
    });
  }

  async function deleteCareerSkillMapping(career_id, skill_id) {
    return await fetchAPI('admin/master.php', {
      method: 'DELETE',
      body: JSON.stringify({ entity: 'mapping', career_id, skill_id })
    });
  }

  // ============ ADMIN: Legacy (Dummy - akan dihapus) ============

  const ADMIN_LS = {
    jobs: "sb.admin.jobs",
    vacancies: "sb.admin.vacancies",
    content: "sb.admin.content",
  };

  // Fungsi dummy untuk Job Admins, Vacancies, Content (belum ada backend)
  async function getJobAdmins() {
    return readAdminCollection("jobs", "../data/admin-jobs.json");
  }

  async function getVacancies() {
    return readAdminCollection("vacancies", "../data/admin-vacancies.json");
  }

  async function getAdminContent() {
    return readAdminCollection("content", "../data/admin-content.json");
  }

  async function setAdminRecordStatus(collection, id, status) {
    // Dummy - untuk jobs, vacancies, content yang belum ada backend
    const key = ADMIN_LS[collection];
    if (!key) return null;
    const list = await readAdminCollection(collection, `../data/admin-${collection}.json`);
    const item = list.find((row) => row.id === id);
    if (!item) return list;
    item.status = status;
    localStorage.setItem(key, JSON.stringify(list));
    return list;
  }

  async function removeAdminRecord(collection, id) {
    // Untuk users, gunakan backend
    if (collection === 'users') {
      return await deleteAdminUser(id);
    }

    // Dummy - untuk jobs, vacancies, content yang belum ada backend
    const key = ADMIN_LS[collection];
    if (!key) return null;
    const list = (await readAdminCollection(collection, `../data/admin-${collection}.json`)).filter(
      (row) => row.id !== id
    );
    localStorage.setItem(key, JSON.stringify(list));
    return list;
  }

  async function getAdminStats() {
    return await fetchAPI('admin/stats.php');
  }

  async function readAdminCollection(name, url) {
    const raw = localStorage.getItem(ADMIN_LS[name]);
    if (raw !== null) return JSON.parse(raw);
    const base = getBaseUrl();
    const res = await fetch(`${base}/${url}`);
    const seeded = await res.json();
    localStorage.setItem(ADMIN_LS[name], JSON.stringify(seeded));
    return seeded;
  }

  // ============ RECRUITER: Company & Team (Backend PostgreSQL) ============

  async function getRecruiterCompany() {
    return await fetchAPI('hr/company.php');
  }

  async function updateRecruiterCompany(data) {
    return await fetchAPI('hr/company.php', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async function getRecruiterTeam() {
    return await fetchAPI('hr/team.php');
  }

  async function addRecruiterTeamMember(data) {
    return await fetchAPI('hr/team.php', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async function updateRecruiterTeamMember(data) {
    return await fetchAPI('hr/team.php', {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async function removeRecruiterTeamMember(id) {
    return await fetchAPI('hr/team.php', {
      method: 'DELETE',
      body: JSON.stringify({ id })
    });
  }

  async function getRecruiterRoles() {
    return await fetchAPI('hr/roles.php');
  }

  async function createRecruiterRole(data) {
    return await fetchAPI('hr/roles.php', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async function updateRecruiterRole(data) {
    return await fetchAPI('hr/roles.php', {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async function deleteRecruiterRole(id) {
    return await fetchAPI('hr/roles.php', {
      method: 'DELETE',
      body: JSON.stringify({ id })
    });
  }

  async function getRecruiterActivity() {
    return await fetchAPI('hr/activity.php');
  }

  async function getRecruiterCandidates() {
    return await fetchAPI('hr/candidates.php');
  }

  async function updateCandidateStatus(data) {
    return await fetchAPI('hr/candidates.php', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async function getRecruiterStats() {
    const data = await getRecruiterCompany();
    return data.metrics || {
      teamSize: 0,
      activeRecruiters: 0,
      openRoles: 0,
      totalPositions: 0,
      pipelineCandidates: 0
    };
  }

  // Legacy compatibility
  async function setRecruiterStatus(id, status) {
    return await updateRecruiterTeamMember({ id, status });
  }

  async function removeRecruiterRecord(id) {
    return await removeRecruiterTeamMember(id);
  }

  // ============ Export API ============

  return {
    // Core
    csrfToken,

    // Skills & Careers
    getCareers,
    getSkillCatalog,
    getMySkills,
    addMySkill,
    removeMySkill,
    addCustomSkill,
    addSkill,        // alias
    removeSkill,     // alias

    // Evidence
    getEvidence,
    addEvidence,
    removeEvidence,

    // Target Career
    getTargetCareer,
    setTargetCareer,
    getTargetId,     // alias
    setTarget,       // alias

    // Skill Gap
    getSkillGap,

    // Profile
    getProfile,
    updateProfile,
    changePassword,
    updateVisibility,

    // Admin - Stats & Users
    getAdminStats,
    getAdminUsers,
    createAdminUser,
    updateAdminUser,
    deleteAdminUser,

    // Admin - Master Data
    getMasterSkills,
    getMasterCareers,
    getCareerSkillMapping,
    createMasterSkill,
    updateMasterSkill,
    deleteMasterSkill,
    createMasterCareer,
    updateMasterCareer,
    deleteMasterCareer,
    createCareerSkillMapping,
    deleteCareerSkillMapping,

    // Admin - Legacy (dummy)
    getJobAdmins,
    getVacancies,
    getAdminContent,
    setAdminRecordStatus,
    removeAdminRecord,

    // Recruiter
    getRecruiterCompany,
    updateRecruiterCompany,
    getRecruiterTeam,
    addRecruiterTeamMember,
    updateRecruiterTeamMember,
    removeRecruiterTeamMember,
    getRecruiterRoles,
    createRecruiterRole,
    updateRecruiterRole,
    deleteRecruiterRole,
    getRecruiterActivity,
    getRecruiterCandidates,
    updateCandidateStatus,
    setRecruiterStatus,
    removeRecruiterRecord,
    getRecruiterStats,

    // Admin - Vacancies
    getVacancies: async () => await fetchAPI('admin/vacancies.php'),
    updateVacancyStatus: async (data) => await fetchAPI('admin/vacancies.php', { method: 'PUT', body: JSON.stringify(data) }),
    deleteVacancy: async (id) => await fetchAPI('admin/vacancies.php', { method: 'DELETE', body: JSON.stringify({ id }) }),

    // CV Processing
    extractCVSkills: async (data) => await fetchAPI('cv.php', { method: 'POST', body: JSON.stringify(data) }),
    confirmCVSkills: async (data) => await fetchAPI('cv.php', { method: 'PUT', body: JSON.stringify(data) }),
  };
})();

window.API = API;
