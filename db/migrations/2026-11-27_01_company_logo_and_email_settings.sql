-- ============================================================
-- Migration: Company Logo URL & Email Notification Settings
-- Date: 2026-11-27
-- ============================================================

-- 1. Tambahkan kolom logo_url di tabel companies jika belum ada
ALTER TABLE companies ADD COLUMN IF NOT EXISTS logo_url TEXT;

-- 2. Buat tabel preferensi notifikasi email perusahaan
CREATE TABLE IF NOT EXISTS company_email_settings (
    id SERIAL PRIMARY KEY,
    company_id INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    notify_application_submitted BOOLEAN DEFAULT TRUE,
    notify_candidate_shortlisted BOOLEAN DEFAULT TRUE,
    notify_interview_scheduled BOOLEAN DEFAULT TRUE,
    notify_weekly_digest BOOLEAN DEFAULT FALSE,
    custom_notification_email VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_company_email_settings UNIQUE (company_id)
);

CREATE INDEX IF NOT EXISTS idx_company_email_settings_company_id ON company_email_settings(company_id);
