-- Migration: Core Bug Fixes and Profiling
-- 1. Kolom foto profil (URL Cloudflare R2 / fallback Data URI) pada tabel users
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url TEXT DEFAULT NULL;

-- 2. Kolom profiling lengkap pada tabel student_profiles
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS institution VARCHAR(150) DEFAULT 'Politeknik Negeri Malang';
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS study_program VARCHAR(150) DEFAULT 'Teknik Informatika';
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS semester INT DEFAULT 3;
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS location VARCHAR(150) DEFAULT 'Malang, Indonesia';
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL;
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL;

-- 3. Kolom metadata komunikasi dua arah HR <-> Mahasiswa pada candidate_pipelines
ALTER TABLE candidate_pipelines ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL;
ALTER TABLE candidate_pipelines ADD COLUMN IF NOT EXISTS decision_payload JSONB DEFAULT '{}'::jsonb;
ALTER TABLE candidate_pipelines ADD COLUMN IF NOT EXISTS candidate_response VARCHAR(40) DEFAULT 'pending';
ALTER TABLE candidate_pipelines ADD COLUMN IF NOT EXISTS candidate_reply_note TEXT DEFAULT NULL;
ALTER TABLE candidate_pipelines ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
