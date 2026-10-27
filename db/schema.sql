-- ============================================================
-- SkillBridge Database Schema (PostgreSQL) - Final (Opsi A)
-- Role: user & admin. Profil publik read-only lewat token.
-- Evidence: kolom JSONB metadata (Keputusan G03).
-- Hasil ekstraksi CV berstatus 'candidate' sampai dikonfirmasi (G02).
-- Tanpa skor/persentase kesiapan (Keputusan G01).
-- ============================================================

-- ---------- ENUM ----------
CREATE TYPE user_role AS ENUM ('user', 'hr', 'admin');
CREATE TYPE evidence_source_type AS ENUM ('cv', 'project', 'portfolio', 'certificate', 'github', 'assessment');
CREATE TYPE skill_source AS ENUM ('manual', 'cv_extracted');
CREATE TYPE skill_status AS ENUM ('candidate', 'confirmed');

-- ---------- FUNGSI GENERIK updated_at ----------
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ---------- MASTER DATA (dikelola admin) ----------
CREATE TABLE careers (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE skills (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    category    VARCHAR(50),
    description TEXT
);

-- Skill yang dibutuhkan tiap target karier
CREATE TABLE career_skill_requirements (
    id           SERIAL PRIMARY KEY,
    career_id    INT NOT NULL REFERENCES careers(id) ON DELETE CASCADE,
    skill_id     INT NOT NULL REFERENCES skills(id)  ON DELETE CASCADE,
    is_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE (career_id, skill_id)
);

CREATE INDEX idx_csr_skill ON career_skill_requirements (skill_id);

-- ---------- PENGGUNA ----------
CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          user_role NOT NULL DEFAULT 'user',
    created_at    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Profil mahasiswa: satu target karier aktif + pengaturan profil publik
CREATE TABLE student_profiles (
    user_id          INT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    target_career_id INT REFERENCES careers(id) ON DELETE SET NULL,
    is_public        BOOLEAN NOT NULL DEFAULT FALSE,
    public_token     UUID UNIQUE,
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- profil publik wajib punya token
    CONSTRAINT chk_public_needs_token CHECK (NOT is_public OR public_token IS NOT NULL)
);

CREATE INDEX idx_student_profiles_target ON student_profiles (target_career_id);

CREATE TRIGGER trg_student_profiles_updated_at
    BEFORE UPDATE ON student_profiles
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ---------- EVIDENCE ----------
CREATE TABLE evidence (
    id          SERIAL PRIMARY KEY,
    user_id     INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    source_type evidence_source_type NOT NULL,
    title       VARCHAR(150) NOT NULL,
    file_url    VARCHAR(500),
    metadata    JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_evidence_user     ON evidence (user_id);
CREATE INDEX idx_evidence_metadata ON evidence USING GIN (metadata);

-- ---------- SKILL MILIK USER ----------
-- 'manual'       : diinput user, langsung boleh 'confirmed'
-- 'cv_extracted' : hasil ekstraksi, mulai dari 'candidate' sampai user konfirmasi
CREATE TABLE user_skills (
    id         SERIAL PRIMARY KEY,
    user_id    INT NOT NULL REFERENCES users(id)  ON DELETE CASCADE,
    skill_id   INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
    source     skill_source NOT NULL DEFAULT 'manual',
    status     skill_status NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, skill_id)
);

CREATE INDEX idx_user_skills_skill ON user_skills (skill_id);

CREATE TRIGGER trg_user_skills_updated_at
    BEFORE UPDATE ON user_skills
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Penghubung: satu evidence bisa membuktikan banyak skill
CREATE TABLE evidence_skills (
    evidence_id INT NOT NULL REFERENCES evidence(id) ON DELETE CASCADE,
    skill_id    INT NOT NULL REFERENCES skills(id)   ON DELETE CASCADE,
    PRIMARY KEY (evidence_id, skill_id)
);

CREATE INDEX idx_evidence_skills_skill ON evidence_skills (skill_id);

-- ---------- VIEW SKILL GAP (deskriptif, tanpa skor) ----------
-- Satu baris per skill yang dibutuhkan target karier user.
-- has_skill = TRUE  -> skill sudah dimiliki (confirmed)
-- has_skill = FALSE -> skill gap
CREATE VIEW v_skill_gap AS
SELECT
    sp.user_id,
    sp.target_career_id AS career_id,
    csr.skill_id,
    s.name              AS skill_name,
    csr.is_mandatory,
    (us.id IS NOT NULL) AS has_skill
FROM student_profiles sp
JOIN career_skill_requirements csr ON csr.career_id = sp.target_career_id
JOIN skills s                      ON s.id = csr.skill_id
LEFT JOIN user_skills us
       ON us.user_id  = sp.user_id
      AND us.skill_id = csr.skill_id
      AND us.status   = 'confirmed';

-- Contoh pemakaian:
--   Skill gap user 1      : SELECT * FROM v_skill_gap WHERE user_id = 1 AND NOT has_skill;
--   Skill yang sudah ada  : SELECT * FROM v_skill_gap WHERE user_id = 1 AND has_skill;
--   Profil publik         : SELECT ... FROM student_profiles WHERE public_token = $1 AND is_public;

-- ---------- MODUL HR / PERUSAHAAN ----------
-- Identitas perusahaan mitra
CREATE TABLE companies (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    industry    VARCHAR(100),
    website     VARCHAR(255),
    description TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_companies_updated_at
    BEFORE UPDATE ON companies
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Relasi user HR dengan perusahaan & role/jabatan di tim
CREATE TABLE company_recruiters (
    id          SERIAL PRIMARY KEY,
    company_id  INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id     INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_title  VARCHAR(100) DEFAULT 'Recruiter',
    status      VARCHAR(20) NOT NULL DEFAULT 'active', -- active, inactive
    created_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (company_id, user_id)
);

CREATE INDEX idx_company_recruiters_company ON company_recruiters (company_id);
CREATE INDEX idx_company_recruiters_user ON company_recruiters (user_id);

CREATE TRIGGER trg_company_recruiters_updated_at
    BEFORE UPDATE ON company_recruiters
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Lowongan / Target Roles yang dibuka perusahaan
CREATE TABLE job_vacancies (
    id              SERIAL PRIMARY KEY,
    company_id      INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    career_id       INT REFERENCES careers(id) ON DELETE SET NULL,
    title           VARCHAR(150) NOT NULL,
    positions       INT NOT NULL DEFAULT 1,
    status          VARCHAR(20) NOT NULL DEFAULT 'active', -- active, inactive, closed
    description     TEXT,
    requirements    TEXT,
    created_by      INT REFERENCES users(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_job_vacancies_company ON job_vacancies (company_id);
CREATE INDEX idx_job_vacancies_career ON job_vacancies (career_id);
CREATE INDEX idx_job_vacancies_status ON job_vacancies (status);

CREATE TRIGGER trg_job_vacancies_updated_at
    BEFORE UPDATE ON job_vacancies
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Status seleksi kandidat mahasiswa oleh HR
CREATE TABLE candidate_pipelines (
    id              SERIAL PRIMARY KEY,
    company_id      INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    vacancy_id      INT REFERENCES job_vacancies(id) ON DELETE SET NULL,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status          VARCHAR(20) NOT NULL DEFAULT 'Shortlisted', -- Shortlisted, Interview, Accepted, Rejected
    notes           TEXT,
    updated_by      INT REFERENCES users(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (company_id, user_id, vacancy_id)
);

CREATE INDEX idx_candidate_pipelines_company ON candidate_pipelines (company_id);
CREATE INDEX idx_candidate_pipelines_user ON candidate_pipelines (user_id);
CREATE INDEX idx_candidate_pipelines_status ON candidate_pipelines (status);

CREATE TRIGGER trg_candidate_pipelines_updated_at
    BEFORE UPDATE ON candidate_pipelines
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Riwayat aktivitas tim HR di perusahaan
CREATE TABLE recruiter_activity_logs (
    id              SERIAL PRIMARY KEY,
    company_id      INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    action_type     VARCHAR(50) NOT NULL, -- 'candidate_shortlist', 'candidate_interview', 'vacancy_created', dll
    target_type     VARCHAR(50), -- 'candidate', 'vacancy', 'team'
    target_id       INT,
    description     TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_recruiter_activity_logs_company ON recruiter_activity_logs (company_id);
CREATE INDEX idx_recruiter_activity_logs_user ON recruiter_activity_logs (user_id);
CREATE INDEX idx_recruiter_activity_logs_created ON recruiter_activity_logs (created_at DESC);